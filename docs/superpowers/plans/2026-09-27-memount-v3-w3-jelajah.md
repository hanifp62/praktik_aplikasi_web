# Memount v3 W3 Klaster Jelajah Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Jelajah, halaman gunung, penilaian kecocokan, rincian jalur, Simpanan, tautan bagikan, dan Bandingkan
berfungsi dengan rupa prototype 02 sampai 07 dan 15 di atas kerangka W2. Setiap jalur membawa label kecocokan berikon
dan berkata tanpa skor, serta status resmi dengan sumber dan kesegarannya. Setiap keadaan kosong menyebut siapa yang
ditunggu, termasuk basis data pemilik yang masih tanpa geometri.

**Architecture:** Server memegang data. Satu kelas halaman per layar (`HalamanJelajah`, `HalamanGunung`,
`PenilaianKecocokan`, `TrailDetailPage`, `RouteComparisonTable`) merakit props yang sudah terformat dari service domain,
dengan anggaran kueri yang tidak tumbuh bersama data. `StatusJalurRingkas` meringkas status resmi banyak jalur dalam
kueri tetap. React memegang interaksi: komponen domain pindah dari `components/ui` ke `components/trails`, halaman
memakai primitif W1 dan layanan kerangka W2 (toast milik halaman, bar aksi, lapisan bawah yang terukur). Tautan
bagikan FR-23 menambah satu tabel (`comparison_shares`, hanya hash token) dan satu halaman publik yang tidak diindeks.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9.3, Vite 8.3.0, Tailwind CSS 3.4.19, MapLibre GL 4.7.1, PHPUnit 12.5.35, Pint, SQLite dalam memori untuk
test, PostgreSQL dan PostGIS untuk suite Spatial di CI, Phosphor Icons core 2.1.1 (path ditanam). Tidak ada dependensi
baru.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 5.3, 6.2 sampai 6.7, 7, 9.2, 9.3,
10.5; DK-31, DK-40, DK-44), rencana induk `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (Global
Constraints, Review Focus, bagian W3 butir 1 sampai 7 dan "Selesai bila"), dua belas keputusan koordinator untuk W3,
dua pembaruan koordinator 28 September 2026 (konvensi eksekusi W2 dan butir W2-F1, W2-F2, W2-F3, W2-F5 yang
diserahkan ke W3), serta aturan eksekusi `.superpowers/sdd/2026-09-26-memount-v3-w1-fondasi-desain/aturan-eksekusi.md`
yang berlaku sama untuk W3. Keadaan awalnya W2 selesai (28 September 2026, `HANDOVER.md` bagian 0, subbagian "W2
Kerangka").

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, Inertia Laravel 3.3.4, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, TypeScript 5.9, MapLibre 4.7.x. Tidak ada perubahan versi kerangka (R-020); peningkatan MapLibre ke 6.x menunggu Q20.
- Tidak ada dependensi baru, runtime maupun dev (spec bagian 15 Q3 dan Q17). Path ikon Phosphor yang baru ditulis lengkap di registri.
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data nyata. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah."
- Satu migrasi baru (`comparison_shares`) diuji di SQLite lewat PHPUnit dan di PostgreSQL CI lewat `tests/Spatial`, dan tidak dijalankan di Supabase sebelum izin pemilik (Q2, keputusan koordinator nomor 10).
- `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker, dan `memount:demo` tidak dipakai. Migrasi dan seeder hanya berjalan di dalam PHPUnit (SQLite dalam memori).
- Setiap perintah Artisan di luar PHPUnit, `npm run build`, dan `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` (Git Bash). Di PowerShell: `$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; <perintah>`, lalu `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`. `DB_URL` di `.env` mengalahkan pengemudi koneksi. `php artisan test ...` tidak butuh awalan karena `phpunit.xml` memaksa koneksi uji. Jangan membaca atau mencetak `.env`.
- Port `127.0.0.1:8100` dicadangkan untuk harness pratinjau koordinator (Tugas 12). Tugas 1 sampai 11 tidak menyalakan server apa pun. Server `php artisan serve` pemilik di `127.0.0.1:8000` tidak pernah dipakai atau dihentikan.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014) di berkas mana pun, tanpa kata "aman" dan "layak berangkat" sebagai klaim (R-012, R-014). "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Label kecocokan publik hanya "Cocok", "Perlu persiapan", dan "Kurang cocok"; UNKNOWN tampil "Belum dinilai", yang katanya datang dari server (`RouteFitLabel`). Tidak ada skor, persen, atau bobot angka di props maupun di layar (R-004, R-005, R-006, BR-09).
- Status akses selalu dengan sumber dan waktunya. Tanpa catatan yang berlaku, statusnya Belum diketahui, bukan Buka, dan Buka tidak pernah berarti aman (R-006, R-012, R-013). Glyph `resmi` hanya untuk sumber OFFICIAL (keputusan koordinator nomor 6).
- Data yang tidak ada tetap tidak diketahui: tanpa angka, rentang, atau km karangan (R-007). Durasi hanya dari data bersumber, dan km pos hanya dari garis rute.
- Token hanya di `resources/css/app.css`; tanpa warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Aksen pos (`bg-aksen-pos`) selalu bersama bentuk dan label (W1-12b, keputusan nomor 3). Kaca paling banyak dua elemen per layar dan tidak pernah di atas peta, kartu, status resmi, atau laporan komunitas.
- Target sentuh kendali utama ≥44 px. Halaman W3 memakai `KotakCentang`, bukan `Checkbox` 28 px (keputusan nomor 5). Primitif baru memakai prop `nilai`, `onUbah`, dan `label` (keputusan nomor 7). Tombol tanpa panah hiasan (DK-39).
- Konvensi kerangka W2: `useKerangka()` dipanggil di komponen yang bertahan (halaman atau daftar), bukan di kartu atau baris (W2-5i). Aksi dengan Urungkan di halaman yang sama memakai `preserveState: true` dan `preserveScroll: true`. Konfirmasi sesudah pindah halaman ditampilkan halaman tujuan dari flash, dengan Urungkan sisi server. Dialog yang mengirim formulir menangani `onNetworkError` di dalam dialog (W2-5e).
- Lapisan W2: halaman tugas memakai `<AppLayout bilahTab={false}>` dan `BarAksi`, yang menulis `--bar-aksi`. Tinggi dan `top` panel yang menempel menghitung `--kepala-atas`, `--pesan-jaringan`, dan `--nav-bawah` (W2-2a, W2-2b, W2-5a, W2-F3).
- Nama props bersama `jumlahSimpanan`, `pendakianAktif`, dan `daftarPengingat` tidak dipakai ulang sebagai prop halaman (`ReminderSheetTest`). Predikat "simpanan aktif" hanya `ConsiderationService::kueriSimpananAktif()`; W3 menghitung dan mendaftar lewat `jumlahSimpanan()` dan `simpanan()`.
- Setiap halaman baru punya test anggaran kueri (keputusan nomor 12), dan setiap rute baru punya test akses tamu dan pengguna lain.
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock-nya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- Tugas dikerjakan berurutan, satu implementer satu waktu, dan hanya menyunting berkas di daftar Files tugasnya. Berkas bersama (`resources/css/app.css`, `resources/js/layouts/NavLinks.tsx`, `resources/js/types/index.d.ts`, `resources/js/components/ui/ikon/phosphor.ts`, `app/Http/Middleware/HandleInertiaRequests.php`, `routes/web.php`, `config/hiking.php`) hanya disunting tugas yang menyebutnya. `HANDOVER.md` dan `CLAUDE.md` hanya disunting koordinator di Tugas 12.
- Berkas baru dibuat seperti W2: test dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction <Nama>`, kelas PHP dengan `make:class`, `make:controller`, `make:request`, `make:model`, `make:factory`, atau `make:migration` berawalan yang sama, lalu isinya diganti persis dengan kode rencana. Nama migrasi harus persis `2026_09_28_000100_create_comparison_shares_table.php`, jadi berkas yang dibuat `make:migration` diganti namanya. Berkas TypeScript dibuat langsung. Setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>`; setelah menyunting TypeScript jalankan `npx tsc --noEmit`; setelah rute berubah jalankan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan wayfinder:generate`.
- Blok "Ganti ... dengan ..." diterapkan berurutan dengan alat Edit. Teks lamanya unik di berkasnya pada keadaan W2 selesai ditambah tugas-tugas sebelumnya, dan sudah diperiksa pada simulasi. Bila teks lama tidak ditemukan persis, berhenti dan laporkan NEEDS_CONTEXT; jangan mengarang padanannya.

## Review Focus

- **Data lapangan kosong.** Nol jalur terbit, nol geometri, nol pos berkoordinat, dan status UNKNOWN di basis data
  pemilik. Setiap layar W3 menampilkan keadaan menunggu yang menyebut siapa yang ditunggu, tidak pernah halaman kosong
  atau galat, dan saringan tidak disalahkan atas katalog kosong. Dijaga Tugas 4
  `TrailIndexSearchTest::test_nothing_published_is_reported_as_waiting_even_with_filters`, Tugas 5
  `TrailIndexMapTest::test_the_map_names_what_it_cannot_draw_and_waits_when_nothing_has_a_line`, Tugas 2
  `MountainPageTest::test_a_mountain_without_published_trails_names_who_is_awaited`, dan Tugas 6
  `TrailDetailPageTest::test_missing_figures_are_stated_honestly`.
- **UNKNOWN yang terbaca rendah atau Buka.** Tuntutan fisik, teknis, dan navigasi, tanjakan, faktor kecocokan, serta
  status tanpa catatan tidak pernah berubah menjadi "rendah", "Sesuai", atau "Buka". Dijaga Tugas 4
  `TrailIndexSearchTest::test_a_climb_range_never_admits_an_unknown_climb`, Tugas 6
  `TrailDetailPageTest::test_the_challenge_rows_never_turn_unknown_into_low`, Tugas 3
  `FitAssessmentPageTest::test_an_unknown_factor_reads_unknown_not_weak`, dan Tugas 1
  `TrustGrammarTest::test_no_current_record_reads_unknown_not_open`.
- **Simpan ganda, halaman basi, dan Urungkan.** Ketuk dua kali, halaman yang basi, Urungkan sesudah menghapus, dan
  simpanan yang penuh tidak pernah membalik keadaan atau menghilangkan catatan pribadi. Dijaga Tugas 4
  `SavedUndoTest::test_saving_twice_keeps_one_row_and_announces_it`,
  `test_undo_after_removal_restores_the_trail_with_its_note`,
  `test_saving_again_never_overwrites_an_existing_note`, dan `test_a_full_list_is_refused_with_the_configured_limit`,
  serta Tugas 7 `SavedRoutesTest::test_removing_the_same_trail_twice_does_not_bring_it_back`.
- **Tautan bagikan dan data milik orang lain.** Tautan kedaluwarsa, dicabut, atau salah tidak terbuka; tautan orang
  lain tidak dapat dicabut; halaman publik tidak membawa catatan, label kecocokan, atau nama pembuatnya; trip orang lain
  ditolak di halaman kecocokan; id di badan permintaan tidak pernah dipercaya. Dijaga Tugas 8
  `ComparisonShareTest::test_an_expired_revoked_or_unknown_link_cannot_be_opened`,
  `test_only_the_owner_can_revoke_and_others_get_not_found`, dan
  `test_the_public_page_is_read_only_unindexed_and_carries_nothing_private`, Tugas 3
  `FitAssessmentPageTest::test_another_hikers_trip_is_refused`, dan Tugas 4
  `SavedUndoTest::test_ids_in_the_request_body_never_reach_the_database`.
- **Lapisan bawah dan panel lengket di 320 sampai 360 px dan 1440 px** (butir yang diserahkan tinjauan akhir W2). Bar
  aksi rincian jalur, baki banding Simpanan, strip pendakian, pesan jaringan, dan tab yang menyala tidak menutup isi,
  tombol, atau jalan pulang. Dijaga Tugas 6 `TrailDetailPageTest::test_the_phone_action_bar_stays_one_row`, Tugas 7
  `SavedRoutesTest::test_the_compare_tray_is_a_one_row_summary_without_names`, Tugas 5
  `TrailIndexMapTest::test_the_sticky_map_and_filter_panel_leave_room_for_the_fixed_layers`, dan Tugas 9
  `NavigationConsistencyTest::test_a_page_and_its_parent_light_the_same_tab`, lalu diukur di peramban pada Tugas 12.

---

## Bukti simulasi rencana

Kode di rencana ini dijalankan tugas demi tugas di salinan repo di scratchpad sesi perencanaan. Salinan itu tanpa
`.env`, dengan basis data hanya SQLite dalam memori milik PHPUnit, dan tanpa `memount:demo`, migrasi, atau seeder di
luar PHPUnit. Dasarnya disalin dari repo nyata pada 28 September 2026 sesudah W2 selesai: 1.239 berkas terlacak dan
belum terlacak dibandingkan hash-nya, dan satu-satunya beda adalah konstanta `ASAL` harness pratinjau (port 8130 milik
simulasi, bukan 8100). Setiap blok "Ganti" diterapkan persis seperti implementer menerapkannya. Setiap tugas diuji merah
dengan test tugas itu terhadap kode tugas sebelumnya, lalu hijau, lalu suite Unit dan Feature penuh, `npx tsc --noEmit`,
build, dan suite Pratinjau. Sesudah dirakit, teks rencana ini diputar ulang dari awal pada dasar tersebut seperti
implementer membacanya, dan hasilnya sama persis dengan keadaan akhir setiap tugas.

| Tugas | Saringan | Merah (test tugas terhadap kode tugas sebelumnya) | Hijau saringan | Suite Unit dan Feature | tsc | Pratinjau |
|---|---|---|---|---|---|---|
| Dasar (W2 selesai) | | | | 1.622 test, 1.620 lulus, 2 dilewati | 0 galat | 9 test, 9 lulus |
| 1 | `TrustGrammar\|Accessibility\|AmbientFit\|CommunityPhoto\|TrailDetailPage\|RouteComparison\|ComparisonAsCost` | 66 test, 54 lulus, 2 gagal, 10 error | 66 test, 66 lulus | 1.630 test, 1.628 lulus, 2 dilewati | 0 galat | 9 test, 9 lulus |
| 2 | `MountainPageTest\|DemoSeederTest\|NavigationConsistency\|LockedBusinessRules` | 41 test, 28 lulus, 1 gagal, 12 error | 41 test, 41 lulus | 1.643 test, 1.641 lulus, 2 dilewati | 0 galat | 10 test, 10 lulus |
| 3 | `FitAssessmentPage\|FactorBars\|NavigationConsistency\|AmbientFit\|TrailDetailPage` | 60 test, 41 lulus, 1 gagal, 18 error | 60 test, 60 lulus | 1.655 test, 1.653 lulus, 2 dilewati | 0 galat | 11 test, 11 lulus |
| 4 | `TrailIndexSearch\|TrailCard\|SavedUndo\|ResultListBudget\|DesignPrimitives\|InertiaLayout\|SavedRoutes\|ComparisonAsCost\|RecommendationExclusion\|Consideration\|ShellFeedback\|ActiveHikeStrip\|LongInputLayout` | 181 test, 149 lulus, 20 gagal, 12 error | 181 test, 181 lulus | 1.685 test, 1.683 lulus, 2 dilewati | 0 galat | 11 test, 11 lulus |
| 5 | `TrailIndexMap\|MapConfig\|MapComponent\|ColourContrast` | 172 test, 165 lulus, 7 gagal, 0 error | 172 test, 172 lulus | 1.691 test, 1.689 lulus, 2 dilewati | 0 galat | 11 test, 11 lulus |
| 6 | `TrailDetailPage\|CheckpointJourney\|ElevationProfileDisplay\|HikeSummary\|TrailDetailMap\|AwaitingData\|AmbientFit\|CheckpointPaceDisplay\|ProgressLadderOnTrailDetail\|EmptyStateCoverage` | 76 test, 43 lulus, 19 gagal, 13 error, 1 dilewati | 76 test, 75 lulus, 1 dilewati | 1.701 test, 1.699 lulus, 2 dilewati | 0 galat | 11 test, 11 lulus |
| 7 | `SavedRoutes\|SavedTrailNote\|DesignSystem` | 58 test, 47 lulus, 10 gagal, 1 error | 58 test, 58 lulus | 1.706 test, 1.704 lulus, 2 dilewati | 0 galat | 12 test, 12 lulus |
| 8 | `ComparisonShare\|RouteAccessMatrix\|PageHeaderContract\|EmptyStateCoverage\|InertiaLinkRegistry\|KeyboardAndScreenReader\|RepoPolicyFiles` | 41 test, 26 lulus, 1 gagal, 12 error, 2 dilewati | 41 test, 39 lulus, 2 dilewati | 1.718 test, 1.716 lulus, 2 dilewati | 0 galat | 13 test, 13 lulus |
| 9 | `RouteComparison\|ComparisonAsCost\|NavigationConsistency` | 42 test, 34 lulus, 6 gagal, 2 error | 42 test, 42 lulus | 1.723 test, 1.721 lulus, 2 dilewati | 0 galat | 14 test, 14 lulus |
| 10 | `ButtonBladeParity` | 2 test, 0 lulus, 1 gagal, 1 error | 2 test, 2 lulus | 1.725 test, 1.723 lulus, 2 dilewati | 0 galat | 14 test, 14 lulus |
| 11 | `DesignDirection\|TeamDocsIndex\|PrdReferenceIntegrity\|RepoHygiene\|SkillGovernance\|SemanticToken` | tidak ada (dokumen) | 65 test, 65 lulus | 1.725 test, 1.723 lulus, 2 dilewati | 0 galat | 14 test, 14 lulus |

Angka di atas memakai SQLite dalam memori. Suite bawaan akhir berisi 1.750 test (1.723 lulus, 27 dilewati). Suite
Spatial (PostgreSQL) melewati dirinya tanpa `SPATIAL_TEST_DSN`; dua berkas Spatial baru (`PosSepanjangJalurTest` dengan
tiga test dan `ComparisonShareSchemaTest` dengan dua) baru berjalan di CI. `vendor/bin/pint --test` lulus untuk seluruh
pohon.

**Pengukuran lapisan di peramban** (Chrome lewat MCP, pratinjau statis simulasi, emulasi seluler dengan sentuh dan rasio
piksel 1):

- Bar aksi rincian jalur di 360 x 740 setinggi 69 px dalam satu baris (Simpan 44 x 44, Banding 90 x 44, Rencanakan trip
  153 x 44). Di 320 x 568 setinggi 82 px, tetap satu baris, dengan label "Rencanakan trip" membungkus di dalam
  tombolnya. Dokumen tidak melebar dari 320 px. Sebelum W3, bar yang membungkus menjadi 163 px (W2-F5).
- Baki banding Simpanan di 360 x 740: 58 px dengan satu centang, 69 px dengan nol, dua, atau tiga centang, dan 78 px
  saat pesan batas tampil. Baki ditambah bilah tab menutup 20 sampai 23 persen layar, dan 31 sampai 34 persen bila
  strip pendakian ikut tampil. Di 320 x 568 baki 58 sampai 98 px: 27 sampai 34 persen tanpa strip dan 40 sampai 47
  persen dengan strip, karena strip dan bilah tab sendiri sudah sekitar 28 persen layar itu. Sebelum W3, baki Jelajah
  dengan lima nama, strip, dan bilah tab menutup 40 sampai 57 persen (W2-F1).
- Jelajah di 1440 x 900 dengan strip pendakian (tepi atas strip di 842 px): pada posisi gulir awal peta berakhir di
  828 px dan panel saringan di 824 px; sesudah menggulir keduanya menempel di 92 sampai 770 px, dan panel saringan
  bergulir sendiri (W2-F3). Pengukuran peta memakai garis sintetis yang hanya ada di scratchpad, karena data contoh
  tidak punya geometri. Pratinjau asli menampilkan panel "Peta menunggu garis rute" (150 sampai 394 px).

**Temuan di luar cakupan W3 (OPEN ISSUE):**

- Batas laju simpan (429) dijawab halaman galat bawaan Inertia, bukan pesan di kartu. Keadaan ini sudah ada sebelum W3
  (`docs/fitur/03-pertimbangkan.md`, baris "Batas laju tercapai").
- `elevation_loss_m` di data contoh dan formulir admin disamakan dengan tanjakan, jadi rincian jalur menampilkan tiga
  angka, bukan empat (W3-R29).
- Label faktor `CompatibilityFactor` ("Kesesuaian elevation gain"), kalimat menunggu `Trail::menunggu()` ("Jarak,
  elevation gain, dan estimasi durasi"), dan sebagian kalimat penjelasan mesin ("Elevation gain 1650 m lebih besar
  dari ...", tanpa pemisah ribuan) memakai istilah dan format mesin. Halaman W3 menampilkannya apa adanya, karena
  mengganti kosakata enum dan kalimat mesin menyentuh test di luar W3.
- `resources/js/components/trails/TrailRow.tsx` tidak lagi dipakai halaman sesudah Tugas 4 dan 7, tetapi masih dijaga
  `TrailRowTest` (Konflik 8).

## Keadaan awal yang terukur (FAKTA, 28 September 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite W2 selesai | Unit dan Feature 1.622 test (1.620 lulus, 2 dilewati); suite bawaan 1.642 test (1.620 lulus, 22 dilewati); tsc 0 galat; Pratinjau 9 test | `HANDOVER.md` bagian 0 subbagian W2; Unit dan Feature diulang di salinan dasar |
| Bundel W2 selesai | `app-*.js` 354,78 kB. Ukuran CSS mutlak ikut view terkompilasi lokal (OPEN ISSUE W2-C10), jadi yang dilaporkan hanya selisih pada himpunan view yang sama | Build di salinan dasar |
| Komponen domain di `components/ui` | `FitBadge`, `StatusBadge`, `Freshness`, `FactorBars`, `ElevationProfile`, dan `CheckpointJourney` masih di folder primitif; `DESIGN.md` bagian Primitif menyebutnya "dipindah W3" | `resources/js/components/ui/`; `DESIGN.md` bagian Primitif |
| Jelajah W2 | Baki banding `ConsiderationTray` dengan daftar nama, menulis `--baki-banding` (perbaikan akhir W2); `FilterForm` lama; kartu `TrailRow`; peta lengket setinggi `100dvh - --kepala-atas - 6rem` tanpa `--nav-bawah` | `resources/js/pages/Trails/Index.tsx`, `resources/js/components/trails/ConsiderationTray.tsx`, `resources/css/app.css`, `resources/js/components/ui/ToastUrungkan.tsx` |
| Tab halaman | `/trails/compare` menyalakan Trip, tetapi induknya Simpanan (tab Jelajah); `/mountains/{slug}` belum ada dan tidak menyalakan tab mana pun | `resources/js/layouts/NavLinks.tsx`, `resources/js/pages/Trails/Compare.tsx` |
| Simpan | Rute `trails.timbang` membalik keadaan (bukan idempoten); lepas lewat `DELETE trails/saved/{trail}` (`trails.saved.remove`) | `routes/web.php`, `app/Http/Controllers/Trails/ConsiderationController.php` |
| Tautan bagikan | `config('hiking.share_links')` ada dengan `ttl_days` 30; tidak ada tabel, rute, atau halaman | `config/hiking.php`; `database/migrations` |
| Data contoh | Tanpa geometri jalur (kolom geografi hanya di PostGIS); lima simpanan Budi; satu aturan izin Gunung Gede | `database/seeders/DemoSeeder.php` |
| Kerangka W2 | `useKerangka()` mengembalikan `LayananKerangka` dengan `tampilkanToast` terikat pemanggilnya; `BarAksi` menulis `--bar-aksi`; `PesanJaringan` menulis `--pesan-jaringan`; `--kepala-atas` bawaan 4,25rem mulai 1024 px | `resources/js/layouts/Kerangka.tsx`, `BarAksi.tsx`, `PesanJaringan.tsx`, `resources/css/app.css` |


## Ruling rencana (W3-R1 sampai W3-R49)

Setiap ruling ditulis dengan format keputusan, alasan, dan akibat bila salah. Keputusan koordinator untuk W3 disebut
dengan nomornya (keputusan nomor 1 sampai 12).

| ID | Keputusan | Alasan | Akibat bila salah |
|---|---|---|---|
| W3-R1 | Dasar rencana adalah repo nyata sesudah W2 selesai (28 September 2026), disalin utuh; blok "Ganti" dihitung dari keadaan itu | Pembaruan koordinator: cocokkan dengan repo nyata, bukan salinan W2 | Blok yang teks lamanya berubah sesudah 28 September tidak ditemukan; implementer berhenti dengan NEEDS_CONTEXT, bukan mengarang |
| W3-R2 | Dua belas tugas: sepuluh tugas implementasi berurutan, satu tugas dokumen, satu verifikasi koordinator. Tugas 6 menggabungkan server dan antarmuka rincian jalur | Bentuk props rincian berubah bersama komponennya; bila dipisah, tsc merah di antara dua tugas | Tugas 6 besar untuk satu tinjauan; peninjau boleh meminta dua putaran |
| W3-R3 | Tata bahasa kepercayaan menjadi komponen domain di `components/trails`: `LabelKecocokan`, `StatusAkses`, `Kesegaran`, `LabelSumber`. `FitBadge`, `StatusBadge`, `Freshness`, dan `rinci/LabelSumber` dihapus, dan pemanggilnya dialihkan (keputusan nomor 4) | Satu sumber untuk label dan status di semua layar; `DESIGN.md` W1 sudah menjadwalkan pemindahan ke W3 | Tidak ada perubahan perilaku; nama lama di dokumen dibetulkan Tugas 11 |
| W3-R4 | Glyph `resmi` hanya menemani sumber OFFICIAL, dan jenis sumber dibaca dari `data_sources`, bukan dari tabel asal catatan (keputusan nomor 6) | R-009: tanda resmi pada sumber lain adalah klaim otoritas palsu | Bila pemilik ingin tanda untuk ADMIN_VERIFIED, cukup satu syarat di `LabelSumber` dan satu test |
| W3-R5 | Kata "Belum dinilai" hanya dikirim server (`kataBelumDinilai` dari `RouteFitLabel`), tidak pernah ditulis di klien (keputusan nomor 8) | Satu sumber kata UNKNOWN; R-004 dan R-006 | Kata di klien yang berselisih dengan server tertangkap `TrustGrammarTest::test_the_unrated_word_is_never_typed_in_the_client` |
| W3-R6 | `StatusJalurRingkas::untuk()` meringkas status akses banyak jalur dalam kueri tetap; tanpa catatan yang berlaku hasilnya UNKNOWN, dan penutupan tingkat gunung dikutip dari catatan gunung | R-006, R-012, kaskade §42; anggaran kueri Jelajah dan Bandingkan | Status yang tampil Buka tanpa catatan akan tertangkap `TrustGrammarTest::test_no_current_record_reads_unknown_not_open` |
| W3-R7 | Halaman gunung `GET /mountains/{mountain:slug}` lewat kelas halaman `HalamanGunung`: hanya jalur terbit, tamu dialihkan ke masuk, hero visual data (profil semua jalur satu skala) sampai foto W8, tanpa kartu PVMBG sampai tabelnya ada di W8, izin dengan "Memount menautkan sistem resmi dan tidak menjual izin", kondisi komunitas di bawah status resmi, dan catatan provenans | Induk W3 butir 5; DK-37, DK-43, DK-44; R-017 (foto komunitas bukan hero) | Hero foto atau kartu PVMBG yang dikarang sebelum datanya ada melanggar R-007 |
| W3-R8 | `/mountains/*` menyalakan tab Jelajah | Spec bagian 5.3 menaruh Gunung di Jelajah; tanpa aturan ini tidak ada tab yang menyala | Satu baris di `NavLinks.tsx` dan satu test |
| W3-R9 | `DemoSeeder` mendapat profil elevasi setiap jalur terbit, status tingkat gunung Gunung Gede, dan satu laporan termoderasi dari pendaki contoh kedua, semuanya berpenanda DATA CONTOH | Tanpa isi ini halaman gunung, profil kartu, dan lapisan komunitas kosong di tangkapan Tugas 12 | Data contoh tidak pernah dijalankan ke basis data nyata (Q1); `DemoSeederTest` memeriksa penandanya |
| W3-R10 | Penilaian kecocokan: tujuh faktor diurut bobot `recommendation_rules`, bobot dalam kata (besar ≥0,25; sedang ≥0,12; kecil <0,12), keadaan Sesuai hanya untuk kategori kuat, Perlu perhatian untuk cukup dan lemah, Belum diketahui untuk UNKNOWN; tanpa angka di props | Spec bagian 6.5; BR-09; R-006 | Faktor lemah yang terbaca Sesuai atau UNKNOWN yang terbaca lemah tertangkap `FitAssessmentPageTest` |
| W3-R11 | `?trip=` milik orang lain ditolak 403 lewat Policy trip yang sama dengan halaman trip; trip untuk jalur lain dijawab 404 | Perilaku IDOR yang berlaku sekarang; pilihan 403 atau 404 masih menunggu pemilik (spec v2 P9, Q5) | Bila pemilik memilih 404, satu baris di controller dan satu test berubah |
| W3-R12 | Halaman kecocokan adalah halaman tugas (`bilahTab={false}`) yang kembali ke rincian jalurnya | Spec bagian 5.1 (Ke jalur, Lanjutkan persiapan) | `NavigationConsistencyTest::test_only_task_pages_hide_the_tab_bar` mencatat daftar halaman tugas |
| W3-R13 | Jelajah memeriksa keadaan menunggu (`jumlahTerbit === 0`) sebelum keadaan tersaring (W1-F1, keputusan nomor 1) | Saringan tidak boleh disalahkan atas katalog kosong | Katalog kosong yang terbaca "tersaring" tertangkap test W1-F1 |
| W3-R14 | Jalur berstatus Tutup disembunyikan secara bawaan dan dihitung di "N jalur disembunyikan saringan" dengan "Tampilkan juga" | Spec bagian 6.2: jalur Tutup tidak pernah menjadi rekomendasi (BR-04) | Pendaki yang mencari jalur Tutup tetap menemukannya lewat "Tampilkan juga" |
| W3-R15 | Setiap kartu berlabel, termasuk "Belum dinilai"; tidak ada kartu tanpa label dan tidak ada skor (keputusan nomor 8) | R-004; prototype 02 | Kartu tanpa label tertangkap `TrailCardTest` |
| W3-R16 | Tafsiran kalimat tampil sebagai chip yang dapat dilepas (`abaikan`) dengan "Kembalikan tafsiran kalimat"; pengenalannya tetap lima alias kota | FR-27, spec bagian 6.2; temuan F3 tetap terbuka | Tafsiran yang salah dapat dilepas pembaca tanpa mengetik ulang |
| W3-R17 | "Hanya rendah" dan rentang tanjakan tidak pernah memasukkan UNKNOWN; setiap opsi saringan menyebut hitungannya; saringan kecocokan hanya untuk pembaca berprofil | R-006; spec bagian 6.2 ("yang belum diketahui tidak dianggap rendah") | UNKNOWN yang lolos saringan rendah tertangkap `TrailIndexSearchTest::test_a_climb_range_never_admits_an_unknown_climb` |
| W3-R18 | Durasi hanya dari data bersumber; tidak ada rentang durasi karangan di kartu | DK-31, R-007 | Kartu menulis "belum diketahui" untuk durasi yang tidak ada |
| W3-R19 | Simpan idempoten: `POST trails/saved/{trail}` (`trails.saved.store`) dan `DELETE` yang sudah ada, dengan Urungkan. Melepas simpanan mengembalikan catatan lewat flash `simpananTerhapus`; simpan ulang tidak menimpa catatan; simpanan bercatatan bertanya dulu; simpanan penuh dijawab dialog | Spec bagian 6.2 dan 9.1; konvensi W2-5i (toast milik halaman, `preserveState`) | Halaman basi tidak membalik keadaan; ketuk ganda tertangkap `SavedUndoTest` |
| W3-R20 | Kandidat Jelajah dibatasi 500 (`hiking.jelajah.batas_kandidat`) dan halaman menyebut bila hasilnya terpotong; props satu halaman kartu di bawah 45 kB, dan kueri tetap untuk pembaca berprofil | Anggaran kueri (keputusan nomor 12); katalog nasional bertambah | Batas terlalu kecil dijawab kalimat "Menampilkan N jalur pertama", bukan pemotongan diam-diam |
| W3-R21 | Baki banding Jelajah (`ConsiderationTray`) dilepas beserta `--baki-banding` di `app.css` dan jarak toast. Hitungan dan pintu Simpanan tetap di kepala halaman (W2 Tugas 3). Test perbaikan akhir W2 yang membaca baki itu diarahkan ulang ke niatnya: setiap variabel lapisan yang dihitung punya penulis | W2-F1; prototype 02 dan 15 tanpa baki; spec bagian 6.2 tidak menyebut baki | Bila pemilik menghendaki baki di Jelajah, `ConsiderationTray` W2 dipulihkan dalam bentuk ringkas (hitungan, "Lihat daftar", "Bandingkan") |
| W3-R22 | Test batas simpanan baki (`ConsiderationTest`) diarahkan ulang ke halaman Simpanan, tidak dihapus; batas 50 tetap (DK-05) | Spec bagian 14 | Tidak ada |
| W3-R23 | Penjaga teks panjang untuk komponen domain W3 (`LongInputLayoutTest::test_domain_cards_wrap_long_names_instead_of_cutting_them`) | Review Focus induk: setiap kartu baru dijaga test teks panjang | Nama jalur yang dipotong tidak dapat dibedakan dari tetangganya |
| W3-R24 | Peta Jelajah membedakan label dengan pola garis (utuh, putus panjang, titik-titik, putus-titik) dan legenda bertulisan. MapLibre 4.7.1 tidak menerima `line-dasharray` berbasis data, jadi satu lapisan per label; versi tidak diubah | Induk W3 butir 2; §87 (tidak lewat warna saja); Q20 | Peningkatan MapLibre kelak boleh menggabung lapisan; perilakunya sama |
| W3-R25 | Atribusi bawaan `© OpenStreetMap contributors, SRTM \| Tampilan: © OpenTopoMap (CC-BY-SA)`, di `config/hiking.php` dan `.env.example` | Spec bagian 10.5; `MapConfigTest` | `MAP_ATTRIBUTION` di `.env` pemilik dapat menimpanya (Pra-cek butir 9) |
| W3-R26 | Tanpa satu pun geometri, MapLibre tidak dimuat, pemilih Daftar/Peta tidak tampil, dan layar lebar menampilkan panel menunggu; dengan sebagian geometri, jumlah jalur tanpa garis disebut di pojok peta | Induk W3 "Selesai bila"; R-007 | Basis data pemilik saat ini tanpa geometri: yang tampil keadaan menunggu, bukan peta kosong |
| W3-R27 | Tinggi peta dan panel saringan yang menempel dikurangi `--kepala-atas`, `--pesan-jaringan`, `--nav-bawah`, dan 6rem untuk kepala halaman pada posisi gulir awal; panel saringan bergulir sendiri | W2-F3; diukur di 1440 x 900 dengan strip | Di layar yang sangat pendek, tinggi peta berhenti di `min-h-[20rem]` dan halaman bergulir |
| W3-R28 | `PetaSebaranJalur` dan `tests/Spatial/PetaSebaranJalurTest.php` tidak diubah: fitur membawa id dan geometri, dan pola diambil dari label kartu di klien | Induk W3 butir 2 menyebut berkas itu; kontraknya sudah cukup | Tidak ada perubahan kueri spasial |
| W3-R29 | Rincian jalur menampilkan tiga angka sekali jalan naik (Jarak, Tanjakan, Naik), bukan empat; "Turunan" tidak ditampilkan | `elevation_loss_m` di data contoh dan formulir admin disamakan dengan tanjakan, jadi angka keempat menyesatkan (R-007). Spec bagian 6.4 menyebut empat angka | Bila pemilik ingin angka turunan, perlu kurasi kolom itu lebih dulu; tercatat sebagai OPEN ISSUE |
| W3-R30 | "Apa tantangannya?" memuat tuntutan fisik, teknis, dan navigasi dengan tiga ruas dan kata, medan, sumber air, dan area kemah; baris "Lingkungan" tidak ada | Tidak ada kolom sumber untuk lingkungan (R-007). Spec bagian 6.4 menyebutnya | Baris itu ditambahkan bila datanya ada |
| W3-R31 | Kecocokan di rincian ditulis "untuk rencana ini" bila pembaca punya trip PLANNED untuk jalur itu dengan tanggal hari ini atau sesudahnya (zona jalur); selain itu kecocokan dasar | Spec bagian 6.4 | Trip lama tidak lagi membentuk kalimat kecocokan |
| W3-R32 | Profil elevasi mengarsir ruas tercuram (jendela 5 dari 20 sampel) dan menulisnya dalam kalimat | Induk W3 butir 3; spec bagian 6.4 | Ruas tercuram dari profil kasar dapat bergeser; kalimatnya menyebut km sehingga dapat diperiksa |
| W3-R33 | km pos hanya dari garis rute (`ST_LineLocatePoint`, `PosSepanjangJalur`); tanpa geometri tidak ada km | R-007 | Tanpa geometri, daftar pos tampil tanpa km, bukan km tebakan |
| W3-R34 | Waktu pos berupa rentang dari rekaman ("A sampai B dari awal"), dengan jumlah rekaman minimal per ruas disebut | DK-31; spec bagian 6.4 | Tanpa rekaman cukup, waktu tidak ditulis |
| W3-R35 | Aksen pos (`bg-aksen-pos`) menandai pos berkemah dan tujuan di profil, peta jalur, dan daftar pos, selalu dengan lingkaran bernomor dan kata jenisnya (keputusan nomor 3) | W1-12b; kuning pos hanya 1,76:1, jadi tidak pernah sendirian | `TrailDetailPageTest::test_posts_carry_the_accent_for_camp_and_destination` |
| W3-R36 | Bagian gunung api tidak tampil di rincian sampai tabelnya dibuat W8 | Induk W3 butir 3; DK-43 | Tidak ada kartu PVMBG karangan |
| W3-R37 | Bar aksi ponsel rincian jalur satu baris: `TombolSimpan` bentuk `bar` (tombol ikon 44 px), Banding dan Rencanakan trip berukuran kecil, dan Rencanakan trip mengisi sisa baris. Kolom kanan layar lebar yang menempel menyisakan lapisan bawah | W2-F5; prototype 04 | Label "Rencanakan trip" membungkus di dalam tombolnya di 320 px, bukan memindah tombol ke baris baru |
| W3-R38 | Ringkasan pendakian memakai `ProfilElevasi` dan `DaftarPos` yang sama dengan rincian jalur; komponen lama dihapus | Satu komponen per konsep | `HikeSummaryTest` diperbarui |
| W3-R39 | Simpanan: `KotakCentang` berlabel nama jalur (keputusan nomor 5), label kecocokan, catatan pribadi "Hanya Anda", dan hapus dengan Urungkan yang toastnya milik halaman | Spec bagian 6.6; W2-5i | Kartu yang hilang oleh aksinya sendiri tidak ikut menutup toastnya |
| W3-R40 | Baki banding Simpanan satu baris ringkas: hitungan "Dipilih N dari 3" dengan ruas, satu aksi, dan pesan batas pendek yang menggantikan hitungan sesaat; tanpa nama jalur | W2-F1; prototype 06 memuat satu aksi, bukan dua | Pesan batas terdengar lewat `role="status"`; nama tetap terlihat dari centang di kartu |
| W3-R41 | "Banding" di rincian jalur hanya tampil bila ada simpanan lain yang masih terbit | DK-40: tidak ada kendali mati | Pembaca tanpa simpanan lain tidak melihat tombol yang tidak dapat dipakai |
| W3-R42 | Tautan bagikan: token acak 192 bit (base64url 32 karakter) yang hanya disimpan hash SHA-256-nya, 7 hari, paling banyak 20 aktif per akun, dicabut hanya oleh pemiliknya (lainnya 404), halaman publik noindex (meta dan `X-Robots-Tag`) tanpa catatan, label kecocokan, atau nama pembuat, tautan tidak berlaku dijawab 404 "Tautan tidak berlaku", batas 60 bukaan per menit per alamat IP, dan tautannya ikut terhapus bersama akun | Induk W3 butir 6; spec bagian 9.3; kebijakan data pribadi bagian 3 (data tanpa dasar retensi dihapus) | Token yang bocor tidak dapat dipulihkan dari basis data; pemilik mencabutnya |
| W3-R43 | Halaman `/b/{token}` dibuka tanpa akun seperti rencana induk dan spec bagian 5.3 dan 9.3, walau PRD baris 1073 dan `docs/fitur/03-pertimbangkan.md` masih mencatat akses tanpa akun menunggu pemilik (spec v2 P8). Butir ini masuk daftar izin akhir | Rencana induk yang disetujui memuatnya; tanpa akses tamu fitur ini tidak berguna bagi teman yang belum mendaftar | Bila pemilik menolak P8, rute `b/{token}` dipindah ke grup `auth` dan test tamu dibalik |
| W3-R44 | Migrasi `comparison_shares` menyalakan RLS di pgsql, diuji di SQLite dan PostgreSQL CI (`tests/Spatial/ComparisonShareSchemaTest.php`), dan tidak dijalankan di Supabase (keputusan nomor 10, Q2) | R-019; DK-23 | Halaman tautan bagikan gagal di produksi sampai migrasinya dijalankan atas izin |
| W3-R45 | Banding milik tab Trip (DK-25, spec bagian 5.3), jadi induknya `/trips`; tautan "Lihat simpanan" di bawah judul tetap membawa ke Simpanan. Test baru memastikan induk setiap halaman menyalakan tab yang sama dengan halamannya | W2-F2: tombol kembali tidak boleh memindah tab. Prototype 07 berselisih dengan dirinya sendiri (tab Trip menyala, kembali ke Simpanan); DK-25 pemilik yang dipakai | Bila pemilik memindah Banding ke Jelajah, aturan Trip dan Jelajah di `NavLinks.tsx`, test W1 `test_the_compare_page_lights_trip_not_jelajah`, dan induk Banding berubah bersama; test induk satu tab tetap berlaku |
| W3-R46 | Baris Bandingkan: status resmi bersumber dan waktunya, laporan terbaru (warna komunitas), izin, bilah tanjakan dan profil satu skala dengan kalimat; "Sorot yang berbeda" menandai beda tanpa pemenang; "Jadikan dasar rencana" berupa tombol setara tanpa panah | Spec bagian 6.7; R-016; DK-31; DK-39 | Tombol yang lebih menonjol untuk satu jalur akan tertangkap `RouteComparisonTest::test_every_trail_can_be_the_plan_base_with_equal_weight` |
| W3-R47 | Komponen Blade `x-ui.button` menyalin kelas `Button.tsx`, dan judul halaman publik jalur memakai `text-judul` (W1-F2, keputusan nomor 2) | Satu rupa tombol di React dan Blade | Kelas yang berselisih tertangkap `ButtonBladeParityTest` |
| W3-R48 | Dokumen diperbarui di tugas tersendiri: `DESIGN.md` bagian Turunan, `docs/ARCHITECTURE.md`, `CONTRIBUTING.md`, dan `docs/fitur/01`, `02`, `03`, `05`. `docs/PRD.md` dan `docs/KEBIJAKAN-DATA-PRIBADI.md` tidak diubah | PRD butuh catatan tata kelola bagian §146; tautan bagikan mengikuti bawaan kebijakan bagian 3 | PRD baris 1073 basi sampai koordinator atau pemilik memperbaruinya (Konflik 5) |
| W3-R49 | Verifikasi gelombang oleh koordinator: tangkapan 390 dan 1440 px terang dan gelap dibanding acuan 02 sampai 07 dan 15, antislop R-20 per layar, Delivery Gate empat blok, dan jalur papan ketik lewat klik koordinator; e2e menunggu Playwright (Q17) | Keputusan nomor 9 dan 11 | Tangkapan yang berselisih dengan acuan dicatat di README tangkapan W3, bukan diabaikan |

### Konflik yang perlu ruling koordinator

1. **Akses tautan bagikan tanpa akun (spec v2 P8).** Rencana induk W3 butir 6 dan spec bagian 5.3 serta 9.3 memuat
   `/b/{token}` publik, sedangkan PRD baris 1073 dan `docs/fitur/03-pertimbangkan.md` masih menulis "menunggu
   persetujuan pemilik". Rekomendasi: bangun seperti rencana induk (W3-R43), masukkan P8 ke permintaan izin akhir, dan
   bila ditolak pindahkan rute ke grup `auth`.
2. **Pemilik tab Banding (W2-F2).** Prototype 07 menyalakan tab Trip tetapi kembali ke Simpanan. Rekomendasi: DK-25 dan
   spec bagian 5.3 yang berlaku, jadi induknya `/trips` (W3-R45). Alternatifnya Banding pindah ke Jelajah, yang mengubah
   arsitektur navigasi pemilik (R-021).
3. **Baki banding Jelajah dilepas, termasuk `--baki-banding` dari perbaikan akhir W2 (W3-R21).** Rekomendasi: terima.
   Prototype 02 dan 15 tidak punya baki, dan hitungan Simpanan sudah di kepala halaman.
4. **Tiga angka, bukan empat, dan tanpa baris Lingkungan di rincian jalur (W3-R29, W3-R30).** Rekomendasi: terima
   sebagai penyimpangan spec bagian 6.4 yang jujur terhadap data; kurasi `elevation_loss_m` dan sumber lingkungan
   menyusul.
5. **PRD baris 1073 (keadaan FR-23) dan `HANDOVER.md` baris 455 (daftar komponen lama) basi sesudah W3.** Rekomendasi:
   koordinator memperbarui `HANDOVER.md` di Tugas 12. PRD diperbarui lewat catatan tata kelola bagian §146 bersama
   keputusan P8.
6. **403 atau 404 untuk trip orang lain di halaman kecocokan (W3-R11).** Rekomendasi: 403 seperti halaman trip sampai
   Q5 diputuskan.
7. **Alasan satu baris di kartu Jelajah.** `TrailFitService::alasan()` mendahulukan faktor lemah, jadi kartu Cocok
   dapat membawa kalimat perhatian, sedangkan prototype 02 memakai alasan kesesuaian. Rekomendasi: pertahankan di W3
   karena rincian jalur dan Beranda memakai kalimat yang sama, lalu putuskan bersama W5.
8. **`TrailRow.tsx` tanpa pemakai.** Jelajah memakai `KartuJalur` dan Simpanan memakai `KartuSimpanan`, sedangkan
   `TrailRowTest` masih menjaga bobot durasi di `TrailRow`. Rekomendasi: hapus komponennya di putaran perbaikan W3 dan
   pindahkan niat test itu ke kartu yang ada, atau pakai ulang di halaman Rekomendasi (W4 atau W5).

### Pra-cek untuk koordinator

Rencana ditulis di atas repo nyata sesudah W2 selesai. Asumsi tentang antarmuka W2 berikut diperiksa langsung di
`E:/praktik_aplikasi_web` (baca saja) pada 28 September 2026. Periksa ulang bila repo berubah sesudah tanggal itu.

| Asumsi | Tempat rencana bergantung | Keadaan di repo nyata |
|---|---|---|
| 1. Salinan dasar sama dengan repo nyata | Semua blok "Ganti" | Hash 1.239 berkas sama, kecuali `tests/Pratinjau/PratinjauTestCase.php` (konstanta `ASAL` simulasi). Rencana memakai `self::ASAL` apa adanya |
| 2. `useKerangka()` mengembalikan `LayananKerangka` dengan `tampilkanToast` terikat pemanggilnya (W2-5i); tipe `LayananKerangka` diekspor `resources/js/layouts/Kerangka.tsx` | Tugas 4 (`Trails/Index.tsx`, `TombolSimpan`), Tugas 6 (`Trails/Show.tsx`), Tugas 7 (`Trails/Saved.tsx`) | Sesuai (`Kerangka.tsx` baris 77 dan 129) |
| 3. Konvensi Urungkan: `preserveState` dan `preserveScroll` untuk aksi di halaman yang sama, flash untuk konfirmasi sesudah pindah halaman, `onNetworkError` di dialog pengirim formulir | Tugas 4 (`TombolSimpan`), Tugas 8 (`LembarBagikan`) | Sesuai; dialog konfirmasi hapus `TombolSimpan` menutup dirinya sebelum mengirim, jadi galat jaringannya ke pesan jaringan global |
| 4. `--pesan-jaringan`, `--nav-bawah`, `--bar-aksi`, dan `--kepala-atas` (bawaan 4,25rem mulai 1024 px) | Tugas 4 sampai 7 (panel lengket, bar aksi, peta) | Sesuai (`resources/css/app.css` sesudah perbaikan akhir W2) |
| 5. `--baki-banding` ditulis `ConsiderationTray` dan dibaca `scroll-padding-bottom` serta jarak toast; `InertiaLayoutTest` dan `DesignPrimitivesTest` menyebutnya | Tugas 4 melepas ketiganya dan mengarahkan ulang kedua test | Sesuai dengan keadaan repo; perlu ruling Konflik 3 |
| 6. `<AppLayout bilahTab={false}>` di `Trails/Show` dan `Trips/Create`; daftar halaman tugas di `NavigationConsistencyTest` | Tugas 3 menambah `Trails/Fit` | Sesuai |
| 7. Nama props bersama `jumlahSimpanan`, `pendakianAktif`, `daftarPengingat`; `jumlahSimpanan` null di `trips.hike` | Tugas 4 dan 8 hanya menambah kunci `flash` (`simpananTerhapus`, `tautanBagikan`); tidak ada prop halaman bernama sama | Sesuai; `ReminderSheetTest` tetap hijau di setiap tugas simulasi |
| 8. `ConsiderationService::kueriSimpananAktif()` privat, dipakai `simpanan()` dan `jumlahSimpanan()`; "aktif" berarti belum diarsip | Tugas 4 menambah `simpan()` yang memeriksa batas lewat `jumlahSimpanan()`; Tugas 7 dan 9 memakai `simpanan()` | Sesuai; pemilihan pendakian aktif tetap urusan W6 |
| 9. `MAP_ATTRIBUTION` di `.env` pemilik | Tugas 5 mengganti nilai bawaan saja | Tidak diperiksa: `.env` tidak dibaca. Bila `.env` pemilik mengisinya, rumusan lama tetap tampil sampai baris itu diganti atau dihapus |
| 10. `muatUlangMilikKerangka` dan pemberitahuan versi baru (W2-F6, W2-F7) | Tidak ada: halaman W3 tidak memakai kunjungan async (`async: true` hanya di `Trips/Preparation.tsx`) | Sesuai |
| 11. Suite nyata sesudah W2: Unit dan Feature 1.622 test | Angka hijau setiap tugas | Sama dengan salinan dasar (1.622 test, 1.620 lulus, 2 dilewati) |
| 12. Butir "Ke W3" dari tinjauan akhir W2 | W2-F1 (Tugas 4 dan 7), W2-F2 (Tugas 9), W2-F3 (Tugas 5), W2-F5 (Tugas 6) | Semua punya test di tugas pemiliknya dan pengukuran di Bukti simulasi |

## Perbandingan skill (DK-57)

Urutan wewenang: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah), lalu antislop, lalu saran skill. antislop
berlaku mode 1 (selama pekerjaan) atas arahan pemilik yang sudah berdiri, dan tidak ada pertanyaan baru kepada pemilik.

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop, antislop-ui, antislop-layoutmobile, antislop-copywriting, antislop-code | R-26: tombol bagikan baru tampil setelah rutenya ada, dan "Banding" hanya bila ada simpanan lain (DK-40). R-27: setiap layar punya keadaan pertama kali, tersaring, memuat, galat peta, dan menunggu. R-23: data contoh berpenanda, pendaki contoh kedua berinisial. Keadaan kosong menyebut siapa yang ditunggu. Tanpa angka karangan (durasi, km, turunan). Tanpa panah hiasan dan titik status hiasan. Target 44 px dan bar satu baris di 320 px. Komentar menjelaskan alasan, bukan mengulang kode | Tidak ada; antislop adalah saringan di atas `DESIGN.md` |
| ui-ux-pro-max | Alternatif teks untuk visualisasi data: profil elevasi dengan kalimat, legenda peta bertulisan, pola garis selain warna. Label untuk setiap isian: kotak cari dan rentang tanjakan yang diketik (WCAG 2.5.7). Kepala baris tabel menempel saat tabel digeser. Lembar bawah untuk saringan di ponsel | Persen kecocokan dan bilah skor (R-005, BR-09); keluaran `--design-system` (gradien, Lucide), sama dengan W1 dan W2 |
| design-taste-frontend | Satu aksen yang tidak pernah sendirian (pos berkemah dan tujuan). Status dibawa bentuk dan kata, bukan titik berwarna. Angka tabular di kartu dan tabel. Tidak ada kartu di dalam kartu kecuali kartu pratinjau peta. Hierarki lewat bobot huruf, bukan warna tambahan | Hero foto untuk halaman gunung (R-017 dan DK-37: foto berlisensi baru W8; yang dipakai visual data) |
| impeccable (`reference/adapt.md`, `reference/adapt.native.md`) | "Restructure, don't stretch": Jelajah tiga panel mulai 1280 px, rincian dua kolom dengan kolom kanan menempel mulai 1024 px. Lembar di ponsel dan panel di layar lebar untuk saringan. Pengungkapan bertahap ("Kenapa?" dan "Lihat penilaian lengkap") | Kerangka peta layar penuh dengan daftar di lembar bawah untuk ponsel (prototype 02 memakai pemilih Daftar dan Peta) |

**Pembacaan desain (antislop bagian 3):** klaster penemuan dan penimbangan jalur untuk pendaki pemula Indonesia di
ponsel, bahasa visual editorial utilitarian yang hangat, dial ENERGY 2 / RHYTHM 2 / MOTION 2. Setiap kartu memberi satu
keputusan: cocok atau tidak, dan mengapa, dengan sumber yang dapat diperiksa.

**Inventaris skill untuk gelombang ini (CLAUDE.md bagian 7):**

- INTI: antislop (dengan antislop-ui, antislop-layoutmobile, antislop-copywriting, antislop-code), impeccable,
  ui-ux-pro-max, design-taste-frontend, testing-best-practices, laravel-best-practices (kelas halaman, Form Request,
  Policy, migrasi), superpowers:test-driven-development.
- PENDUKUNG: tailwindcss-development (nilai arbitrer `calc()`, varian `min-[360px]`), mobile-native (target sentuh,
  `dvh`), apple-design (lembar bawah, tombol kembali), infer-conventions (penamaan Indonesia sesuai berkas tetangga),
  ecc:frontend-a11y, ecc:security-review (token dan halaman publik), ecc:postgres-patterns (indeks dan RLS migrasi).
- LINTAS: superpowers:verification-before-completion dan Delivery Gate antislop.
- N/A:
  - slides, banner-design, imagegen-frontend-web, imagegen-frontend-mobile: bukan artefak halaman;
  - brand dan brandkit: aset logo menunggu gerbang DK-16;
  - animate, improve-animations, emil-design-eng: gerak W3 memakai daftar tertutup W1 tanpa animasi baru;
  - livewire-development dan volt-development: Livewire sudah dipensiunkan;
  - deploying-to-cloud: tanpa rilis;
  - write-swift: bukan aplikasi native.

## File Structure

**Dibuat:**

- Tugas 1: `app/Services/Trails/StatusJalurRingkas.php`, `resources/js/components/trails/Kesegaran.tsx`, `resources/js/components/trails/LabelKecocokan.tsx`, `resources/js/components/trails/LabelSumber.tsx`, `resources/js/components/trails/StatusAkses.tsx`, `tests/Feature/TrustGrammarTest.php`.
- Tugas 2: `app/Http/Controllers/Trails/MountainPageController.php`, `app/Http/Resources/MountainHeaderResource.php`, `app/Services/Mountains/HalamanGunung.php`, `app/Services/Trails/SkalaProfil.php`, `resources/js/components/trails/BarisStatus.tsx`, `resources/js/components/trails/gunung/DaftarJalurGunung.tsx`, `resources/js/components/trails/gunung/KartuIzin.tsx`, `resources/js/components/trails/gunung/KartuStatusGunung.tsx`, `resources/js/components/trails/gunung/KondisiPendaki.tsx`, `resources/js/components/trails/gunung/VisualProfilGunung.tsx`, `resources/js/components/trails/gunung/types.ts`, `resources/js/pages/Mountains/Show.tsx`, `tests/Feature/MountainPageTest.php`.
- Tugas 3: `app/Http/Controllers/Trails/TrailFitAssessmentController.php`, `app/Services/Trails/PenilaianKecocokan.php`, `resources/js/components/trails/FaktorKecocokan.tsx`, `resources/js/components/trails/kecocokan/DinilaiDari.tsx`, `resources/js/components/trails/kecocokan/KepalaPenilaian.tsx`, `resources/js/components/trails/kecocokan/LangkahBerikut.tsx`, `resources/js/components/trails/kecocokan/SyaratDasar.tsx`, `resources/js/components/trails/kecocokan/types.ts`, `resources/js/pages/Trails/Fit.tsx`, `tests/Feature/FitAssessmentPageTest.php`.
- Tugas 4: `app/Http/Requests/Trails/StoreSavedTrailRequest.php`, `app/Http/Resources/KartuJalurResource.php`, `app/Services/Trails/HalamanJelajah.php`, `resources/js/components/trails/GarisProfil.tsx`, `resources/js/components/trails/TombolSimpan.tsx`, `resources/js/components/trails/jelajah/KartuBelumDinilai.tsx`, `resources/js/components/trails/jelajah/KartuJalur.tsx`, `resources/js/components/trails/jelajah/KotakCari.tsx`, `resources/js/components/trails/jelajah/SaringanJelajah.tsx`, `resources/js/components/trails/jelajah/TafsiranKalimat.tsx`, `resources/js/components/trails/jelajah/saringan.ts`, `tests/Feature/SavedUndoTest.php`, `tests/Feature/TrailCardTest.php`.
- Tugas 6: `app/Services/Trails/PosSepanjangJalur.php`, `app/Services/Trails/ProfilElevasiJalur.php`, `resources/js/components/trails/ProfilElevasi.tsx`, `resources/js/components/trails/rinci/AngkaNaik.tsx`, `resources/js/components/trails/rinci/CocokkahUntukSaya.tsx`, `resources/js/components/trails/rinci/DaftarPos.tsx`, `resources/js/components/trails/rinci/Persiapan.tsx`, `resources/js/components/trails/rinci/SumberHalaman.tsx`, `resources/js/components/trails/rinci/Tantangan.tsx`, `tests/Spatial/PosSepanjangJalurTest.php`.
- Tugas 8: `app/Http/Controllers/Trails/ComparisonShareController.php`, `app/Http/Requests/Trails/StoreComparisonShareRequest.php`, `app/Models/ComparisonShare.php`, `app/Services/Trails/TautanBagikan.php`, `database/factories/ComparisonShareFactory.php`, `database/migrations/2026_09_28_000100_create_comparison_shares_table.php`, `resources/js/components/trails/banding/LembarBagikan.tsx`, `resources/js/pages/Public/SharedComparison.tsx`, `tests/Feature/ComparisonShareTest.php`, `tests/Spatial/ComparisonShareSchemaTest.php`.
- Tugas 10: `resources/views/components/ui/button.blade.php`, `tests/Feature/ButtonBladeParityTest.php`.

**Dihapus:**

- Tugas 1: `resources/js/components/trails/rinci/LabelSumber.tsx`, `resources/js/components/ui/FitBadge.tsx`, `resources/js/components/ui/Freshness.tsx`, `resources/js/components/ui/StatusBadge.tsx`.
- Tugas 3: `resources/js/components/ui/FactorBars.tsx`.
- Tugas 4: `resources/js/components/trails/ConsiderationTray.tsx`, `resources/js/components/trails/FilterForm.tsx`, `resources/js/components/trails/Menunggu.tsx`.
- Tugas 6: `resources/js/components/ui/CheckpointJourney.tsx`, `resources/js/components/ui/ElevationProfile.tsx`.

**Diubah:**

- Tugas 1: `app/Http/Controllers/Trails/RouteComparisonController.php`, `app/Services/DataFreshnessService.php`, `app/Services/OfficialStatusService.php`, `app/Services/Trails/TrailDetailPage.php`, `resources/js/components/community/types.ts`, `resources/js/components/discovery/KartuHasil.tsx`, `resources/js/components/discovery/types.ts`, `resources/js/components/trails/FitLine.tsx`, `resources/js/components/trails/banding/TabelBanding.tsx`, `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/components/trails/rinci/LaporanKomunitas.tsx`, `resources/js/components/trails/rinci/types.ts`, `resources/js/components/trips/FitTrip.tsx`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/pages/Admin/OfficialStatuses.tsx`, `resources/js/pages/Community/TrailNews.tsx`, `resources/js/pages/Trails/Compare.tsx`, `resources/js/pages/Trails/Show.tsx`, `resources/js/pages/Trips/Readiness.tsx`, `tests/Feature/AccessibilityTest.php`, `tests/Feature/AmbientFitTest.php`, `tests/Feature/CommunityPhotoTest.php`, `tests/Feature/TrailDetailPageTest.php`.
- Tugas 2: `app/Services/Trails/TrailFigures.php`, `database/seeders/DemoSeeder.php`, `resources/js/components/trails/StatusAkses.tsx`, `resources/js/components/trails/types.ts`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/layouts/NavLinks.tsx`, `routes/web.php`, `tests/Feature/DemoSeederTest.php`, `tests/Feature/NavigationConsistencyTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 3: `resources/js/components/discovery/PanelAlasan.tsx`, `resources/js/components/discovery/types.ts`, `resources/js/components/trails/rinci/types.ts`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/pages/Trails/Show.tsx`, `routes/web.php`, `tests/Feature/FactorBarsTest.php`, `tests/Feature/NavigationConsistencyTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 4: `app/Http/Controllers/Trails/ConsiderationController.php`, `app/Http/Controllers/Trails/SavedRoutesController.php`, `app/Http/Controllers/Trails/TrailIndexController.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Resources/TrailRowResource.php`, `app/Services/ConsiderationService.php`, `app/Services/RouteFit/TrailFitSummary.php`, `app/Services/TrailFitService.php`, `app/Services/Trails/TrailSearch.php`, `config/hiking.php`, `resources/css/app.css`, `resources/js/components/trails/types.ts`, `resources/js/components/ui/ToastUrungkan.tsx`, `resources/js/components/ui/TombolIkon.tsx`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/layouts/StripPendakian.tsx`, `resources/js/pages/Trails/Index.tsx`, `resources/js/types/index.d.ts`, `routes/web.php`, `tests/Feature/ComparisonAsCostTest.php`, `tests/Feature/ConsiderationTest.php`, `tests/Feature/DesignPrimitivesTest.php`, `tests/Feature/InertiaLayoutTest.php`, `tests/Feature/LongInputLayoutTest.php`, `tests/Feature/RecommendationExclusionTest.php`, `tests/Feature/ResultListBudgetTest.php`, `tests/Feature/SavedRoutesTest.php`, `tests/Feature/TrailIndexSearchTest.php`.
- Tugas 5: `.env.example`, `config/hiking.php`, `resources/js/components/trails/PetaJelajah.tsx`, `resources/js/pages/Trails/Index.tsx`, `tests/Feature/MapConfigTest.php`, `tests/Feature/TrailIndexMapTest.php`.
- Tugas 6: `app/Http/Resources/TrailHeaderResource.php`, `app/Models/TripPlan.php`, `app/Services/Community/HasilPendakian.php`, `app/Services/PreparationService.php`, `app/Services/TrailFitService.php`, `app/Services/Trails/CheckpointJourney.php`, `app/Services/Trails/PenilaianKecocokan.php`, `app/Services/Trails/SkalaProfil.php`, `app/Services/Trails/TrailDetailPage.php`, `config/hiking.php`, `resources/js/components/trails/TombolSimpan.tsx`, `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/components/trails/rinci/PetaJalur.tsx`, `resources/js/components/trails/rinci/types.ts`, `resources/js/pages/Community/HikeSummary.tsx`, `resources/js/pages/Trails/Show.tsx`, `tests/Feature/AmbientFitTest.php`, `tests/Feature/AwaitingDataTest.php`, `tests/Feature/CheckpointJourneyTest.php`, `tests/Feature/CheckpointPaceDisplayTest.php`, `tests/Feature/ElevationProfileDisplayTest.php`, `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/HikeSummaryTest.php`, `tests/Feature/ProgressLadderOnTrailDetailTest.php`, `tests/Feature/TrailDetailMapTest.php`, `tests/Feature/TrailDetailPageTest.php`.
- Tugas 7: `app/Http/Controllers/Trails/SavedRoutesController.php`, `resources/js/components/trails/TombolSimpan.tsx`, `resources/js/components/trails/banding/AksiPilihan.tsx`, `resources/js/components/trails/banding/KartuSimpanan.tsx`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/pages/Trails/Saved.tsx`, `tests/Feature/SavedRoutesTest.php`, `tests/Feature/SavedTrailNoteTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 8: `app/Http/Controllers/Trails/SavedRoutesController.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `config/hiking.php`, `database/README.md`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/lib/bukanHalamanReact.json`, `resources/js/pages/Trails/Saved.tsx`, `resources/js/types/index.d.ts`, `routes/web.php`, `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/PageHeaderContractTest.php`, `tests/Feature/RouteAccessMatrixTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 9: `app/Http/Controllers/Trails/RouteComparisonController.php`, `app/Services/Trails/RouteComparisonTable.php`, `resources/js/components/trails/banding/TabelBanding.tsx`, `resources/js/pages/Trails/Compare.tsx`, `tests/Feature/NavigationConsistencyTest.php`, `tests/Feature/RouteComparisonTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`.
- Tugas 10: `resources/views/public/trail.blade.php`.
- Tugas 11: `CONTRIBUTING.md`, `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur/01-jelajah-rute.md`, `docs/fitur/02-detail-rute.md`, `docs/fitur/03-pertimbangkan.md`, `docs/fitur/05-kabar-kondisi.md`.

Tugas 12 (koordinator) membuat `docs/desain/tangkapan/w3/*.webp` dan `docs/desain/tangkapan/w3/README.md`, dan memperbarui `HANDOVER.md` bagian 0.

---

### Task 1: Tata bahasa kepercayaan (label kecocokan, status akses, kesegaran, sumber)

Spec bagian 7; keputusan koordinator nomor 4, 6, dan 8; ruling W3-R3 sampai W3-R6. Tugas ini membangun kosakata yang
dipakai semua layar W3 sesudahnya:

- label kecocokan selalu ikon dan kata;
- status akses dibawa bentuk dan kata, beserta cakupannya;
- kesegaran menyebut waktu dan keadaannya ("Terkini", "Perlu ditinjau", "Kedaluwarsa");
- label sumber memakai jenis sumber dari `data_sources`, dengan glyph `resmi` hanya untuk OFFICIAL.

`StatusJalurRingkas` meringkas status akses banyak jalur dalam kueri tetap, dan UNKNOWN bila tidak ada catatan yang
berlaku. Tiga komponen domain lama (`FitBadge`, `StatusBadge`, `Freshness`) dan `rinci/LabelSumber` pindah ke
`components/trails` dengan nama baru, dan semua pemanggilnya dialihkan.

**Files:**
- Create: `app/Services/Trails/StatusJalurRingkas.php`, `resources/js/components/trails/Kesegaran.tsx`, `resources/js/components/trails/LabelKecocokan.tsx`, `resources/js/components/trails/LabelSumber.tsx`, `resources/js/components/trails/StatusAkses.tsx`
- Modify: `app/Http/Controllers/Trails/RouteComparisonController.php`, `app/Services/DataFreshnessService.php`, `app/Services/OfficialStatusService.php`, `app/Services/Trails/TrailDetailPage.php`, `resources/js/components/community/types.ts`, `resources/js/components/discovery/KartuHasil.tsx`, `resources/js/components/discovery/types.ts`, `resources/js/components/trails/FitLine.tsx`, `resources/js/components/trails/banding/TabelBanding.tsx`, `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/components/trails/rinci/LaporanKomunitas.tsx`, `resources/js/components/trails/rinci/types.ts`, `resources/js/components/trips/FitTrip.tsx`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/pages/Admin/OfficialStatuses.tsx`, `resources/js/pages/Community/TrailNews.tsx`, `resources/js/pages/Trails/Compare.tsx`, `resources/js/pages/Trails/Show.tsx`, `resources/js/pages/Trips/Readiness.tsx`
- Delete: `resources/js/components/trails/rinci/LabelSumber.tsx`, `resources/js/components/ui/FitBadge.tsx`, `resources/js/components/ui/Freshness.tsx`, `resources/js/components/ui/StatusBadge.tsx`
- Test: `tests/Feature/TrustGrammarTest.php` (baru), `tests/Feature/AccessibilityTest.php`, `tests/Feature/AmbientFitTest.php`, `tests/Feature/CommunityPhotoTest.php`, `tests/Feature/TrailDetailPageTest.php`

**Interfaces:**
- Consumes: `OfficialStatusService` dan kaskade gunung ke jalur (sudah ada), `DataFreshnessService::hariTinjau()` dan `hariPeringatan()` (sudah ada), `RouteFitLabel::UNRATED_TEXT` ("Belum dinilai"), `Icon` dan glyph domain `resmi` (W1), `Lencana` dengan `NadaLencana` (W1).
- Produces:
  - `App\Services\Trails\StatusJalurRingkas::untuk(Collection $trails): array<int, array{status: string|null, label: string, cakupan: string|null, sumber: array{jenis: string|null, label: string|null, nama: string|null}|null, waktu: string|null, kesegaran: string|null}>` berkunci id jalur, dan `bentuk(?OfficialStatus $catatan, string $zona): array` untuk satu catatan;
  - `DataFreshnessService::keadaan(OfficialStatus $status): string` ("Terkini", "Perlu ditinjau", atau "Kedaluwarsa");
  - props rincian jalur `statusResmi.jenisSumber` dan `perizinan.jenisSumber`; prop Bandingkan `kataBelumDinilai`;
  - `resources/js/components/trails/LabelKecocokan.tsx`: `LabelKecocokan({ nilai, label, className })`, `type LabelFit = 'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | null`;
  - `resources/js/components/trails/StatusAkses.tsx`: `StatusAkses({ nilai, label, cakupan, className })`, `type StatusResmi = 'OPEN' | 'RESTRICTED' | 'CLOSED' | null`;
  - `resources/js/components/trails/Kesegaran.tsx`: `Kesegaran({ waktu, awalan = 'Diperbarui', keadaan, className })`;
  - `resources/js/components/trails/LabelSumber.tsx`: `LabelSumber({ jenis, className })`, `LabelKomunitas()`, `type JenisSumber`, `interface SumberKeterangan`.

- [ ] **Step 1: Tulis test yang gagal.** Buat test baru dan perbarui test yang membaca jalur komponen lama. Setiap test
  yang diubah menulis alasannya di docblock.

Buat `tests/Feature/TrustGrammarTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tata bahasa kepercayaan (spec v3 bagian 7; W3 Tugas 1): label kecocokan, status akses,
 * dan asal sebuah keterangan terbaca dengan cara yang sama di setiap layar klaster Jelajah.
 *
 * Yang dijaga di sini adalah klaim yang dapat menyesatkan pendaki bila salah: cap "resmi"
 * yang dipinjam keterangan tak resmi (R-009), status yang diwariskan gunung tetapi dikutip
 * dari jalurnya (§42), status kosong yang terbaca Buka (§95), dan dua kata untuk satu
 * keadaan UNKNOWN (R-006).
 */
class TrustGrammarTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    /**
     * Glyph "resmi" hanya untuk keterangan bersumber OFFICIAL (R-009; parkir Tugas 4 W1
     * "ditegakkan di tugas pemakai resmi"). Satu-satunya pemakainya baris OFFICIAL di
     * LabelSumber; registri ikon dan pameran primitif (hanya local) dikecualikan.
     */
    public function test_the_official_seal_marks_only_official_sources(): void
    {
        $label = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/LabelSumber.tsx')));
        $barisResmi = array_values(array_filter(explode("\n", $label), fn (string $baris) => str_contains($baris, "'resmi'")));

        $this->assertCount(1, $barisResmi, 'Glyph resmi harus tepat satu kali di LabelSumber.');
        $this->assertStringStartsWith('    OFFICIAL: {', $barisResmi[0], 'Glyph resmi hanya boleh milik baris OFFICIAL.');

        $pelanggar = [];
        $kecuali = ['components/trails/LabelSumber.tsx', 'components/ui/Icon.tsx', 'components/ui/ikon/glyphDomain.ts', 'pages/Dev/Primitif.tsx'];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $jalur = str_replace('\\', '/', $berkas->getRelativePathname());

            if (in_array($jalur, $kecuali, true) || str_starts_with($jalur, 'routes/') || str_starts_with($jalur, 'actions/') || str_starts_with($jalur, 'wayfinder/')) {
                continue;
            }

            if (preg_match('/(?:name|ikon)=(?:"resmi"|\{\s*\'resmi\'\s*\})|ikon:\s*\'resmi\'/', $this->tanpaKomentarJs($berkas->getContents()))) {
                $pelanggar[] = $jalur;
            }
        }

        $this->assertSame([], $pelanggar, 'Glyph resmi dipakai di luar LabelSumber: '.implode(', ', $pelanggar));
    }

    /**
     * Jenis sumber dibaca dari data_sources, bukan dari nama tabelnya. Catatan yang disalin
     * admin tetap "Terverifikasi admin", dan catatan tanpa sumber data tercatat tidak diberi
     * jenis sama sekali.
     */
    public function test_the_source_type_comes_from_the_data_source_not_the_table(): void
    {
        $resmi = $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::OFFICIAL);
        $salinan = $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::ADMIN_VERIFIED);
        $tanpaSumber = $this->jalurDenganStatus(OfficialStatusValue::OPEN, null);

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get());

        $this->assertSame(['jenis' => 'OFFICIAL', 'label' => 'Resmi', 'nama' => 'Balai Uji'], $ringkas[$resmi->id]['sumber']);
        $this->assertSame('ADMIN_VERIFIED', $ringkas[$salinan->id]['sumber']['jenis']);
        $this->assertSame('Terverifikasi admin', $ringkas[$salinan->id]['sumber']['label']);
        $this->assertNull($ringkas[$tanpaSumber->id]['sumber']['jenis']);
        $this->assertNull($ringkas[$tanpaSumber->id]['sumber']['label']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $salinan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('statusResmi.jenisSumber', 'ADMIN_VERIFIED'));
    }

    /**
     * §42: pembatasan turun dari gunung ke jalur. Jalur yang tercatat Buka di gunung yang
     * ditutup terbaca Tutup, dan yang dikutip adalah catatan gunungnya.
     */
    public function test_a_mountain_closure_is_quoted_from_the_mountain_record(): void
    {
        $jalur = $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::OFFICIAL);

        OfficialStatus::factory()->create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $jalur->mountain_id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::CLOSED->value,
            'source' => 'Balai Gunung',
            'data_source_id' => DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value])->id,
        ]);

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get())[$jalur->id];

        $this->assertSame('CLOSED', $ringkas['status']);
        $this->assertSame('Tutup', $ringkas['label']);
        $this->assertSame('Gunung', $ringkas['cakupan']);
        $this->assertSame('Balai Gunung', $ringkas['sumber']['nama']);
    }

    /**
     * §95, BR-07: tanpa catatan yang berlaku statusnya UNKNOWN, tidak pernah Buka, dan tanpa
     * sumber yang dapat dikutip.
     */
    public function test_no_current_record_reads_unknown_not_open(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();

        $ringkas = app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get())[$jalur->id];

        $this->assertNull($ringkas['status']);
        $this->assertSame('Belum diketahui', $ringkas['label']);
        $this->assertNull($ringkas['sumber']);
        $this->assertNull($ringkas['waktu']);
    }

    /**
     * Dua kueri untuk berapa pun jalurnya (CLAUDE.md bagian 6): daftar Jelajah, halaman
     * gunung, dan banding memanggilnya untuk satu halaman hasil sekaligus.
     */
    public function test_the_summary_costs_the_same_for_two_or_twelve_trails(): void
    {
        foreach (range(1, 2) as $i) {
            $this->jalurDenganStatus(OfficialStatusValue::OPEN, SourceType::OFFICIAL);
        }

        $sedikit = $this->hitungKueri(fn () => app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get()));

        foreach (range(1, 10) as $i) {
            $this->jalurDenganStatus(OfficialStatusValue::RESTRICTED, SourceType::ADMIN_VERIFIED);
        }

        $banyak = $this->hitungKueri(fn () => app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->get()));

        $this->assertSame($sedikit, $banyak, "Ringkasan status tumbuh: {$sedikit} lalu {$banyak} kueri.");
    }

    /**
     * DK-32: empat bentuk untuk empat keadaan, selalu bersama kata; forced-colors menimpa
     * warna, bukan bentuk.
     */
    public function test_the_access_status_is_carried_by_shape_and_word(): void
    {
        $status = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/StatusAkses.tsx')));

        $this->assertStringContainsString("{nilai === 'OPEN' ? <circle", $status);
        $this->assertStringContainsString("{nilai === 'RESTRICTED' ? <rect x=\"4\" y=\"4\" width=\"8\" height=\"8\" transform=\"rotate(45 8 8)\"", $status);
        $this->assertStringContainsString("{nilai === 'CLOSED' ? <rect", $status);
        $this->assertStringContainsString('{nilai === null ? <circle cx="8" cy="8" r="5" fill="none" strokeWidth="2" strokeDasharray="2.5 2"', $status);
        $this->assertStringContainsString('<svg viewBox="0 0 16 16" aria-hidden="true"', $status);
    }

    /**
     * Label kecocokan selalu ikon dan kata, untuk ketiga label dan keadaan UNKNOWN, dan
     * tidak pernah menggambar angka (R-004, R-005, spec v3 bagian 7).
     */
    public function test_the_fit_label_pairs_an_icon_with_a_word_and_never_a_number(): void
    {
        $label = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/LabelKecocokan.tsx')));

        foreach (["COCOK: { nada: 'cocok', ikon: 'check' }", "PERLU_PERSIAPAN: { nada: 'perlu', ikon: 'warning' }", "KURANG_COCOK: { nada: 'kurang', ikon: 'x' }", "BELUM: { nada: 'belum', ikon: 'question' }"] as $rupa) {
            $this->assertStringContainsString($rupa, $label);
        }

        $this->assertStringContainsString("const r = rupa[nilai ?? 'BELUM'];", $label);
        $this->assertDoesNotMatchRegularExpression('/%|toFixed|skor|score/', $label);
    }

    /**
     * R-006, M-5 W1: keadaan UNKNOWN punya satu kata dari satu sumber, RouteFitLabel. Tidak
     * ada halaman atau komponen yang menuliskannya sendiri; pameran primitif (hanya local)
     * dikecualikan karena isinya contoh.
     */
    public function test_the_unrated_word_is_never_typed_in_the_client(): void
    {
        $pelanggar = [];

        foreach ($this->berkasTsx() as $berkas) {
            $jalur = str_replace('\\', '/', $berkas->getRelativePathname());

            if ($jalur !== 'pages/Dev/Primitif.tsx' && str_contains($this->tanpaKomentarJs($berkas->getContents()), 'Belum dinilai')) {
                $pelanggar[] = $jalur;
            }
        }

        $this->assertSame([], $pelanggar, '"Belum dinilai" ditulis di klien: '.implode(', ', $pelanggar));
    }

    private function jalurDenganStatus(OfficialStatusValue $nilai, ?SourceType $jenis): Trail
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();

        OfficialStatus::factory()->create([
            'statusable_type' => $jalur->getMorphClass(),
            'statusable_id' => $jalur->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => $nilai->value,
            'source' => 'Balai Uji',
            'data_source_id' => $jenis === null ? null : DataSource::factory()->create(['source_type' => $jenis->value])->id,
        ]);

        // Rincian jalur butuh jalur terbit; gerbang publikasi dilalui lewat model, bukan kolom.
        $jalur->forceFill(['data_source_id' => DataSource::factory()->create()->id])->saveQuietly();
        $jalur->checkpoints()->create(['sequence' => 1, 'name' => 'Pos uji '.$jalur->id]);
        $jalur->refresh()->update(['is_published' => true]);

        return $jalur;
    }

    private function hitungKueri(callable $kerja): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $kerja();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
```

`tests/Feature/AccessibilityTest.php`:

1. Ganti:

```php
     * dua kali.
```

dengan:

```php
     * dua kali.
     *
     * Diperbarui W3: FitBadge dan StatusBadge dipindah ke components/trails dan ditulis
     * ulang (LabelKecocokan, StatusAkses). Ikon lencana kini Icon tanpa label, yang
     * aria-hidden dengan sendirinya; status dibawa bentuk (DK-32) dan kata.
```

2. Ganti:

```php
        $lencana = File::get(resource_path('js/components/ui/FitBadge.tsx'));
        $this->assertMatchesRegularExpression('/<span aria-hidden="true">\{gaya\[1\]\}<\/span>\s*\{text\}/', $lencana, 'FitBadge menyampaikan kecocokan hanya lewat warna.');

        $status = File::get(resource_path('js/components/ui/StatusBadge.tsx'));
        $this->assertStringContainsString('<span className="sr-only">Status resmi:</span>', $status);
        $this->assertStringContainsString("{text ?? 'Belum diketahui'}", $status, 'Status tanpa label harus disebut belum diketahui, bukan kosong.');
```

dengan:

```php
        $lencana = File::get(resource_path('js/components/trails/LabelKecocokan.tsx'));
        $this->assertStringContainsString('<Lencana nada={r.nada} bentuk="pil" ikon={r.ikon} className={className}>', $lencana);
        $this->assertMatchesRegularExpression('/>\s*\{label\}\s*<\/Lencana>/', $lencana, 'LabelKecocokan menyampaikan kecocokan hanya lewat warna.');

        $status = File::get(resource_path('js/components/trails/StatusAkses.tsx'));
        $this->assertStringContainsString('<span className="sr-only">Status resmi:</span>', $status);
        $this->assertStringContainsString("{label ?? 'Belum diketahui'}", $status, 'Status tanpa label harus disebut belum diketahui, bukan kosong.');
```

`tests/Feature/AmbientFitTest.php`:

1. Ganti:

```php
     * menentukan warna dan simbolnya. Ketiadaan penilaian ditulis "Belum dinilai" (R-006,
```

dengan:

```php
     * menentukan warna dan simbolnya (LabelKecocokan sejak W3, dahulu FitBadge). Ketiadaan
     * penilaian ditulis "Belum dinilai" (R-006,
```

2. Ganti:

```php
        $this->assertStringContainsString("export type LabelFit = {$tipe};", File::get(resource_path('js/components/ui/FitBadge.tsx')));
```

dengan:

```php
        $this->assertStringContainsString("export type LabelFit = {$tipe};", File::get(resource_path('js/components/trails/LabelKecocokan.tsx')));
```

`tests/Feature/CommunityPhotoTest.php`:

Ganti:

```php
        $this->assertStringContainsString('bg-community-50', file_get_contents(resource_path('js/components/trails/rinci/LabelSumber.tsx')));
```

dengan:

```php
        // Sejak W3 label sumber tinggal di components/trails/LabelSumber.tsx dan memakai
        // Lencana bernada komunitas bergaris putus-putus (spec v3 bagian 7).
        $this->assertStringContainsString("COMMUNITY: { label: 'Komunitas', ikon: 'user', nada: 'komunitas', garis: 'putus' }", file_get_contents(resource_path('js/components/trails/LabelSumber.tsx')));
        $this->assertStringContainsString("komunitas: { isi: 'bg-community-50 text-community-900'", file_get_contents(resource_path('js/components/ui/Lencana.tsx')));
```

`tests/Feature/TrailDetailPageTest.php`:

Ganti:

```php
        $kondisi = File::get(resource_path('js/components/trails/rinci/Kondisi.tsx'));
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', $kondisi);
        $this->assertStringContainsString('<LabelResmi />', $kondisi);
```

dengan:

```php
        // Sejak W3 label sumbernya mengikuti jenis sumber catatan (R-009), bukan "Resmi"
        // untuk setiap catatan di tabel status resmi.
        $this->assertArrayHasKey('jenisSumber', $props['statusResmi']);

        $kondisi = File::get(resource_path('js/components/trails/rinci/Kondisi.tsx'));
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', $kondisi);
        $this->assertStringContainsString('<LabelSumber jenis={status.jenisSumber} />', $kondisi);
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrustGrammar|Accessibility|AmbientFit|CommunityPhoto|TrailDetailPage|RouteComparison|ComparisonAsCost'`
Expected: FAIL: 66 test, 54 lulus, 2 gagal, 10 error. Antara lain `TrailDetailPageTest::test_each_condition_names_its_source_and_time`, `TrustGrammarTest::test_the_unrated_word_is_never_typed_in_the_client`, `AccessibilityTest::test_status_is_never_conveyed_by_colour_alone`.

- [ ] **Step 3: Tulis implementasinya.** Buat kelas dan komponen baru, alihkan pemanggil, lalu hapus komponen lama.

**Server**

Buat `app/Services/Trails/StatusJalurRingkas.php`:

```php
<?php

namespace App\Services\Trails;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Services\DataFreshnessService;
use App\Support\Timezone;
use Illuminate\Support\Collection;

/**
 * Status akses satu baris jalur untuk daftar (Jelajah, halaman gunung, banding), beserta
 * dari mana status itu datang: jenis sumbernya, nama sumbernya, dan waktunya (spec v3
 * bagian 7; PRD §60, §92).
 *
 * Dua kueri untuk sekumpulan jalur, bukan dua per jalur: satu untuk catatan status jalur,
 * satu untuk catatan status gunungnya, keduanya beserta jenis sumber datanya. Kaskade §42
 * diterapkan di memori dengan aturan yang sama dengan OfficialStatusService: pembatasan
 * turun dari gunung ke jalur, kelonggaran tidak pernah naik. Catatan yang dipakai adalah
 * catatan yang benar-benar menentukan status itu, jadi jalur yang tertutup karena gunungnya
 * menyebut sumber gunungnya.
 *
 * Jenis sumber dibaca dari data_sources.source_type, tidak diasumsikan dari nama tabelnya:
 * catatan di official_statuses yang disalin admin tanpa bukti resmi bukan "Resmi" (R-009).
 * Catatan tanpa sumber data tercatat hanya menyebut nama sumbernya, tanpa jenis.
 */
final class StatusJalurRingkas
{
    public function __construct(private readonly DataFreshnessService $kesegaran) {}

    /**
     * @param  Collection<int, Trail>  $trails  dengan relasi mountain termuat
     * @return array<int, array{status: string|null, label: string, cakupan: string|null, sumber: array{jenis: string|null, label: string|null, nama: string|null}|null, waktu: string|null, kesegaran: string|null}>
     */
    public function untuk(Collection $trails): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        $catatanJalur = $this->terbaru((new Trail)->getMorphClass(), $trails->pluck('id'));
        $catatanGunung = $this->terbaru((new Mountain)->getMorphClass(), $trails->pluck('mountain_id')->filter()->unique()->values());

        $hasil = [];

        foreach ($trails as $trail) {
            $jalur = $catatanJalur[$trail->id] ?? null;
            $gunung = $catatanGunung[$trail->mountain_id] ?? null;
            $penentu = $this->penentu($jalur, $gunung);

            $hasil[$trail->id] = $this->bentuk($penentu, Timezone::forTrail($trail));
        }

        return $hasil;
    }

    /**
     * Satu catatan (atau ketiadaannya) menjadi bentuk yang dikirim ke halaman.
     *
     * @return array{status: string|null, label: string, cakupan: string|null, sumber: array{jenis: string|null, label: string|null, nama: string|null}|null, waktu: string|null, kesegaran: string|null}
     */
    public function bentuk(?OfficialStatus $catatan, string $zona): array
    {
        $nilai = $catatan?->status ?? OfficialStatusValue::UNKNOWN;
        $jenis = $catatan?->dataSource?->source_type;
        $waktu = $catatan?->verified_at ?? $catatan?->published_at ?? $catatan?->effective_at;

        return [
            'status' => $nilai === OfficialStatusValue::UNKNOWN ? null : $nilai->value,
            'label' => $nilai->label(),
            'cakupan' => $catatan?->scope?->label(),
            'sumber' => $catatan === null ? null : [
                'jenis' => $jenis?->value,
                'label' => $jenis?->label(),
                'nama' => $catatan->source,
            ],
            'waktu' => $waktu ? $waktu->copy()->setTimezone($zona)->translatedFormat('j M') : null,
            'kesegaran' => $catatan === null ? null : $this->kesegaran->keadaan($catatan),
        ];
    }

    /**
     * Catatan yang menentukan status efektif jalur menurut kaskade §42.
     */
    private function penentu(?OfficialStatus $jalur, ?OfficialStatus $gunung): ?OfficialStatus
    {
        $nilaiGunung = $gunung?->status;
        $nilaiJalur = $jalur?->status ?? OfficialStatusValue::UNKNOWN;

        if ($nilaiGunung === OfficialStatusValue::CLOSED) {
            return $gunung;
        }

        if ($nilaiGunung === OfficialStatusValue::RESTRICTED && $nilaiJalur !== OfficialStatusValue::CLOSED) {
            return $gunung;
        }

        return $jalur;
    }

    /**
     * Catatan terbaru yang sedang berlaku per entitas, beserta jenis sumbernya.
     *
     * @param  Collection<int, int>  $ids
     * @return array<int, OfficialStatus>
     */
    private function terbaru(string $morph, Collection $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $baris = OfficialStatus::query()
            ->where('statusable_type', $morph)
            ->whereIn('statusable_id', $ids)
            ->currentlyEffective()
            ->with('dataSource:id,source_type')
            ->orderByDesc('effective_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $terbaru = [];

        foreach ($baris as $satu) {
            // Urutan sudah menurun, jadi kemunculan pertama adalah yang terbaru.
            $terbaru[$satu->statusable_id] ??= $satu;
        }

        return $terbaru;
    }
}
```

`app/Http/Controllers/Trails/RouteComparisonController.php`:

1. Ganti:

```php
use App\Http\Controllers\Controller;
```

dengan:

```php
use App\Enums\RouteFitLabel;
use App\Http\Controllers\Controller;
```

2. Ganti:

```php
            'pesan' => $pesan,
```

dengan:

```php
            'pesan' => $pesan,
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
```

`app/Services/DataFreshnessService.php`:

Ganti:

```php
    private function dasar(): Builder
```

dengan:

```php
    /**
     * Keadaan satu catatan status untuk pendaki (spec v3 bagian 7): "Terkini" bila
     * diverifikasi dalam masa tinjau dan tidak segera kedaluwarsa, "Perlu ditinjau" bila
     * belum pernah diverifikasi, terlalu lama tidak diverifikasi, atau segera kedaluwarsa,
     * dan "Kedaluwarsa" bila masa berlakunya sudah lewat. Ambangnya sama dengan antrean
     * tinjau admin di atas, jadi pendaki dan admin tidak pernah membaca dua penilaian.
     */
    public function keadaan(OfficialStatus $status): string
    {
        if ($status->expires_at !== null && $status->expires_at->isPast()) {
            return 'Kedaluwarsa';
        }

        $basi = $status->verified_at === null || $status->verified_at->lt(now()->subDays($this->hariTinjau()));
        $segera = $status->expires_at !== null && $status->expires_at->lte(now()->addDays($this->hariPeringatan()));

        return $basi || $segera ? 'Perlu ditinjau' : 'Terkini';
    }

    private function dasar(): Builder
```

`app/Services/OfficialStatusService.php`:

Ganti:

```php
            'source' => $record?->source,
```

dengan:

```php
            'source' => $record?->source,
            // Jenis sumber dari data_sources (R-009): halaman menyebut "Resmi" hanya bila
            // catatannya memang bersumber OFFICIAL, bukan karena tersimpan di tabel ini.
            'source_type' => $record?->dataSource?->source_type?->value,
```

`app/Services/Trails/TrailDetailPage.php`:

1. Ganti:

```php
            'source' => $snapshot['source'] ?? 'Belum tercatat',
```

dengan:

```php
            'source' => $snapshot['source'] ?? 'Belum tercatat',
            'jenisSumber' => $snapshot['source_type'] ?? null,
```

2. Ganti:

```php
            'source' => $permit->source ?? 'Belum dicatat',
```

dengan:

```php
            'source' => $permit->source ?? 'Belum dicatat',
            'jenisSumber' => $permit->dataSource?->source_type?->value,
```

**Antarmuka**

Buat `resources/js/components/trails/Kesegaran.tsx`:

```tsx
import { Lencana, type NadaLencana } from '@/components/ui/Lencana';
import { kelas } from '@/lib/kelas';

const nadaKeadaan: Record<string, NadaLencana> = {
    Terkini: 'merek',
    'Perlu ditinjau': 'peringatan',
    Kedaluwarsa: 'bahaya',
};

interface KesegaranProps {
    /** Waktu yang sudah diformat server dalam zona gunung beserta penandanya ("18 Sep 2026 14.00 WIB"). */
    waktu: string | null;
    awalan?: string;
    /** Keadaan kesegaran dari server (DataFreshnessService::keadaan atau FreshnessState). */
    keadaan?: string | null;
    className?: string;
}

/**
 * Kesegaran data (PRD §93, spec v3 bagian 7): kapan sebuah keterangan terakhir diperiksa,
 * dan apakah ia masih terkini. Klien tidak pernah mengonversi waktu sendiri; waktunya datang
 * sudah diformat dalam zona gunungnya. Ketiadaan waktu disebut, tidak dikosongkan.
 * Dipindah dari components/ui/Freshness.tsx dan ditulis ulang W3.
 */
export function Kesegaran({ waktu, awalan = 'Diperbarui', keadaan, className }: KesegaranProps) {
    return (
        <p className={kelas('flex flex-wrap items-center gap-x-2 gap-y-1 text-meta text-muted', className)}>
            <span>{waktu ? `${awalan} ${waktu}` : 'Waktu pembaruan tidak tersedia'}</span>
            {keadaan ? <Lencana nada={nadaKeadaan[keadaan] ?? 'netral'}>{keadaan}</Lencana> : null}
        </p>
    );
}
```

Buat `resources/js/components/trails/LabelKecocokan.tsx`:

```tsx
import type { NamaIkon } from '@/components/ui/Icon';
import { Lencana, type NadaLencana } from '@/components/ui/Lencana';

export type LabelFit = 'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | null;

const rupa: Record<'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | 'BELUM', { nada: NadaLencana; ikon: NamaIkon }> = {
    COCOK: { nada: 'cocok', ikon: 'check' },
    PERLU_PERSIAPAN: { nada: 'perlu', ikon: 'warning' },
    KURANG_COCOK: { nada: 'kurang', ikon: 'x' },
    BELUM: { nada: 'belum', ikon: 'question' },
};

interface LabelKecocokanProps {
    /** Nilai enum RouteFitLabel; null berarti belum dinilai (UNKNOWN), bukan label keempat. */
    nilai: LabelFit;
    /** Kata dari server (RouteFitLabel::textFor): satu sumber untuk ketiga label dan "Belum dinilai". */
    label: string;
    className?: string;
}

/**
 * Label kecocokan publik (R-004): tiga label, ditambah keadaan UNKNOWN yang tidak pernah
 * menjadi label keempat (R-006). Selalu ikon dan kata (spec v3 bagian 7), karena warna
 * saja tidak terbaca di forced-colors maupun oleh pembaca layar; ikonnya disembunyikan dari
 * pembaca layar supaya kata tidak dibacakan dua kali. Tidak pernah angka (R-005, BR-09).
 * Dipindah dari components/ui/FitBadge.tsx dan ditulis ulang W3.
 */
export function LabelKecocokan({ nilai, label, className }: LabelKecocokanProps) {
    const r = rupa[nilai ?? 'BELUM'];

    return (
        <Lencana nada={r.nada} bentuk="pil" ikon={r.ikon} className={className}>
            {label}
        </Lencana>
    );
}
```

Buat `resources/js/components/trails/LabelSumber.tsx`:

```tsx
import type { NamaIkon } from '@/components/ui/Icon';
import { Lencana, type NadaLencana } from '@/components/ui/Lencana';

/** Jenis sumber (App\Enums\SourceType), dalam urutan otoritas R-009. */
export type JenisSumber = 'OFFICIAL' | 'ADMIN_VERIFIED' | 'ACCREDITED_EXPERT' | 'COMMUNITY' | 'AI_INTERPRETATION';

/** Sumber satu keterangan sebagaimana dikirim server: jenis dari data_sources, bukan tebakan. */
export interface SumberKeterangan {
    jenis: JenisSumber | null;
    label: string | null;
    nama: string | null;
}

/*
 * Glyph "resmi" hanya ada di baris OFFICIAL (R-009, ruling W1 Tugas 4): cap itu adalah
 * klaim otoritas, dan keterangan yang disalin admin, disumbang ahli, atau dilaporkan
 * komunitas tidak boleh meminjamnya. TrustGrammarTest menolak glyph ini di tempat lain.
 */
const rupa: Record<JenisSumber, { label: string; ikon: NamaIkon | null; nada: NadaLencana; garis: 'padat' | 'putus' }> = {
    OFFICIAL: { label: 'Resmi', ikon: 'resmi', nada: 'merek', garis: 'padat' },
    ADMIN_VERIFIED: { label: 'Terverifikasi admin', ikon: 'check', nada: 'netral', garis: 'padat' },
    ACCREDITED_EXPERT: { label: 'Ahli bersertifikat', ikon: 'user', nada: 'netral', garis: 'padat' },
    COMMUNITY: { label: 'Komunitas', ikon: 'user', nada: 'komunitas', garis: 'putus' },
    AI_INTERPRETATION: { label: 'Interpretasi sistem', ikon: null, nada: 'netral', garis: 'putus' },
};

/**
 * Label otoritas sumber (PRD §92, R-009, R-010): keterangan resmi dan masukan komunitas
 * terbedakan dengan kata, ikon, dan garis (padat untuk resmi, putus-putus untuk komunitas),
 * tidak dengan warna saja. Pasangan warnanya dihitung ColourContrastTest. Tanpa jenis
 * sumber yang tercatat tidak ada label yang digambar: menyebut "Resmi" tanpa dasar adalah
 * klaim yang tidak dimiliki sistem.
 */
export function LabelSumber({ jenis, className }: { jenis: JenisSumber | null; className?: string }) {
    if (jenis === null) {
        return null;
    }

    const r = rupa[jenis];

    return (
        <Lencana nada={r.nada} garis={r.garis} ikon={r.ikon ?? undefined} className={className}>
            {r.label}
        </Lencana>
    );
}

/** Laporan pendaki: selalu Komunitas, lolos moderasi atau tidak tampil sama sekali (§57). */
export function LabelKomunitas() {
    return <LabelSumber jenis="COMMUNITY" />;
}
```

Buat `resources/js/components/trails/StatusAkses.tsx`:

```tsx
import { kelas } from '@/lib/kelas';

export type StatusResmi = 'OPEN' | 'RESTRICTED' | 'CLOSED' | null;

/**
 * Bentuk per status (DK-32): Buka lingkaran, Dibatasi belah ketupat, Tutup persegi, Belum
 * diketahui lingkaran putus-putus. Bentuk yang membawa arti, bukan warnanya: mode
 * forced-colors menimpa fill dan stroke, dan bentuknya tetap terbedakan.
 */
function Bentuk({ nilai }: { nilai: StatusResmi }) {
    return (
        <svg viewBox="0 0 16 16" aria-hidden="true" className="size-4 shrink-0">
            {nilai === 'OPEN' ? <circle cx="8" cy="8" r="5" className="fill-status-buka" /> : null}
            {nilai === 'RESTRICTED' ? <rect x="4" y="4" width="8" height="8" transform="rotate(45 8 8)" className="fill-status-dibatasi" /> : null}
            {nilai === 'CLOSED' ? <rect x="3.5" y="3.5" width="9" height="9" className="fill-status-tutup" /> : null}
            {nilai === null ? <circle cx="8" cy="8" r="5" fill="none" strokeWidth="2" strokeDasharray="2.5 2" className="stroke-status-belum" /> : null}
        </svg>
    );
}

interface StatusAksesProps {
    nilai: StatusResmi;
    /** Kata dari server (OfficialStatusValue::label). */
    label?: string | null;
    /** Cakupan catatan penentu, misalnya "Gunung" ketika status turun dari gunungnya (§42). */
    cakupan?: string | null;
    className?: string;
}

/**
 * Status akses resmi sebagai bentuk dan kata (DK-32, WCAG 1.4.1). OPEN bukan jaminan
 * keselamatan (R-012): komponen ini hanya menyebut keadaan operasionalnya, dan layar utama
 * yang memakainya menambahkan kalimat R-012. Status kosong adalah UNKNOWN, tidak pernah
 * Buka (§95). Dipindah dari components/ui/StatusBadge.tsx dan ditulis ulang W3.
 */
export function StatusAkses({ nilai, label, cakupan, className }: StatusAksesProps) {
    return (
        <span data-status-akses={nilai ?? 'UNKNOWN'} className={kelas('inline-flex max-w-full items-center gap-1.5 text-kecil font-bold text-primary', className)}>
            <Bentuk nilai={nilai} />
            <span className="sr-only">Status resmi:</span>
            <span className="min-w-0 break-words">
                {label ?? 'Belum diketahui'}
                {cakupan ? <span className="font-semibold text-secondary"> (cakupan {cakupan.toLowerCase()})</span> : null}
            </span>
        </span>
    );
}
```

`resources/js/components/community/types.ts`:

Ganti:

```ts
import type { StatusResmi } from '@/components/ui/StatusBadge';
```

dengan:

```ts
import type { StatusResmi } from '@/components/trails/StatusAkses';
```

`resources/js/components/discovery/KartuHasil.tsx`:

1. Ganti:

```tsx
import { FitBadge } from '@/components/ui/FitBadge';
```

dengan:

```tsx
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
```

2. Ganti:

```tsx
                    <FitBadge label={hasil.label} text={hasil.labelText} />
```

dengan:

```tsx
                    <LabelKecocokan nilai={hasil.label} label={hasil.labelText} />
```

`resources/js/components/discovery/types.ts`:

Ganti:

```ts
import type { LabelFit } from '@/components/ui/FitBadge';
```

dengan:

```ts
import type { LabelFit } from '@/components/trails/LabelKecocokan';
```

`resources/js/components/trails/FitLine.tsx`:

1. Ganti:

```tsx
import { FitBadge } from '@/components/ui/FitBadge';
```

dengan:

```tsx
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
```

2. Ganti:

```tsx
                <FitBadge label={fit.label} text={fit.labelText} />
```

dengan:

```tsx
                <LabelKecocokan nilai={fit.label} label={fit.labelText} />
```

`resources/js/components/trails/banding/TabelBanding.tsx`:

1. Ganti:

```tsx
import { StatusBadge, type StatusResmi } from '@/components/ui/StatusBadge';
```

dengan:

```tsx
import { StatusAkses, type StatusResmi } from '@/components/trails/StatusAkses';
```

2. Ganti:

```tsx
    sembunyikanSama: boolean;
```

dengan:

```tsx
    sembunyikanSama: boolean;
    /** Kata UNKNOWN dari server (RouteFitLabel::UNRATED_TEXT), untuk pembaca tanpa profil. */
    kataBelumDinilai: string;
```

3. Ganti:

```tsx
export function TabelBanding({ jalur, baris, beda, sembunyikanSama }: TabelBandingProps) {
```

dengan:

```tsx
export function TabelBanding({ jalur, baris, beda, sembunyikanSama, kataBelumDinilai }: TabelBandingProps) {
```

4. Ganti:

```tsx
                                    {j.fit ? <FitLine fit={j.fit} trailId={j.id} /> : <span className="text-sm text-muted">Belum dinilai</span>}
```

dengan:

```tsx
                                    {j.fit ? <FitLine fit={j.fit} trailId={j.id} /> : <span className="text-sm text-muted">{kataBelumDinilai}</span>}
```

5. Ganti:

```tsx
                                    <StatusBadge status={j.status.status} text={j.status.label} />
```

dengan:

```tsx
                                    <StatusAkses nilai={j.status.status} label={j.status.label} />
```

`resources/js/components/trails/rinci/Kondisi.tsx`:

1. Ganti:

```tsx
import { Card } from '@/components/ui/Card';
import { Freshness } from '@/components/ui/Freshness';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { LabelResmi } from './LabelSumber';
```

dengan:

```tsx
import { Kesegaran } from '@/components/trails/Kesegaran';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Card } from '@/components/ui/Card';
```

2. Ganti:

```tsx
                <StatusBadge status={status.status} text={status.statusLabel} scope={status.scope} />
                <span className="inline-flex flex-wrap items-center gap-2 text-sm text-secondary">
                    <LabelResmi />
```

dengan:

```tsx
                <StatusAkses nilai={status.status} label={status.statusLabel} cakupan={status.scope} />
                <span className="inline-flex flex-wrap items-center gap-2 text-sm text-secondary">
                    <LabelSumber jenis={status.jenisSumber} />
```

3. Ganti:

```tsx
                <Freshness rendered={status.diverifikasi} prefix="Diverifikasi" />
```

dengan:

```tsx
                <Kesegaran waktu={status.diverifikasi} awalan="Diverifikasi" />
```

4. Ganti:

```tsx
                <Freshness rendered={izin.diverifikasi} prefix="Aturan diverifikasi" />
            </div>
            <p className="mt-1 text-xs text-muted">
                <LabelResmi /> Sumber: {izin.source}. Aturan perizinan dapat berubah; periksa kembali ke penyelenggara sebelum berangkat.
```

dengan:

```tsx
                <Kesegaran waktu={izin.diverifikasi} awalan="Aturan diverifikasi" />
            </div>
            <p className="mt-1 text-xs text-muted">
                <LabelSumber jenis={izin.jenisSumber} /> Sumber: {izin.source}. Aturan perizinan dapat berubah; periksa kembali ke penyelenggara sebelum berangkat.
```

5. Ganti:

```tsx
                    <Freshness rendered={cuaca.diperbarui} stateLabel={cuaca.freshnessLabel} />
```

dengan:

```tsx
                    <Kesegaran waktu={cuaca.diperbarui} keadaan={cuaca.freshnessLabel} />
```

`resources/js/components/trails/rinci/LaporanKomunitas.tsx`:

Ganti:

```tsx
import { LabelKomunitas } from './LabelSumber';
```

dengan:

```tsx
import { LabelKomunitas } from '@/components/trails/LabelSumber';
```

`resources/js/components/trails/rinci/types.ts`:

1. Ganti:

```ts
import type { Penanda } from '@/components/trails/types';
import type { FaktorBar } from '@/components/ui/FactorBars';
import type { LabelFit } from '@/components/ui/FitBadge';
import type { StatusResmi } from '@/components/ui/StatusBadge';
```

dengan:

```ts
import type { JenisSumber } from '@/components/trails/LabelSumber';
import type { Penanda } from '@/components/trails/types';
import type { FaktorBar } from '@/components/ui/FactorBars';
import type { LabelFit } from '@/components/trails/LabelKecocokan';
import type { StatusResmi } from '@/components/trails/StatusAkses';
```

2. Ganti:

```ts
    reason: string | null;
```

dengan:

```ts
    /** Jenis sumber catatan penentu (SourceType); null bila tidak tercatat. */
    jenisSumber: JenisSumber | null;
    reason: string | null;
```

3. Ganti:

```ts
    source: string;
}
```

dengan:

```ts
    source: string;
    jenisSumber: JenisSumber | null;
}
```

`resources/js/components/trips/FitTrip.tsx`:

1. Ganti:

```tsx
import { FitBadge } from '@/components/ui/FitBadge';
```

dengan:

```tsx
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
```

2. Ganti:

```tsx
                <FitBadge label={fit.label} text={fit.labelText} />
```

dengan:

```tsx
                <LabelKecocokan nilai={fit.label} label={fit.labelText} />
```

`resources/js/components/ui/ikon/phosphor.ts`:

1. Ganti:

```ts
    | 'plus'
```

dengan:

```ts
    | 'plus'
    | 'question'
    | 'user'
    | 'warning'
```

2. Ganti:

```ts
    'x': {
```

dengan:

```ts
    'question': {
        regular: 'M140,180a12,12,0,1,1-12-12A12,12,0,0,1,140,180ZM128,72c-22.06,0-40,16.15-40,36v4a8,8,0,0,0,16,0v-4c0-11,10.77-20,24-20s24,9,24,20-10.77,20-24,20a8,8,0,0,0-8,8v8a8,8,0,0,0,16,0v-.72c18.24-3.35,32-17.9,32-35.28C168,88.15,150.06,72,128,72Zm104,56A104,104,0,1,1,128,24,104.11,104.11,0,0,1,232,128Zm-16,0a88,88,0,1,0-88,88A88.1,88.1,0,0,0,216,128Z',
        bold: 'M144,180a16,16,0,1,1-16-16A16,16,0,0,1,144,180Zm92-52A108,108,0,1,1,128,20,108.12,108.12,0,0,1,236,128Zm-24,0a84,84,0,1,0-84,84A84.09,84.09,0,0,0,212,128ZM128,64c-24.26,0-44,17.94-44,40v4a12,12,0,0,0,24,0v-4c0-8.82,9-16,20-16s20,7.18,20,16-9,16-20,16a12,12,0,0,0-12,12v8a12,12,0,0,0,23.73,2.56C158.31,137.88,172,122.37,172,104,172,81.94,152.26,64,128,64Z',
    },
    'user': {
        regular: 'M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z',
        bold: 'M234.38,210a123.36,123.36,0,0,0-60.78-53.23,76,76,0,1,0-91.2,0A123.36,123.36,0,0,0,21.62,210a12,12,0,1,0,20.77,12c18.12-31.32,50.12-50,85.61-50s67.49,18.69,85.61,50a12,12,0,0,0,20.77-12ZM76,96a52,52,0,1,1,52,52A52.06,52.06,0,0,1,76,96Z',
    },
    'warning': {
        regular: 'M236.8,188.09,149.35,36.22h0a24.76,24.76,0,0,0-42.7,0L19.2,188.09a23.51,23.51,0,0,0,0,23.72A24.35,24.35,0,0,0,40.55,224h174.9a24.35,24.35,0,0,0,21.33-12.19A23.51,23.51,0,0,0,236.8,188.09ZM222.93,203.8a8.5,8.5,0,0,1-7.48,4.2H40.55a8.5,8.5,0,0,1-7.48-4.2,7.59,7.59,0,0,1,0-7.72L120.52,44.21a8.75,8.75,0,0,1,15,0l87.45,151.87A7.59,7.59,0,0,1,222.93,203.8ZM120,144V104a8,8,0,0,1,16,0v40a8,8,0,0,1-16,0Zm20,36a12,12,0,1,1-12-12A12,12,0,0,1,140,180Z',
        bold: 'M240.26,186.1,152.81,34.23h0a28.74,28.74,0,0,0-49.62,0L15.74,186.1a27.45,27.45,0,0,0,0,27.71A28.31,28.31,0,0,0,40.55,228h174.9a28.31,28.31,0,0,0,24.79-14.19A27.45,27.45,0,0,0,240.26,186.1Zm-20.8,15.7a4.46,4.46,0,0,1-4,2.2H40.55a4.46,4.46,0,0,1-4-2.2,3.56,3.56,0,0,1,0-3.73L124,46.2a4.77,4.77,0,0,1,8,0l87.44,151.87A3.56,3.56,0,0,1,219.46,201.8ZM116,136V104a12,12,0,0,1,24,0v32a12,12,0,0,1-24,0Zm28,40a16,16,0,1,1-16-16A16,16,0,0,1,144,176Z',
    },
    'x': {
```

`resources/js/pages/Admin/OfficialStatuses.tsx`:

1. Ganti:

```tsx
import { Freshness } from '@/components/ui/Freshness';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { StatusBadge, type StatusResmi } from '@/components/ui/StatusBadge';
```

dengan:

```tsx
import { Kesegaran } from '@/components/trails/Kesegaran';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { StatusAkses, type StatusResmi } from '@/components/trails/StatusAkses';
```

2. Ganti:

```tsx
/** UNKNOWN tidak punya warna operasional: StatusBadge menampilkannya netral. */
```

dengan:

```tsx
/** UNKNOWN tidak punya warna operasional: StatusAkses menggambarnya lingkaran putus-putus. */
```

3. Ganti:

```tsx
                                                    <StatusBadge status={badge(item.status)} text={item.statusText} />
```

dengan:

```tsx
                                                    <StatusAkses nilai={badge(item.status)} label={item.statusText} />
```

4. Ganti:

```tsx
                                <td className="p-4"><StatusBadge status={badge(r.status)} text={r.statusText} /></td>
```

dengan:

```tsx
                                <td className="p-4"><StatusAkses nilai={badge(r.status)} label={r.statusText} /></td>
```

5. Ganti:

```tsx
                                    <Freshness rendered={r.diverifikasi} prefix="Diverifikasi" />
```

dengan:

```tsx
                                    <Kesegaran waktu={r.diverifikasi} awalan="Diverifikasi" />
```

`resources/js/pages/Community/TrailNews.tsx`:

1. Ganti:

```tsx
import { StatusBadge } from '@/components/ui/StatusBadge';
```

dengan:

```tsx
import { StatusAkses } from '@/components/trails/StatusAkses';
```

2. Ganti:

```tsx
                <StatusBadge status={butir.status} text={butir.statusLabel} scope={butir.cakupan} />
```

dengan:

```tsx
                <StatusAkses nilai={butir.status} label={butir.statusLabel} cakupan={butir.cakupan} />
```

`resources/js/pages/Trails/Compare.tsx`:

1. Ganti:

```tsx
    beda: { fit: boolean | null; status: boolean | null };
```

dengan:

```tsx
    beda: { fit: boolean | null; status: boolean | null };
    kataBelumDinilai: string;
```

2. Ganti:

```tsx
    const { pilihan, jumlahTersimpan, pesan, jalur, baris, beda, pengumuman } = usePage<Props>().props;
```

dengan:

```tsx
    const { pilihan, jumlahTersimpan, pesan, jalur, baris, beda, kataBelumDinilai, pengumuman } = usePage<Props>().props;
```

3. Ganti:

```tsx
                        <TabelBanding jalur={jalur} baris={baris} beda={beda} sembunyikanSama={sembunyikanSama} />
```

dengan:

```tsx
                        <TabelBanding jalur={jalur} baris={baris} beda={beda} sembunyikanSama={sembunyikanSama} kataBelumDinilai={kataBelumDinilai} />
```

`resources/js/pages/Trails/Show.tsx`:

1. Ganti:

```tsx
import { FitBadge } from '@/components/ui/FitBadge';
```

dengan:

```tsx
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
```

2. Ganti:

```tsx
                                <FitBadge label={fit.label} text={fit.labelText} />
```

dengan:

```tsx
                                <LabelKecocokan nilai={fit.label} label={fit.labelText} />
```

`resources/js/pages/Trips/Readiness.tsx`:

1. Ganti:

```tsx
import { Freshness } from '@/components/ui/Freshness';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatusBadge, type StatusResmi } from '@/components/ui/StatusBadge';
```

dengan:

```tsx
import { Kesegaran } from '@/components/trails/Kesegaran';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatusAkses, type StatusResmi } from '@/components/trails/StatusAkses';
```

2. Ganti:

```tsx
                        <StatusBadge status={check.statusResmi.status} text={check.statusResmi.label} />
```

dengan:

```tsx
                        <StatusAkses nilai={check.statusResmi.status} label={check.statusResmi.label} />
```

3. Ganti:

```tsx
                            <Freshness rendered={check.cuaca.diperbarui ?? null} stateLabel={check.cuaca.kesegaran} />
```

dengan:

```tsx
                            <Kesegaran waktu={check.cuaca.diperbarui ?? null} keadaan={check.cuaca.kesegaran} />
```

Hapus berkas `resources/js/components/trails/rinci/LabelSumber.tsx`.

Hapus berkas `resources/js/components/ui/FitBadge.tsx`.

Hapus berkas `resources/js/components/ui/Freshness.tsx`.

Hapus berkas `resources/js/components/ui/StatusBadge.tsx`.

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='TrustGrammar|Accessibility|AmbientFit|CommunityPhoto|TrailDetailPage|RouteComparison|ComparisonAsCost'`
Expected: PASS: 66 test, 66 lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.630 test, 1.628 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus. Pada simulasi rencana: 9 test, 9 lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Halaman gunung

Induk W3 butir 5 (DK-44); ruling W3-R7 sampai W3-R9. `GET /mountains/{mountain:slug}` untuk pengguna yang masuk, lewat
kelas halaman `HalamanGunung`. Halamannya memuat:

- tinggi puncak dengan kalimat BR-02 ("Ketinggian hanya keterangan");
- status akses tingkat gunung dengan kalimat R-012 ("Buka di tingkat gunung tidak berarti semua jalur buka");
- daftar jalur terbit dengan label dan status;
- izin yang menautkan sistem resmi ("Memount menautkan sistem resmi dan tidak menjual izin");
- kondisi terbaru dari pendaki di bawah status resmi;
- catatan provenans.

Hero-nya visual data (profil semua jalur pada satu skala) sampai foto berlisensi W8. Kartu status gunung api menunggu
tabelnya di W8. Halaman gunung menyalakan tab Jelajah. Data contoh mendapat profil elevasi, status tingkat gunung, dan
satu laporan termoderasi supaya pratinjau punya isi.

**Files:**
- Create: `app/Http/Controllers/Trails/MountainPageController.php`, `app/Http/Resources/MountainHeaderResource.php`, `app/Services/Mountains/HalamanGunung.php`, `app/Services/Trails/SkalaProfil.php`, `resources/js/components/trails/BarisStatus.tsx`, `resources/js/components/trails/gunung/DaftarJalurGunung.tsx`, `resources/js/components/trails/gunung/KartuIzin.tsx`, `resources/js/components/trails/gunung/KartuStatusGunung.tsx`, `resources/js/components/trails/gunung/KondisiPendaki.tsx`, `resources/js/components/trails/gunung/VisualProfilGunung.tsx`, `resources/js/components/trails/gunung/types.ts`, `resources/js/pages/Mountains/Show.tsx`
- Modify: `app/Services/Trails/TrailFigures.php`, `database/seeders/DemoSeeder.php`, `resources/js/components/trails/StatusAkses.tsx`, `resources/js/components/trails/types.ts`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/layouts/NavLinks.tsx`, `routes/web.php`
- Test: `tests/Feature/MountainPageTest.php` (baru), `tests/Feature/DemoSeederTest.php`, `tests/Feature/NavigationConsistencyTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `StatusJalurRingkas::untuk()` dan `StatusAkses` (Tugas 1), `LabelKecocokan`, `TrailFitService` dan `TrailFitSummary` (sudah ada), `PermitService` dan `PermitRequirement`, `OfficialStatusService` untuk catatan tingkat gunung, `TrailConditionReport` termoderasi, `PageHeader` dengan `induk` (W2), `DemoSeeder::PENANDA`.
- Produces:
  - rute `mountains.show` (`GET mountains/{mountain:slug}`, grup `auth`) ke `App\Http\Controllers\Trails\MountainPageController` (invokable);
  - `App\Services\Mountains\HalamanGunung::props(Mountain $gunung, User $pembaca): array` dengan kunci `mengikuti`, `bolehDiikuti`, `status`, `jalur`, `jumlahMenunggu`, `profil`, `izin`, `kondisi`, `provenans`, `kataBelumDinilai`;
  - `App\Http\Resources\MountainHeaderResource`, yang dikirim controller sebagai prop `gunung` bersama `title` dan `pengumuman`;
  - `App\Services\Trails\SkalaProfil::titik(?array $profil): ?array`, `naikBersama(array $profilPerJalur): array`, `mutlakBersama(array $profilPerJalur): array` (skala vertikal bersama, dipakai kartu Jelajah dan Bandingkan);
  - `App\Services\Trails\TrailFigures::ringkas(Trail $trail): string`;
  - `resources/js/components/trails/BarisStatus.tsx`: `BarisStatus({ status, className })` dengan tipe `StatusRingkas` di `resources/js/components/trails/types.ts`;
  - `StatusAkses` menerima `ukuran?: 'kecil' | 'bagian'` (bawaan `'kecil'`);
  - komponen `resources/js/components/trails/gunung/` (`DaftarJalurGunung`, `KartuIzin`, `KartuStatusGunung`, `KondisiPendaki`, `VisualProfilGunung`) dan tipenya;
  - aturan tab Jelajah di `NavLinks.tsx` yang ikut menyala di `/mountains/`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/MountainPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\ModerationStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman gunung (DK-44, spec v3 bagian 6.3, W3 Tugas 2): hub jalur sebuah gunung untuk
 * pengguna masuk. Gunung membuka jalurnya (R-001), keputusan tetap per jalur (R-002), dan
 * hanya jalur terbit yang tampil (R-022).
 */
class MountainPageTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    private function gunung(array $atribut = []): Mountain
    {
        return Mountain::factory()->create(array_merge(['name' => 'Gunung Uji', 'slug' => 'gunung-uji', 'elevation_mdpl' => 2958], $atribut));
    }

    private function halaman(Mountain $gunung, ?User $pembaca = null): TestResponse
    {
        return $this->actingAs($pembaca ?? User::factory()->create())->get(route('mountains.show', $gunung))->assertOk();
    }

    private function sumber(): string
    {
        return $this->tanpaKomentarJs(File::get(resource_path('js/pages/Mountains/Show.tsx')));
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('mountains.show', $this->gunung()))->assertRedirect(route('login'));
    }

    public function test_an_archived_mountain_answers_not_found(): void
    {
        $gunung = $this->gunung(['archived_at' => now()]);

        $this->actingAs(User::factory()->create())->get(route('mountains.show', $gunung))->assertNotFound();
    }

    /**
     * R-022: jalur draf tidak pernah tampil sebagai pilihan; ia hanya dihitung sebagai jalur
     * yang menunggu kurator.
     */
    public function test_only_published_trails_are_offered(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Terbit']);
        Trail::factory()->for($gunung)->unpublished()->create(['name' => 'Via Draf']);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Arsip', 'archived_at' => now()]);

        $respons = $this->halaman($gunung);

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Mountains/Show')
            ->where('title', 'Gunung Uji')
            ->has('jalur', 1)
            ->where('jalur.0.nama', 'Via Terbit')
            ->where('jumlahMenunggu', 1));
        $respons->assertDontSee('Via Draf');
    }

    /**
     * BR-02: ketinggian hanya keterangan, bukan ukuran berat-ringan pendakian.
     */
    public function test_the_height_is_context_not_difficulty(): void
    {
        $this->halaman($this->gunung())->assertInertia(fn (AssertableInertia $page) => $page->where('gunung.puncak', '2.958 mdpl'));

        $this->assertStringContainsString('Ketinggian hanya keterangan. Berat-ringannya pendakian dinilai per jalur', $this->sumber());
    }

    /**
     * §42 dan R-012: status tingkat gunung dikutip dari catatannya sendiri, dan Buka di tingkat
     * gunung disertai kalimat bahwa jalurnya belum tentu buka dan bukan jaminan keselamatan.
     */
    public function test_a_mountain_level_status_says_nothing_about_each_trail(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();

        OfficialStatus::factory()->create([
            'statusable_type' => $gunung->getMorphClass(),
            'statusable_id' => $gunung->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Balai Uji',
            'data_source_id' => DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value])->id,
        ]);

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('status.status', 'OPEN')
            ->where('status.sumber.jenis', 'OFFICIAL')
            ->where('status.sumber.nama', 'Balai Uji'));

        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/gunung/KartuStatusGunung.tsx')));
        $this->assertStringContainsString('Status di tingkat gunung tidak berarti semua jalur buka, dan bukan jaminan keselamatan.', $kartu);
        $this->assertStringContainsString('statusnya tidak dianggap buka', $kartu);
    }

    /**
     * PRD §153: izin ditautkan ke sistem resmi, tidak dijual; kuota tanpa sumber tidak ditebak.
     */
    public function test_the_permit_links_out_and_never_sells(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();
        PermitRequirement::factory()->create([
            'mountain_id' => $gunung->id,
            'trail_id' => null,
            'authority' => 'Balai Uji',
            'booking_url' => 'https://booking.contoh.test',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
            'daily_quota' => null,
        ]);

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('izin.pengelola', 'Balai Uji')
            ->where('izin.jendela', 'Dibuka H-30, ditutup H-3 dari tanggal naik')
            ->where('izin.kuota', null)
            ->where('izin.bookingUrl', 'https://booking.contoh.test'));

        $izin = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/gunung/KartuIzin.tsx')));
        $this->assertStringContainsString('Memount menautkan sistem resmi dan tidak menjual izin.', $izin);
        $this->assertStringContainsString('Belum ada sumber terverifikasi. Menunggu konfirmasi pengelola.', $izin);
        $this->assertStringContainsString('rel="noopener noreferrer"', $izin);
    }

    /**
     * Gunung tanpa jalur terbit menyebut siapa yang ditunggu, bukan daftar kosong (PRD §104).
     */
    public function test_a_mountain_without_published_trails_names_who_is_awaited(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->unpublished()->create();

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page->has('jalur', 0)->where('profil', null));

        $this->assertMatchesRegularExpression('/jalur\.length === 0 \? \(\s*<EmptyState\s+jenis="menunggu"/', $this->sumber());
        $this->assertStringContainsString('menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"', $this->sumber());
    }

    /**
     * R-010: laporan komunitas hanya yang lolos moderasi, dari jalur terbit gunung ini, dan
     * ditandai komunitas; kesegarannya dari tanggal pendakian.
     */
    public function test_community_reports_complement_the_official_status(): void
    {
        $gunung = $this->gunung();
        $jalur = Trail::factory()->for($gunung)->published()->create(['name' => 'Via Laporan']);
        $draf = Trail::factory()->for($gunung)->unpublished()->create();

        foreach ([[$jalur, ModerationStatus::APPROVED, 'Licin di pos dua'], [$jalur, ModerationStatus::PENDING, 'Belum dimoderasi'], [$draf, ModerationStatus::APPROVED, 'Dari jalur draf']] as [$t, $moderasi, $catatan]) {
            TrailConditionReport::factory()->for($t)->create([
                'user_id' => User::factory()->create()->id,
                'moderation_status' => $moderasi->value,
                'note' => $catatan,
                'hike_date' => now()->subDays(2)->toDateString(),
            ]);
        }

        $this->halaman($gunung)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('kondisi', 1)
            ->where('kondisi.0.jalur', 'Via Laporan')
            ->where('kondisi.0.catatan', 'Licin di pos dua'));

        $this->assertStringContainsString('Laporan komunitas melengkapi status resmi, tidak menggantikannya.', $this->sumber());
        $this->assertStringContainsString('<LabelKomunitas />', File::get(resource_path('js/components/trails/gunung/KondisiPendaki.tsx')));
    }

    /**
     * BR-09, R-005: kecocokan per jalur hanya label, kata, dan alasan; tidak ada skor.
     */
    public function test_no_score_reaches_the_page(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create();
        $pembaca = User::factory()->create();
        $pembaca->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);

        $respons = $this->halaman($gunung, $pembaca->fresh());

        $this->assertSame(['label', 'labelText', 'alasan', 'denganRencana'], array_keys($respons->viewData('page')['props']['jalur'][0]['fit']));

        foreach (['internalScore', 'internal_score', '"score"', '"weight"'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $respons->getContent());
        }
    }

    /**
     * Visual data pengganti foto: profil setiap jalur pada skala mutlak yang sama, jadi jalur
     * yang naik lebih tinggi mencapai garis yang lebih tinggi dan jarak yang lebih panjang
     * tergambar lebih panjang.
     */
    public function test_the_hero_draws_every_trail_on_one_scale(): void
    {
        $gunung = $this->gunung();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Panjang', 'distance_km' => 10, 'elevation_gain_m' => 1000, 'elevation_profile' => [['km' => 0, 'm' => 1000], ['km' => 10, 'm' => 2000]]]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Pendek', 'distance_km' => 5, 'elevation_gain_m' => 500, 'elevation_profile' => [['km' => 0, 'm' => 1500], ['km' => 5, 'm' => 2000]]]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Via Tanpa Profil', 'elevation_profile' => null]);

        $profil = $this->halaman($gunung)->viewData('page')['props']['profil'];
        $garis = collect($profil['garis'])->keyBy('nama');

        $this->assertCount(2, $garis, 'Jalur tanpa profil tidak digambar sebagai garis karangan.');
        $this->assertSame([[0.0, 0.0], [1.0, 1.0]], $garis['Via Panjang']['titik']);
        $this->assertSame([[0.0, 0.5], [0.5, 1.0]], $garis['Via Pendek']['titik']);
        $this->assertStringContainsString('Via Panjang naik 1.000 m dalam 10,0 km', $profil['keterangan']);
    }

    /**
     * Anggaran kueri (CLAUDE.md bagian 6): halaman tidak tumbuh bersama jumlah jalur, laporan,
     * dan status.
     */
    public function test_the_page_does_not_grow_with_the_number_of_trails(): void
    {
        $gunung = $this->gunung();
        $pembaca = User::factory()->create();
        $pembaca->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $this->actingAs($pembaca->fresh());

        Trail::factory()->count(2)->for($gunung)->published()->create();
        $url = route('mountains.show', $gunung);

        $this->get($url)->assertOk();
        $sedikit = $this->hitung($url);

        Trail::factory()->count(10)->for($gunung)->published()->create();

        $banyak = $this->hitung($url);

        $this->assertSame($sedikit, $banyak, "Halaman gunung tumbuh: {$sedikit} lalu {$banyak} kueri.");
    }

    /**
     * Rincian jalur dan kartu Jelajah menautkan ke halaman ini, jadi halamannya punya kepala
     * turunan ke Jelajah dan bilah tabnya tetap (bukan halaman tugas).
     */
    public function test_the_page_is_a_child_of_jelajah(): void
    {
        $this->assertMatchesRegularExpression('/<PageHeader\s+induk=\{\{ href: trailsIndex\.url\(\), label: \'Jelajah\' \}\}/', $this->sumber());
        $this->assertStringContainsString('Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;', $this->sumber());
    }

    private function hitung(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
```

`tests/Feature/DemoSeederTest.php`:

1. Ganti:

```php
use App\Models\Trail;
```

dengan:

```php
use App\Models\Trail;
use App\Models\TrailConditionReport;
```

2. Ganti:

```php
            ...PermitRequirement::whereNotNull('notes')->pluck('notes')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
```

dengan:

```php
            ...PermitRequirement::whereNotNull('notes')->pluck('notes')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
            // W3: laporan kondisi contoh untuk halaman gunung dan rincian jalur.
            ...TrailConditionReport::pluck('note')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
```

`tests/Feature/NavigationConsistencyTest.php`:

Ganti:

```php
    }

    /**
     * Mode pendakian satu trip tinggal di /trips/{id}/hike, di bawah path tab Trip. Tanpa
```

dengan:

```php
    }

    /**
     * Halaman gunung milik Jelajah (spec v3 bagian 5.3, W3): gunung membuka jalurnya, dan
     * pembaca datang dari kartu atau rincian jalur. Path-nya di luar /trails, jadi tanpa
     * aturan sendiri tidak ada tab yang menyala di sana.
     */
    public function test_the_mountain_page_lights_jelajah(): void
    {
        $this->assertStringContainsString("p.startsWith('/mountains/')", $this->aturan('Jelajah'));

        foreach (['Beranda', 'Trip', 'Mendaki', 'Progres'] as $tab) {
            $this->assertStringNotContainsString('/mountains', $this->aturan($tab), "Tab {$tab} ikut menyala di halaman gunung.");
        }
    }

    /**
     * Mode pendakian satu trip tinggal di /trips/{id}/hike, di bawah path tab Trip. Tanpa
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

Ganti:

```php
    public function test_jelajah_tanpa_jalur_terbit(): void
```

dengan:

```php
    /**
     * Halaman gunung W3 (acuan 03): visual data profil, status tingkat gunung, tiga jalur Gede,
     * izin, dan satu laporan pendaki.
     */
    public function test_gunung(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('gunung', $this->get(self::ASAL.'/mountains/gunung-gede'), 'Mountains/Show');
    }

    public function test_jelajah_tanpa_jalur_terbit(): void
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='MountainPageTest|DemoSeederTest|NavigationConsistency|LockedBusinessRules'`
Expected: FAIL: 41 test, 28 lulus, 1 gagal, 12 error. Antara lain `NavigationConsistencyTest::test_the_mountain_page_lights_jelajah`, `MountainPageTest::test_a_guest_is_sent_to_login`, `MountainPageTest::test_an_archived_mountain_answers_not_found`.

- [ ] **Step 3: Tulis implementasinya.**

**Data, rute, dan konfigurasi**

`database/seeders/DemoSeeder.php`:

1. Ganti:

```php
use App\Enums\ExperienceLevel;
```

dengan:

```php
use App\Enums\ConditionTag;
use App\Enums\ExperienceLevel;
use App\Enums\ModerationStatus;
```

2. Ganti:

```php
use App\Models\Trail;
```

dengan:

```php
use App\Models\Trail;
use App\Models\TrailConditionReport;
```

3. Ganti:

```php
 * penjadwal yang sama dengan fitur aslinya. Isinya meniru contoh di prototype pemilik supaya
 * tangkapan pratinjau dapat dibandingkan dengan gambar acuannya: tanjakan tertinggi Budi
 * 700 m dari Prau, sehingga Cibodas (1.650 m) menjadi tanjakan terbesarnya.
```

dengan:

```php
 * penjadwal yang sama dengan fitur aslinya. Sejak W3 setiap jalur terbit membawa profil
 * elevasi, Gunung Gede punya status tingkat gunung, dan satu laporan kondisi termoderasi dari
 * pendaki contoh kedua. Isinya meniru contoh di prototype pemilik supaya tangkapan pratinjau
 * dapat dibandingkan dengan gambar acuannya: tanjakan tertinggi Budi 700 m dari Prau,
 * sehingga Cibodas (1.650 m) menjadi tanjakan terbesarnya.
```

4. Ganti:

```php
        $this->izin($sumber, $jalur);
```

dengan:

```php
        $this->izin($sumber, $jalur);
        $this->statusGunung($sumber, $jalur);
        $this->laporan($jalur);
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
     * Status akses tingkat gunung untuk Gunung Gede (halaman gunung W3): Buka, dengan sumber
     * dan penanda yang sama dengan status jalurnya, supaya kalimat "status tingkat gunung tidak
     * berarti semua jalur buka" punya status untuk diterangkan.
     *
     * @param  array<string, Trail>  $jalur
     */
    private function statusGunung(DataSource $sumber, array $jalur): void
    {
        OfficialStatus::create([
            'statusable_type' => (new Mountain)->getMorphClass(),
            'statusable_id' => $jalur['gede-via-cibodas']->mountain_id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::OPEN->value,
            'data_source_id' => $sumber->id,
            'source' => self::PENANDA.', bukan status resmi',
            'published_at' => now()->subDays(9),
            'fetched_at' => now()->subDays(9),
            'effective_at' => now()->subDays(9),
            'verified_at' => now()->subDays(7),
        ]);
    }

    /**
     * Satu laporan kondisi yang lolos moderasi di Gede via Cibodas, dari pendaki contoh kedua
     * berinisial (antislop R-23), supaya lapisan komunitas halaman gunung dan rincian jalur
     * punya isi. Catatannya berpenanda DATA CONTOH.
     *
     * @param  array<string, Trail>  $jalur
     */
    private function laporan(array $jalur): void
    {
        $pelapor = User::create(['name' => 'Sari Wulandari', 'email' => 'sari@contoh.test', 'password' => Str::password(20, symbols: false)]);

        TrailConditionReport::create([
            'trail_id' => $jalur['gede-via-cibodas']->id,
            'user_id' => $pelapor->id,
            'hike_date' => Carbon::parse(Timezone::earliestDateInIndonesia())->subDays(2)->toDateString(),
            'condition_tags' => [ConditionTag::SLIPPERY->value, ConditionTag::MUDDY->value],
            'note' => self::PENANDA.'. Licin dan berlumpur antara Air Panas dan Kandang Badak setelah hujan sore.',
            'moderation_status' => ModerationStatus::APPROVED->value,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Sebelas jalur. Jalur dengan status null belum punya pos dan status resmi, jadi tetap
```

6. Ganti:

```php
                ],
            ],
            'gede-via-selabintana' => [
```

dengan:

```php
                ],
                'profil' => [['km' => 0, 'm' => 1450], ['km' => 2.5, 'm' => 1900], ['km' => 5.0, 'm' => 2750], ['km' => 7.2, 'm' => 2958]],
            ],
            'gede-via-selabintana' => [
```

7. Ganti:

```php
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
            ],
```

dengan:

```php
                    ['Puncak Gede', CheckpointType::SUMMIT, 2958],
                ],
                'profil' => [['km' => 0, 'm' => 1100], ['km' => 4.0, 'm' => 1700], ['km' => 8.0, 'm' => 2400], ['km' => 10.0, 'm' => 2750], ['km' => 11.8, 'm' => 2958]],
            ],
```

8. Ganti:

```php
                    ['Puncak Pangrango', CheckpointType::SUMMIT, 3019],
                ],
            ],
```

dengan:

```php
                    ['Puncak Pangrango', CheckpointType::SUMMIT, 3019],
                ],
                'profil' => [['km' => 0, 'm' => 1340], ['km' => 5.3, 'm' => 2150], ['km' => 7.2, 'm' => 2395], ['km' => 11.2, 'm' => 3019]],
            ],
```

9. Ganti:

```php
                    ['Tegal Alun', CheckpointType::POS, 2600],
                ],
            ],
```

dengan:

```php
                    ['Tegal Alun', CheckpointType::POS, 2600],
                ],
                'profil' => [['km' => 0, 'm' => 2050], ['km' => 2.5, 'm' => 2288], ['km' => 5.0, 'm' => 2450], ['km' => 7.4, 'm' => 2600]],
            ],
```

10. Ganti:

```php
                    ['Puncak Gunung Batu', CheckpointType::SUMMIT, 875],
                ],
            ],
```

dengan:

```php
                    ['Puncak Gunung Batu', CheckpointType::SUMMIT, 875],
                ],
                'profil' => [['km' => 0, 'm' => 600], ['km' => 0.8, 'm' => 760], ['km' => 1.6, 'm' => 875]],
            ],
```

11. Ganti:

```php
                    ['Puncak Prau', CheckpointType::SUMMIT, 2590],
                ],
            ],
```

dengan:

```php
                    ['Puncak Prau', CheckpointType::SUMMIT, 2590],
                ],
                'profil' => [['km' => 0, 'm' => 1890], ['km' => 2.0, 'm' => 2150], ['km' => 4.0, 'm' => 2400], ['km' => 6.1, 'm' => 2590]],
            ],
```

12. Ganti:

```php
                    ['Puncak Andong', CheckpointType::SUMMIT, 1726],
                ],
            ],
```

dengan:

```php
                    ['Puncak Andong', CheckpointType::SUMMIT, 1726],
                ],
                'profil' => [['km' => 0, 'm' => 1340], ['km' => 1.4, 'm' => 1550], ['km' => 2.8, 'm' => 1726]],
            ],
```

13. Ganti:

```php
                    ['Puncak Batur', CheckpointType::SUMMIT, 1717],
                ],
            ],
```

dengan:

```php
                    ['Puncak Batur', CheckpointType::SUMMIT, 1717],
                ],
                'profil' => [['km' => 0, 'm' => 1100], ['km' => 2.3, 'm' => 1450], ['km' => 4.6, 'm' => 1717]],
            ],
```

14. Ganti:

```php
                    ['Puncak Rinjani', CheckpointType::SUMMIT, 3726],
                ],
            ],
```

dengan:

```php
                    ['Puncak Rinjani', CheckpointType::SUMMIT, 3726],
                ],
                'profil' => [['km' => 0, 'm' => 1150], ['km' => 8.0, 'm' => 1800], ['km' => 15.0, 'm' => 2639], ['km' => 22.5, 'm' => 3726]],
            ],
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Trails\MountainFollowController;
```

dengan:

```php
use App\Http\Controllers\Trails\MountainFollowController;
use App\Http\Controllers\Trails\MountainPageController;
```

2. Ganti:

```php
    Route::post('mountains/{mountain}/follow', [MountainFollowController::class, 'store'])->middleware('throttle:60,1')->name('mountains.follow');
```

dengan:

```php
    Route::post('mountains/{mountain}/follow', [MountainFollowController::class, 'store'])->middleware('throttle:60,1')->name('mountains.follow');
    // Halaman gunung (DK-44): hub jalur sebuah gunung, hanya untuk pengguna masuk.
    Route::get('mountains/{mountain:slug}', MountainPageController::class)->name('mountains.show');
```

**Server**

Buat `app/Http/Controllers/Trails/MountainPageController.php`:

```php
<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Resources\MountainHeaderResource;
use App\Models\Mountain;
use App\Services\Mountains\HalamanGunung;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman gunung terautentikasi (DK-44, spec v3 bagian 6.3): pintu dari Jelajah dan dari
 * rincian jalur ke seluruh jalur sebuah gunung. Gunung terarsip ditarik dari peredaran dan
 * menjawab 404, sama seperti jalur terarsip. Halaman publik SEO `/gunung/{slug}` tetap
 * mengikuti rencana D-B dan bukan halaman ini.
 */
class MountainPageController extends Controller
{
    public function __invoke(Request $request, Mountain $mountain, HalamanGunung $halaman): Response
    {
        abort_if($mountain->isArchived(), 404);

        return Inertia::render('Mountains/Show', [
            'title' => $mountain->name,
            'gunung' => (new MountainHeaderResource($mountain))->resolve($request),
            'pengumuman' => session('pengumuman', ''),
        ] + $halaman->props($mountain, $request->user()));
    }
}
```

Buat `app/Http/Resources/MountainHeaderResource.php`:

```php
<?php

namespace App\Http\Resources;

use App\Models\Mountain;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Identitas gunung di kepala halaman gunung: nama, provinsi, zona waktu, dan ketinggian
 * puncak, sudah diformat di server.
 *
 * Sama dengan TrailHeaderResource: ketinggian puncak hanya keterangan identitas, bukan
 * ukuran kesulitan (BR-02), jadi disusun di lapisan presentasi ini dan tidak pernah di
 * app/Services yang dijaga LockedBusinessRulesTest dari MDPL.
 *
 * @mixin Mountain
 */
class MountainHeaderResource extends JsonResource
{
    public function __construct(Mountain $mountain)
    {
        parent::__construct($mountain);
    }

    /**
     * @return array{id: int, nama: string, provinsi: ?string, puncak: ?string, zona: string}
     */
    public function toArray(Request $request): array
    {
        $mdpl = $this->elevation_mdpl;

        return [
            'id' => $this->id,
            'nama' => $this->name,
            'provinsi' => $this->province,
            'puncak' => $mdpl !== null ? number_format((int) $mdpl, 0, ',', '.').' mdpl' : null,
            'zona' => Timezone::label(Timezone::isSupported($this->timezone) ? $this->timezone : Timezone::DEFAULT),
        ];
    }
}
```

Buat `app/Services/Mountains/HalamanGunung.php`:

```php
<?php

namespace App\Services\Mountains;

use App\Enums\RouteFitLabel;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\ConditionAggregatorService;
use App\Services\OfficialStatusService;
use App\Services\TrailFitService;
use App\Services\TrailNewsService;
use App\Services\Trails\SkalaProfil;
use App\Services\Trails\StatusJalurRingkas;
use App\Services\Trails\TrailFigures;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection;

/**
 * Props halaman gunung (spec v3 bagian 6.3, DK-44): hub jalur sebuah gunung, bukan pengganti
 * keputusan per jalur (R-001, R-002). Identitas gunung beserta ketinggian puncaknya disusun
 * MountainHeaderResource, di luar lapisan layanan (BR-02, LockedBusinessRulesTest).
 *
 * Yang ditampilkan berurutan dengan pertanyaan pendaki: gunung apa ini, bagaimana statusnya
 * di tingkat gunung, jalur mana yang dapat didaki dan seberapa cocok untuknya, bagaimana
 * mengurus izinnya, apa kata pendaki terakhir, dan dari mana semua keterangan itu. Hanya
 * jalur terbit yang tampil (R-022); jalur yang datanya belum lengkap hanya dihitung.
 *
 * Kueri tidak tumbuh bersama jumlah jalur: kecocokan, status, dan laporan dimuat batch.
 */
final class HalamanGunung
{
    /** Laporan komunitas terbaru yang ditampilkan, dari jalur mana pun di gunung ini. */
    public const MAKS_LAPORAN = 3;

    public function __construct(
        private readonly TrailFitService $fit,
        private readonly StatusJalurRingkas $statusJalur,
        private readonly OfficialStatusService $statusResmi,
        private readonly TrailNewsService $kabar,
        private readonly ConditionAggregatorService $kondisi,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(Mountain $gunung, User $pembaca): array
    {
        $zona = Timezone::isSupported($gunung->timezone) ? $gunung->timezone : Timezone::DEFAULT;
        $gunung->loadMissing('dataSource:id,source_name,source_type,verified_at');

        /** @var Collection<int, Trail> $jalur */
        $jalur = $gunung->trails()->published()->with('mountain')->orderBy('name')->get();
        $fit = $pembaca->hasCompletedProfile() ? $this->fit->forTrails($pembaca, $jalur) : [];
        $status = $this->statusJalur->untuk($jalur);

        return [
            'mengikuti' => $this->kabar->follows($pembaca, $gunung),
            // MountainFollowController hanya menerima gunung yang masih punya jalur beredar;
            // tombolnya tidak ditawarkan bila aksinya akan ditolak (DK-40).
            'bolehDiikuti' => $gunung->trails()->whereNull('archived_at')->exists(),
            'status' => $this->statusGunung($gunung, $zona),
            'jalur' => $jalur->map(fn (Trail $t) => [
                'id' => $t->id,
                'nama' => $t->name,
                'url' => route('trails.show', $t),
                'fit' => isset($fit[$t->id]) ? [
                    'label' => $fit[$t->id]->label?->value,
                    'labelText' => RouteFitLabel::textFor($fit[$t->id]->label),
                    'alasan' => $fit[$t->id]->alasan,
                    'denganRencana' => $fit[$t->id]->denganRencana,
                ] : null,
                'angka' => TrailFigures::ringkas($t),
                'medan' => $t->terrainTypes() === [] ? null : TrailFigures::medan($t),
                'status' => $status[$t->id],
            ])->values()->all(),
            'jumlahMenunggu' => $gunung->trails()->active()->where('is_published', false)->count(),
            'profil' => $this->profil($jalur),
            'izin' => $this->izin($gunung, $zona),
            'kondisi' => $this->laporan($jalur),
            'provenans' => [
                'sumber' => $gunung->dataSource === null ? null : [
                    'jenis' => $gunung->dataSource->source_type?->value,
                    'label' => $gunung->dataSource->source_type?->label(),
                    'nama' => $gunung->dataSource->source_name,
                ],
                'diverifikasi' => $gunung->dataSource?->verified_at?->copy()->setTimezone($zona)->translatedFormat('j M Y'),
            ],
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
        ];
    }

    /**
     * Status akses tingkat gunung. Buka di tingkat gunung tidak berarti setiap jalurnya buka
     * (§42, R-012); halaman menulis kalimat itu di samping status ini.
     *
     * @return array<string, mixed>
     */
    private function statusGunung(Mountain $gunung, string $zona): array
    {
        $catatan = $this->statusResmi->currentFor($gunung);

        if ($catatan instanceof OfficialStatus) {
            $catatan->loadMissing('dataSource:id,source_type');
        }

        return $this->statusJalur->bentuk($catatan, $zona);
    }

    /**
     * Visual data pengganti foto (spec v3 bagian 8.7, DK-37): profil setiap jalur terbit pada
     * skala mutlak yang sama. Tanpa satu pun profil tidak ada visual; kalimatnya tetap ada.
     *
     * @param  Collection<int, Trail>  $jalur
     * @return array<string, mixed>|null
     */
    private function profil(Collection $jalur): ?array
    {
        $skala = SkalaProfil::mutlakBersama($jalur->mapWithKeys(fn (Trail $t) => [$t->id => $t->elevation_profile])->all());

        if ($skala['garis'] === []) {
            return null;
        }

        $nama = $jalur->pluck('name', 'id');

        return [
            'garis' => collect($skala['garis'])->map(fn (array $titik, int $id) => ['id' => $id, 'nama' => $nama[$id], 'titik' => $titik])->values()->all(),
            'keterangan' => 'Profil elevasi '.count($skala['garis']).' jalur pada skala yang sama, ketinggian '
                .number_format($skala['mMin'], 0, ',', '.').' sampai '.number_format($skala['mMaks'], 0, ',', '.')
                .' m dan jarak sampai '.number_format($skala['kmMaks'], 1, ',', '.').' km: '
                .$jalur->filter(fn (Trail $t) => isset($skala['garis'][$t->id]))
                    ->map(fn (Trail $t) => $t->name.' naik '.number_format((int) $t->elevation_gain_m, 0, ',', '.').' m dalam '.number_format((float) $t->distance_km, 1, ',', '.').' km')
                    ->join('; ').'.',
        ];
    }

    /**
     * Aturan izin tingkat gunung (PRD §153): sistem menautkan sistem resmi dan tidak pernah
     * menjual atau memesan izin. Kuota yang tidak bersumber tidak ditebak.
     *
     * @return array<string, mixed>|null
     */
    private function izin(Mountain $gunung, string $zona): ?array
    {
        $aturan = PermitRequirement::query()
            ->where('mountain_id', $gunung->id)
            ->whereNull('trail_id')
            ->with('dataSource:id,source_type')
            ->latest('verified_at')
            ->first();

        if ($aturan === null) {
            return null;
        }

        $buka = $aturan->booking_opens_days_before;
        $tutup = $aturan->booking_closes_days_before;

        return [
            'pengelola' => $aturan->authority,
            'jendela' => match (true) {
                $buka !== null && $tutup !== null => "Dibuka H-{$buka}, ditutup H-{$tutup} dari tanggal naik",
                $tutup !== null => "Ditutup H-{$tutup} dari tanggal naik",
                $buka !== null => "Dibuka H-{$buka} dari tanggal naik",
                default => null,
            },
            'kuota' => $aturan->daily_quota ? number_format($aturan->daily_quota, 0, ',', '.').' pendaki per hari' : null,
            'pemandu' => $aturan->guide_required ? 'Wajib pemandu terdaftar' : null,
            'catatan' => $aturan->notes,
            'bookingUrl' => $aturan->booking_url,
            'sumber' => [
                'jenis' => $aturan->dataSource?->source_type?->value,
                'label' => $aturan->dataSource?->source_type?->label(),
                'nama' => $aturan->source,
            ],
            'diverifikasi' => $aturan->verified_at?->copy()->setTimezone($zona)->translatedFormat('j M Y'),
        ];
    }

    /**
     * Laporan komunitas terbaru dari jalur terbit gunung ini, termoderasi (R-010): pelengkap
     * status resmi, bukan penggantinya. Kesegarannya dihitung dari tanggal pendakian (§51).
     *
     * @param  Collection<int, Trail>  $jalur
     * @return array<int, array<string, mixed>>
     */
    private function laporan(Collection $jalur): array
    {
        if ($jalur->isEmpty()) {
            return [];
        }

        return TrailConditionReport::query()
            ->visibleToPublic()
            ->whereIn('trail_id', $jalur->pluck('id'))
            ->where('hike_date', '>=', now()->subDays(30)->toDateString())
            ->with('trail:id,name')
            ->orderByDesc('hike_date')
            ->orderByDesc('id')
            ->limit(self::MAKS_LAPORAN)
            ->get()
            ->map(fn (TrailConditionReport $r) => [
                'id' => $r->id,
                'jalur' => $r->trail->name,
                'tags' => array_map(fn ($tag) => $tag->label(), $r->tags()),
                'catatan' => $r->note,
                'dilaporkan' => $r->created_at->diffForHumans(),
                'tanggalPendakian' => $r->hike_date->translatedFormat('j M'),
                'kesegaran' => $this->kondisi->reportFreshness($r)->label(),
            ])
            ->all();
    }
}
```

Buat `app/Services/Trails/SkalaProfil.php`:

```php
<?php

namespace App\Services\Trails;

/**
 * Profil elevasi beberapa jalur pada satu skala, untuk digambar berdampingan: garis profil
 * kartu Jelajah, profil di tabel banding, dan visual data halaman gunung (spec v3 bagian 6.2,
 * 6.3, 6.7; "grafik: skala sama saat membandingkan").
 *
 * Titiknya dinormalkan di server menjadi 0..1, jadi klien hanya menggambar dan tidak pernah
 * menghitung skala sendiri. Satu profil yang tidak dapat digambar (kurang dari dua titik sah)
 * dikirim sebagai tidak ada, bukan garis datar karangan (R-007). Profil panjang dijarangkan
 * menjadi paling banyak MAKS_TITIK titik: bentuknya tetap, ukuran halamannya tidak tumbuh
 * bersama resolusi rekaman GPS.
 */
final class SkalaProfil
{
    public const MAKS_TITIK = 48;

    /**
     * Titik sah sebuah profil, [km, m], urut sepanjang jalur; null bila tidak dapat digambar.
     *
     * @param  array<int, mixed>|null  $profil
     * @return array<int, array{0: float, 1: float}>|null
     */
    public static function titik(?array $profil): ?array
    {
        $titik = [];

        foreach ((array) $profil as $t) {
            if (is_array($t) && is_numeric($t['km'] ?? null) && is_numeric($t['m'] ?? null)) {
                $titik[] = [(float) $t['km'], (float) $t['m']];
            }
        }

        usort($titik, fn (array $a, array $b) => $a[0] <=> $b[0]);

        return count($titik) >= 2 && end($titik)[0] > $titik[0][0] ? $titik : null;
    }

    /**
     * Skala naik yang sama untuk setiap profil: setiap garis memenuhi lebarnya sendiri, dan
     * tingginya diukur dalam meter yang sama. Jalur yang naik 280 m terbaca landai di sebelah
     * jalur yang naik 1.650 m, persis seperti di prototype 02 dan 07.
     *
     * @param  array<int, array<int, mixed>|null>  $profilPerJalur  berkunci id jalur
     * @return array{skalaMeter: int, garis: array<int, array<int, array{0: float, 1: float}>>}
     */
    public static function naikBersama(array $profilPerJalur): array
    {
        $bersih = array_filter(array_map(self::titik(...), $profilPerJalur));
        $rentang = array_map(fn (array $t) => max(array_column($t, 1)) - min(array_column($t, 1)), $bersih);
        $skala = max([1.0, ...array_values($rentang)]);

        $garis = [];

        foreach ($bersih as $id => $titik) {
            $awal = $titik[0][0];
            $panjang = end($titik)[0] - $awal;
            $terendah = min(array_column($titik, 1));

            $garis[$id] = self::jarangkan(array_map(fn (array $t) => [
                round(($t[0] - $awal) / $panjang, 3),
                round(($t[1] - $terendah) / $skala, 3),
            ], $titik));
        }

        return ['skalaMeter' => (int) round($skala), 'garis' => $garis];
    }

    /**
     * Skala mutlak bersama untuk jalur di satu gunung: jarak dan ketinggian diukur sama untuk
     * semuanya, jadi jalur yang lebih panjang tergambar lebih panjang dan puncak yang sama
     * berada di ketinggian yang sama.
     *
     * @param  array<int, array<int, mixed>|null>  $profilPerJalur  berkunci id jalur
     * @return array{kmMaks: float, mMin: int, mMaks: int, garis: array<int, array<int, array{0: float, 1: float}>>}
     */
    public static function mutlakBersama(array $profilPerJalur): array
    {
        $bersih = array_filter(array_map(self::titik(...), $profilPerJalur));

        if ($bersih === []) {
            return ['kmMaks' => 0.0, 'mMin' => 0, 'mMaks' => 0, 'garis' => []];
        }

        $semua = array_merge(...array_values($bersih));
        $kmMaks = max(array_map(fn (array $t) => end($t)[0] - $t[0][0], $bersih));
        $mMin = min(array_column($semua, 1));
        $mMaks = max(array_column($semua, 1));
        $tinggi = max(1.0, $mMaks - $mMin);

        $garis = [];

        foreach ($bersih as $id => $titik) {
            $awal = $titik[0][0];

            $garis[$id] = self::jarangkan(array_map(fn (array $t) => [
                round(($t[0] - $awal) / $kmMaks, 3),
                round(($t[1] - $mMin) / $tinggi, 3),
            ], $titik));
        }

        return ['kmMaks' => round($kmMaks, 1), 'mMin' => (int) round($mMin), 'mMaks' => (int) round($mMaks), 'garis' => $garis];
    }

    /**
     * Paling banyak MAKS_TITIK titik, dengan titik pertama dan terakhir selalu ikut.
     *
     * @param  array<int, array{0: float, 1: float}>  $titik
     * @return array<int, array{0: float, 1: float}>
     */
    private static function jarangkan(array $titik): array
    {
        $jumlah = count($titik);

        if ($jumlah <= self::MAKS_TITIK) {
            return $titik;
        }

        $langkah = ($jumlah - 1) / (self::MAKS_TITIK - 1);
        $hasil = [];

        for ($i = 0; $i < self::MAKS_TITIK; $i++) {
            $hasil[] = $titik[(int) round($i * $langkah)];
        }

        return $hasil;
    }
}
```

`app/Services/Trails/TrailFigures.php`:

Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Baris ukuran padat untuk daftar ("9,7 km · ↑1.650 m · 7,5 j"): titik tengah hanya di
     * baris ukuran seperti ini (spec v3 bagian 8.8). Angka yang tidak ada tidak ditulis nol,
     * dan baris yang sebagian angkanya belum ada mengakuinya (PRD §95).
     */
    public static function ringkas(Trail $trail): string
    {
        $bagian = array_values(array_filter([
            $trail->distance_km !== null ? number_format((float) $trail->distance_km, 1, ',', '.').' km' : null,
            $trail->elevation_gain_m !== null ? '↑'.number_format($trail->elevation_gain_m, 0, ',', '.').' m' : null,
            ($trail->estimated_duration_minutes ?? 0) > 0 ? Durasi::pendek($trail->estimated_duration_minutes) : null,
        ]));

        if ($bagian === []) {
            return 'Angka jalur belum tersedia';
        }

        return implode(' · ', $bagian).(count($bagian) < 3 ? ' · sebagian angka belum tersedia' : '');
    }
}
```

**Antarmuka**

Buat `resources/js/components/trails/BarisStatus.tsx`:

```tsx
import { kelas } from '@/lib/kelas';
import { StatusAkses } from './StatusAkses';
import type { StatusRingkas } from './types';

/**
 * Status akses dalam satu baris daftar: bentuk dan kata (DK-32), lalu dari mana dan kapan
 * ("Resmi, 12 Sep"). Kata jenis sumbernya datang dari server (R-009), jadi "Resmi" hanya
 * tertulis untuk catatan bersumber OFFICIAL. Status yang belum diketahui menyebut dirinya
 * tidak dianggap buka (§95), tidak dibiarkan terbaca netral. Berupa span berblok supaya dapat
 * duduk di dalam tautan baris tanpa menaruh paragraf di dalam elemen sebaris.
 */
export function BarisStatus({ status, className }: { status: StatusRingkas; className?: string }) {
    const asal = status.sumber ? [status.sumber.label ?? status.sumber.nama, status.waktu].filter(Boolean).join(', ') : null;

    return (
        <span className={kelas('flex flex-wrap items-center gap-x-2 gap-y-1', className)}>
            <StatusAkses nilai={status.status} label={status.label} cakupan={status.cakupan === 'Gunung' ? status.cakupan : null} />
            {asal ? <span className="text-meta text-muted">{asal}</span> : null}
            {status.status === null ? <span className="text-meta text-muted">Tidak dianggap buka</span> : null}
        </span>
    );
}
```

Buat `resources/js/components/trails/gunung/DaftarJalurGunung.tsx`:

```tsx
import { BarisStatus } from '@/components/trails/BarisStatus';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { JalurGunung } from './types';

interface DaftarJalurGunungProps {
    jalur: JalurGunung[];
    /** Kata UNKNOWN dari server, untuk pembaca yang profilnya belum lengkap. */
    kataBelumDinilai: string;
}

/**
 * Jalur terbit sebuah gunung (spec v3 bagian 6.3): satu gunung, beberapa jalur dengan
 * tantangan berbeda (R-001, R-002). Setiap baris adalah tautan ke rincian jalurnya dan
 * membawa label kecocokan dasar, baris ukuran, dan status aksesnya sendiri. Urutannya nama
 * jalur, bukan kecocokan: halaman ini menyajikan pilihan, bukan peringkat. Chevron menandai
 * baris yang membuka halaman lain (spec v3 bagian 8.8).
 */
export function DaftarJalurGunung({ jalur, kataBelumDinilai }: DaftarJalurGunungProps) {
    return (
        <ul className="divide-y divide-subtle overflow-hidden rounded-lg border border-subtle bg-surface">
            {jalur.map((j) => (
                <li key={j.id}>
                    <Tautan href={j.url} className={kelas('flex items-center gap-3 px-4 py-4 hover:bg-surface-sunken', fokusTerlihat)}>
                        <span className="min-w-0 flex-1">
                            <span className="flex flex-wrap items-start justify-between gap-2">
                                <span className="min-w-0 break-words text-subjudul text-primary">{j.nama}</span>
                                <LabelKecocokan nilai={j.fit?.label ?? null} label={j.fit?.labelText ?? kataBelumDinilai} />
                            </span>
                            <span data-angka="" className="mt-1 block text-kecil font-semibold text-primary">
                                {j.angka}
                            </span>
                            <BarisStatus status={j.status} className="mt-1" />
                            {j.medan ? <span className="mt-1 block text-meta text-secondary">Medan: {j.medan}</span> : null}
                        </span>
                        <Icon name="caret-right" size={20} className="shrink-0 text-muted" />
                    </Tautan>
                </li>
            ))}
        </ul>
    );
}
```

Buat `resources/js/components/trails/gunung/KartuIzin.tsx`:

```tsx
import { Kesegaran } from '@/components/trails/Kesegaran';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';
import type { IzinGunung } from './types';

/**
 * Izin pendakian tingkat gunung (spec v3 bagian 6.3, PRD §153). Memount mencatat aturan
 * pengelola dan menautkan sistem resminya; ia tidak memesan, menjual, atau melacak kuota.
 * Kuota yang tidak bersumber ditulis sebagai belum ada sumber terverifikasi, tidak ditebak
 * (R-007). Tautan keluar membawa ikon tautan keluar dan membuka tab baru yang disebutkan.
 */
export function KartuIzin({ izin }: { izin: IzinGunung }) {
    const butir: Array<[string, string]> = [
        ['Dikelola', izin.pengelola],
        ['Jendela pesan', izin.jendela ?? 'Belum tercatat. Periksa ke pengelola.'],
        ['Kuota harian', izin.kuota ?? 'Belum ada sumber terverifikasi. Menunggu konfirmasi pengelola.'],
    ];

    if (izin.pemandu) {
        butir.push(['Pemandu', izin.pemandu]);
    }

    return (
        <div className="rounded-lg border border-subtle bg-surface p-5">
            <dl className="grid grid-cols-[minmax(0,7rem)_minmax(0,1fr)] gap-x-4 gap-y-3 text-kecil">
                {butir.map(([label, nilai]) => (
                    <div key={label} className="contents">
                        <dt className="text-secondary">{label}</dt>
                        <dd className="break-words font-bold text-primary">{nilai}</dd>
                    </div>
                ))}
            </dl>
            {izin.catatan ? <p className="mt-3 text-kecil text-secondary">{izin.catatan}</p> : null}
            {izin.bookingUrl ? (
                <a
                    href={izin.bookingUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={kelas('mt-4 flex min-h-11 w-full items-center justify-center gap-2 rounded-tombol border border-control bg-surface px-5 py-3 text-center text-isi font-bold text-primary hover:bg-surface-sunken', fokusTerlihat, umpanTekan)}
                >
                    Buka sistem pemesanan resmi
                    <Icon name="arrow-square-out" size={20} />
                    <span className="sr-only">(membuka tab baru)</span>
                </a>
            ) : null}
            <p className="mt-3 text-meta text-secondary">Memount menautkan sistem resmi dan tidak menjual izin.</p>
            <div className="mt-2 flex flex-wrap items-center gap-2">
                <LabelSumber jenis={izin.sumber.jenis} />
                <span className="text-meta text-muted">Sumber: {izin.sumber.nama ?? 'belum tercatat'}</span>
            </div>
            <Kesegaran waktu={izin.diverifikasi} awalan="Aturan diverifikasi" className="mt-1" />
        </div>
    );
}
```

Buat `resources/js/components/trails/gunung/KartuStatusGunung.tsx`:

```tsx
import { Kesegaran } from '@/components/trails/Kesegaran';
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import type { StatusRingkas } from '@/components/trails/types';
import { kelas } from '@/lib/kelas';

/**
 * Status akses tingkat gunung (spec v3 bagian 6.3). Kalimat R-012 dan kaskade §42 selalu
 * ikut: Buka di tingkat gunung tidak berarti setiap jalurnya buka, dan status tiap jalur
 * tertulis di daftarnya sendiri. Garis padat merek hanya untuk catatan bersumber resmi
 * (spec v3 bagian 7); catatan lain bergaris rambut biasa. Tanpa catatan, kartunya menyebut
 * bahwa status belum diketahui dan tidak dianggap buka.
 */
export function KartuStatusGunung({ status }: { status: StatusRingkas }) {
    const resmi = status.sumber?.jenis === 'OFFICIAL';

    return (
        <section aria-labelledby="judul-status-gunung" className={kelas('rounded-lg bg-surface p-5', resmi ? 'border-[1.5px] border-brand-ink' : 'border border-subtle')}>
            <div className="flex flex-wrap items-center gap-2">
                {status.sumber ? <LabelSumber jenis={status.sumber.jenis} /> : null}
                <h3 id="judul-status-gunung" className="text-meta font-bold text-secondary">
                    Status akses gunung
                </h3>
            </div>
            <div className="mt-2">
                <StatusAkses nilai={status.status} label={status.label} ukuran="bagian" />
            </div>
            <p className="mt-2 text-kecil text-secondary">
                {status.status === null
                    ? 'Belum ada status resmi yang berlaku untuk gunung ini, jadi statusnya tidak dianggap buka. Status tiap jalur ada di bawah.'
                    : 'Status di tingkat gunung tidak berarti semua jalur buka, dan bukan jaminan keselamatan. Status tiap jalur ada di bawah.'}
            </p>
            {status.sumber ? (
                <p className="mt-2 text-meta text-muted">
                    Sumber: {status.sumber.nama ?? 'belum tercatat'}
                    {status.cakupan ? `, cakupan ${status.cakupan.toLowerCase()}` : null}.
                </p>
            ) : null}
            {status.sumber ? <Kesegaran waktu={status.waktu} awalan="Diverifikasi" keadaan={status.kesegaran} className="mt-1" /> : null}
        </section>
    );
}
```

Buat `resources/js/components/trails/gunung/KondisiPendaki.tsx`:

```tsx
import { LabelKomunitas } from '@/components/trails/LabelSumber';
import { Lencana } from '@/components/ui/Lencana';
import type { LaporanGunung } from './types';

/**
 * Kondisi terbaru dari pendaki di jalur-jalur gunung ini (spec v3 bagian 6.3): lapisan
 * komunitas yang melengkapi status resmi, tidak menggantikannya (R-010). Setiap kartu
 * bergaris putus-putus komunitas, menyebut jalurnya, dan menghitung kesegarannya dari tanggal
 * pendakian, bukan tanggal kirim (§51).
 */
export function KondisiPendaki({ laporan }: { laporan: LaporanGunung[] }) {
    return (
        <ul className="space-y-3">
            {laporan.map((l) => (
                <li key={l.id} className="rounded-lg border-[1.5px] border-dashed border-community-500 bg-community-50 p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <LabelKomunitas />
                        <span className="text-meta font-bold text-community-900">{l.jalur}</span>
                        <Lencana nada="netral">{l.kesegaran}</Lencana>
                    </div>
                    {l.tags.length > 0 ? (
                        <ul className="mt-2 flex flex-wrap gap-1.5">
                            {l.tags.map((t) => (
                                <li key={t}>
                                    <Lencana nada="komunitas" bentuk="pil">
                                        {t}
                                    </Lencana>
                                </li>
                            ))}
                        </ul>
                    ) : null}
                    {l.catatan ? <p className="mt-2 break-words text-kecil text-primary">{l.catatan}</p> : null}
                    <p className="mt-2 text-meta text-community-900">
                        Dilaporkan {l.dilaporkan}, dari pendakian {l.tanggalPendakian}, lolos moderasi.
                    </p>
                </li>
            ))}
        </ul>
    );
}
```

Buat `resources/js/components/trails/gunung/VisualProfilGunung.tsx`:

```tsx
import type { ProfilGunung } from './types';

const LEBAR = 120;
const TINGGI = 56;

/** Titik 0..1 menjadi koordinat SVG; sumbu y dibalik karena ketinggian naik ke atas. */
function jalurGaris(titik: Array<[number, number]>): string {
    return titik.map(([x, y]) => `${(x * LEBAR).toFixed(1)},${(TINGGI - y * (TINGGI - 4) - 2).toFixed(1)}`).join(' ');
}

/** Bidang di bawah garis: dari dasar kiri, menyusuri garis, lalu turun ke dasar di ujung garis. */
function bidang(titik: Array<[number, number]>): string {
    const ujung = titik.at(-1)?.[0] ?? 0;

    return `0,${TINGGI} ${jalurGaris(titik)} ${(ujung * LEBAR).toFixed(1)},${TINGGI}`;
}

/**
 * Visual data pengganti foto gunung (spec v3 bagian 8.7, DK-37): profil setiap jalur terbit
 * sebagai kelipatan kecil pada skala mutlak yang sama, jadi jalur yang lebih panjang tampak
 * lebih panjang dan puncak yang sama berada di ketinggian yang sama. Kelipatan kecil, bukan
 * garis bertumpuk: garis yang berakhir di puncak yang sama saling menutupi, dan membedakannya
 * dengan warna saja melanggar WCAG 1.4.1. Pembaca layar mendapat kalimat ringkasnya.
 */
export function VisualProfilGunung({ profil }: { profil: ProfilGunung }) {
    return (
        <figure className="rounded-utama border border-subtle bg-surface p-4">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {profil.garis.map((g) => (
                    <div key={g.id} className="min-w-0">
                        <p className="break-words text-meta font-bold text-primary">{g.nama}</p>
                        <svg viewBox={`0 0 ${LEBAR} ${TINGGI}`} preserveAspectRatio="none" aria-hidden="true" className="mt-1 h-14 w-full">
                            <polygon points={bidang(g.titik)} className="fill-brand-100" />
                            <polyline points={jalurGaris(g.titik)} fill="none" strokeWidth="2" strokeLinejoin="round" className="stroke-brand-ink" vectorEffect="non-scaling-stroke" />
                        </svg>
                    </div>
                ))}
            </div>
            <figcaption className="mt-3 text-meta text-secondary">{profil.keterangan}</figcaption>
        </figure>
    );
}
```

Buat `resources/js/components/trails/gunung/types.ts`:

```ts
import type { SumberKeterangan } from '@/components/trails/LabelSumber';
import type { FitRingkas, StatusRingkas } from '@/components/trails/types';

/** Bentuk yang dikirim App\Services\Mountains\HalamanGunung; angka sudah diformat server. */
export interface GunungRingkas {
    id: number;
    nama: string;
    provinsi: string | null;
    puncak: string | null;
    zona: string;
}

export interface JalurGunung {
    id: number;
    nama: string;
    url: string;
    fit: FitRingkas | null;
    angka: string;
    medan: string | null;
    status: StatusRingkas;
}

/** Profil setiap jalur pada skala mutlak yang sama (SkalaProfil::mutlakBersama), 0..1. */
export interface ProfilGunung {
    garis: Array<{ id: number; nama: string; titik: Array<[number, number]> }>;
    keterangan: string;
}

export interface IzinGunung {
    pengelola: string;
    jendela: string | null;
    kuota: string | null;
    pemandu: string | null;
    catatan: string | null;
    bookingUrl: string | null;
    sumber: SumberKeterangan;
    diverifikasi: string | null;
}

export interface LaporanGunung {
    id: number;
    jalur: string;
    tags: string[];
    catatan: string | null;
    dilaporkan: string;
    tanggalPendakian: string;
    kesegaran: string;
}

export interface Provenans {
    sumber: SumberKeterangan | null;
    diverifikasi: string | null;
}
```

Buat `resources/js/pages/Mountains/Show.tsx`:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { DaftarJalurGunung } from '@/components/trails/gunung/DaftarJalurGunung';
import { KartuIzin } from '@/components/trails/gunung/KartuIzin';
import { KartuStatusGunung } from '@/components/trails/gunung/KartuStatusGunung';
import { KondisiPendaki } from '@/components/trails/gunung/KondisiPendaki';
import type { GunungRingkas, IzinGunung, JalurGunung, LaporanGunung, ProfilGunung, Provenans } from '@/components/trails/gunung/types';
import { VisualProfilGunung } from '@/components/trails/gunung/VisualProfilGunung';
import type { StatusRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { follow } from '@/routes/mountains';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    gunung: GunungRingkas;
    mengikuti: boolean;
    bolehDiikuti: boolean;
    status: StatusRingkas;
    jalur: JalurGunung[];
    jumlahMenunggu: number;
    profil: ProfilGunung | null;
    izin: IzinGunung | null;
    kondisi: LaporanGunung[];
    provenans: Provenans;
    kataBelumDinilai: string;
    pengumuman: string;
}

const judulBagian = 'text-bagian text-primary';

/**
 * Halaman gunung (spec v3 bagian 6.3, DK-44, prototype 03). Gunung membuka jalurnya
 * (R-001): keputusan tetap per jalur (R-002), jadi halaman ini menyajikan status tingkat
 * gunung, pilihan jalur dengan label dan status masing-masing, izin, kondisi dari pendaki,
 * dan asal-usul datanya. Tanpa foto berlisensi (DK-37, W8) kepala halamannya visual data:
 * profil setiap jalur pada skala yang sama.
 */
export default function Show() {
    const { gunung, mengikuti, bolehDiikuti, status, jalur, jumlahMenunggu, profil, izin, kondisi, provenans, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const [memproses, setMemproses] = useState(false);

    const ikuti = () => {
        setMemproses(true);
        router.post(follow.url({ mountain: gunung.id }), { ya: !mengikuti }, { preserveScroll: true, onFinish: () => setMemproses(false) });
    };

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6">
                <PageHeader
                    induk={{ href: trailsIndex.url(), label: 'Jelajah' }}
                    title={gunung.nama}
                    aksi={
                        bolehDiikuti ? (
                            <Button variant={mengikuti ? 'primary' : 'secondary'} size="sm" aria-pressed={mengikuti} processing={memproses} onClick={ikuti}>
                                <Icon name="bell" size={16} />
                                {mengikuti ? 'Diikuti' : 'Ikuti kabar'}
                            </Button>
                        ) : undefined
                    }
                >
                    <p className="mt-1 text-kecil text-secondary">{[gunung.provinsi, `waktu dalam ${gunung.zona}`].filter(Boolean).join(', ')}</p>
                </PageHeader>

                {profil ? <VisualProfilGunung profil={profil} /> : null}

                <section aria-labelledby="judul-puncak" className="rounded-lg border border-subtle bg-surface p-5 sm:flex sm:items-center sm:gap-6">
                    <div className="shrink-0">
                        <h2 id="judul-puncak" className="text-meta font-bold text-secondary">
                            Puncak
                        </h2>
                        <p data-angka="" className="text-judul-kartu text-primary">
                            {gunung.puncak ?? 'Belum tercatat'}
                        </p>
                    </div>
                    <p className="mt-3 text-kecil text-secondary sm:mt-0 sm:border-l sm:border-subtle sm:pl-6">
                        Ketinggian hanya keterangan. Berat-ringannya pendakian dinilai per jalur dari jarak, tanjakan, durasi, medan, dan navigasi.
                    </p>
                </section>

                <section aria-labelledby="judul-status" className="space-y-3">
                    <h2 id="judul-status" className={judulBagian}>
                        Status saat ini
                    </h2>
                    <KartuStatusGunung status={status} />
                </section>

                <section aria-labelledby="judul-jalur" className="space-y-3">
                    <div>
                        <h2 id="judul-jalur" className={judulBagian}>
                            Pilih jalur <span data-angka="" className="text-muted">{jalur.length}</span>
                        </h2>
                        <p className="mt-1 text-kecil text-secondary">
                            Keputusan dibuat per jalur. Label menunjukkan kecocokan dasar dengan profil Anda, status tertulis untuk tiap jalur.
                        </p>
                    </div>
                    {jalur.length === 0 ? (
                        <EmptyState
                            jenis="menunggu"
                            title="Belum ada jalur terbit di gunung ini"
                            menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"
                            description="Jalur tampil di sini setelah sumber, jarak, tanjakan, durasi, pos, dan status resminya tercatat."
                        />
                    ) : (
                        <DaftarJalurGunung jalur={jalur} kataBelumDinilai={kataBelumDinilai} />
                    )}
                    {jumlahMenunggu > 0 && jalur.length > 0 ? (
                        <p className="text-kecil text-secondary">
                            <span data-angka="">{jumlahMenunggu}</span> jalur lain di gunung ini dikenali sistem, tetapi datanya belum lengkap. Menunggu kurator Memount.
                        </p>
                    ) : null}
                </section>

                <section aria-labelledby="judul-izin" className="space-y-3">
                    <h2 id="judul-izin" className={judulBagian}>
                        Izin pendakian
                    </h2>
                    {izin ? (
                        <KartuIzin izin={izin} />
                    ) : (
                        <EmptyState
                            jenis="menunggu"
                            title="Aturan izin gunung ini belum tercatat"
                            menunggu="Menunggu kurator Memount mencatat aturan pengelola"
                            description="Periksa ketentuan izin langsung ke pengelola kawasan sebelum berangkat."
                        />
                    )}
                </section>

                <section aria-labelledby="judul-kondisi" className="space-y-3">
                    <h2 id="judul-kondisi" className={judulBagian}>
                        Kondisi dari pendaki
                    </h2>
                    {kondisi.length === 0 ? (
                        <p className="text-kecil text-secondary">Belum ada laporan pendaki yang lolos moderasi dalam 30 hari terakhir.</p>
                    ) : (
                        <KondisiPendaki laporan={kondisi} />
                    )}
                    <p className="text-meta text-secondary">Laporan komunitas melengkapi status resmi, tidak menggantikannya.</p>
                </section>

                <footer className="border-t border-subtle pt-4 text-meta text-muted">
                    <p>
                        Data gunung: {provenans.sumber ? `${provenans.sumber.nama ?? 'sumber tanpa nama'}${provenans.sumber.label ? ` (${provenans.sumber.label.toLowerCase()})` : ''}` : 'sumber belum tercatat'}
                        {provenans.diverifikasi ? `, diverifikasi ${provenans.diverifikasi}` : null}.
                    </p>
                    <p className="mt-1">Foto: belum ada foto gunung berlisensi.{profil ? ' Visual di atas digambar dari profil elevasi jalurnya.' : null}</p>
                </footer>

                <p role="status" className="sr-only">
                    {pengumuman}
                </p>
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`resources/js/components/trails/StatusAkses.tsx`:

1. Ganti:

```tsx
    cakupan?: string | null;
```

dengan:

```tsx
    cakupan?: string | null;
    /** "bagian" untuk kartu status utama (18 px); bawaan 14 px untuk baris daftar. */
    ukuran?: 'kecil' | 'bagian';
```

2. Ganti:

```tsx
export function StatusAkses({ nilai, label, cakupan, className }: StatusAksesProps) {
    return (
        <span data-status-akses={nilai ?? 'UNKNOWN'} className={kelas('inline-flex max-w-full items-center gap-1.5 text-kecil font-bold text-primary', className)}>
```

dengan:

```tsx
export function StatusAkses({ nilai, label, cakupan, ukuran = 'kecil', className }: StatusAksesProps) {
    return (
        <span
            data-status-akses={nilai ?? 'UNKNOWN'}
            className={kelas('inline-flex max-w-full items-center gap-1.5 font-bold text-primary', ukuran === 'bagian' ? 'text-bagian' : 'text-kecil', className)}
        >
```

`resources/js/components/trails/types.ts`:

1. Ganti:

```ts
export interface FitRingkas {
```

dengan:

```ts
import type { SumberKeterangan } from './LabelSumber';
import type { StatusResmi } from './StatusAkses';

export interface FitRingkas {
```

2. Ganti:

```ts
/** Bentuk yang dikirim TrailRowResource: angka sudah diformat di server. */
```

dengan:

```ts
/**
 * Status akses satu jalur beserta asalnya (App\Services\Trails\StatusJalurRingkas): nilai,
 * kata, cakupan catatan penentunya, jenis dan nama sumbernya, tanggal pendek dalam zona
 * gunung, dan kesegarannya. Tanpa catatan yang berlaku, status null dan sumber null.
 */
export interface StatusRingkas {
    status: StatusResmi;
    label: string;
    cakupan: string | null;
    sumber: SumberKeterangan | null;
    waktu: string | null;
    kesegaran: string | null;
}

/** Bentuk yang dikirim TrailRowResource: angka sudah diformat di server. */
```

`resources/js/components/ui/ikon/phosphor.ts`:

1. Ganti:

```ts
    | 'arrow-left'
```

dengan:

```ts
    | 'arrow-left'
    | 'arrow-square-out'
```

2. Ganti:

```ts
    | 'bookmark-simple'
```

dengan:

```ts
    | 'bookmark-simple'
    | 'caret-right'
```

3. Ganti:

```ts
    'arrow-up': {
```

dengan:

```ts
    'arrow-square-out': {
        regular: 'M224,104a8,8,0,0,1-16,0V59.32l-66.33,66.34a8,8,0,0,1-11.32-11.32L196.68,48H152a8,8,0,0,1,0-16h64a8,8,0,0,1,8,8Zm-40,24a8,8,0,0,0-8,8v72H48V80h72a8,8,0,0,0,0-16H48A16,16,0,0,0,32,80V208a16,16,0,0,0,16,16H176a16,16,0,0,0,16-16V136A8,8,0,0,0,184,128Z',
        bold: 'M228,104a12,12,0,0,1-24,0V69l-59.51,59.51a12,12,0,0,1-17-17L187,52H152a12,12,0,0,1,0-24h64a12,12,0,0,1,12,12Zm-44,24a12,12,0,0,0-12,12v64H52V84h64a12,12,0,0,0,0-24H48A20,20,0,0,0,28,80V208a20,20,0,0,0,20,20H176a20,20,0,0,0,20-20V140A12,12,0,0,0,184,128Z',
    },
    'arrow-up': {
```

4. Ganti:

```ts
        bold: 'M184,28H72A20,20,0,0,0,52,48V224a12,12,0,0,0,18.36,10.18l57.63-36,57.65,36A12,12,0,0,0,204,224V48A20,20,0,0,0,184,28Zm-4,174.35-45.65-28.53a12,12,0,0,0-12.72,0L76,202.35V52H180Z',
```

dengan:

```ts
        bold: 'M184,28H72A20,20,0,0,0,52,48V224a12,12,0,0,0,18.36,10.18l57.63-36,57.65,36A12,12,0,0,0,204,224V48A20,20,0,0,0,184,28Zm-4,174.35-45.65-28.53a12,12,0,0,0-12.72,0L76,202.35V52H180Z',
    },
    'caret-right': {
        regular: 'M181.66,133.66l-80,80a8,8,0,0,1-11.32-11.32L164.69,128,90.34,53.66a8,8,0,0,1,11.32-11.32l80,80A8,8,0,0,1,181.66,133.66Z',
        bold: 'M184.49,136.49l-80,80a12,12,0,0,1-17-17L159,128,87.51,56.49a12,12,0,1,1,17-17l80,80A12,12,0,0,1,184.49,136.49Z',
```

`resources/js/layouts/NavLinks.tsx`:

1. Ganti:

```tsx
 * aktif dihitung dari path, satu tab per halaman.
```

dengan:

```tsx
 * aktif dihitung dari path, satu tab per halaman. Halaman gunung milik Jelajah (spec v3
 * bagian 5.3): path-nya di luar /trails, jadi disebut sendiri.
```

2. Ganti:

```tsx
        aktif: (p: string) => (p === '/trails' || p.startsWith('/trails/')) && !p.startsWith('/trails/compare'),
```

dengan:

```tsx
        aktif: (p: string) => ((p === '/trails' || p.startsWith('/trails/')) && !p.startsWith('/trails/compare')) || p.startsWith('/mountains/'),
```

- [ ] **Step 4: Bangkitkan rute bertipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan wayfinder:generate`
Expected: tanpa galat. `resources/js/routes/mountains` memuat `show`; berkas bangkitan diabaikan git.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='MountainPageTest|DemoSeederTest|NavigationConsistency|LockedBusinessRules'`
Expected: PASS: 41 test, 41 lulus.

- [ ] **Step 6: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.643 test, 1.641 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_gunung`. Pada simulasi rencana: 10 test, 10 lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Penilaian kecocokan

Induk W3 butir 4; spec bagian 6.5; ruling W3-R10 sampai W3-R12. `GET /trails/{trail:slug}/kecocokan` dengan `?trip=`
opsional milik pembaca, lewat kelas halaman `PenilaianKecocokan`. Halamannya memuat:

- label dan definisinya dari `RouteFitLabel`;
- "Dinilai dari" (profil yang diisi sendiri dan rencana trip, masing-masing dengan "Ubah");
- lapis 1 syarat dasar dengan kalimat "Bila satu saja gagal...";
- tujuh faktor diurut bobot `recommendation_rules`, dengan bobot dalam kata dan keadaan per faktor (Sesuai, Perlu
  perhatian, Belum diketahui), serta rincian "Jalur ini" lawan "Anda";
- "Kenapa bukan Cocok?" dari faktornya dan "Yang bisa Anda lakukan";
- versi aturan dan waktu penilaian.

Tidak ada angka skor atau bobot di props. `FactorBars` lama pindah menjadi `FaktorKecocokan` (keputusan nomor 4). Halaman
ini halaman tugas tanpa bilah tab yang kembali ke rincian jalurnya.

**Files:**
- Create: `app/Http/Controllers/Trails/TrailFitAssessmentController.php`, `app/Services/Trails/PenilaianKecocokan.php`, `resources/js/components/trails/FaktorKecocokan.tsx`, `resources/js/components/trails/kecocokan/DinilaiDari.tsx`, `resources/js/components/trails/kecocokan/KepalaPenilaian.tsx`, `resources/js/components/trails/kecocokan/LangkahBerikut.tsx`, `resources/js/components/trails/kecocokan/SyaratDasar.tsx`, `resources/js/components/trails/kecocokan/types.ts`, `resources/js/pages/Trails/Fit.tsx`
- Modify: `resources/js/components/discovery/PanelAlasan.tsx`, `resources/js/components/discovery/types.ts`, `resources/js/components/trails/rinci/types.ts`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/pages/Trails/Show.tsx`, `routes/web.php`
- Delete: `resources/js/components/ui/FactorBars.tsx`
- Test: `tests/Feature/FitAssessmentPageTest.php` (baru), `tests/Feature/FactorBarsTest.php`, `tests/Feature/NavigationConsistencyTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `CompatibilityScorer`, `TrailFitService`, `RecommendationExplanationService`, `RouteFitLabel`, tabel `recommendation_rules` (sudah ada); `PreparationService` untuk butir kritis trip; Policy `TripPlan` (`view`); `LabelKecocokan` (Tugas 1); `PageHeader`, `BarAksi`, `AppLayout bilahTab={false}` (W2).
- Produces:
  - rute `trails.fit` (`GET trails/{trail:slug}/kecocokan`, grup `auth`) ke `App\Http\Controllers\Trails\TrailFitAssessmentController` (invokable);
  - `App\Services\Trails\PenilaianKecocokan::props(Trail $trail, User $pembaca, ?TripPlan $trip): array` dengan kunci `jalur`, `aksi`, `penilaian`, `dinilaiDari`, `syaratDasar`, `faktor`, `hitungan`, `kenapaBukanCocok`, `yangBisaDilakukan`, `dinilai`, `aturan`; controller menambah `title`;
  - `resources/js/components/trails/FaktorKecocokan.tsx`: `FaktorKecocokan({ faktor, className })`, `type KategoriFaktor = 'kuat' | 'cukup' | 'lemah' | 'tak-diketahui'`, `interface FaktorBaris`;
  - komponen `resources/js/components/trails/kecocokan/` (`DinilaiDari`, `KepalaPenilaian`, `LangkahBerikut`, `SyaratDasar`) dan tipenya;
  - halaman `resources/js/pages/Trails/Fit.tsx`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/FitAssessmentPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\RecommendationRule;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\RouteFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman penilaian kecocokan (spec v3 bagian 6.5 dan 9.3, W3 Tugas 3). Pola penjaganya sama
 * dengan FactorBarsTest dan TrailDetailPageTest: skor dan bobot berhenti di server (BR-09,
 * R-005), faktor yang datanya belum ada tidak terbaca rendah (R-006), dan trip di URL hanya
 * boleh milik pembacanya (PRD §77).
 */
class FitAssessmentPageTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    private function pendaki(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 3, 'terrain_experience' => ['FOREST'], 'highest_elevation_gain_m' => 700]);

        return $user->fresh();
    }

    private function jalur(array $atribut = []): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create($atribut);
    }

    private function trip(User $user, Trail $trail): TripPlan
    {
        return TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Rencana uji',
            'planned_date' => now()->addDays(15)->toDateString(),
            'end_date' => now()->addDays(16)->toDateString(),
            'participants_count' => 3,
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
    }

    private function halaman(User $user, Trail $trail, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get(route('trails.fit', ['trail' => $trail->slug] + $query));
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get(route('trails.fit', $this->jalur()))->assertRedirect(route('login'));
    }

    /**
     * R-022: jalur draf dan arsip tidak dinilai di halaman ini.
     */
    public function test_only_a_published_trail_is_assessed(): void
    {
        $draf = Trail::factory()->for(Mountain::factory()->create())->unpublished()->create();
        $arsip = $this->jalur(['archived_at' => now()]);

        $this->halaman($this->pendaki(), $draf)->assertNotFound();
        $this->halaman($this->pendaki(), $arsip)->assertNotFound();
    }

    /**
     * BR-09, R-005: tidak ada skor, bobot, atau angka pecahan di props; bobot hanya sampai
     * sebagai kata pengaruh.
     */
    public function test_no_score_or_weight_reaches_the_page(): void
    {
        $respons = $this->halaman($this->pendaki(), $this->jalur())->assertOk();
        $props = $respons->viewData('page')['props'];

        $this->assertCount(7, $props['faktor']);

        foreach ($props['faktor'] as $faktor) {
            $this->assertSame(['kunci', 'label', 'detail', 'kategori', 'pengaruh', 'jalurIni', 'anda'], array_keys($faktor));
            $this->assertContains($faktor['pengaruh'], ['besar', 'sedang', 'kecil']);
        }

        array_walk_recursive($props, fn ($nilai, $kunci) => $this->assertIsNotFloat($nilai, "Nilai pecahan di props ({$kunci})."));

        foreach (['internalScore', 'internal_score', '"score"', '"weight"', 'probab'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $respons->getContent());
        }
    }

    /**
     * Tujuh faktor diurut menurut bobot dari recommendation_rules, seri diputus urutan enum;
     * kata pengaruh besar ≥0,25, sedang ≥0,12, kecil di bawahnya (spec v3 bagian 6.5).
     */
    public function test_the_factors_follow_the_weights_in_the_rules_table(): void
    {
        $user = $this->pendaki();
        $trail = $this->jalur();

        $bawaan = collect($this->halaman($user, $trail)->viewData('page')['props']['faktor']);

        $this->assertSame(['Pengalaman', 'Durasi', 'Medan', 'Tuntutan teknis', 'Tanjakan', 'Navigasi', 'Jenis trip'], $bawaan->pluck('label')->all());
        $this->assertSame(['besar', 'sedang', 'sedang', 'sedang', 'kecil', 'kecil', 'kecil'], $bawaan->pluck('pengaruh')->all());

        foreach (['experience_match' => 0.10, 'navigation_match' => 0.30] as $kunci => $bobot) {
            RecommendationRule::create(['key' => $kunci, 'category' => 'compatibility', 'weight' => $bobot, 'active' => true, 'engine_version' => RouteFitService::ENGINE_VERSION]);
        }

        $diubah = collect($this->halaman($user, $trail)->viewData('page')['props']['faktor']);

        $this->assertSame('Navigasi', $diubah->first()['label']);
        $this->assertSame('besar', $diubah->first()['pengaruh']);
    }

    /**
     * R-006: faktor yang datanya belum ada tertulis "Belum diketahui", tidak pernah perlu
     * perhatian dan tidak pernah sesuai.
     */
    public function test_an_unknown_factor_reads_unknown_not_weak(): void
    {
        $props = $this->halaman($this->pendaki(), $this->jalur(['terrain_character' => null]))->viewData('page')['props'];
        $medan = collect($props['faktor'])->firstWhere('label', 'Medan');

        $this->assertSame('tak-diketahui', $medan['kategori']);
        $this->assertSame('Belum diketahui', $medan['jalurIni']);
        $this->assertGreaterThanOrEqual(1, $props['hitungan']['belumDiketahui']);

        $baris = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/FaktorKecocokan.tsx')));
        $this->assertStringContainsString("'tak-diketahui': { kata: 'Belum diketahui', ikon: 'question'", $baris);
    }

    public function test_another_hikers_trip_is_refused(): void
    {
        $trail = $this->jalur();
        $orangLain = $this->trip(User::factory()->create(), $trail);

        $this->halaman($this->pendaki(), $trail, ['trip' => $orangLain->id])->assertForbidden();
    }

    public function test_a_trip_for_another_trail_is_not_found(): void
    {
        $user = $this->pendaki();
        $trip = $this->trip($user, $this->jalur());

        $this->halaman($user, $this->jalur(), ['trip' => $trip->id])->assertNotFound();
    }

    /**
     * Dengan trip milik sendiri penilaiannya untuk rencana itu: bahan rencananya disebut,
     * butir persiapan kritis yang belum dikonfirmasi menjadi langkah, dan aksi utamanya
     * melanjutkan persiapan.
     */
    public function test_the_own_trip_is_assessed_for_its_plan(): void
    {
        $user = $this->pendaki();
        $trail = $this->jalur();
        $trip = $this->trip($user, $trail);

        TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Headlamp cadangan', 'is_critical' => true, 'status' => PreparationStatus::NOT_CONFIRMED->value]);
        TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Jas hujan', 'is_critical' => true, 'status' => PreparationStatus::CONFIRMED->value]);

        $this->halaman($user, $trail, ['trip' => $trip->id])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trails/Fit')
            ->where('penilaian.denganRencana', true)
            ->where('dinilaiDari.rencana', fn (string $rencana) => str_contains($rencana, 'Camping') && str_contains($rencana, '3 orang'))
            ->where('yangBisaDilakukan.0.judul', 'Selesaikan 1 butir persiapan kritis')
            ->where('yangBisaDilakukan.0.keterangan', 'Headlamp cadangan')
            ->where('aksi.label', 'Lanjutkan persiapan'));
    }

    /**
     * Pembaca tanpa profil tidak dinilai diam-diam; yang ditawarkan jalan mengisi profil.
     */
    public function test_a_reader_without_a_profile_is_invited_not_judged(): void
    {
        $this->halaman(User::factory()->create(), $this->jalur())->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('penilaian', null)
            ->missing('faktor'));

        $this->assertStringContainsString('Isi profil pendaki', File::get(resource_path('js/pages/Trails/Fit.tsx')));
    }

    /**
     * "Kenapa bukan Cocok?" disusun dari keadaan faktor dengan aturan tertulis: jumlah faktor
     * yang perlu perhatian, faktor berpengaruh besar di antaranya, dan akibatnya pada label.
     */
    public function test_why_not_cocok_is_written_from_the_factors(): void
    {
        $props = $this->halaman($this->pendaki(), $this->jalur(['elevation_gain_m' => 1650, 'distance_km' => 9.7, 'estimated_duration_minutes' => 450]))->viewData('page')['props'];

        $this->assertNotSame('COCOK', $props['penilaian']['label']);
        $this->assertStringContainsString('faktor perlu perhatian', $props['kenapaBukanCocok']);
        $this->assertMatchesRegularExpression('/(Kurang cocok, berapa pun faktor lainnya|tetap relevan asal Anda bersiap|belum cukup untuk Perlu persiapan)\.$/', $props['kenapaBukanCocok']);
    }

    /**
     * Anggaran kueri: halaman tidak tumbuh bersama simpanan dan butir persiapan pembacanya.
     */
    public function test_the_page_does_not_grow_with_saved_trails_or_preparation_items(): void
    {
        $user = $this->pendaki();
        $trail = $this->jalur();
        $trip = $this->trip($user, $trail);
        $this->actingAs($user);
        $url = route('trails.fit', ['trail' => $trail->slug, 'trip' => $trip->id]);

        // Satu simpanan dan satu butir lebih dulu: relasi yang dimuat di muka baru menagih
        // kuerinya ketika barisnya ada, jadi nol lawan enam bukan ukuran pertumbuhan.
        app(ConsiderationService::class)->toggle($user, $this->jalur());
        TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Butir awal', 'is_critical' => true, 'status' => PreparationStatus::NOT_CONFIRMED->value]);

        $this->get($url)->assertOk();
        $sedikit = $this->hitung($url);

        foreach (range(1, 6) as $i) {
            app(ConsiderationService::class)->toggle($user, $this->jalur());
            TripPreparationItem::create(['trip_plan_id' => $trip->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Butir '.$i, 'is_critical' => true, 'status' => PreparationStatus::NOT_CONFIRMED->value]);
        }

        $banyak = $this->hitung($url);

        $this->assertSame($sedikit, $banyak, "Halaman penilaian tumbuh: {$sedikit} lalu {$banyak} kueri.");
    }

    /**
     * Halaman tugas dengan bar aksi (spec v3 bagian 5.1): bilah tab disembunyikan, tombol
     * kembali menuju jalurnya, dan BR-11 tertulis.
     */
    public function test_the_page_is_a_task_page_that_returns_to_its_trail(): void
    {
        $halaman = $this->tanpaKomentarJs(File::get(resource_path('js/pages/Trails/Fit.tsx')));

        $this->assertStringContainsString('Fit.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;', $halaman);
        $this->assertStringContainsString('<PageHeader induk={{ href: jalur.url, label: jalur.nama }} title="Penilaian kecocokan">', $halaman);
        $this->assertStringContainsString('<BarAksi label="Aksi penilaian kecocokan">', $halaman);
        $this->assertStringContainsString('Label tidak naik hanya karena Anda menyelesaikan pendakian.', $halaman);
    }

    private function hitung(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
```

`tests/Feature/FactorBarsTest.php`:

1. Ganti:

```php
 * Penalaran mesin Route Fit sebagai batang.
```

dengan:

```php
 * Penalaran mesin Route Fit sebagai keadaan per faktor.
```

2. Ganti:

```php
 * kategori sebelum apa pun dikirim ke klien, dan FactorBars.tsx hanya menggambar
 * kategorinya. Test ini menjaga keduanya.
```

dengan:

```php
 * kategori sebelum apa pun dikirim ke klien, dan klien hanya menggambar kategorinya. Test
 * ini menjaga keduanya.
 *
 * Diperbarui W3 (spec v3 bagian 6.5, prototype 05): FactorBars.tsx dipindah dari
 * components/ui dan ditulis ulang menjadi components/trails/FaktorKecocokan.tsx. Batang
 * diganti baris keadaan berkata (Sesuai, Perlu perhatian, Belum diketahui) berikon; niatnya
 * tetap: tanpa skor dalam bentuk apa pun, tiga kategori dari ambang mesin, faktor tanpa data
 * tidak terbaca lemah, dan arti tidak dibawa warna saja. "Sesuai" hanya untuk kategori kuat,
 * karena kalimat rincian mesin dipilih dengan ambang kuat yang sama; cukup dan lemah
 * sama-sama "Perlu perhatian", juga di panel alasan rekomendasi.
```

3. Ganti:

```php
        return File::get(resource_path('js/components/ui/FactorBars.tsx'));
```

dengan:

```php
        return File::get(resource_path('js/components/trails/FaktorKecocokan.tsx'));
```

4. Ganti:

```php
        $this->assertStringContainsString('{f.detail ? <p', $batang);
```

dengan:

```php
        $this->assertStringContainsString('{f.detail ? <span', $batang);
```

5. Ganti:

```php
        // Lebar batang adalah salah satu dari tiga kelas tetap, bukan angka dari data.
        // Komentarnya boleh menyebut "73%" untuk menjelaskan mengapa; kodenya tidak.
        $batang = $this->batang();
        $kode = $this->tanpaKomentarJs($batang);
        $this->assertStringNotContainsString('style=', $kode);
        $this->assertStringNotContainsString('%', $kode);
        $this->assertStringContainsString("const lebar: Record<KategoriFaktor, string> = { kuat: 'w-full', cukup: 'w-2/3', lemah: 'w-1/3', 'tak-diketahui': 'w-full' };", $batang);
```

dengan:

```php
        // Tidak ada lebar, gaya, atau persen yang dapat dibaca balik menjadi angka: keadaan
        // hanya satu dari tiga kata tetap.
        $kode = $this->tanpaKomentarJs($this->batang());
        $this->assertStringNotContainsString('style=', $kode);
        $this->assertStringNotContainsString('%', $kode);
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])w-(?:\d|\[)/', $kode);
```

6. Ganti:

```php
        $this->assertStringContainsString("const kata: Record<KategoriFaktor, string> = { kuat: 'Mendukung', cukup: 'Cukup', lemah: 'Menahan', 'tak-diketahui': 'Belum diketahui' };", $this->batang());
```

dengan:

```php
        $batang = $this->batang();
        $this->assertStringContainsString("kuat: { kata: 'Sesuai', ikon: 'check'", $batang);
        $this->assertStringContainsString("cukup: { kata: 'Perlu perhatian', ikon: 'warning'", $batang);
        $this->assertStringContainsString("lemah: { kata: 'Perlu perhatian', ikon: 'warning'", $batang);
```

7. Ganti:

```php
        $this->assertStringContainsString("'tak-diketahui': 'Belum diketahui'", $batang);
        $this->assertStringNotContainsString("'tak-diketahui': 'w-1/3'", $batang);
        $this->assertStringNotContainsString("'tak-diketahui': 'Menahan'", $batang);
```

dengan:

```php
        $this->assertStringContainsString("'tak-diketahui': { kata: 'Belum diketahui', ikon: 'question'", $batang);
        $this->assertStringNotContainsString("'tak-diketahui': { kata: 'Perlu perhatian'", $batang);
```

8. Ganti:

```php
        // Katanya ada sebagai teks, dan batangnya sendiri disembunyikan dari pembaca
        // layar karena ia hanya mengulang kata yang sudah terbaca di sebelahnya.
        $this->assertStringContainsString('{kata[f.kategori]}</span>', $batang);
        $this->assertMatchesRegularExpression('/<div className="[^"]*" aria-hidden="true">\s*<div className=\{`h-full rounded-full \$\{lebar\[f\.kategori\]\}/', $batang);
```

dengan:

```php
        // Katanya ada sebagai teks, dan lingkaran ikonnya disembunyikan dari pembaca layar
        // karena ia hanya mengulang kata yang sudah terbaca di sebelahnya.
        $this->assertStringContainsString('<span className="text-meta font-bold text-secondary">{k.kata}</span>', $batang);
        $this->assertStringContainsString('<span aria-hidden="true" className={kelas(', $batang);
```

9. Ganti:

```php
        $this->assertMatchesRegularExpression('/if \(factors\.length === 0\) \{\s*return null;\s*\}/', $this->batang());
```

dengan:

```php
        $this->assertMatchesRegularExpression('/if \(faktor\.length === 0\) \{\s*return null;\s*\}/', $this->batang());
```

`tests/Feature/NavigationConsistencyTest.php`:

1. Ganti:

```php
     * detail jalur dan rencanakan trip (penilaian kecocokan menyusul di W3). Halaman lain
     * tetap membawa bilah tab.
```

dengan:

```php
     * detail jalur, penilaian kecocokan (W3), dan rencanakan trip. Halaman lain tetap
     * membawa bilah tab.
```

2. Ganti:

```php
        $this->assertSame(['Trails/Show.tsx', 'Trips/Create.tsx'], $tanpaBilah);
```

dengan:

```php
        $this->assertSame(['Trails/Fit.tsx', 'Trails/Show.tsx', 'Trips/Create.tsx'], $tanpaBilah);
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

Ganti:

```php
    public function test_jelajah_tanpa_jalur_terbit(): void
```

dengan:

```php
    /**
     * Penilaian kecocokan W3 (acuan 05): Gede via Cibodas untuk trip camping Budi, jadi
     * bahan rencananya ikut dinilai dan aksi utamanya melanjutkan persiapan.
     */
    public function test_kecocokan(): void
    {
        $budi = $this->sebagaiBudi();
        $trip = TripPlan::where('user_id', $budi->id)->where('status', TripStatus::PLANNED->value)->sole();

        $this->tulisPratinjau('kecocokan', $this->get(self::ASAL.'/trails/gede-via-cibodas/kecocokan?trip='.$trip->id), 'Trails/Fit');
    }

    public function test_jelajah_tanpa_jalur_terbit(): void
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='FitAssessmentPage|FactorBars|NavigationConsistency|AmbientFit|TrailDetailPage'`
Expected: FAIL: 60 test, 41 lulus, 1 gagal, 18 error. Antara lain `NavigationConsistencyTest::test_only_task_pages_hide_the_tab_bar`, `FactorBarsTest::test_each_factor_is_named_with_its_reason`, `FactorBarsTest::test_the_score_never_reaches_the_page_in_any_form`.

- [ ] **Step 3: Tulis implementasinya.**

**Data, rute, dan konfigurasi**

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Trails\TrailDetailController;
```

dengan:

```php
use App\Http\Controllers\Trails\TrailDetailController;
use App\Http\Controllers\Trails\TrailFitAssessmentController;
```

2. Ganti:

```php
    Route::get('trails/{trail:slug}', TrailDetailController::class)->name('trails.show');
```

dengan:

```php
    Route::get('trails/{trail:slug}', TrailDetailController::class)->name('trails.show');
    // Penilaian kecocokan satu jalur (spec v3 bagian 6.5), opsional ?trip= milik pembaca.
    Route::get('trails/{trail:slug}/kecocokan', TrailFitAssessmentController::class)->name('trails.fit');
```

**Server**

Buat `app/Http/Controllers/Trails/TrailFitAssessmentController.php`:

```php
<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Services\Trails\PenilaianKecocokan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Penilaian kecocokan satu jalur (spec v3 bagian 6.5 dan 9.3), opsional untuk satu trip
 * (`?trip=`). Hanya jalur terbit yang dinilai (R-022); jalur draf dan arsip menjawab 404.
 *
 * Trip dibaca dari URL, jadi kepemilikannya diperiksa Policy (PRD §77): trip orang lain
 * ditolak 403 seperti halaman trip itu sendiri, dan trip untuk jalur lain menjawab 404
 * karena penilaiannya bukan tentang jalur di URL.
 */
class TrailFitAssessmentController extends Controller
{
    public function __invoke(Request $request, Trail $trail, PenilaianKecocokan $halaman): Response
    {
        abort_if($trail->archived_at !== null || ! $trail->is_published, 404);

        $trail->load('mountain');
        $trip = null;

        if ($request->query->has('trip')) {
            $trip = TripPlan::query()->findOrFail($request->integer('trip'));
            $this->authorize('view', $trip);
            abort_unless($trip->trail_id === $trail->id, 404);
        }

        return Inertia::render('Trails/Fit', [
            'title' => 'Penilaian kecocokan '.$trail->name,
        ] + $halaman->props($trail, $request->user(), $trip));
    }
}
```

Buat `app/Services/Trails/PenilaianKecocokan.php`:

```php
<?php

namespace App\Services\Trails;

use App\Enums\CompatibilityFactor;
use App\Enums\PreparationStatus;
use App\Enums\RouteFitLabel;
use App\Enums\TerrainCharacter;
use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\OfficialStatusService;
use App\Services\RouteFit\CompatibilityScorer;
use App\Services\RouteFit\FactorScore;
use App\Services\RouteFit\RouteFitResult;
use App\Services\RouteFitService;
use App\Support\Durasi;
use App\Support\FaktorKategori;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

/**
 * Props halaman penilaian kecocokan (spec v3 bagian 6.5, prototype 05): mengapa sebuah jalur
 * berlabel seperti itu untuk pendaki ini, dalam kata-kata, tanpa satu pun angka skor atau
 * bobot (BR-09, R-005).
 *
 * Tiga lapis mesin tampil sebagai tiga bagian: syarat dasar (lapis 1), tujuh faktor diurut
 * menurut bobotnya (lapis 2 dan 3), dan kalimat "Kenapa bukan Cocok?" yang disusun dari
 * keadaan faktor dengan aturan tertulis, bukan AI. Bobot hanya sampai ke klien sebagai kata
 * pengaruh (besar, sedang, kecil) yang dibaca dari recommendation_rules; keadaan faktor
 * memakai ambang yang sama dengan penjelasan mesin (FaktorKategori). Faktor yang datanya
 * belum ada tertulis "Belum diketahui", tidak pernah "Perlu perhatian" (R-006).
 */
final class PenilaianKecocokan
{
    /** Ambang kata pengaruh (spec v3 bagian 6.5): besar ≥0,25, sedang ≥0,12, kecil di bawahnya. */
    private const PENGARUH_BESAR = 0.25;

    private const PENGARUH_SEDANG = 0.12;

    private const FAKTOR_KRITIS = [
        CompatibilityFactor::EXPERIENCE_MATCH,
        CompatibilityFactor::TECHNICAL_MATCH,
        CompatibilityFactor::NAVIGATION_MATCH,
    ];

    public function __construct(
        private readonly RouteFitService $mesin,
        private readonly CompatibilityScorer $penilai,
        private readonly OfficialStatusService $statusResmi,
        private readonly ConsiderationService $simpanan,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(Trail $trail, User $pembaca, ?TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trail);
        $dasar = [
            'jalur' => [
                'id' => $trail->id,
                'nama' => $trail->name,
                'url' => route('trails.show', $trail),
                'gunung' => $trail->mountain->name,
                'gunungUrl' => route('mountains.show', $trail->mountain),
            ],
            'aksi' => $trip !== null
                ? ['label' => 'Lanjutkan persiapan', 'url' => route('trips.preparation', $trip)]
                : ['label' => 'Rencanakan trip', 'url' => route('trips.create', ['trail' => $trail->id])],
        ];

        if (! $pembaca->hasCompletedProfile()) {
            return $dasar + ['penilaian' => null];
        }

        $pembaca->loadMissing(['profile', 'experience', 'preference']);
        $goal = $trip === null ? null : ($trip->hikingGoal ?? $this->tujuanDariTrip($trip));
        $hasil = $this->mesin->evaluate($pembaca, $goal, $trail);
        $bobot = $this->mesin->weights();
        $faktor = $this->faktor($hasil, $bobot, $trail, $pembaca, $goal);

        return $dasar + [
            'penilaian' => [
                'label' => $hasil->label?->value,
                'labelText' => RouteFitLabel::textFor($hasil->label),
                'definisi' => $hasil->label?->description(),
                'layak' => $hasil->eligible,
                'denganRencana' => $goal !== null,
                'alasanTidakDinilai' => array_map($this->kalimatSyarat(...), $hasil->failedRules),
            ],
            'dinilaiDari' => [
                'profil' => $this->ringkasProfil($pembaca),
                'profilUrl' => route('profile'),
                'rencana' => $trip === null ? null : $this->ringkasRencana($trip),
                'rencanaUrl' => $trip === null ? null : route('trips.edit', $trip),
            ],
            'syaratDasar' => $this->syaratDasar($hasil, $trail, $goal),
            'faktor' => $faktor,
            'hitungan' => [
                'sesuai' => count(array_filter($faktor, fn (array $f) => $f['kategori'] === 'kuat')),
                'perluPerhatian' => count(array_filter($faktor, fn (array $f) => in_array($f['kategori'], ['cukup', 'lemah'], true))),
                'belumDiketahui' => count(array_filter($faktor, fn (array $f) => $f['kategori'] === 'tak-diketahui')),
            ],
            'kenapaBukanCocok' => $this->kenapaBukanCocok($hasil, $faktor),
            'yangBisaDilakukan' => $this->langkah($trail, $pembaca, $trip),
            'dinilai' => Timezone::display(Carbon::now(), $zona, 'j M Y, H.i'),
            'aturan' => 'aturan Route Fit '.str_replace('route-fit-', '', RouteFitService::ENGINE_VERSION),
        ];
    }

    /**
     * Trip lama yang dibuat tanpa tujuan pendakian tetap dinilai untuk rencananya: jenis dan
     * tanggal tripnya dipakai sebagai tujuan sementara yang tidak pernah disimpan.
     */
    private function tujuanDariTrip(TripPlan $trip): HikingGoal
    {
        return new HikingGoal([
            'trip_type' => $trip->trip_type,
            'target_date' => $trip->planned_date,
        ]);
    }

    /**
     * Tujuh faktor, diurut menurut bobotnya dari yang paling berpengaruh; seri diputus urutan
     * enum supaya urutannya tetap. Setiap baris membawa kategori (ambang FaktorKategori), kata
     * pengaruh, rincian mesin, dan perbandingan "Jalur ini" lawan "Anda" dalam kata.
     *
     * @param  array<string, float>  $bobot
     * @return array<int, array{kunci: string, label: string, detail: string|null, kategori: string, pengaruh: string, jalurIni: string, anda: string}>
     */
    private function faktor(RouteFitResult $hasil, array $bobot, Trail $trail, User $pembaca, ?HikingGoal $goal): array
    {
        $kategori = FaktorKategori::dari($hasil->factorsToArray());
        $urutan = array_flip(array_map(fn (CompatibilityFactor $f) => $f->value, CompatibilityFactor::cases()));
        $baris = [];

        foreach ($hasil->factors as $i => $faktor) {
            $berat = $bobot[$faktor->factor->value] ?? $faktor->factor->defaultWeight();

            $baris[] = [
                'kunci' => $faktor->factor->value,
                'label' => $this->nama($faktor->factor),
                'detail' => $kategori[$i]['detail'],
                'kategori' => $kategori[$i]['kategori'],
                'pengaruh' => $berat >= self::PENGARUH_BESAR ? 'besar' : ($berat >= self::PENGARUH_SEDANG ? 'sedang' : 'kecil'),
                'jalurIni' => $this->jalurIni($faktor->factor, $trail),
                'anda' => $this->anda($faktor->factor, $pembaca, $goal),
                '_berat' => $berat,
                '_urut' => $urutan[$faktor->factor->value],
            ];
        }

        usort($baris, fn (array $a, array $b) => [$b['_berat'], $a['_urut']] <=> [$a['_berat'], $b['_urut']]);

        return array_map(function (array $b) {
            unset($b['_berat'], $b['_urut']);

            return $b;
        }, $baris);
    }

    private function nama(CompatibilityFactor $faktor): string
    {
        return match ($faktor) {
            CompatibilityFactor::EXPERIENCE_MATCH => 'Pengalaman',
            CompatibilityFactor::DURATION_MATCH => 'Durasi',
            CompatibilityFactor::TERRAIN_MATCH => 'Medan',
            CompatibilityFactor::TECHNICAL_MATCH => 'Tuntutan teknis',
            CompatibilityFactor::ELEVATION_GAIN_MATCH => 'Tanjakan',
            CompatibilityFactor::NAVIGATION_MATCH => 'Navigasi',
            CompatibilityFactor::TRIP_PREFERENCE => 'Jenis trip',
        };
    }

    private function jalurIni(CompatibilityFactor $faktor, Trail $trail): string
    {
        return match ($faktor) {
            CompatibilityFactor::EXPERIENCE_MATCH => $this->bebanFisik($trail),
            CompatibilityFactor::DURATION_MATCH => ucfirst(Durasi::panjang($trail->estimated_duration_minutes)),
            CompatibilityFactor::TERRAIN_MATCH => $trail->terrainTypes() === [] ? 'Belum diketahui' : TrailFigures::medan($trail),
            CompatibilityFactor::TECHNICAL_MATCH => $trail->technical_demand->label(),
            CompatibilityFactor::ELEVATION_GAIN_MATCH => $trail->elevation_gain_m === null ? 'Belum diketahui' : '↑'.number_format($trail->elevation_gain_m, 0, ',', '.').' m',
            CompatibilityFactor::NAVIGATION_MATCH => $trail->navigation_complexity->label(),
            CompatibilityFactor::TRIP_PREFERENCE => $trail->camping_available ? 'Ada area kemah' : 'Tanpa area kemah resmi',
        };
    }

    private function anda(CompatibilityFactor $faktor, User $pembaca, ?HikingGoal $goal): string
    {
        $pengalaman = $pembaca->experience;
        $belum = 'Belum Anda isi';

        return match ($faktor) {
            CompatibilityFactor::EXPERIENCE_MATCH, CompatibilityFactor::TECHNICAL_MATCH => trim(($pembaca->profile?->experience_level?->label() ?? $belum)
                .($pengalaman?->completed_hikes_count !== null ? ', '.$pengalaman->completed_hikes_count.' pendakian selesai' : '')),
            CompatibilityFactor::DURATION_MATCH => $goal?->expected_duration_minutes
                ? 'Target '.Durasi::panjang($goal->expected_duration_minutes)
                : ($pembaca->preference?->preferred_duration?->label() ?? $belum),
            CompatibilityFactor::TERRAIN_MATCH => ($pengalaman?->terrain_experience ?? []) === []
                ? $belum
                : collect($pengalaman->terrain_experience)->map(fn (string $t) => TerrainCharacter::tryFrom($t)?->label())->filter()->join(', '),
            CompatibilityFactor::ELEVATION_GAIN_MATCH => $pengalaman?->highest_elevation_gain_m !== null
                ? 'Tertinggi '.number_format($pengalaman->highest_elevation_gain_m, 0, ',', '.').' m'
                : $belum,
            CompatibilityFactor::NAVIGATION_MATCH => $pengalaman?->navigation_experience?->label() ?? $belum,
            CompatibilityFactor::TRIP_PREFERENCE => ($goal?->trip_type ?? $pembaca->preference?->preferred_trip_type)?->label() ?? $belum,
        };
    }

    private function bebanFisik(Trail $trail): string
    {
        $beban = $this->penilai->physicalDemand($trail);

        if (! $beban['known']) {
            return 'Belum diketahui';
        }

        return 'Beban fisik '.match ($beban['rank']) {
            1 => 'ringan',
            2 => 'sedang',
            3 => 'berat',
            default => 'sangat berat',
        };
    }

    /**
     * Lapis 1 dalam kata. Setiap syarat menyebut apakah ia terpenuhi; syarat rencana hanya
     * muncul bila ada rencana yang membawanya.
     *
     * @return array<int, array{teks: string, terpenuhi: bool}>
     */
    private function syaratDasar(RouteFitResult $hasil, Trail $trail, ?HikingGoal $goal): array
    {
        $gagal = fn (string $aturan) => in_array($aturan, $hasil->failedRules, true);
        $status = $this->statusResmi->effectiveStatusForTrail($trail);

        $syarat = [
            ['teks' => 'Status resmi tidak Tutup (sekarang '.$status->label().')', 'terpenuhi' => ! $gagal('official_status_closed')],
            ['teks' => 'Data kritis jalur lengkap: sumber, jarak, tanjakan, durasi, pos, status resmi, dan garis peta', 'terpenuhi' => ! $gagal('trail_data_incomplete')],
        ];

        if ($goal?->trip_type !== null) {
            $syarat[] = ['teks' => 'Lama perjalanan sesuai jenis trip ('.mb_strtolower($goal->trip_type->label()).')', 'terpenuhi' => ! $gagal('duration_requires_overnight')];
        }

        if ($goal?->max_elevation_gain_m !== null) {
            $syarat[] = ['teks' => 'Tanjakan tidak melebihi batas yang Anda tentukan', 'terpenuhi' => ! $gagal('elevation_gain_above_goal_limit')];
        }

        return $syarat;
    }

    /**
     * "Kenapa bukan Cocok?" dari keadaan faktor, dengan aturan yang sama dengan mesin: faktor
     * kritis yang belum diketahui atau jauh di bawah ambang menahan label di Kurang cocok, dan
     * faktor yang belum diketahui tidak pernah menaikkan label (§95). Null bila labelnya Cocok
     * atau jalurnya tidak dinilai.
     *
     * @param  array<int, array<string, string|null>>  $faktor
     */
    private function kenapaBukanCocok(RouteFitResult $hasil, array $faktor): ?string
    {
        if ($hasil->label === null || $hasil->label === RouteFitLabel::COCOK) {
            return null;
        }

        $perhatian = array_values(array_filter($faktor, fn (array $f) => in_array($f['kategori'], ['cukup', 'lemah'], true)));
        $belum = array_values(array_filter($faktor, fn (array $f) => $f['kategori'] === 'tak-diketahui'));
        $besar = array_values(array_filter($perhatian, fn (array $f) => $f['pengaruh'] === 'besar'));
        $kalimat = [];

        if ($perhatian !== []) {
            $kalimat[] = count($perhatian).' faktor perlu perhatian'
                .($besar !== [] ? ', dan '.mb_strtolower($besar[0]['label']).' berpengaruh besar' : '').'.';
        }

        if ($belum !== []) {
            $kalimat[] = count($belum).' faktor belum diketahui, dan faktor yang belum diketahui tidak pernah menaikkan label.';
        }

        $lantai = (float) config('hiking.route_fit.critical_factor_floor');
        $kritis = array_values(array_filter($hasil->factors, fn (FactorScore $f) => in_array($f->factor, self::FAKTOR_KRITIS, true) && ($f->isUnknown || $f->score < $lantai)));

        if ($hasil->label === RouteFitLabel::KURANG_COCOK && $kritis !== []) {
            $kalimat[] = 'Faktor kritis ('.implode(', ', array_map(fn (FactorScore $f) => mb_strtolower($this->nama($f->factor)), $kritis)).') menahan label di Kurang cocok, berapa pun faktor lainnya.';
        } elseif ($hasil->label === RouteFitLabel::PERLU_PERSIAPAN) {
            $kalimat[] = 'Tidak ada syarat dasar yang gagal, jadi jalur ini tetap relevan asal Anda bersiap.';
        } else {
            $kalimat[] = 'Gabungan faktornya belum cukup untuk Perlu persiapan.';
        }

        return implode(' ', $kalimat);
    }

    /**
     * Yang bisa dilakukan pendaki (spec v3 bagian 6.5): butir persiapan kritis bila ada trip,
     * saran pendamping yang tidak mengubah label, dan banding dengan simpanannya sendiri.
     *
     * @return array<int, array{judul: string, keterangan: string, url: string|null}>
     */
    private function langkah(Trail $trail, User $pembaca, ?TripPlan $trip): array
    {
        $langkah = [];

        if ($trip !== null) {
            $kritis = $trip->preparationItems()
                ->where('is_critical', true)
                ->where('status', PreparationStatus::NOT_CONFIRMED->value)
                ->orderBy('id')
                ->pluck('label');

            if ($kritis->isNotEmpty()) {
                $langkah[] = [
                    'judul' => 'Selesaikan '.$kritis->count().' butir persiapan kritis',
                    'keterangan' => $kritis->take(3)->join(', '),
                    'url' => route('trips.preparation', $trip),
                ];
            }
        }

        $langkah[] = [
            'judul' => 'Mendaki bersama yang sudah pernah lewat jalur ini',
            'keterangan' => 'Pengalaman pendamping tidak mengubah label, tetapi mengurangi yang perlu Anda tebak di jalur.',
            'url' => null,
        ];

        $lain = $this->simpanan->simpanan($pembaca)
            ->map(fn (TrailConsideration $baris) => $baris->trail)
            ->filter(fn (Trail $t) => $t->is_published && $t->id !== $trail->id)
            ->take(2)
            ->values();

        if ($lain->isNotEmpty()) {
            $langkah[] = [
                'judul' => 'Bandingkan dengan jalur lain',
                'keterangan' => $lain->pluck('name')->join(' dan ').' ada di simpanan Anda.',
                'url' => route('trails.compare', ['trails' => $lain->pluck('id')->prepend($trail->id)->join(',')]),
            ];
        }

        return $langkah;
    }

    private function ringkasProfil(User $pembaca): string
    {
        $pengalaman = $pembaca->experience;

        return implode(', ', array_filter([
            $pembaca->profile?->experience_level?->label(),
            $pengalaman?->completed_hikes_count !== null ? $pengalaman->completed_hikes_count.' pendakian selesai' : null,
            $pengalaman?->highest_elevation_gain_m !== null ? 'tanjakan tertinggi '.number_format($pengalaman->highest_elevation_gain_m, 0, ',', '.').' m' : null,
            $pengalaman?->longest_hike_duration_minutes ? 'terlama '.Durasi::panjang($pengalaman->longest_hike_duration_minutes) : null,
        ]));
    }

    private function ringkasRencana(TripPlan $trip): string
    {
        $mulai = $trip->planned_date;
        $selesai = $trip->end_date;
        $tanggal = $selesai !== null && ! $selesai->isSameDay($mulai)
            ? $mulai->translatedFormat('j').' sampai '.$selesai->translatedFormat('j M Y')
            : $mulai->translatedFormat('j M Y');

        return implode(', ', array_filter([
            $trip->trip_type->label(),
            $tanggal,
            $trip->participants_count ? $trip->participants_count.' orang' : null,
        ]));
    }

    private function kalimatSyarat(string $aturan): string
    {
        return Lang::has('recommendation.'.$aturan) ? __('recommendation.'.$aturan) : 'Tidak memenuhi syarat dasar untuk ditawarkan.';
    }
}
```

**Antarmuka**

Buat `resources/js/components/trails/FaktorKecocokan.tsx`:

```tsx
import { Icon, type NamaIkon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';

export type KategoriFaktor = 'kuat' | 'cukup' | 'lemah' | 'tak-diketahui';

/**
 * Satu faktor kecocokan sebagaimana dikirim server: nama, rincian mesin, dan kategori dari
 * ambang yang sama dengan penjelasan mesin (App\Support\FaktorKategori). Halaman penilaian
 * kecocokan menambahkan kata pengaruh dan perbandingan "Jalur ini" lawan "Anda".
 */
export interface FaktorBaris {
    label: string;
    detail: string | null;
    kategori: KategoriFaktor;
    pengaruh?: 'besar' | 'sedang' | 'kecil';
    jalurIni?: string;
    anda?: string;
}

/*
 * Tiga keadaan. "Sesuai" hanya untuk kategori kuat: mesin memilih kalimat rinciannya dengan
 * ambang kuat yang sama (CompatibilityScorer), jadi faktor cukup datang dengan kalimat
 * "di atas pengalaman Anda" dan menyebutnya Sesuai berarti membantah kalimatnya sendiri.
 * Cukup dan lemah sama-sama "Perlu perhatian"; data yang belum ada bukan nilai rendah (R-006).
 * Bentuk ikon ikut membawa keadaan, bukan warna saja.
 */
const keadaan: Record<KategoriFaktor, { kata: string; ikon: NamaIkon; nada: string }> = {
    kuat: { kata: 'Sesuai', ikon: 'check', nada: 'bg-fit-cocok text-fit-cocok-teks' },
    cukup: { kata: 'Perlu perhatian', ikon: 'warning', nada: 'bg-fit-perlu text-fit-perlu-teks' },
    lemah: { kata: 'Perlu perhatian', ikon: 'warning', nada: 'bg-fit-perlu text-fit-perlu-teks' },
    'tak-diketahui': { kata: 'Belum diketahui', ikon: 'question', nada: 'bg-fit-belum text-fit-belum-teks' },
};

function Kepala({ f }: { f: FaktorBaris }) {
    const k = keadaan[f.kategori];

    return (
        <span className="flex min-w-0 flex-1 items-start gap-3">
            <span aria-hidden="true" className={kelas('mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-full', k.nada)}>
                <Icon name={k.ikon} size={16} />
            </span>
            <span className="min-w-0 flex-1">
                <span className="flex flex-wrap items-baseline justify-between gap-x-3">
                    <span className="text-subjudul text-primary">{f.label}</span>
                    <span className="text-meta font-bold text-secondary">{k.kata}</span>
                </span>
                {f.detail ? <span className="mt-0.5 block text-kecil text-secondary">{f.detail}</span> : null}
                {f.pengaruh ? <span className="mt-0.5 block text-meta text-muted">Pengaruh {f.pengaruh}</span> : null}
            </span>
        </span>
    );
}

/**
 * Faktor kecocokan sebagai baris keadaan kata (spec v3 bagian 6.5), bukan batang: batang
 * selebar sekian dapat dibaca balik menjadi angka skor (BR-09). Klien hanya menggambar
 * kategori yang sudah dihitung server; skor dan bobot tidak pernah sampai ke sini. Baris yang
 * membawa perbandingan "Jalur ini" dan "Anda" dapat dibuka dengan details asli (papan ketik
 * dan pembaca layar tanpa kode tambahan). Dipindah dari components/ui/FactorBars.tsx dan
 * ditulis ulang W3.
 */
export function FaktorKecocokan({ faktor, className }: { faktor: FaktorBaris[]; className?: string }) {
    if (faktor.length === 0) {
        return null;
    }

    return (
        <ul className={kelas('divide-y divide-subtle', className)}>
            {faktor.map((f) =>
                f.jalurIni !== undefined && f.anda !== undefined ? (
                    <li key={f.label}>
                        <details className="group">
                            <summary className="flex min-h-11 cursor-pointer list-none items-start gap-2 py-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 [&::-webkit-details-marker]:hidden">
                                <Kepala f={f} />
                                <Icon name="caret-down" size={20} className="mt-1 shrink-0 text-muted transition-transform duration-cepat ease-gerak group-open:rotate-180" />
                            </summary>
                            <dl className="mb-3 grid grid-cols-2 gap-2 pl-10">
                                <div className="rounded-control bg-surface-sunken p-3">
                                    <dt className="text-meta text-secondary">Jalur ini</dt>
                                    <dd className="mt-0.5 break-words text-kecil font-bold text-primary">{f.jalurIni}</dd>
                                </div>
                                <div className="rounded-control bg-surface-sunken p-3">
                                    <dt className="text-meta text-secondary">Anda</dt>
                                    <dd className="mt-0.5 break-words text-kecil font-bold text-primary">{f.anda}</dd>
                                </div>
                            </dl>
                        </details>
                    </li>
                ) : (
                    <li key={f.label} className="py-3">
                        <Kepala f={f} />
                    </li>
                ),
            )}
        </ul>
    );
}
```

Buat `resources/js/components/trails/kecocokan/DinilaiDari.tsx`:

```tsx
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { DinilaiDari as DataDinilai } from './types';

const tautanUbah = 'inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-control px-2 text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Bahan penilaian (spec v3 bagian 6.5): profil yang diisi sendiri dan rencana trip, masing-
 * masing dengan jalan untuk mengubahnya. Pengalaman yang diisi sendiri adalah masukan, bukan
 * sertifikasi (PRD §22), jadi pembaca diberi tempat untuk membetulkannya.
 */
export function DinilaiDari({ data }: { data: DataDinilai }) {
    const baris: Array<{ judul: string; isi: string; url: string; nama: string }> = [
        { judul: 'Profil Anda (diisi sendiri)', isi: data.profil, url: data.profilUrl, nama: 'profil' },
    ];

    if (data.rencana !== null && data.rencanaUrl !== null) {
        baris.push({ judul: 'Rencana trip', isi: data.rencana, url: data.rencanaUrl, nama: 'rencana trip' });
    }

    return (
        <dl className="divide-y divide-subtle rounded-lg border border-subtle bg-surface">
            {baris.map((b) => (
                <div key={b.judul} className="flex items-start justify-between gap-3 px-4 py-3">
                    <div className="min-w-0">
                        <dt className="text-meta text-secondary">{b.judul}</dt>
                        <dd className="mt-0.5 break-words text-kecil font-bold text-primary">{b.isi}</dd>
                    </div>
                    <Tautan href={b.url} className={kelas(tautanUbah)}>
                        Ubah<span className="sr-only"> {b.nama}</span>
                    </Tautan>
                </div>
            ))}
        </dl>
    );
}
```

Buat `resources/js/components/trails/kecocokan/KepalaPenilaian.tsx`:

```tsx
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import type { Penilaian } from './types';

/**
 * Label penilaian beserta definisinya (spec v3 bagian 6.5). Tiga label yang mungkin disebut
 * bersama alasannya tanpa skor: angka seperti "82%" memberi kesan pasti yang tidak dimiliki
 * sistem (R-005). Jalur yang gagal syarat dasar tidak diberi label sama sekali; yang tampil
 * adalah syarat yang gagal, dalam kalimat, bukan kunci mesin.
 */
export function KepalaPenilaian({ penilaian }: { penilaian: Penilaian }) {
    return (
        <section aria-labelledby="judul-label" className="rounded-utama border border-subtle bg-surface p-5">
            <h2 id="judul-label" className="sr-only">
                Label kecocokan
            </h2>
            <div className="flex flex-wrap items-center gap-3">
                <LabelKecocokan nilai={penilaian.label} label={penilaian.labelText} />
                <span className="text-meta text-muted">{penilaian.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
            </div>
            {penilaian.definisi ? <p className="mt-3 text-isi text-primary">{penilaian.definisi}</p> : null}
            {!penilaian.layak ? (
                <div className="mt-3">
                    <p className="text-isi text-primary">Jalur ini tidak dinilai karena syarat dasarnya belum terpenuhi:</p>
                    <ul className="mt-2 list-disc space-y-1 pl-5 text-kecil text-secondary">
                        {penilaian.alasanTidakDinilai.map((a) => (
                            <li key={a}>{a}</li>
                        ))}
                    </ul>
                </div>
            ) : null}
            <p className="mt-4 rounded-control bg-warn-50 px-4 py-3 text-kecil text-warn-900">
                Tiga label yang mungkin: Cocok, Perlu persiapan, Kurang cocok. Tidak ada skor angka, karena angka seperti persentase memberi kesan pasti yang
                tidak kami miliki.
            </p>
        </section>
    );
}
```

Buat `resources/js/components/trails/kecocokan/LangkahBerikut.tsx`:

```tsx
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import type { Langkah } from './types';

/**
 * "Yang bisa Anda lakukan" (spec v3 bagian 6.5). Langkah yang punya tujuan menjadi baris
 * bertautan dengan chevron (membuka halaman lain, spec v3 bagian 8.8); saran tanpa tujuan
 * tetap teks biasa, tidak dibungkus kontrol yang tidak melakukan apa pun (DK-40).
 */
export function LangkahBerikut({ langkah }: { langkah: Langkah[] }) {
    return (
        <ul className="divide-y divide-subtle overflow-hidden rounded-lg border border-subtle bg-surface">
            {langkah.map((l) => (
                <li key={l.judul}>
                    {l.url ? (
                        <Tautan href={l.url} className={kelas('flex items-center gap-3 px-4 py-3 hover:bg-surface-sunken', fokusTerlihat)}>
                            <span className="min-w-0 flex-1">
                                <span className="block text-kecil font-bold text-primary">{l.judul}</span>
                                <span className="mt-0.5 block text-meta text-secondary">{l.keterangan}</span>
                            </span>
                            <Icon name="caret-right" size={20} className="shrink-0 text-muted" />
                        </Tautan>
                    ) : (
                        <div className="px-4 py-3">
                            <p className="text-kecil font-bold text-primary">{l.judul}</p>
                            <p className="mt-0.5 text-meta text-secondary">{l.keterangan}</p>
                        </div>
                    )}
                </li>
            ))}
        </ul>
    );
}
```

Buat `resources/js/components/trails/kecocokan/SyaratDasar.tsx`:

```tsx
import { Icon } from '@/components/ui/Icon';
import type { Syarat } from './types';

/**
 * Lapis 1 Route Fit (PRD §26): syarat dasar yang menentukan boleh tidaknya jalur dijadikan
 * rekomendasi. Terpenuhi dan tidak dibedakan ikon dan kata tersembunyi untuk pembaca layar,
 * bukan warna saja.
 */
export function SyaratDasar({ syarat }: { syarat: Syarat[] }) {
    return (
        <div className="rounded-lg border border-subtle bg-surface p-4">
            <ul className="space-y-2">
                {syarat.map((s) => (
                    <li key={s.teks} className="flex items-start gap-2 text-kecil text-primary">
                        <span className={s.terpenuhi ? 'text-brand-ink' : 'text-danger-900'}>
                            <Icon name={s.terpenuhi ? 'check' : 'x'} size={16} />
                        </span>
                        <span className="min-w-0">
                            <span className="sr-only">{s.terpenuhi ? 'Terpenuhi: ' : 'Tidak terpenuhi: '}</span>
                            {s.teks}
                        </span>
                    </li>
                ))}
            </ul>
            <p className="mt-3 border-t border-subtle pt-3 text-meta text-secondary">
                Bila satu saja gagal, jalur tidak dijadikan rekomendasi. Jalur Dibatasi tetap tampil, dengan peringatan.
            </p>
        </div>
    );
}
```

Buat `resources/js/components/trails/kecocokan/types.ts`:

```ts
import type { LabelFit } from '@/components/trails/LabelKecocokan';

/** Bentuk yang dikirim App\Services\Trails\PenilaianKecocokan: kata, tanpa skor dan bobot (BR-09). */
export interface JalurDinilai {
    id: number;
    nama: string;
    url: string;
    gunung: string;
    gunungUrl: string;
}

export interface Penilaian {
    label: LabelFit;
    labelText: string;
    definisi: string | null;
    /** Lolos syarat dasar (lapis 1); false berarti jalur tidak dinilai, bukan Kurang cocok. */
    layak: boolean;
    denganRencana: boolean;
    alasanTidakDinilai: string[];
}

export interface DinilaiDari {
    profil: string;
    profilUrl: string;
    rencana: string | null;
    rencanaUrl: string | null;
}

export interface Syarat {
    teks: string;
    terpenuhi: boolean;
}

export interface Langkah {
    judul: string;
    keterangan: string;
    url: string | null;
}
```

Buat `resources/js/pages/Trails/Fit.tsx`:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FaktorKecocokan, type FaktorBaris } from '@/components/trails/FaktorKecocokan';
import { DinilaiDari } from '@/components/trails/kecocokan/DinilaiDari';
import { KepalaPenilaian } from '@/components/trails/kecocokan/KepalaPenilaian';
import { LangkahBerikut } from '@/components/trails/kecocokan/LangkahBerikut';
import { SyaratDasar } from '@/components/trails/kecocokan/SyaratDasar';
import type { DinilaiDari as DataDinilai, JalurDinilai, Langkah, Penilaian, Syarat } from '@/components/trails/kecocokan/types';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { onboarding } from '@/routes';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    jalur: JalurDinilai;
    aksi: { label: string; url: string };
    penilaian: Penilaian | null;
    dinilaiDari?: DataDinilai;
    syaratDasar?: Syarat[];
    faktor?: FaktorBaris[];
    hitungan?: { sesuai: number; perluPerhatian: number; belumDiketahui: number };
    kenapaBukanCocok?: string | null;
    yangBisaDilakukan?: Langkah[];
    dinilai?: string;
    aturan?: string;
}

const judulBagian = 'text-bagian text-primary';
const lapis = 'text-meta font-bold text-muted';

/**
 * Penilaian kecocokan satu jalur (spec v3 bagian 6.5, prototype 05): label dan definisinya,
 * bahan penilaiannya, tiga lapis mesin dalam kata, kenapa labelnya bukan Cocok, dan langkah
 * yang dapat diambil. Tanpa skor dan tanpa bobot angka (R-005, BR-09); bobot tampil sebagai
 * kata pengaruh. Halaman tugas dengan bar aksi, jadi bilah tab disembunyikan dan tombol
 * kembali menuju jalurnya (spec v3 bagian 5.1).
 */
export default function Fit() {
    const { jalur, aksi, penilaian, dinilaiDari, syaratDasar, faktor, hitungan, kenapaBukanCocok, yangBisaDilakukan, dinilai, aturan } = usePage<Props>().props;

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-2xl space-y-8 px-4 sm:px-6">
                <PageHeader induk={{ href: jalur.url, label: jalur.nama }} title="Penilaian kecocokan">
                    <p className="mt-1 text-kecil text-secondary">
                        {jalur.nama},{' '}
                        <Tautan href={jalur.gunungUrl} className="font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                            {jalur.gunung}
                        </Tautan>
                    </p>
                </PageHeader>

                {penilaian === null ? (
                    <EmptyState
                        jenis="pertama"
                        title="Isi profil pendaki untuk dinilai"
                        description="Kecocokan dihitung dari pengalaman yang Anda isi sendiri. Tanpa profil, jalur ini belum dinilai, dan itu tidak berarti jalurnya mudah atau sulit."
                    >
                        <Button href={onboarding.url()}>Isi profil pendaki</Button>
                    </EmptyState>
                ) : (
                    <>
                        <KepalaPenilaian penilaian={penilaian} />

                        {dinilaiDari ? (
                            <section aria-labelledby="judul-dinilai" className="space-y-3">
                                <h2 id="judul-dinilai" className={judulBagian}>
                                    Dinilai dari
                                </h2>
                                <DinilaiDari data={dinilaiDari} />
                            </section>
                        ) : null}

                        {syaratDasar ? (
                            <section aria-labelledby="judul-syarat" className="space-y-3">
                                <div>
                                    <p className={lapis}>Lapis 1</p>
                                    <h2 id="judul-syarat" className={judulBagian}>
                                        Syarat dasar: {penilaian.layak ? 'terpenuhi' : 'belum terpenuhi'}
                                    </h2>
                                </div>
                                <SyaratDasar syarat={syaratDasar} />
                            </section>
                        ) : null}

                        {faktor && hitungan ? (
                            <section aria-labelledby="judul-faktor" className="space-y-3">
                                <div>
                                    <p className={lapis}>Lapis 2 dan 3</p>
                                    <h2 id="judul-faktor" className={judulBagian}>
                                        Tujuh faktor, dari yang paling berpengaruh
                                    </h2>
                                    <p className="mt-1 text-kecil text-secondary">
                                        <span data-angka="">{hitungan.sesuai}</span> sesuai, <span data-angka="">{hitungan.perluPerhatian}</span> perlu perhatian,{' '}
                                        <span data-angka="">{hitungan.belumDiketahui}</span> belum diketahui
                                    </p>
                                </div>
                                <div className="rounded-lg border border-subtle bg-surface px-4">
                                    <FaktorKecocokan faktor={faktor} />
                                </div>
                            </section>
                        ) : null}

                        {kenapaBukanCocok ? (
                            <section aria-labelledby="judul-kenapa" className="space-y-2">
                                <h2 id="judul-kenapa" className={judulBagian}>
                                    Kenapa bukan Cocok?
                                </h2>
                                <p className="text-isi text-primary">{kenapaBukanCocok}</p>
                            </section>
                        ) : null}

                        {yangBisaDilakukan ? (
                            <section aria-labelledby="judul-langkah" className="space-y-3">
                                <h2 id="judul-langkah" className={judulBagian}>
                                    Yang bisa Anda lakukan
                                </h2>
                                <LangkahBerikut langkah={yangBisaDilakukan} />
                            </section>
                        ) : null}

                        <div className="space-y-2 text-meta text-secondary">
                            <p>Label tidak naik hanya karena Anda menyelesaikan pendakian. Bila pengalaman Anda sudah berubah, ubah sendiri di Profil.</p>
                            <p>
                                Dinilai {dinilai}, {aturan}. Dinilai dengan aturan tertulis, bukan AI. Bila profil, rencana, atau kondisi berubah, penilaian dibuat ulang.
                            </p>
                        </div>
                    </>
                )}

                <BarAksi label="Aksi penilaian kecocokan">
                    <Button variant="secondary" href={jalur.url}>
                        Ke jalur
                    </Button>
                    <Button href={aksi.url}>{aksi.label}</Button>
                </BarAksi>
            </div>
        </div>
    );
}

Fit.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
```

`resources/js/components/discovery/PanelAlasan.tsx`:

1. Ganti:

```tsx
import { FactorBars, type FaktorBar } from '@/components/ui/FactorBars';
import type { HasilRekomendasi } from './types';

const kelompok: Array<{ kunci: string; simbol: string; judul: string; masuk: (f: FaktorBar) => boolean }> = [
    // Kuat dan cukup sama-sama tidak menahan; kata di sebelah batangnya tetap membedakan.
    { kunci: 'sesuai', simbol: '✓', judul: 'Sesuai', masuk: (f) => f.kategori === 'kuat' || f.kategori === 'cukup' },
    // Sama dengan "Yang perlu diperhatikan" di bawah: mesin memakai ambang lemah yang sama.
    { kunci: 'perhatian', simbol: '⚠︎', judul: 'Perlu perhatian', masuk: (f) => f.kategori === 'lemah' },
```

dengan:

```tsx
import { FaktorKecocokan, type FaktorBaris } from '@/components/trails/FaktorKecocokan';
import type { HasilRekomendasi } from './types';

const kelompok: Array<{ kunci: string; simbol: string; judul: string; masuk: (f: FaktorBaris) => boolean }> = [
    // Hanya kategori kuat: kalimat rincian mesin dipilih dengan ambang kuat yang sama (W3).
    { kunci: 'sesuai', simbol: '✓', judul: 'Sesuai', masuk: (f) => f.kategori === 'kuat' },
    // Cukup dan lemah: kalimat rinciannya menyebut apa yang belum sepadan dengan jalurnya.
    { kunci: 'perhatian', simbol: '⚠︎', judul: 'Perlu perhatian', masuk: (f) => f.kategori === 'cukup' || f.kategori === 'lemah' },
```

2. Ganti:

```tsx
                                    <FactorBars factors={isi} className="mt-2" />
```

dengan:

```tsx
                                    <FaktorKecocokan faktor={isi} className="mt-2" />
```

`resources/js/components/discovery/types.ts`:

1. Ganti:

```ts
import type { FaktorBar } from '@/components/ui/FactorBars';
```

dengan:

```ts
import type { FaktorBaris } from '@/components/trails/FaktorKecocokan';
```

2. Ganti:

```ts
    faktor: FaktorBar[];
```

dengan:

```ts
    faktor: FaktorBaris[];
```

`resources/js/components/trails/rinci/types.ts`:

1. Ganti:

```ts
import type { FaktorBar } from '@/components/ui/FactorBars';
```

dengan:

```ts
import type { FaktorBaris } from '@/components/trails/FaktorKecocokan';
```

2. Ganti:

```ts
    faktor: FaktorBar[];
```

dengan:

```ts
    faktor: FaktorBaris[];
```

`resources/js/components/ui/ikon/phosphor.ts`:

1. Ganti:

```ts
    | 'bookmark-simple'
```

dengan:

```ts
    | 'bookmark-simple'
    | 'caret-down'
```

2. Ganti:

```ts
        bold: 'M184,28H72A20,20,0,0,0,52,48V224a12,12,0,0,0,18.36,10.18l57.63-36,57.65,36A12,12,0,0,0,204,224V48A20,20,0,0,0,184,28Zm-4,174.35-45.65-28.53a12,12,0,0,0-12.72,0L76,202.35V52H180Z',
```

dengan:

```ts
        bold: 'M184,28H72A20,20,0,0,0,52,48V224a12,12,0,0,0,18.36,10.18l57.63-36,57.65,36A12,12,0,0,0,204,224V48A20,20,0,0,0,184,28Zm-4,174.35-45.65-28.53a12,12,0,0,0-12.72,0L76,202.35V52H180Z',
    },
    'caret-down': {
        regular: 'M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z',
        bold: 'M216.49,104.49l-80,80a12,12,0,0,1-17,0l-80-80a12,12,0,0,1,17-17L128,159l71.51-71.52a12,12,0,0,1,17,17Z',
```

`resources/js/pages/Trails/Show.tsx`:

1. Ganti:

```tsx
import { FactorBars } from '@/components/ui/FactorBars';
```

dengan:

```tsx
import { FaktorKecocokan } from '@/components/trails/FaktorKecocokan';
```

2. Ganti:

```tsx
                                    <FactorBars factors={fit.faktor} className="mt-3" />
```

dengan:

```tsx
                                    <FaktorKecocokan faktor={fit.faktor} className="mt-3" />
```

Hapus berkas `resources/js/components/ui/FactorBars.tsx`.

- [ ] **Step 4: Bangkitkan rute bertipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan wayfinder:generate`
Expected: tanpa galat. `resources/js/routes/trails` memuat `fit`; berkas bangkitan diabaikan git.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='FitAssessmentPage|FactorBars|NavigationConsistency|AmbientFit|TrailDetailPage'`
Expected: PASS: 60 test, 60 lulus.

- [ ] **Step 6: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.655 test, 1.653 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_kecocokan`. Pada simulasi rencana: 11 test, 11 lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Jelajah daftar, saringan, kartu jalur, dan simpan dengan Urungkan

Induk W3 butir 1; spec bagian 6.2; keputusan nomor 1, 5, 7, 8, dan 12; butir W2-F1; ruling W3-R13 sampai W3-R23. Kelas
halaman `HalamanJelajah` menggantikan isi `TrailIndexController`. Halamannya memuat:

- chip tafsiran kalimat yang dapat dilepas, dengan "Kembalikan tafsiran kalimat";
- lembar saringan di ponsel dan panel kiri di layar lebar: kecocokan dasar, jenis trip, "Hanya rendah", status resmi,
  dan rentang tanjakan yang diketik, dengan hitungan "Tampilkan N jalur";
- kartu jalur: label ikon dan kata, simpan 44 px dengan Urungkan, tiga angka, garis profil berskala vertikal sama,
  satu alasan, status resmi bersumber, dan "Kenapa?";
- kartu "Belum bisa dinilai" bergaris putus-putus yang tidak dihitung;
- "N jalur disembunyikan saringan" dengan "Tampilkan juga";
- keadaan menunggu (diperiksa lebih dulu, W1-F1), tersaring habis, dan memuat.

Simpan menjadi idempoten (`trails.saved.store`), dan toast Urungkan dimiliki halaman (W2-5i). Baki banding Jelajah
dilepas bersama `--baki-banding`, karena hitungan dan pintu Simpanan sudah di kepala halaman (W3-R21). Dua test
perbaikan akhir W2 yang membaca baki itu diarahkan ulang ke niatnya.

**Files:**
- Create: `app/Http/Requests/Trails/StoreSavedTrailRequest.php`, `app/Http/Resources/KartuJalurResource.php`, `app/Services/Trails/HalamanJelajah.php`, `resources/js/components/trails/GarisProfil.tsx`, `resources/js/components/trails/TombolSimpan.tsx`, `resources/js/components/trails/jelajah/KartuBelumDinilai.tsx`, `resources/js/components/trails/jelajah/KartuJalur.tsx`, `resources/js/components/trails/jelajah/KotakCari.tsx`, `resources/js/components/trails/jelajah/SaringanJelajah.tsx`, `resources/js/components/trails/jelajah/TafsiranKalimat.tsx`, `resources/js/components/trails/jelajah/saringan.ts`
- Modify: `app/Http/Controllers/Trails/ConsiderationController.php`, `app/Http/Controllers/Trails/SavedRoutesController.php`, `app/Http/Controllers/Trails/TrailIndexController.php` (tulis ulang), `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Resources/TrailRowResource.php`, `app/Services/ConsiderationService.php`, `app/Services/RouteFit/TrailFitSummary.php`, `app/Services/TrailFitService.php`, `app/Services/Trails/TrailSearch.php` (tulis ulang), `config/hiking.php`, `resources/css/app.css`, `resources/js/components/trails/types.ts`, `resources/js/components/ui/ToastUrungkan.tsx`, `resources/js/components/ui/TombolIkon.tsx`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/layouts/StripPendakian.tsx`, `resources/js/pages/Trails/Index.tsx` (tulis ulang), `resources/js/types/index.d.ts`, `routes/web.php`
- Delete: `resources/js/components/trails/ConsiderationTray.tsx`, `resources/js/components/trails/FilterForm.tsx`, `resources/js/components/trails/Menunggu.tsx`
- Test: `tests/Feature/SavedUndoTest.php` (baru), `tests/Feature/TrailCardTest.php` (baru), `tests/Feature/ComparisonAsCostTest.php`, `tests/Feature/ConsiderationTest.php`, `tests/Feature/DesignPrimitivesTest.php`, `tests/Feature/InertiaLayoutTest.php`, `tests/Feature/LongInputLayoutTest.php`, `tests/Feature/RecommendationExclusionTest.php`, `tests/Feature/ResultListBudgetTest.php`, `tests/Feature/SavedRoutesTest.php`, `tests/Feature/TrailIndexSearchTest.php`

**Interfaces:**
- Consumes: `LabelKecocokan`, `StatusAkses`, `StatusJalurRingkas` (Tugas 1), `SkalaProfil`, `BarisStatus`, `TrailFigures::ringkas()` (Tugas 2), `TrailFitService` dan `TrailFitSummary`, `ConsiderationService::jumlahSimpanan()` dan `batasSimpanan()`, `useKerangka()` dan `LayananKerangka['tampilkanToast']` (W2), `KotakCentang`, `ChipPilihan`, `LembarBawah`, `KontrolSegmen`, `TombolIkon`, `Dialog` (W1), `TautanSimpanan` (W2 Tugas 3).
- Produces:
  - rute `trails.saved.store` (`POST trails/saved/{trail}`, batas laju `simpan-jalur`) ke `ConsiderationController::store(StoreSavedTrailRequest $request, int $trail, ConsiderationService $consideration): RedirectResponse`, dengan flash `simpanan-terhapus` pada `destroy`;
  - `ConsiderationService::simpan(User $user, Trail $trail, ?string $catatan = null): ConsiderationOutcome` (idempoten, tidak menimpa catatan, batas di bawah kunci);
  - flash bersama `simpananTerhapus: { trailId: number; nama: string; catatan: string | null } | null` di `HandleInertiaRequests` dan `resources/js/types/index.d.ts`;
  - `App\Services\Trails\HalamanJelajah::props(TrailSearch $cari, User $pembaca, Request $request): array` dengan kunci `filter`, `tafsiran`, `trails`, `ringkasan`, `saringan`, `atribut`, `dinilai`, `dasarPenilaian`, `jumlahTerbit`, `menunggu`, `petaSebaran`, `kataBelumDinilai`; controller menambah `title` dan `pengumuman`;
  - `TrailSearch::tafsiran()`, `kandidat(int $batas)`, `gagalPada(array $atribut, bool $dinilai, ?string $abaikan = null)`, `teknisDariTafsiran()`, `tampilkanSemua()`, konstanta `TAFSIRAN`, `SARINGAN`, `JENIS`, `STATUS_BAWAAN`;
  - `App\Http\Resources\KartuJalurResource`;
  - `resources/js/components/trails/TombolSimpan.tsx`: `TombolSimpan({ jalur, tampilkanToast, bentuk = 'ikon' })`, `interface JalurSimpan { id; name; ditimbang; adaCatatan }`;
  - komponen `resources/js/components/trails/jelajah/` (`KartuJalur`, `KartuBelumDinilai`, `KotakCari({ nilai, onUbah })`, `SaringanJelajah`, `TafsiranKalimat`) dan `saringan.ts` (`drafDari`, `drafKosong`, `gagalPada`, `hitungLolos`, `alihPilihan`, `kueriDari`);
  - `resources/js/components/trails/GarisProfil.tsx`: `GarisProfil({ titik, label, className })`;
  - `TombolIkon` menerima `isi` untuk keadaan menyala.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/SavedUndoTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Simpan dengan urungkan (spec v3 bagian 6.2 dan 9.1; W3 Tugas 4).
 *
 * POST trails/saved/{trail} menyimpan secara idempoten: kiriman ulang atau halaman basi tidak
 * pernah membalik simpanan menjadi terhapus, berbeda dengan toggle lama. Urungkan sesudah
 * "Hapus dari simpanan" memakai rute yang sama dengan catatan yang dititipkan flash, jadi
 * catatan pribadi (FR-22) kembali utuh. Pemilik dari sesi, jalur dari rute, dan hanya
 * `catatan` yang dibaca dari badan permintaan.
 */
class SavedUndoTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    private function jalur(string $nama = 'Jalur Selo'): Trail
    {
        return Trail::factory()->for(Mountain::factory())->published()->create(['name' => $nama]);
    }

    private function catatan(User $user, Trail $trail): ?string
    {
        return TrailConsideration::query()->where('user_id', $user->id)->where('trail_id', $trail->id)->value('note');
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->post(route('trails.saved.store', $this->jalur()->id))->assertRedirect(route('login'));
    }

    public function test_saving_twice_keeps_one_row_and_announces_it(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();

        $this->actingAs($user)->from(route('trails.index'))->post(route('trails.saved.store', $trail->id))
            ->assertRedirect(route('trails.index'))
            ->assertSessionHas('pengumuman', 'Jalur Selo disimpan.');
        $this->actingAs($user)->post(route('trails.saved.store', $trail->id))->assertRedirect();

        $this->assertSame(1, TrailConsideration::query()->where('user_id', $user->id)->count());
    }

    /**
     * Urungkan mengembalikan jalur beserta catatannya: flash menitipkan catatan satu kali
     * kepada pemiliknya, lalu rute simpan menulisnya kembali.
     */
    public function test_undo_after_removal_restores_the_trail_with_its_note(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $baris = TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $trail->id]);
        $baris->forceFill(['note' => 'Bawa jas hujan'])->save();

        $this->actingAs($user)->from(route('trails.index'))->delete(route('trails.saved.remove', $trail->id))
            ->assertSessionHas('simpanan-terhapus', ['trailId' => $trail->id, 'nama' => 'Jalur Selo', 'catatan' => 'Bawa jas hujan']);
        $this->assertNull($this->catatan($user, $trail));

        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('flash.simpananTerhapus.catatan', 'Bawa jas hujan'));

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['catatan' => 'Bawa jas hujan'])->assertRedirect();
        $this->assertSame('Bawa jas hujan', $this->catatan($user, $trail));
    }

    /**
     * Idempoten juga untuk catatan: simpan ulang tidak menimpa catatan yang sudah ada.
     */
    public function test_saving_again_never_overwrites_an_existing_note(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $trail->id])->forceFill(['note' => 'Catatan lama'])->save();

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['catatan' => 'Catatan baru'])->assertRedirect();

        $this->assertSame('Catatan lama', $this->catatan($user, $trail));
    }

    public function test_the_note_is_limited_like_any_private_note(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['catatan' => str_repeat('a', TrailConsideration::CATATAN_MAKS + 1)])
            ->assertSessionHasErrors('catatan');

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
    }

    /**
     * Pemilik dari sesi: user_id dan trail_id di badan permintaan diabaikan (mass assignment).
     */
    public function test_ids_in_the_request_body_never_reach_the_database(): void
    {
        $user = User::factory()->create();
        $lain = User::factory()->create();
        $trail = $this->jalur();
        $trailLain = $this->jalur('Jalur Lain');

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['user_id' => $lain->id, 'trail_id' => $trailLain->id])->assertRedirect();

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $lain->id]);
        $this->assertDatabaseMissing('trail_considerations', ['trail_id' => $trailLain->id]);
    }

    public function test_only_published_trails_can_be_saved(): void
    {
        $user = User::factory()->create();
        $draf = Trail::factory()->for(Mountain::factory())->unpublished()->create();

        $this->actingAs($user)->post(route('trails.saved.store', $draf->id))->assertNotFound();
        $this->actingAs($user)->post(route('trails.saved.store', 999999))->assertNotFound();
    }

    /**
     * Simpanan yang penuh ditolak dengan batas yang berlaku, dan tombolnya menjawab dengan
     * dialog di tempat tombol ditekan (bukan pesan di ujung halaman).
     */
    public function test_a_full_list_is_refused_with_the_configured_limit(): void
    {
        config()->set('hiking.saved_trails.max', 1);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('trails.saved.store', $this->jalur()->id));
        $kedua = $this->jalur('Jalur Kedua');

        $this->actingAs($user)->post(route('trails.saved.store', $kedua->id))
            ->assertSessionHas('timbangan-penuh', 'Simpanan sudah penuh (1 jalur). Hapus satu dulu sebelum menyimpan jalur lain.');
        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $kedua->id]);

        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));
        $this->assertStringContainsString('judul="Simpanan sudah penuh"', $tombol);
        $this->assertStringContainsString('<Button href={saved.url()}>Buka Simpanan</Button>', $tombol);
    }

    public function test_saving_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($user)->post(route('trails.saved.store', $trail->id))->assertRedirect();
        }

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id))->assertStatus(429);
    }

    /**
     * Tombol simpan memakai dua rute idempoten, membawa keadaannya lewat aria-pressed, dan
     * bertanya dulu sebelum melepas simpanan yang bercatatan.
     */
    public function test_the_save_button_uses_idempotent_routes_and_asks_before_losing_a_note(): void
    {
        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        $this->assertStringContainsString('router.post(store.url({ trail: jalur.id }), { catatan }', $tombol);
        $this->assertStringContainsString('router.delete(remove.url({ trail: jalur.id })', $tombol);
        $this->assertDoesNotMatchRegularExpression('/\btimbang\b/', $tombol, 'Rute toggle lama dapat membalik simpanan pada halaman basi.');
        $this->assertStringContainsString('aria-pressed={jalur.ditimbang}', $tombol);
        $this->assertStringContainsString('judul="Hapus dari simpanan?"', $tombol);
        $this->assertStringContainsString('simpan(terhapus?.catatan ?? null, false)', $tombol);
    }

    /**
     * Ruling W2-5i: toast Urungkan hidup selama komponen pemanggil useKerangka terpasang.
     * Kartu dan chip dapat hilang oleh aksinya sendiri (Simpanan, saringan), jadi useKerangka
     * dipanggil halaman dan tampilkanToast diteruskan ke bawah; aksi yang memunculkan Urungkan
     * memakai preserveState supaya halamannya tidak dipasang ulang.
     */
    public function test_the_undo_toast_belongs_to_the_page_not_the_card(): void
    {
        $pemanggil = [];

        foreach ($this->berkasTsx('components/trails', true) as $berkas) {
            if (str_contains($this->tanpaKomentarJs($berkas->getContents()), 'useKerangka(')) {
                $pemanggil[] = str_replace('\\', '/', $berkas->getRelativePathname());
            }
        }

        $this->assertSame([], $pemanggil, 'Komponen jalur tidak boleh memiliki toast Urungkan sendiri.');
        $this->assertStringContainsString('const { tampilkanToast } = useKerangka();', File::get(resource_path('js/pages/Trails/Index.tsx')));
        $this->assertStringContainsString('const opsi = { preserveScroll: true, preserveState: true } as const;', File::get(resource_path('js/components/trails/TombolSimpan.tsx')));
    }
}
```

Buat `tests/Feature/TrailCardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Kartu jalur Jelajah (spec v3 bagian 6.2, prototype 02 dan 15; W3 Tugas 4).
 *
 * Tiga janji kartu yang dijaga di sini: props tidak pernah membawa skor atau bobot (BR-09,
 * R-005); garis profil semua kartu di satu halaman memakai skala tinggi yang sama, jadi
 * bentuknya dapat dibandingkan sekilas; dan jalur yang menunggu data tidak dihitung, tidak
 * diberi angka, dan tidak pernah dinilai (R-008, R-022).
 */
class TrailCardTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    /** Kunci yang tidak boleh ada di mana pun dalam props Jelajah. */
    private const KUNCI_TERLARANG = ['score', 'internal_score', 'internalScore', 'weight', 'weights', 'skor', 'bobot', 'probability'];

    private function pendaki(): User
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $user->experience()->create(['completed_hikes_count' => 4, 'terrain_experience' => ['FOREST'], 'highest_elevation_gain_m' => 900]);

        return $user->fresh();
    }

    private function jelajah(User $user, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get(route('trails.index', $query))->assertOk();
    }

    /**
     * @return array<int, array{km: float|int, m: float|int}>
     */
    private function profil(int $naik): array
    {
        return [['km' => 0, 'm' => 1000], ['km' => 1.5, 'm' => 1000 + intdiv($naik, 2)], ['km' => 3, 'm' => 1000 + $naik]];
    }

    public function test_card_props_carry_labels_and_reasons_but_never_a_score(): void
    {
        $gunung = Mountain::factory()->create();
        // Tanjakan di atas pengalaman yang diisi memaksa skor internal berkoma; tanpa itu
        // pemeriksaan pecahan di bawah dapat lolos tanpa memeriksa apa pun.
        Trail::factory()->for($gunung)->published()->easy()->create(['elevation_gain_m' => 1600]);
        Trail::factory()->for($gunung)->published()->create();

        $respons = $this->jelajah($this->pendaki());
        $props = $respons->viewData('page')['props'];

        $kunci = [];
        array_walk_recursive($props, function ($nilai, $k) use (&$kunci) {
            $kunci[] = (string) $k;
        });
        $this->assertSame([], array_values(array_intersect(self::KUNCI_TERLARANG, $kunci)), 'Kunci skor atau bobot bocor ke props Jelajah.');

        foreach ($props['trails']['data'] as $kartu) {
            $this->assertContains($kartu['fit']['label'], ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK', null]);
            $this->assertIsString($kartu['fit']['labelText']);
            $this->assertIsString($kartu['fit']['alasan']);
            array_walk_recursive($kartu['fit'], fn ($nilai, $k) => $this->assertIsNotFloat($nilai, "Nilai pecahan di kecocokan kartu ({$k})."));
        }

        foreach (['internalScore', 'internal_score', '"score"', '"weight"', 'probab'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $respons->getContent());
        }
    }

    /**
     * Jalur yang naik 280 m terbaca landai di sebelah jalur yang naik 1.650 m: tinggi garis
     * diukur dalam meter yang sama untuk semua kartu di halaman (SkalaProfil::naikBersama).
     */
    public function test_every_card_profile_uses_the_same_vertical_scale(): void
    {
        $gunung = Mountain::factory()->create();
        $landai = Trail::factory()->for($gunung)->published()->create(['name' => 'A Landai', 'elevation_profile' => $this->profil(280)]);
        $terjal = Trail::factory()->for($gunung)->published()->create(['name' => 'B Terjal', 'elevation_profile' => $this->profil(1650)]);

        $kartu = collect($this->jelajah(User::factory()->create())->viewData('page')['props']['trails']['data'])->keyBy('id');
        $puncak = fn (int $id) => max(array_column($kartu[$id]['garis'], 1));

        $this->assertSame(1.0, (float) $puncak($terjal->id));
        $this->assertEqualsWithDelta(280 / 1650, $puncak($landai->id), 0.001);
        $this->assertSame(1.0, (float) max(array_column($kartu[$landai->id]['garis'], 0)), 'Setiap garis memenuhi lebarnya sendiri.');
    }

    /**
     * R-007: jalur tanpa profil tidak diberi garis datar karangan.
     */
    public function test_a_trail_without_a_profile_draws_no_line(): void
    {
        Trail::factory()->for(Mountain::factory())->published()->create(['elevation_profile' => null]);

        $this->jelajah(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.garis', null));
    }

    /**
     * R-008, R-022: jalur yang menunggu data tampil terpisah dengan nama dan wilayahnya saja,
     * tidak ikut jumlah hasil, tidak ikut atribut saringan, dan tidak pernah diberi label.
     */
    public function test_waiting_trails_are_listed_apart_uncounted_and_never_rated(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Uji', 'province' => 'Jawa Tengah']);
        $terbit = Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Terbit']);
        $draf = Trail::factory()->for($gunung)->unpublished()->create(['name' => 'Jalur Draf']);

        $respons = $this->jelajah($this->pendaki());
        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ringkasan.jumlah', 1)
            ->where('jumlahTerbit', 1)
            ->has('trails.data', 1)
            ->where('trails.data.0.id', $terbit->id)
            ->has('atribut', 1)
            ->where('atribut.0.id', $terbit->id)
            ->where('menunggu', [[
                'id' => $draf->id,
                'name' => 'Jalur Draf',
                'url' => route('trails.show', $draf->slug),
                'mountain' => 'Gunung Uji',
                'province' => 'Jawa Tengah',
            ]]));

        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/jelajah/KartuBelumDinilai.tsx')));
        $this->assertStringContainsString('border-dashed', $kartu);
        $this->assertStringContainsString('Belum bisa dinilai', $kartu);
        $this->assertStringContainsString('Menunggu kurator Memount', $kartu);
    }

    /**
     * Gunung dan wilayah menuju halaman gunung (DK-44); status membawa asal catatannya.
     */
    public function test_a_card_links_its_mountain_and_names_the_status_source(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->create();

        $this->jelajah(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.gunungUrl', route('mountains.show', $gunung->slug))
            ->has('trails.data.0.status', fn (AssertableInertia $status) => $status
                ->hasAll(['status', 'label', 'cakupan', 'sumber', 'waktu', 'kesegaran'])));
    }

    /**
     * Susunan kartu (prototype 02): label untuk setiap kartu termasuk "Belum dinilai", simpan
     * 44 px dengan urungkan, "Kenapa?" sebagai tombol yang menyebut keadaannya, dan tautan ke
     * penilaian lengkap. Tidak ada angka persen.
     */
    public function test_the_card_shows_its_parts_in_the_disclosure_order(): void
    {
        $kartu = $this->tanpaKomentarJs(File::get(resource_path('js/components/trails/jelajah/KartuJalur.tsx')));

        $this->assertStringContainsString('<LabelKecocokan nilai={fit?.label ?? null} label={fit?.labelText ?? kataBelumDinilai} />', $kartu);
        $this->assertStringContainsString('<TombolSimpan jalur={jalur} tampilkanToast={tampilkanToast} />', $kartu);
        $this->assertMatchesRegularExpression('/aria-expanded=\{terbuka\}\s+aria-controls=\{idAlasan\}/', $kartu);
        $this->assertStringContainsString('Lihat penilaian lengkap', $kartu);
        $this->assertStringContainsString('{jalur.garis ? <GarisProfil', $kartu);
        $this->assertStringNotContainsString('%', $kartu);

        $urutan = array_map(fn (string $s) => strpos($kartu, $s), ['<LabelKecocokan', '<h2', '<dl', '<GarisProfil', 'data-fit-reason', '<BarisStatus', 'Kenapa?']);
        $this->assertSame($urutan, array_values(array_filter($urutan, 'is_int')), 'Setiap bagian kartu harus ada.');
        $terurut = $urutan;
        sort($terurut);
        $this->assertSame($terurut, $urutan, 'Urutan kartu mengikuti PRD §89: label, nama, angka, profil, alasan, status, Kenapa?.');
    }
}
```

`tests/Feature/ComparisonAsCostTest.php`:

1. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

dengan:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
```

2. Ganti:

```php
     * Perbandingan dapat dicapai dari halaman jelajah, bukan hanya dari rekomendasi.
```

dengan:

```php
     * Perbandingan dapat dicapai dari halaman jelajah, bukan hanya dari rekomendasi. Sejak W3
     * jalannya lewat tautan Simpanan berhitungan di kepala Jelajah (spec v3 bagian 6.2) ke
     * aksi banding di Simpanan (bagian 6.6); baki Jelajah lama tidak ada di prototype.
```

3. Ganti:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page->has('ditimbang', 2));
```

dengan:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page->where('jumlahSimpanan', 2));
        $this->actingAs($user)
            ->get(route('trails.saved'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 2));

        $this->assertStringContainsString('<TautanSimpanan', File::get(resource_path('js/pages/Trails/Index.tsx')));
        $this->assertStringContainsString('compare.url(', File::get(resource_path('js/components/trails/banding/AksiPilihan.tsx')));
```

`tests/Feature/ConsiderationTest.php`:

1. Ganti:

```php
     * Baki Jelajah menyebut batas simpanan yang sama dengan penolakannya.
     */
    public function test_the_jelajah_tray_states_the_configured_saved_limit(): void
```

dengan:

```php
     * Batas yang disebut ke pembaca sama dengan batas penolakannya. Diperbarui W3: baki
     * Jelajah dilepas bersama prop batasTimbangan, jadi batasnya kini disebut halaman
     * Simpanan, dan penolakan di Jelajah dijawab dialog "Simpanan sudah penuh"
     * (SavedUndoTest).
     */
    public function test_the_saved_page_states_the_configured_saved_limit(): void
```

2. Ganti:

```php
            ->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('batasTimbangan', 12));
```

dengan:

```php
            ->get(route('trails.saved'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('batas', 12));
```

`tests/Feature/DesignPrimitivesTest.php`:

1. Ganti:

```php
        $this->assertStringContainsString('{trails.data.length === 0 && adaSaringan ? (', $jelajah);
        $this->assertMatchesRegularExpression('/jenis="tersaring"[\s\S]*?Hapus semua saringan/', $jelajah);
        $this->assertMatchesRegularExpression('/\) : trails\.data\.length === 0 \? \(\s*<EmptyState\s+jenis="menunggu"/', $jelajah);
```

dengan:

```php
        // W1-F1: "menunggu" diperiksa lebih dulu dari jumlah jalur terbit yang tidak disaring,
        // baru sesudahnya "tersaring"; saringan tidak pernah disalahkan atas katalog kosong.
        $this->assertMatchesRegularExpression('/\{jumlahTerbit === 0 \? \(\s*<EmptyState\s+jenis="menunggu"/', $jelajah);
        $this->assertMatchesRegularExpression('/\) : trails\.data\.length === 0 \? \(\s*<EmptyState\s+jenis="tersaring"/', $jelajah);
        $this->assertLessThan(strpos($jelajah, 'jenis="tersaring"'), strpos($jelajah, 'jenis="menunggu"'));
        $this->assertMatchesRegularExpression('/jenis="tersaring"[\s\S]*?Tampilkan juga[\s\S]*?Hapus semua saringan/', $jelajah);
```

2. Ganti:

```php
     * baris tombol baki di 360 px.
```

dengan:

```php
     * baris tombol baki di 360 px.
     *
     * Diperbarui W3: baki banding Jelajah dilepas bersama --baki-banding (prototype 02 dan 15
     * tanpa baki), jadi jarak bawah toast kembali menghitung navigasi bawah dan bar aksi.
     * Baki banding Simpanan adalah bar aksi, jadi toast tetap melayang di atasnya.
```

3. Ganti:

```php
            'bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+var(--bar-aksi,0px)+var(--baki-banding,0px)+1rem)] z-40',
            $this->sumber('components/ui/ToastUrungkan.tsx'),
            'Toast melayang di atas bar aksi dan baki banding, bukan di tepi yang sama.'
```

dengan:

```php
            'bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+var(--bar-aksi,0px)+1rem)] z-40',
            $this->sumber('components/ui/ToastUrungkan.tsx'),
            'Toast melayang di atas bar aksi, bukan di tepi yang sama.'
```

`tests/Feature/InertiaLayoutTest.php`:

1. Ganti:

```php
        $form = File::get(resource_path('js/components/trails/FilterForm.tsx'));
        preg_match_all('/<(?:TextInput|Select)\s(?:[^>]*\s)?id="([^"]+)"/', $form, $kontrol);
        $this->assertNotEmpty($kontrol[1]);

        foreach ($kontrol[1] as $id) {
            $this->assertStringContainsString('htmlFor="'.$id.'"', $form, "Kontrol #{$id} tanpa label.");
```

dengan:

```php
        // Isian Jelajah: rentang tanjakan di saringan dan kotak cari. id berupa ekspresi
        // useId, jadi label dicocokkan dengan ekspresi yang sama persis.
        foreach (['js/components/trails/jelajah/SaringanJelajah.tsx' => 2, 'js/components/trails/jelajah/KotakCari.tsx' => 1] as $berkas => $jumlah) {
            $form = File::get(resource_path($berkas));
            preg_match_all('/<(?:TextInput|Select|input)\s(?:[^>]*\s)?id=(\{[^}]*\}\}|\{[^}]+\}|"[^"]+")/', $form, $kontrol);
            $this->assertCount($jumlah, $kontrol[1], $berkas);

            foreach ($kontrol[1] as $id) {
                $this->assertStringContainsString('htmlFor='.$id, $form, "Kontrol {$id} di {$berkas} tanpa label.");
            }
```

2. Ganti:

```php
     * berhenti tertutup penuh oleh baki.
```

dengan:

```php
     * berhenti tertutup penuh oleh baki. Diperbarui W3: baki itu dilepas bersama
     * --baki-banding, jadi scroll-padding-bottom kembali ke navigasi bawah dan bar aksi.
```

3. Ganti:

```php
        $this->assertStringContainsString('scroll-padding-bottom: calc(var(--nav-bawah) + var(--bar-aksi, 0px) + var(--baki-banding, 0px) + 1.25rem);', $css, 'scroll-padding-bottom harus memperhitungkan tinggi bar aksi dan baki banding.');
```

dengan:

```php
        $this->assertStringContainsString('scroll-padding-bottom: calc(var(--nav-bawah) + var(--bar-aksi, 0px) + 1.25rem);', $css, 'scroll-padding-bottom harus memperhitungkan tinggi bar aksi.');
```

4. Ganti:

```php
     * Perbaikan akhir W2 (F-1, tinjauan akhir I-1a): baki banding Jelajah menempel di tepi
     * bawah seperti strip pendakian dan bar aksi, tetapi semula di luar sistem jarak lapisan,
     * sehingga fokus papan ketik di 360 px berhenti tertutup penuh di baliknya (WCAG 2.4.11).
     * Pola strip dipakai ulang (ActiveHikeStripTest): tinggi yang membungkus diukur lewat
     * ResizeObserver, ditulis ke --baki-banding selagi baki tampil, dan dihapus saat baki
     * hilang (simpanan habis, jadi efeknya berjalan ulang menurut tampil) atau dilepas.
     *
     * Kelas sm: yang menanggung safe area sendiri dibuang: titik henti kerangka kini lg
     * (W2-K6), dan --nav-bawah selalu sudah memuat safe area, lewat tinggi bilah tab atau
     * lewat --bilah-tab yang bernilai safe area ketika bilahnya tidak ada atau tersembunyi.
     */
    public function test_the_comparison_tray_measures_itself_like_the_other_bottom_layers(): void
    {
        $baki = File::get(resource_path('js/components/trails/ConsiderationTray.tsx'));

        $this->assertStringContainsString('<div ref={wadah} className="sticky bottom-[var(--nav-bawah,0px)] z-30 ', $baki);
        $this->assertStringContainsString("akar.style.setProperty('--baki-banding', `\${elemen.offsetHeight}px`)", $baki, 'Tinggi baki yang membungkus diukur, bukan ditebak.');
        $this->assertStringContainsString('new ResizeObserver(ukur)', $baki);
        $this->assertMatchesRegularExpression("/akar\.style\.removeProperty\('--baki-banding'\);\s*\};\s*\}, \[tampil\]\);/", $baki, 'Variabel dihapus saat baki hilang atau dilepas, bukan tertinggal sesudah simpanan habis.');

        preg_match('/<div ref=\{wadah\} className="([^"]*)"/', $baki, $kelas);

        $this->assertStringNotContainsString('sm:', $kelas[1] ?? '', 'Baki tidak lagi berasumsi navigasi bawah hilang mulai 640 px.');
        $this->assertStringNotContainsString('safe-area-inset-bottom', $kelas[1] ?? '', 'Safe area sudah dimuat --nav-bawah; baki tidak menanggungnya dua kali.');
```

dengan:

```php
     * Setiap lapisan bawah yang dihitung jarak lapisan mengukur dirinya sendiri (perbaikan
     * akhir W2, F-1, tinjauan akhir I-1a): tinggi yang membungkus diukur lewat ResizeObserver,
     * ditulis ke <html> selagi lapisannya tampil, dan dihapus saat hilang atau dilepas, supaya
     * fokus papan ketik di 360 px tidak berhenti tertutup di baliknya (WCAG 2.4.11).
     *
     * Diperbarui W3: baki banding Jelajah dilepas (prototype 02 dan 15 tanpa baki; hitungan dan
     * pintu Simpanan ada di kepala halaman), dan --baki-banding ikut dilepas dari
     * scroll-padding dan jarak toast. Niat test semula dijaga dari dua arah: setiap variabel
     * lapisan yang dibaca scroll-padding-bottom dan jarak bawah toast punya penulis dan
     * penghapus, dan tidak ada variabel yang tertinggal tanpa penulis.
     */
    public function test_every_bottom_layer_the_spacing_counts_measures_itself(): void
    {
        $css = File::get(resource_path('css/app.css'));
        $toast = File::get(resource_path('js/components/ui/ToastUrungkan.tsx'));

        preg_match('/scroll-padding-bottom: ([^;]+);/', $css, $jarak);
        preg_match('/bottom-\[(calc\([^\]]+\))\] z-40/', $toast, $jarakToast);
        preg_match_all('/var\((--[a-z-]+)/', ($jarak[1] ?? '').' '.($jarakToast[1] ?? ''), $dibaca);

        // Diturunkan app.css dari keberadaan elemen, bukan hanya diukur komponennya.
        $dariCss = ['--nav-bawah', '--bilah-tab', '--strip-pendakian'];
        $diukur = array_values(array_diff(array_unique($dibaca[1]), $dariCss));

        $this->assertSame(['--bar-aksi'], $diukur, 'Lapisan bawah yang dihitung berubah; pastikan setiap lapisan baru mengukur dirinya.');

        $js = collect(File::allFiles(resource_path('js')))->map(fn ($berkas): string => $berkas->getContents())->implode("\n");

        foreach ($diukur as $variabel) {
            $this->assertStringContainsString("setProperty('{$variabel}'", $js, "{$variabel} dibaca tanpa penulis.");
            $this->assertStringContainsString("removeProperty('{$variabel}')", $js, "{$variabel} tidak pernah dihapus.");
        }

        $this->assertStringNotContainsString('--baki-banding', $css.$js, 'Baki banding Jelajah sudah dilepas W3; variabelnya tidak boleh tertinggal tanpa penulis.');
```

`tests/Feature/LongInputLayoutTest.php`:

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
     * Kartu dan komponen domain klaster Jelajah (W3) membungkus nama jalur, gunung, dan pos
     * yang panjang, tidak memotongnya (Review Focus rencana induk: teks panjang di 360 px).
     * Nama jalur sering panjang dan hanya berbeda di ujungnya ("Gede via Gunung Putri" dan
     * "Gede via Gunung Putri lewat Surya Kencana"), jadi nama yang terpotong tidak dapat
     * dibedakan dari tetangganya. Folder yang belum ada dilewati, dan berkas yang ditambahkan
     * tugas berikutnya ikut diperiksa.
     */
    public function test_domain_cards_wrap_long_names_instead_of_cutting_them(): void
    {
        $pemotong = [];

        foreach (['components/trails', 'pages/Trails', 'pages/Mountains', 'pages/Public'] as $folder) {
            if (! is_dir(resource_path('js/'.$folder))) {
                continue;
            }

            foreach (File::allFiles(resource_path('js/'.$folder)) as $berkas) {
                $isi = (string) preg_replace('#/\*.*?\*/#s', ' ', $berkas->getContents());

                if (preg_match('/\b(?:truncate|whitespace-nowrap|text-ellipsis|line-clamp-\d)\b/', $isi) === 1) {
                    $pemotong[] = $folder.'/'.str_replace('\\', '/', $berkas->getRelativePathname());
                }
            }
        }

        $this->assertSame([], $pemotong, 'Komponen domain memotong labelnya: '.implode(', ', $pemotong));
    }

    /**
     * Pameran primitif memuat satu blok selebar 360 px yang menaruh untaian Indonesia
```

`tests/Feature/RecommendationExclusionTest.php`:

1. Ganti:

```php
use App\Models\Checkpoint;
```

dengan:

```php
use App\Enums\RouteFitLabel;
use App\Models\Checkpoint;
```

2. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

dengan:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
```

3. Ganti:

```php
     * Baris jalur merender fit-line kapan pun ringkasannya ada, tanpa memeriksa
     * `eligible`. Yang menjaga kontraknya adalah label yang bernilai null untuk jalur
     * tak lengkap, sehingga lencananya jatuh ke "Belum dinilai" (kata tunggal untuk
     * UNKNOWN sejak perbaikan akhir W1, M-5; sebelumnya "Tidak dinilai"). Itu perilaku
     * yang benar, tetapi selama ini tidak ada test yang menguncinya: satu perubahan pada
     * fit-badge dapat memulihkan label publik tanpa ada yang menyadarinya.
```

dengan:

```php
     * Kartu merender labelnya kapan pun ringkasannya ada. Yang menjaga kontraknya adalah
     * label yang bernilai null untuk jalur tak lengkap, sehingga lencananya jatuh ke kata
     * UNKNOWN dari RouteFitLabel ("Belum dinilai", M-5 W1), dan jalur itu tidak dihitung
     * pada pilihan saringan kecocokan mana pun. Sejak W3 halaman juga menyebut ketiga label
     * sebagai pilihan saringan, jadi pemeriksaannya pada props kartu, bukan pada seluruh HTML.
```

4. Ganti:

```php
        $halaman = $this->actingAs($user)->get(route('trails.index'));

        $halaman->assertOk()
            ->assertSee($cacat->name)
            ->assertDontSee('Cocok')
            ->assertDontSee('Perlu persiapan')
            ->assertDontSee('Kurang cocok')
            ->assertSee('Belum dinilai');
```

dengan:

```php
        $this->actingAs($user)->get(route('trails.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trails.data.0.id', $cacat->id)
                ->where('trails.data.0.fit.label', null)
                ->where('trails.data.0.fit.labelText', RouteFitLabel::UNRATED_TEXT)
                ->where('trails.data.0.fit.layak', false)
                ->where('atribut.0.kecocokan', null)
                ->where('saringan.kecocokan', fn ($pilihan) => collect($pilihan)->sum('jumlah') === 0));
```

5. Ganti:

```php
        $halaman = $this->actingAs($user)->get(route('trails.index'));

        $halaman->assertOk()
            ->assertSee($lengkap->name)
            ->assertDontSee('Belum dinilai');
```

dengan:

```php
        $this->actingAs($user)->get(route('trails.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('trails.data.0.id', $lengkap->id)
                ->where('trails.data.0.fit.label', fn (?string $label) => in_array($label, ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK'], true))
                ->where('trails.data.0.fit.labelText', fn (string $teks) => $teks !== RouteFitLabel::UNRATED_TEXT));
```

`tests/Feature/ResultListBudgetTest.php`:

1. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

dengan:

```php
use App\Services\Trails\TrailSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
```

2. Ganti:

```php
    private const ANGGARAN_KB = 120;

    /**
```

dengan:

```php
    private const ANGGARAN_KB = 120;

    /**
     * Jelajah untuk 40 jalur berprofil rapat, diukur di simulasi W3 Tugas 4: 29 kB untuk satu
     * halaman kartu ditambah atribut semua kandidat, 75 kB bila semua kandidat dikirim
     * sebagai kartu penuh. Ambangnya di antara keduanya.
     */
    private const ANGGARAN_JELAJAH_KB = 45;

    /**
```

3. Ganti:

```php
    }

    /**
     * Ketika hasilnya memang sedikit, tidak ada yang perlu disembunyikan dan tidak
```

dengan:

```php
    }

    /**
     * Jelajah (W3 Tugas 4): kartu dikirim satu halaman (TrailSearch::PER_HALAMAN), dan untuk
     * seluruh kandidat hanya atribut saringan (enam nilai pendek per jalur) supaya "Tampilkan
     * N jalur" dihitung di klien tanpa kunjungan ke server. Anggaran ini menutup kembalinya
     * kartu penuh untuk seluruh kandidat.
     */
    public function test_jelajah_stays_within_its_size_budget(): void
    {
        [$user] = $this->hikerWithTrails(40);
        // Profil rekaman GPS yang rapat, seperti data sungguhan: garis kartu dijarangkan ke
        // SkalaProfil::MAKS_TITIK, jadi resolusi rekaman tidak menambah ukuran halaman.
        $profil = array_map(fn (int $i) => ['km' => $i / 20, 'm' => 1200 + (int) round(600 * sin($i / 40))], range(0, 199));
        Trail::query()->update(['elevation_profile' => json_encode($profil)]);

        $kb = strlen($this->actingAs($user)->get(route('trails.index'))->assertOk()->getContent()) / 1024;

        $this->assertLessThan(
            self::ANGGARAN_JELAJAH_KB,
            $kb,
            sprintf('Jelajah %.0f kB untuk 40 jalur, melewati anggaran %d kB.', $kb, self::ANGGARAN_JELAJAH_KB)
        );
    }

    public function test_jelajah_sends_one_page_of_cards_and_attributes_for_every_candidate(): void
    {
        [$user] = $this->hikerWithTrails(40);

        $this->actingAs($user)->get(route('trails.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('trails.data', TrailSearch::PER_HALAMAN)
                ->where('trails.total', 40)
                ->has('atribut', 40)
                ->has('atribut.0', 6));
    }

    /**
     * Pembaca yang dinilai memicu penilaian kecocokan dan status untuk seluruh kandidat;
     * keduanya batch, jadi jumlah kueri tidak tumbuh bersama jumlah jalur (PageQueryBudgetTest
     * menjaga jalur pembaca tanpa profil).
     */
    public function test_jelajah_queries_do_not_grow_with_the_number_of_trails_for_a_rated_reader(): void
    {
        [$user] = $this->hikerWithTrails(4);
        $this->actingAs($user)->get(route('trails.index'))->assertOk();

        $sedikit = $this->hitungKueri(route('trails.index'));
        Trail::factory()->published()->count(20)->create();
        $banyak = $this->hitungKueri(route('trails.index'));

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} kueri untuk 4 jalur, {$banyak} untuk 24.");
    }

    private function hitungKueri(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    /**
     * Ketika hasilnya memang sedikit, tidak ada yang perlu disembunyikan dan tidak
```

`tests/Feature/SavedRoutesTest.php`:

1. Ganti:

```php
    public function test_the_jelajah_tray_links_to_the_list(): void
```

dengan:

```php
    public function test_jelajah_links_to_the_list(): void
```

2. Ganti:

```php
        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('ditimbang', 1));
        $this->assertStringContainsString('href={saved.url()}', $this->sumber('js/components/trails/ConsiderationTray.tsx'));
```

dengan:

```php
        // W3: baki Jelajah diganti tautan Simpanan berhitungan di kepala halaman (ponsel) dan
        // di header layar lebar (spec v3 bagian 5.2 dan 6.2); pintunya tetap dua.
        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jumlahSimpanan', 1));
        $this->assertStringContainsString('aksi={<TautanSimpanan className="lg:hidden" />}', $this->sumber('js/pages/Trails/Index.tsx'));
        $this->assertStringContainsString('<TautanSimpanan', $this->sumber('js/layouts/KepalaLebar.tsx'));
        $this->assertStringContainsString('href={saved.url()}', $this->sumber('js/layouts/TautanSimpanan.tsx'));
```

Ganti seluruh isi `tests/Feature/TrailIndexSearchTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\TrailFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Filter pencarian sebelumnya menggabungkan kondisinya tanpa dikurung, sehingga jalur
 * mana pun yang nama gunungnya cocok lolos dari filter published(): draft dan arsip
 * tampil ke publik begitu kata kunci mengenai nama gunungnya.
 *
 * Halaman Jelajah kini React (Inertia); yang diuji adalah data yang dikirim server ke
 * halaman, lewat query string yang sama dengan yang dipakai peramban.
 */
class TrailIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_by_mountain_name_never_exposes_unpublished_trails(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Rahasia']);
        Trail::factory()->for($mountain)->unpublished()->create(['name' => 'Jalur Draft']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Terbit']);

        // Draft boleh tampil di bagian "belum tersedia" yang terpisah, tetapi tidak boleh
        // masuk daftar hasil. Pemisahannya diuji di AwaitingDataTest.
        $this->jelajah(['search' => 'Rahasia'])->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails.data', 1)
            ->where('trails.data.0.name', 'Jalur Terbit')
            ->has('menunggu', 1)
            ->where('menunggu.0.name', 'Jalur Draft'));
    }

    public function test_searching_by_mountain_name_never_exposes_archived_trails(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Arsip']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Diarsipkan', 'archived_at' => now()]);

        $this->jelajah(['search' => 'Arsip'])->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails.data', 0)
            ->has('menunggu', 0));
    }

    public function test_searching_by_trail_name_still_works(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Lain']);
        Trail::factory()->published()->for($mountain)->create(['name' => 'Jalur Cemoro Sewu']);

        $this->jelajah(['search' => 'Cemoro'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.data.0.name', 'Jalur Cemoro Sewu'));
    }

    public function test_search_combines_with_the_technical_filter(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Uji']);
        Trail::factory()->published()->for($mountain)->easy()->create(['name' => 'Jalur Ringan']);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Teknis']);

        $this->assertSame(['Jalur Ringan'], $this->namaHasil(['search' => 'Uji', 'technical' => 'LOW']));
    }

    public function test_distance_and_duration_filters_can_be_combined_with_search_and_technical_filters(): void
    {
        $mountain = Mountain::factory()->create(['name' => 'Gunung Filter']);
        Trail::factory()->published()->for($mountain)->easy()->create(['name' => 'Jalur Sempit']);
        Trail::factory()->published()->for($mountain)->create([
            'name' => 'Jalur Menengah',
            'distance_km' => 12,
            'estimated_duration_minutes' => 480,
            'technical_demand' => 'MODERATE',
        ]);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Panjang']);

        $this->assertSame(
            ['Jalur Sempit'],
            $this->namaHasil(['search' => 'Filter', 'distance' => 10, 'duration' => 6, 'technical' => 'LOW'])
        );
    }

    public function test_simple_sentence_search_uses_location_and_beginner_hint(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($malang)->highlyTechnical()->create(['name' => 'Jalur Kawi Sulit']);

        $this->assertSame(['Jalur Kawi Ringan'], $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula']));
    }

    public function test_simple_sentence_search_keeps_an_explicit_technical_filter(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        $jawaBarat = Mountain::factory()->create(['name' => 'Gunung Gede', 'province' => 'Jawa Barat']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($malang)->highlyTechnical()->create(['name' => 'Jalur Kawi Sulit']);
        Trail::factory()->published()->for($jawaBarat)->highlyTechnical()->create(['name' => 'Jalur Gede Sulit']);

        $this->assertSame(
            ['Jalur Kawi Sulit'],
            $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula', 'technical' => 'VERY_HIGH'])
        );
    }

    public function test_simple_sentence_search_keeps_an_explicit_region_filter(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        $jawaBarat = Mountain::factory()->create(['name' => 'Gunung Gede', 'province' => 'Jawa Barat']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($jawaBarat)->easy()->create(['name' => 'Jalur Gede Ringan']);

        $this->assertSame(
            ['Jalur Gede Ringan'],
            $this->namaHasil(['search' => 'gunung dekat Malang untuk pemula', 'region' => 'Jawa Barat'])
        );
    }

    public function test_unrecognized_simple_sentence_is_searched_as_literal_text(): void
    {
        $search = 'gunung dekat Kediri untuk pemula';
        $mountain = Mountain::factory()->create(['name' => $search]);
        Trail::factory()->published()->for($mountain)->highlyTechnical()->create(['name' => 'Jalur Literal']);

        $this->assertSame(['Jalur Literal'], $this->namaHasil(['search' => $search]));
    }

    /**
     * W1-F1: ketika belum ada satu pun jalur terbit, halaman menyebut siapa yang ditunggu apa
     * pun saringannya. jumlahTerbit tidak ikut disaring; DesignPrimitivesTest menjaga cabang
     * "menunggu" diperiksa sebelum cabang "tersaring".
     */
    public function test_nothing_published_is_reported_as_waiting_even_with_filters(): void
    {
        Trail::factory()->for(Mountain::factory())->unpublished()->create(['name' => 'Jalur Draf']);

        $this->jelajah(['jenis' => 'TEKTOK', 'status' => 'OPEN', 'teknisRendah' => '1', 'naikDari' => 100])
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('jumlahTerbit', 0)
                ->has('trails.data', 0)
                ->where('ringkasan.jumlah', 0)
                ->where('ringkasan.tersembunyi', [])
                ->has('menunggu', 1));
    }

    /**
     * Sisi sebaliknya: jalur terbit ada, saringan yang menyembunyikannya, dan halaman menyebut
     * saringan mana dan berapa jalur.
     */
    public function test_filters_that_hide_every_published_trail_say_what_they_hide(): void
    {
        Trail::factory()->for(Mountain::factory())->published()->create(['name' => 'Jalur Sedang']);

        $this->jelajah(['teknisRendah' => '1'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jumlahTerbit', 1)
            ->has('trails.data', 0)
            ->where('ringkasan.tersembunyi', [['kunci' => 'teknis', 'label' => 'tuntutan teknis', 'jumlah' => 1]]));
    }

    /**
     * "Tampilkan juga" menaruh yang tersembunyi SESUDAH hasil dan menyebut saringan yang
     * menyembunyikannya; jumlah hasil tidak ikut bertambah.
     */
    public function test_show_also_lists_hidden_trails_after_the_results_and_names_the_filter(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Awal Sedang']);
        Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Jalur Ringan']);

        $this->jelajah(['teknisRendah' => '1', 'tampilkanSemua' => '1'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ringkasan.jumlah', 1)
            ->where('trails.data.0.name', 'Jalur Ringan')
            ->where('trails.data.0.tersembunyiOleh', null)
            ->where('trails.data.1.name', 'Jalur Awal Sedang')
            ->where('trails.data.1.tersembunyiOleh', 'tuntutan teknis'));
    }

    /**
     * Status Tutup disembunyikan secara bawaan dan disebut sebagai tersembunyi, tidak dibuang
     * diam-diam. Status yang belum diketahui tetap tampil: tidak diketahui bukan tutup (R-006).
     */
    public function test_closed_trails_are_hidden_by_default_and_counted_as_hidden(): void
    {
        $gunung = Mountain::factory()->create();
        $tutup = Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Tutup']);
        $tutup->officialStatuses()->update(['status' => OfficialStatusValue::CLOSED->value]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Belum Diketahui']);

        $this->jelajah([])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filter.status', ['OPEN', 'RESTRICTED', 'UNKNOWN'])
            ->where('ringkasan.jumlah', 1)
            ->where('trails.data.0.name', 'Jalur Belum Diketahui')
            ->where('trails.data.0.status.status', null)
            ->where('trails.data.0.status.label', OfficialStatusValue::UNKNOWN->label())
            ->where('ringkasan.tersembunyi', [['kunci' => 'status', 'label' => 'status resmi', 'jumlah' => 1]]));

        $this->assertSame(['Jalur Belum Diketahui', 'Jalur Tutup'], $this->namaHasil(['status' => 'UNKNOWN,CLOSED']));
    }

    /**
     * Jenis trip dibaca dari data jalur: tektok bila durasinya muat satu hari, camping bila
     * area kemahnya ada. Parameter kosong berarti semua pilihan dilepas, jadi tidak ada yang
     * lolos; tanpa parameter berarti tanpa saringan.
     */
    public function test_trip_type_filter_reads_duration_and_camping(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Tektok', 'estimated_duration_minutes' => 300, 'camping_available' => false]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Kemah', 'estimated_duration_minutes' => 1800, 'camping_available' => true]);

        $this->assertSame(['Jalur Tektok'], $this->namaHasil(['jenis' => 'TEKTOK']));
        $this->assertSame(['Jalur Kemah'], $this->namaHasil(['jenis' => 'CAMPING']));
        $this->assertSame([], $this->namaHasil(['jenis' => '']));
        $this->assertSame(['Jalur Kemah', 'Jalur Tektok'], $this->namaHasil([]));
    }

    /**
     * R-007: tanjakan yang tidak diketahui tidak masuk rentang apa pun, termasuk rentang
     * yang dimulai dari nol, dan disebut sebagai tersembunyi oleh saringan tanjakan.
     */
    public function test_a_climb_range_never_admits_an_unknown_climb(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur 800 m', 'elevation_gain_m' => 800]);
        $warisan = Trail::factory()->for($gunung)->published()->create(['name' => 'Jalur Tanpa Tanjakan']);
        $warisan->forceFill(['elevation_gain_m' => null])->saveQuietly();

        $this->assertSame(['Jalur 800 m'], $this->namaHasil(['naikDari' => 0]));
        $this->assertSame(['Jalur 800 m'], $this->namaHasil(['naikDari' => 500, 'naikSampai' => 900]));
        $this->assertSame([], $this->namaHasil(['naikSampai' => 700]));

        $this->jelajah(['naikDari' => 0])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ringkasan.tersembunyi', [['kunci' => 'naik', 'label' => 'tanjakan', 'jumlah' => 1]]));
    }

    /**
     * FR-27: kalimat dibaca menjadi chip yang dapat dilepas. Melepas satu tafsiran berhenti
     * menyaring dengannya dan mencatatnya di "dilepas", supaya "Kembalikan tafsiran kalimat"
     * dapat mengembalikannya.
     */
    public function test_a_sentence_reading_is_shown_as_chips_that_can_be_released(): void
    {
        $malang = Mountain::factory()->create(['name' => 'Gunung Kawi', 'province' => 'Jawa Timur']);
        Trail::factory()->published()->for($malang)->easy()->create(['name' => 'Jalur Kawi Ringan']);
        Trail::factory()->published()->for($malang)->highlyTechnical()->create(['name' => 'Jalur Kawi Sulit']);
        $kalimat = 'gunung dekat Malang untuk pemula';

        $this->jelajah(['search' => $kalimat])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tafsiran.chip', [
                ['kunci' => 'wilayah', 'label' => 'Wilayah: Jawa Timur (Malang)'],
                ['kunci' => 'teknis', 'label' => 'Tuntutan teknis: rendah'],
            ])
            ->where('tafsiran.dilepas', [])
            ->where('saringan.teknisRendah.aktif', true));

        $this->jelajah(['search' => $kalimat, 'abaikan' => 'teknis'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tafsiran.chip', [['kunci' => 'wilayah', 'label' => 'Wilayah: Jawa Timur (Malang)']])
            ->where('tafsiran.dilepas', ['teknis'])
            ->where('saringan.teknisRendah.aktif', false));

        $this->assertSame(['Jalur Kawi Ringan', 'Jalur Kawi Sulit'], $this->namaHasil(['search' => $kalimat, 'abaikan' => 'teknis']));
        $this->assertSame(['Jalur Kawi Ringan'], $this->namaHasil(['search' => $kalimat, 'abaikan' => 'bukan-kunci']));
    }

    /**
     * Hitungan setiap pilihan saringan memakai semua saringan LAIN, bukan dirinya sendiri:
     * pilihan "Camping" tetap menyebut berapa jalur yang akan tampil bila dipilih.
     */
    public function test_each_option_counts_the_trails_it_would_show(): void
    {
        $gunung = Mountain::factory()->create();
        Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Ringan Tektok', 'camping_available' => false]);
        Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Ringan Kemah', 'estimated_duration_minutes' => 1800]);
        Trail::factory()->for($gunung)->published()->create(['name' => 'Sedang Kemah', 'estimated_duration_minutes' => 1800]);

        $this->jelajah(['teknisRendah' => '1', 'jenis' => 'TEKTOK'])->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ringkasan.jumlah', 1)
            ->where('saringan.jenis', [
                ['nilai' => 'TEKTOK', 'label' => 'Tektok', 'jumlah' => 1],
                ['nilai' => 'CAMPING', 'label' => 'Camping', 'jumlah' => 1],
            ])
            ->where('saringan.teknisRendah.jumlah', 1)
            ->has('atribut', 3));
    }

    /**
     * Saringan kecocokan hanya ditawarkan kepada pembaca yang dinilai, dan hanya meloloskan
     * label yang dipilih; urutannya mengikuti mesin yang sama dengan kartunya.
     */
    public function test_the_fit_filter_is_offered_to_a_rated_reader_and_follows_the_engine(): void
    {
        $gunung = Mountain::factory()->create();
        $ringan = Trail::factory()->for($gunung)->published()->easy()->create(['name' => 'Jalur Ringan']);
        $sulit = Trail::factory()->for($gunung)->published()->highlyTechnical()->create(['name' => 'Jalur Sulit']);
        $pemula = User::factory()->create();
        $pemula->profile()->create(['experience_level' => ExperienceLevel::BEGINNER->value, 'completed_at' => now()]);
        $pemula = $pemula->fresh();

        $mesin = app(TrailFitService::class)->forTrails($pemula, Trail::query()->whereKey([$ringan->id, $sulit->id])->get());
        $labelRingan = $mesin[$ringan->id]->label?->value;
        $this->assertNotSame($labelRingan, $mesin[$sulit->id]->label?->value, 'Kedua jalur harus berlabel berbeda supaya saringannya benar-benar diuji.');

        $this->actingAs(User::factory()->create())->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('saringan.kecocokan', null)->where('dinilai', false));

        $this->actingAs($pemula)->get(route('trails.index', ['kecocokan' => $labelRingan]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dinilai', true)
                ->has('saringan.kecocokan', 3)
                ->has('trails.data', 1)
                ->where('trails.data.0.name', 'Jalur Ringan')
                ->where('trails.data.0.fit.label', $labelRingan));
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function jelajah(array $filter): TestResponse
    {
        return $this->actingAs(User::factory()->create())->get(route('trails.index', $filter))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<int, string>
     */
    private function namaHasil(array $filter): array
    {
        $data = $this->jelajah($filter)->viewData('page')['props']['trails']['data'];

        return array_map(fn (array $baris) => $baris['name'], $data);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrailIndexSearch|TrailCard|SavedUndo|ResultListBudget|DesignPrimitives|InertiaLayout|SavedRoutes|ComparisonAsCost|RecommendationExclusion|Consideration|ShellFeedback|ActiveHikeStrip|LongInputLayout'`
Expected: FAIL: 181 test, 149 lulus, 20 gagal, 12 error. Antara lain `DesignPrimitivesTest::test_jelajah_names_who_is_awaited_when_nothing_is_published`, `DesignPrimitivesTest::test_the_action_bar_floats_on_the_single_elevation`, `InertiaLayoutTest::test_fixed_layers_leave_room_for_the_content_and_the_keyboard_focus`.

- [ ] **Step 3: Tulis implementasinya.**

**Data, rute, dan konfigurasi**

`config/hiking.php`:

Ganti:

```php
    | Simpanan dan Banding Jalur (DK-05, DK-29)
```

dengan:

```php
    | Jelajah (spec v3 bagian 6.2)
    |--------------------------------------------------------------------------
    */
    'jelajah' => [
        // Kandidat yang dinilai di memori untuk satu permintaan (kecocokan, status, saringan).
        // Kueri tidak tumbuh bersama angka ini; waktu hitungnya ya, jadi dibatasi, dan halaman
        // menyebut bila hasilnya terpotong alih-alih memotongnya diam-diam.
        'batas_kandidat' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Simpanan dan Banding Jalur (DK-05, DK-29)
```

`routes/web.php`:

Ganti:

```php
    Route::delete('trails/saved/{trail}', [ConsiderationController::class, 'destroy'])->whereNumber('trail')->name('trails.saved.remove');
```

dengan:

```php
    Route::delete('trails/saved/{trail}', [ConsiderationController::class, 'destroy'])->whereNumber('trail')->name('trails.saved.remove');
    // Simpan idempoten (spec v3 bagian 6.2 dan 9.1): tombol simpan dan Urungkan sesudah
    // "Hapus dari simpanan", yang mengembalikan jalur beserta catatannya.
    Route::post('trails/saved/{trail}', [ConsiderationController::class, 'store'])->whereNumber('trail')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,simpan-jalur')->name('trails.saved.store');
```

**Server**

Buat `app/Http/Requests/Trails/StoreSavedTrailRequest.php`:

```php
<?php

namespace App\Http\Requests\Trails;

use App\Models\TrailConsideration;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Simpan satu jalur (spec v3 bagian 6.2 dan 9.1), juga Urungkan sesudah "Hapus dari simpanan"
 * yang mengirim kembali catatan pribadi yang ikut terhapus.
 *
 * Hanya `catatan` yang dibaca. Jalur datang dari rute dan pemilik dari sesi, jadi `user_id`
 * atau `trail_id` di badan permintaan tidak pernah sampai ke basis data. Catatan boleh
 * kosong (simpan biasa, atau jalur yang memang tidak bercatatan) dan dibatasi seperti
 * catatan biasa.
 */
class StoreSavedTrailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'catatan' => ['nullable', 'string', 'max:'.TrailConsideration::CATATAN_MAKS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.string' => 'Catatan pribadi harus berupa teks.',
            'catatan.max' => 'Catatan pribadi tidak boleh lebih dari '.TrailConsideration::CATATAN_MAKS.' karakter.',
        ];
    }
}
```

Buat `app/Http/Resources/KartuJalurResource.php`:

```php
<?php

namespace App\Http\Resources;

use App\Models\Trail;
use App\Services\RouteFit\TrailFitSummary;
use Illuminate\Http\Request;

/**
 * Kartu jalur Jelajah (spec v3 bagian 6.2, prototype 02 dan 15): baris jalur ditambah yang
 * hanya dibutuhkan kartunya, yaitu tautan ke halaman gunung, status akses beserta asalnya,
 * garis profil pada skala yang sama dengan kartu lain di halaman itu, penanda catatan
 * pribadi (supaya melepas simpanan yang bercatatan bertanya dulu), dan saringan yang
 * menyembunyikannya bila kartu itu tampil lewat "Tampilkan juga".
 *
 * Skor internal tetap tidak ikut (BR-09): kecocokan datang dari TrailFitSummary.
 *
 * @mixin Trail
 */
class KartuJalurResource extends TrailRowResource
{
    /**
     * @param  array<string, mixed>  $status  bentuk StatusJalurRingkas
     * @param  array<int, array{0: float, 1: float}>|null  $garis  titik SkalaProfil::naikBersama
     */
    public function __construct(
        Trail $trail,
        ?TrailFitSummary $fit,
        bool $ditimbang,
        private readonly array $status,
        private readonly ?array $garis,
        private readonly bool $adaCatatan,
        private readonly ?string $tersembunyiOleh,
    ) {
        parent::__construct($trail, $fit, $ditimbang);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'gunungUrl' => $this->mountain ? route('mountains.show', $this->mountain) : null,
            'status' => $this->status,
            'garis' => $this->garis,
            'adaCatatan' => $this->adaCatatan,
            'tersembunyiOleh' => $this->tersembunyiOleh,
        ];
    }
}
```

Buat `app/Services/Trails/HalamanJelajah.php`:

```php
<?php

namespace App\Services\Trails;

use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\TechnicalDemand;
use App\Enums\TripType;
use App\Http\Resources\KartuJalurResource;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\RouteFit\TrailFitSummary;
use App\Services\TrailFitService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Props halaman Jelajah (spec v3 bagian 6.2, prototype 02 dan 15; W3 Tugas 4).
 *
 * Satu kelas halaman seperti RingkasanDasbor dan TrailDetailPage (spec v3 bagian 9.2):
 * TrailSearch memilih kandidat dan memegang aturan saringan, kelas ini menilai kecocokan dan
 * status seluruh kandidat sekaligus (batch, kueri tidak tumbuh bersama jumlah jalur), memisah
 * hasil dari yang disembunyikan saringan, menghitung setiap pilihan saringan, lalu membentuk
 * kartu untuk satu halaman hasil.
 *
 * Kandidat dibatasi hiking.jelajah.batas_kandidat supaya penilaian di memori tetap terbatas;
 * bila terpotong, halaman mengatakannya. Jumlah jalur terbit dikirim terpisah (W1-F1):
 * ketika belum ada satu pun jalur terbit, halaman menyebut siapa yang ditunggu, apa pun
 * saringannya, alih-alih menyalahkan saringan.
 */
final class HalamanJelajah
{
    /** Nama setiap saringan dalam kalimat "N jalur disembunyikan saringan ...". */
    private const NAMA_SARINGAN = [
        'status' => 'status resmi',
        'kecocokan' => 'kecocokan',
        'teknis' => 'tuntutan teknis',
        'jenis' => 'jenis trip',
        'naik' => 'tanjakan',
    ];

    /** Urutan kecocokan pada daftar: Cocok lebih dulu, yang tidak dinilai paling akhir. */
    private const PERINGKAT = ['COCOK' => 0, 'PERLU_PERSIAPAN' => 1, 'KURANG_COCOK' => 2];

    public function __construct(
        private readonly TrailFitService $fit,
        private readonly StatusJalurRingkas $status,
        private readonly PetaSebaranJalur $peta,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(TrailSearch $cari, User $pembaca, Request $request): array
    {
        $batas = (int) config('hiking.jelajah.batas_kandidat', 500);
        $kandidat = $cari->kandidat($batas);
        $terpotong = $kandidat->count() > $batas;
        $kandidat = $kandidat->take($batas)->values();

        $dinilai = $pembaca->hasCompletedProfile();
        $fit = $dinilai ? $this->fit->forTrails($pembaca, $kandidat) : [];
        $status = $this->status->untuk($kandidat);
        $atribut = $kandidat->mapWithKeys(fn (Trail $t) => [$t->id => $this->atribut($t, $fit[$t->id] ?? null, $status[$t->id])])->all();

        $gagal = $kandidat->mapWithKeys(fn (Trail $t) => [$t->id => $cari->gagalPada($atribut[$t->id], $dinilai)])->all();
        $hasil = $this->urutkan($kandidat->filter(fn (Trail $t) => $gagal[$t->id] === null), $atribut, $dinilai);
        $tersembunyi = $kandidat->reject(fn (Trail $t) => $gagal[$t->id] === null)->values();
        $daftar = $cari->tampilkanSemua() ? $hasil->concat($tersembunyi) : $hasil;

        $nomor = max(1, $request->integer('page', 1));
        $halaman = new LengthAwarePaginator(
            $daftar->forPage($nomor, TrailSearch::PER_HALAMAN)->values(),
            $daftar->count(),
            TrailSearch::PER_HALAMAN,
            $nomor,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        /** @var Collection<int, Trail> $isi */
        $isi = collect($halaman->items());
        $garis = SkalaProfil::naikBersama($isi->mapWithKeys(fn (Trail $t) => [$t->id => $t->elevation_profile])->all());
        $catatan = TrailConsideration::query()->whereBelongsTo($pembaca)->pluck('note', 'trail_id');
        $menunggu = $cari->menunggu();

        return [
            'filter' => $cari->filter(),
            'tafsiran' => $cari->tafsiran(),
            'trails' => [
                'data' => $isi->map(fn (Trail $t) => (new KartuJalurResource(
                    $t,
                    $fit[$t->id] ?? null,
                    $catatan->has($t->id),
                    $status[$t->id],
                    $garis['garis'][$t->id] ?? null,
                    $catatan->get($t->id) !== null,
                    $gagal[$t->id] === null ? null : self::NAMA_SARINGAN[$gagal[$t->id]],
                ))->resolve($request))->values()->all(),
                'total' => $halaman->total(),
                'links' => $halaman->linkCollection()->map(fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']])->all(),
            ],
            'ringkasan' => [
                'jumlah' => $hasil->count(),
                'tersembunyi' => collect(TrailSearch::SARINGAN)
                    ->map(fn (string $kunci) => ['kunci' => $kunci, 'label' => self::NAMA_SARINGAN[$kunci], 'jumlah' => count(array_keys($gagal, $kunci, true))])
                    ->filter(fn (array $s) => $s['jumlah'] > 0)
                    ->values()
                    ->all(),
                'terpotong' => $terpotong ? $batas : null,
                'urutan' => $dinilai ? 'kecocokan' : 'nama',
            ],
            'saringan' => $this->saringan($cari, $kandidat, $atribut, $dinilai),
            'atribut' => $kandidat->map(fn (Trail $t) => ['id' => $t->id, ...$atribut[$t->id]])->values()->all(),
            'dinilai' => $dinilai,
            'dasarPenilaian' => $dinilai ? $this->dasarPenilaian($pembaca) : null,
            'jumlahTerbit' => Trail::query()->published()->count(),
            'menunggu' => $menunggu->map(fn (Trail $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'url' => route('trails.show', $t->slug),
                'mountain' => $t->mountain?->name,
                'province' => $t->mountain?->province,
            ])->values()->all(),
            // Bentuk jalur, bukan titik gunung (keputusan F2), untuk satu halaman hasil.
            'petaSebaran' => $this->peta->untuk($isi),
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
        ];
    }

    /**
     * Dasar label di Jelajah, seperti prototype 02: tingkat pengalaman dan tanjakan tertinggi
     * yang diisi sendiri. Rincian lengkapnya ada di halaman penilaian kecocokan.
     */
    private function dasarPenilaian(User $pembaca): string
    {
        $tertinggi = $pembaca->experience?->highest_elevation_gain_m;

        return implode(', ', array_filter([
            $pembaca->profile?->experience_level?->label(),
            $tertinggi !== null ? 'tanjakan tertinggi '.number_format($tertinggi, 0, ',', '.').' m' : null,
        ]));
    }

    /**
     * Atribut saringan satu jalur, dihitung sekali dan dipakai tiga kali: menyaring hasil,
     * menghitung pilihan, dan (di klien) menghitung "Tampilkan N jalur".
     *
     * @param  array<string, mixed>  $status
     * @return array{kecocokan: string|null, jenis: array<int, string>, teknisRendah: bool, status: string, naik: int|null}
     */
    private function atribut(Trail $t, ?TrailFitSummary $fit, array $status): array
    {
        $durasi = $t->estimated_duration_minutes;

        return [
            'kecocokan' => $fit?->label?->value,
            'jenis' => array_values(array_filter([
                $durasi !== null && $durasi > 0 && $durasi <= TripType::TEKTOK->typicalMaxDurationMinutes() ? TripType::TEKTOK->value : null,
                $t->camping_available ? TripType::CAMPING->value : null,
            ])),
            'teknisRendah' => $t->technical_demand === TechnicalDemand::LOW,
            'status' => $status['status'] ?? OfficialStatusValue::UNKNOWN->value,
            'naik' => $t->elevation_gain_m,
        ];
    }

    /**
     * Hitungan setiap pilihan saringan: jalur yang lolos semua saringan lain dan bernilai
     * pilihan itu. Kecocokan hanya ditawarkan kepada pembaca yang dinilai.
     *
     * @param  Collection<int, Trail>  $kandidat
     * @param  array<int, array<string, mixed>>  $atribut
     * @return array<string, mixed>
     */
    private function saringan(TrailSearch $cari, Collection $kandidat, array $atribut, bool $dinilai): array
    {
        $lolosKecuali = fn (string $kunci) => $kandidat->filter(fn (Trail $t) => $cari->gagalPada($atribut[$t->id], $dinilai, $kunci) === null);

        $tanpaKecocokan = $lolosKecuali('kecocokan');
        $tanpaJenis = $lolosKecuali('jenis');
        $tanpaStatus = $lolosKecuali('status');
        $tanpaTeknis = $lolosKecuali('teknis');

        return [
            'kecocokan' => $dinilai ? array_map(fn (RouteFitLabel $l) => [
                'nilai' => $l->value,
                'label' => $l->label(),
                'jumlah' => $tanpaKecocokan->filter(fn (Trail $t) => $atribut[$t->id]['kecocokan'] === $l->value)->count(),
            ], RouteFitLabel::cases()) : null,
            'jenis' => array_map(fn (TripType $j) => [
                'nilai' => $j->value,
                'label' => $j === TripType::TEKTOK ? 'Tektok' : 'Camping',
                'jumlah' => $tanpaJenis->filter(fn (Trail $t) => in_array($j->value, $atribut[$t->id]['jenis'], true))->count(),
            ], TrailSearch::JENIS),
            'teknisRendah' => [
                'aktif' => $cari->filter()['teknisRendah'] || $cari->teknisDariTafsiran(),
                'jumlah' => $tanpaTeknis->filter(fn (Trail $t) => $atribut[$t->id]['teknisRendah'])->count(),
            ],
            'status' => array_map(fn (OfficialStatusValue $s) => [
                'nilai' => $s->value,
                'label' => $s->label(),
                'jumlah' => $tanpaStatus->filter(fn (Trail $t) => $atribut[$t->id]['status'] === $s->value)->count(),
            ], [OfficialStatusValue::OPEN, OfficialStatusValue::RESTRICTED, OfficialStatusValue::UNKNOWN, OfficialStatusValue::CLOSED]),
        ];
    }

    /**
     * Hasil diurut kecocokan untuk pembaca yang dinilai (Cocok lebih dulu), lalu nama; untuk
     * pembaca tanpa profil cukup nama, karena tidak ada kecocokan untuk diurutkan.
     *
     * @param  Collection<int, Trail>  $hasil
     * @param  array<int, array<string, mixed>>  $atribut
     * @return Collection<int, Trail>
     */
    private function urutkan(Collection $hasil, array $atribut, bool $dinilai): Collection
    {
        if (! $dinilai) {
            return $hasil->values();
        }

        return $hasil
            ->sortBy([
                fn (Trail $a, Trail $b) => (self::PERINGKAT[$atribut[$a->id]['kecocokan']] ?? 3) <=> (self::PERINGKAT[$atribut[$b->id]['kecocokan']] ?? 3),
                fn (Trail $a, Trail $b) => strcmp($a->name, $b->name),
            ])
            ->values();
    }
}
```

`app/Http/Controllers/Trails/ConsiderationController.php`:

1. Ganti:

```php
use App\Models\Trail;
```

dengan:

```php
use App\Http\Requests\Trails\StoreSavedTrailRequest;
use App\Models\Trail;
use App\Models\TrailConsideration;
```

2. Ganti:

```php
     * Menghapus dari simpanan, sumber yang sama dengan baki Jelajah dan halaman Banding.
```

dengan:

```php
     * Menghapus dari simpanan, sumber yang sama dengan hitungan Simpanan dan halaman Banding.
```

3. Ganti:

```php
        $consideration->remove($request->user(), $trail);
```

dengan:

```php
        // Catatan dibaca sebelum barisnya dihapus, lalu dititipkan satu kali di sesi pemiliknya
        // sendiri, supaya Urungkan dapat memulihkannya utuh (spec v3 bagian 9.1).
        $catatan = TrailConsideration::query()
            ->where('user_id', $request->user()->id)
            ->where('trail_id', $trail)
            ->value('note');

        $consideration->remove($request->user(), $trail);
```

4. Ganti:

```php
        return back()->with(
            'pengumuman',
            $nama ? $nama.' '.Str::lcfirst(ConsiderationOutcome::DIHAPUS->label()).'.' : ''
        );
```

dengan:

```php
        return back()
            ->with('pengumuman', $nama ? $nama.' '.Str::lcfirst(ConsiderationOutcome::DIHAPUS->label()).'.' : '')
            ->with('simpanan-terhapus', $nama ? ['trailId' => $trail, 'nama' => $nama, 'catatan' => $catatan] : null);
    }

    /**
     * Simpan idempoten: tombol simpan kartu dan bar aksi, dan Urungkan sesudah "Hapus dari
     * simpanan" yang mengirim kembali catatannya. Hanya jalur terbit, sama dengan toggle();
     * simpanan yang penuh ditolak dengan pesan yang sama.
     */
    public function store(StoreSavedTrailRequest $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $jalur = Trail::published()->findOrFail($trail);

        $hasil = $consideration->simpan($request->user(), $jalur, $request->validated('catatan'));

        if ($hasil === ConsiderationOutcome::DITOLAK) {
            return back()->with(
                'timbangan-penuh',
                ConsiderationOutcome::DITOLAK->label().' ('.$consideration->batasSimpanan().' jalur). Hapus satu dulu sebelum menyimpan jalur lain.'
            );
        }

        return back()->with('pengumuman', $jalur->name.' '.Str::lcfirst(ConsiderationOutcome::DITAMBAHKAN->label()).'.');
```

`app/Http/Controllers/Trails/SavedRoutesController.php`:

Ganti:

```php
 * dengan baki di Jelajah dan pilihan bawaan halaman Banding. Simpanan yang hidup di sesi
 * hilang setiap kali pendaki keluar, karena Logout memanggil Session::invalidate().
```

dengan:

```php
 * dengan hitungan Simpanan di kepala halaman dan pilihan bawaan halaman Banding. Simpanan
 * yang hidup di sesi hilang setiap kali pendaki keluar, karena Logout memanggil
 * Session::invalidate().
```

Ganti seluruh isi `app/Http/Controllers/Trails/TrailIndexController.php` dengan:

```php
<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Services\Trails\HalamanJelajah;
use App\Services\Trails\TrailSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jelajah: telusur, tafsiran kalimat, dan saringan jalur (PRD §94, FR-27, spec v3 bagian 6.2).
 * Isinya disusun HalamanJelajah; controller ini hanya membaca permintaan dan merender.
 */
class TrailIndexController extends Controller
{
    public function __invoke(Request $request, HalamanJelajah $halaman): Response
    {
        return Inertia::render('Trails/Index', [
            'title' => 'Jelajahi Jalur',
            'pengumuman' => session('pengumuman', ''),
        ] + $halaman->props(TrailSearch::dariInput($request->query()), $request->user(), $request));
    }
}
```

`app/Http/Middleware/HandleInertiaRequests.php`:

Ganti:

```php
                'timbanganPenuh' => fn () => $request->session()->get('timbangan-penuh'),
```

dengan:

```php
                'timbanganPenuh' => fn () => $request->session()->get('timbangan-penuh'),
                // Jalur yang baru dihapus dari simpanan beserta catatannya, satu kali dan hanya
                // di sesi pemiliknya: toast Urungkan memulihkannya utuh (W3).
                'simpananTerhapus' => fn () => $request->session()->get('simpanan-terhapus'),
```

`app/Http/Resources/TrailRowResource.php`:

Ganti:

```php
                'denganRencana' => $this->fit->denganRencana,
```

dengan:

```php
                'denganRencana' => $this->fit->denganRencana,
                // Lolos syarat dasar. Bila tidak, labelnya null (kata UNKNOWN "Belum dinilai")
                // dan kartunya menulis alasannya, jadi tidak terbaca sebagai nilai rendah (R-006, R-008).
                'layak' => $this->fit->eligible,
                'mengapaSesuai' => $this->fit->mengapaSesuai,
                'perluDiperhatikan' => $this->fit->perluDiperhatikan,
```

`app/Services/ConsiderationService.php`:

Ganti:

```php
    }

    /**
     * Baris simpanan milik $user untuk satu jalur, atau 404.
```

dengan:

```php
    }

    /**
     * Menyimpan satu jalur secara idempoten, dengan catatan pribadi bila ada (spec v3 bagian
     * 6.2 dan 9.1). Dipakai tombol simpan kartu dan bar aksi, dan oleh Urungkan sesudah
     * "Hapus dari simpanan" untuk mengembalikan jalurnya beserta catatannya.
     *
     * Berbeda dengan toggle(), kiriman ulang atau halaman yang basi tidak pernah membalik
     * simpanan menjadi terhapus: jalur yang sudah tersimpan tetap tersimpan dan catatannya
     * tidak ditimpa. Batas simpanan dijaga dengan kunci yang sama dengan toggle(). Catatan
     * ditulis lewat simpanCatatan() setelah barisnya dipastikan milik $user.
     */
    public function simpan(User $user, Trail $trail, ?string $catatan = null): ConsiderationOutcome
    {
        return DB::transaction(function () use ($user, $trail, $catatan) {
            User::whereKey($user->id)->lockForUpdate()->first();

            if (TrailConsideration::where('user_id', $user->id)->where('trail_id', $trail->id)->exists()) {
                return ConsiderationOutcome::DITAMBAHKAN;
            }

            if ($this->jumlahSimpanan($user) >= $this->batasSimpanan()) {
                return ConsiderationOutcome::DITOLAK;
            }

            $baris = TrailConsideration::createOrFirst(['user_id' => $user->id, 'trail_id' => $trail->id]);
            $this->simpanCatatan($baris, $catatan);

            return ConsiderationOutcome::DITAMBAHKAN;
        });
    }

    /**
     * Baris simpanan milik $user untuk satu jalur, atau 404.
```

`app/Services/RouteFit/TrailFitSummary.php`:

1. Ganti:

```php
    public function __construct(
```

dengan:

```php
    /**
     * @param  array<int, string>  $mengapaSesuai  paling banyak dua kalimat why_it_fits mesin
     * @param  array<int, string>  $perluDiperhatikan  paling banyak dua kalimat what_to_watch mesin
     */
    public function __construct(
```

2. Ganti:

```php
        public bool $denganRencana,
```

dengan:

```php
        public bool $denganRencana,
        public array $mengapaSesuai = [],
        public array $perluDiperhatikan = [],
```

`app/Services/TrailFitService.php`:

1. Ganti:

```php
            $ringkasan[$trail->id] = new TrailFitSummary(
```

dengan:

```php
            // Isi "Kenapa?" di kartu Jelajah (spec v3 bagian 6.2): dua kalimat teratas dari
            // penjelasan mesin yang sama dengan halaman rekomendasi, bukan kalimat baru.
            $ringkasan[$trail->id] = new TrailFitSummary(
```

2. Ganti:

```php
                denganRencana: $goal !== null,
```

dengan:

```php
                denganRencana: $goal !== null,
                mengapaSesuai: $hasil->eligible ? array_slice($hasil->explanation['why_it_fits'] ?? [], 0, 2) : [],
                perluDiperhatikan: array_slice($hasil->explanation['what_to_watch'] ?? [], 0, 2),
```

Ganti seluruh isi `app/Services/Trails/TrailSearch.php` dengan:

```php
<?php

namespace App\Services\Trails;

use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\TripType;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pencarian dan saringan Jelajah (spec v3 bagian 6.2, FR-27).
 *
 * Dua tahap dengan satu sumber aturan. Tahap kueri menyaring yang dapat disaring basis data
 * dengan murah (terbit, kata kunci, wilayah, dan saringan lama dari URL yang pernah
 * dibagikan). Tahap saringan menilai setiap kandidat di memori terhadap atribut yang sudah
 * dihitung server (kecocokan, jenis trip, tuntutan teknis, status resmi, tanjakan), karena
 * kecocokan dan status efektif tidak dapat disaring SQL dan hitungan setiap pilihan saringan
 * butuh kandidat yang sama. Aturan yang dijaga:
 *
 * - hanya jalur terbit yang masuk hasil (R-022); pencarian nama gunung dikurung supaya OR-nya
 *   tidak meloloskan draf;
 * - jalur yang dikenal tetapi belum berdata tampil TERPISAH, tidak dihitung (R-008);
 * - nilai yang tidak diketahui tidak pernah lolos saringan rendah (R-006, R-007): tuntutan
 *   teknis yang belum diketahui bukan "rendah", tanjakan kosong tidak masuk rentang apa pun;
 * - status Tutup disembunyikan secara bawaan dan disebut sebagai jalur yang disembunyikan
 *   saringan, tidak dibuang diam-diam.
 *
 * Kalimat seperti "gunung dekat Bogor untuk pemula" dibaca menjadi tafsiran yang dapat
 * dilepas satu per satu (FR-27): wilayah dan tuntutan teknis rendah. Saringan eksplisit dari
 * URL selalu menang atas tafsiran; kalimat yang tidak dikenali dicari sebagai kata kunci
 * biasa dan pembaca diberi tahu.
 */
final class TrailSearch
{
    public const PER_HALAMAN = 12;

    public const MAKS_MENUNGGU = 8;

    /** Kunci tafsiran kalimat yang dapat dilepas lewat ?abaikan=. */
    public const TAFSIRAN = ['wilayah', 'teknis'];

    /** Urutan saringan; jalur yang tersembunyi dicatat pada saringan pertama yang menolaknya. */
    public const SARINGAN = ['status', 'kecocokan', 'teknis', 'jenis', 'naik'];

    /** Jenis trip yang dapat disaring Jelajah (prototype 02 dan 15). */
    public const JENIS = [TripType::TEKTOK, TripType::CAMPING];

    /** Status bawaan: Tutup tidak ikut sampai pembaca memintanya. */
    public const STATUS_BAWAAN = ['OPEN', 'RESTRICTED', 'UNKNOWN'];

    /**
     * @param  array{search: string, abaikan: array<int, string>, technical: string, region: string, distance: float|null, duration: int|null, kecocokan: array<int, string>|null, jenis: array<int, string>|null, teknisRendah: bool, status: array<int, string>, naikDari: int|null, naikSampai: int|null, tampilkanSemua: bool}  $filter
     */
    public function __construct(private array $filter) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function dariInput(array $input): self
    {
        $angka = static fn (mixed $v): ?float => is_numeric($v) ? (float) $v : null;
        $meter = static fn (mixed $v): ?int => is_numeric($v) && (int) $v >= 0 ? (int) $v : null;
        $daftar = static fn (mixed $v, array $boleh): array => is_string($v)
            ? array_values(array_intersect($boleh, array_map('trim', explode(',', $v))))
            : [];

        return new self([
            'search' => trim((string) ($input['search'] ?? '')),
            'abaikan' => $daftar($input['abaikan'] ?? null, self::TAFSIRAN),
            'technical' => (string) ($input['technical'] ?? ''),
            'region' => trim((string) ($input['region'] ?? '')),
            'distance' => $angka($input['distance'] ?? null),
            'duration' => $angka($input['duration'] ?? null) === null ? null : (int) $angka($input['duration']),
            // Tanpa parameter berarti tanpa saringan; parameter kosong berarti pembaca melepas
            // semua pilihannya, jadi tidak ada yang lolos (sama dengan status).
            'kecocokan' => array_key_exists('kecocokan', $input) ? $daftar($input['kecocokan'], array_column(RouteFitLabel::cases(), 'value')) : null,
            'jenis' => array_key_exists('jenis', $input) ? $daftar($input['jenis'], array_map(fn (TripType $t) => $t->value, self::JENIS)) : null,
            'teknisRendah' => ($input['teknisRendah'] ?? null) === '1',
            'status' => array_key_exists('status', $input)
                ? $daftar($input['status'], array_column(OfficialStatusValue::cases(), 'value'))
                : self::STATUS_BAWAAN,
            'naikDari' => $meter($input['naikDari'] ?? null),
            'naikSampai' => $meter($input['naikSampai'] ?? null),
            'tampilkanSemua' => ($input['tampilkanSemua'] ?? null) === '1',
        ]);
    }

    /**
     * Saringan yang sedang berlaku, untuk dikembalikan ke isian halaman.
     *
     * @return array<string, mixed>
     */
    public function filter(): array
    {
        return $this->filter;
    }

    /**
     * Tafsiran kalimat (FR-27): chip yang dapat dilepas, kunci yang sudah dilepas, dan catatan
     * bila kalimatnya tidak dikenali seluruhnya.
     *
     * @return array{chip: array<int, array{kunci: string, label: string}>, dilepas: array<int, string>, catatan: string|null}
     */
    public function tafsiran(): array
    {
        $bacaan = $this->bacaan();

        if ($bacaan === null) {
            return [
                'chip' => [],
                'dilepas' => [],
                'catatan' => $this->kalimatPemula() ? 'Kalimat Anda tidak dikenali seluruhnya, jadi dicari sebagai kata kunci.' : null,
            ];
        }

        // Chip hanya untuk tafsiran yang benar-benar berlaku: saringan eksplisit dari URL
        // menang, dan tafsiran yang kalah tidak boleh tampil seolah menyaring.
        $chip = [];

        if ($bacaan['wilayah'] !== null && $this->filter['region'] === '' && ! in_array('wilayah', $this->filter['abaikan'], true)) {
            $chip[] = ['kunci' => 'wilayah', 'label' => 'Wilayah: '.$bacaan['wilayah'].' ('.$bacaan['kota'].')'];
        }

        if ($this->teknisDariTafsiran()) {
            $chip[] = ['kunci' => 'teknis', 'label' => 'Tuntutan teknis: rendah'];
        }

        return ['chip' => $chip, 'dilepas' => $this->filter['abaikan'], 'catatan' => null];
    }

    /**
     * Kandidat hasil: jalur terbit yang lolos tahap kueri, urut nama. Diambil satu lebih dari
     * batas supaya pemanggil tahu hasilnya terpotong.
     *
     * @return Collection<int, Trail>
     */
    public function kandidat(int $batas): Collection
    {
        return $this->terapkan(Trail::query()->withPublishabilityData()->published())
            ->limit($batas + 1)
            ->get();
    }

    /**
     * Jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak berwenang.
     *
     * @return Collection<int, Trail>
     */
    public function menunggu(): Collection
    {
        return $this->terapkan(Trail::query()->active()->where('is_published', false))
            ->limit(self::MAKS_MENUNGGU)
            ->get();
    }

    /**
     * Saringan pertama yang menolak satu kandidat, atau null bila lolos semuanya. Atributnya
     * dihitung pemanggil (HalamanJelajah::atribut) dan dikirim juga ke klien, supaya hitungan
     * "Tampilkan N jalur" di lembar saringan memakai atribut yang sama.
     *
     * @param  array{kecocokan: string|null, jenis: array<int, string>, teknisRendah: bool, status: string, naik: int|null}  $atribut
     * @param  bool  $dinilai  kecocokan hanya menyaring bila pembacanya dinilai
     * @param  string|null  $abaikan  satu saringan yang dilewati, untuk menghitung pilihannya sendiri
     */
    public function gagalPada(array $atribut, bool $dinilai, ?string $abaikan = null): ?string
    {
        $f = $this->filter;
        $teknisRendah = $f['teknisRendah'] || $this->teknisDariTafsiran();

        $uji = [
            'status' => fn () => in_array($atribut['status'], $f['status'], true),
            'kecocokan' => fn () => ! $dinilai || $f['kecocokan'] === null || in_array($atribut['kecocokan'], $f['kecocokan'], true),
            'teknis' => fn () => ! $teknisRendah || $atribut['teknisRendah'],
            'jenis' => fn () => $f['jenis'] === null || array_intersect($f['jenis'], $atribut['jenis']) !== [],
            'naik' => fn () => ($f['naikDari'] === null && $f['naikSampai'] === null)
                || ($atribut['naik'] !== null
                    && ($f['naikDari'] === null || $atribut['naik'] >= $f['naikDari'])
                    && ($f['naikSampai'] === null || $atribut['naik'] <= $f['naikSampai'])),
        ];

        foreach (self::SARINGAN as $kunci) {
            if ($kunci !== $abaikan && ! $uji[$kunci]()) {
                return $kunci;
            }
        }

        return null;
    }

    /**
     * Tuntutan teknis rendah dari tafsiran kalimat yang belum dilepas; saringan teknis
     * eksplisit dari URL menang atasnya.
     */
    public function teknisDariTafsiran(): bool
    {
        $bacaan = $this->bacaan();

        return $bacaan !== null
            && $bacaan['teknis']
            && $this->filter['technical'] === ''
            && ! in_array('teknis', $this->filter['abaikan'], true);
    }

    public function tampilkanSemua(): bool
    {
        return $this->filter['tampilkanSemua'];
    }

    /**
     * @param  Builder<Trail>  $query
     * @return Builder<Trail>
     */
    private function terapkan(Builder $query): Builder
    {
        $bacaan = $this->bacaan();
        $search = $bacaan === null ? $this->filter['search'] : '';
        $region = $this->filter['region'] !== ''
            ? $this->filter['region']
            : (($bacaan['wilayah'] ?? null) !== null && ! in_array('wilayah', $this->filter['abaikan'], true) ? $bacaan['wilayah'] : '');
        $technical = $this->filter['technical'];
        $distance = $this->filter['distance'];
        $duration = $this->filter['duration'];

        return $query
            ->with('mountain')
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $group) use ($search) {
                    $group->where('name', 'like', "%{$search}%")
                        ->orWhereHas('mountain', fn (Builder $m) => $m->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($technical !== '', fn (Builder $q) => $q->where('technical_demand', $technical))
            ->when($region !== '', function (Builder $q) use ($region) {
                $q->whereHas('mountain', fn (Builder $m) => $m->where('region', $region)->orWhere('province', $region));
            })
            ->when($distance !== null, fn (Builder $q) => $q->where('distance_km', '<=', (float) $distance))
            ->when($duration !== null, fn (Builder $q) => $q->where('estimated_duration_minutes', '<=', (int) $duration * 60))
            ->orderBy('name');
    }

    private function kalimatPemula(): bool
    {
        return $this->filter['search'] !== ''
            && preg_match('/(?:\b(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b|\b(?:pemula|beginner|novice)\b)/i', $this->filter['search']) === 1;
    }

    /**
     * Kalimat sederhana seperti "dekat Malang untuk pemula" dibaca menjadi wilayah dan tuntutan
     * teknis rendah. Null bila kalimatnya tidak dikenali; pencariannya lalu memakai kata kunci.
     *
     * @return array{wilayah: string|null, kota: string, teknis: bool}|null
     */
    private function bacaan(): ?array
    {
        $search = $this->filter['search'];

        if (! $this->kalimatPemula()) {
            return null;
        }

        $lokasi = null;

        if (preg_match('/\b(?:dekat|sekitar|di|ke|dari)\s+([a-z0-9\s-]+?)(?=\s+(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b|\s*$)/i', $search, $m)) {
            $lokasi = trim($m[1]);
        } elseif (preg_match('/\b([a-z0-9\s-]+?)\s+(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b/i', $search, $m)) {
            $lokasi = trim($m[1]);
        }

        $kota = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $lokasi)));

        $alias = match ($kota) {
            'malang', 'surabaya' => 'Jawa Timur',
            'bandung', 'bogor' => 'Jawa Barat',
            'semarang' => 'Jawa Tengah',
            default => null,
        };

        if ($alias === null) {
            return null;
        }

        return ['wilayah' => $alias, 'kota' => ucwords($kota), 'teknis' => true];
    }
}
```

**Antarmuka**

Buat `resources/js/components/trails/GarisProfil.tsx`:

```tsx
import { kelas } from '@/lib/kelas';
import type { LabelFit } from './LabelKecocokan';

const LEBAR = 200;
const TINGGI = 40;

/** Warna garis mengikuti label; labelnya sendiri selalu tertulis di kartu, jadi warna bukan satu-satunya pembawa arti. */
const warna: Record<'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | 'BELUM', { garis: string; isi: string }> = {
    COCOK: { garis: 'stroke-fit-cocok-teks', isi: 'fill-fit-cocok' },
    PERLU_PERSIAPAN: { garis: 'stroke-fit-perlu-teks', isi: 'fill-fit-perlu' },
    KURANG_COCOK: { garis: 'stroke-fit-kurang-teks', isi: 'fill-fit-kurang' },
    BELUM: { garis: 'stroke-brand-ink', isi: 'fill-brand-100' },
};

function koordinat(titik: Array<[number, number]>): string {
    return titik.map(([x, y]) => `${(x * LEBAR).toFixed(1)},${(TINGGI - y * (TINGGI - 4) - 2).toFixed(1)}`).join(' ');
}

/**
 * Garis profil kartu (spec v3 bagian 6.2): bentuk naik-turun jalur pada skala tinggi yang sama
 * untuk semua kartu di halaman (SkalaProfil::naikBersama), sehingga jalur yang naik 280 m
 * terbaca landai di samping jalur yang naik 1.650 m. Hiasan data, bukan satu-satunya sumber:
 * angka tanjakannya tertulis di atasnya, jadi grafiknya disembunyikan dari pembaca layar.
 * Tanpa profil tidak ada garis; garis datar karangan akan terbaca sebagai jalur landai.
 */
export function GarisProfil({ titik, label, className }: { titik: Array<[number, number]>; label: LabelFit; className?: string }) {
    const w = warna[label ?? 'BELUM'];
    const ujung = titik.at(-1)?.[0] ?? 1;

    return (
        <svg viewBox={`0 0 ${LEBAR} ${TINGGI}`} preserveAspectRatio="none" aria-hidden="true" className={kelas('h-10 w-full', className)}>
            <polygon points={`0,${TINGGI} ${koordinat(titik)} ${(ujung * LEBAR).toFixed(1)},${TINGGI}`} className={w.isi} />
            <polyline points={koordinat(titik)} fill="none" strokeWidth="2" strokeLinejoin="round" vectorEffect="non-scaling-stroke" className={w.garis} />
        </svg>
    );
}
```

Buat `resources/js/components/trails/TombolSimpan.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useRef, useState, type MouseEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { Dialog } from '@/components/ui/Lembar';
import { TombolIkon } from '@/components/ui/TombolIkon';
import type { LayananKerangka } from '@/layouts/Kerangka';
import { saved } from '@/routes/trails';
import { remove, store } from '@/routes/trails/saved';
import type { PropsBersama } from '@/types';

export interface JalurSimpan {
    id: number;
    name: string;
    ditimbang: boolean;
    adaCatatan: boolean;
}

interface TombolSimpanProps {
    jalur: JalurSimpan;
    /**
     * tampilkanToast milik halaman atau daftar yang bertahan (useKerangka di sana, ruling
     * W2-5i), bukan milik tombol ini: kartu yang hilang oleh aksinya sendiri, misalnya di
     * Simpanan, ikut menutup toast yang dimilikinya.
     */
    tampilkanToast: LayananKerangka['tampilkanToast'];
    /** "ikon" untuk kartu Jelajah (44 px), "tombol" untuk bar aksi rincian jalur. */
    bentuk?: 'ikon' | 'tombol';
}

const opsi = { preserveScroll: true, preserveState: true } as const;

/**
 * Simpan dan lepas simpanan dengan urungkan (spec v3 bagian 6.2 dan 9.1). Keduanya idempoten:
 * simpan memakai POST trails/saved/{trail} dan lepas memakai DELETE-nya, jadi halaman yang
 * basi tidak pernah membalik keadaan. Urungkan mengirim aksi kebalikannya; melepas simpanan
 * mengembalikan catatan pribadinya lewat flash simpananTerhapus, jadi Urungkan memulihkannya
 * utuh. Simpanan yang bercatatan bertanya dulu, karena catatan hilang bila toastnya terlewat.
 * Simpanan penuh dijawab dialog yang menyebut batasnya dan jalan ke Simpanan, di tempat
 * tombol ditekan, bukan pesan di ujung halaman.
 *
 * Keadaannya dibawa bentuk ikon (isi) dan aria-pressed, bukan warna saja. Aksi dari papan
 * ketik (event.detail === 0) memindah fokus ke Urungkan supaya terjangkau sebelum toast
 * hilang (W1-F4, WCAG 2.2.1).
 */
export function TombolSimpan({ jalur, tampilkanToast, bentuk = 'ikon' }: TombolSimpanProps) {
    const [memproses, setMemproses] = useState(false);
    const [tanya, setTanya] = useState(false);
    const [penuh, setPenuh] = useState<string | null>(null);
    const papanKetik = useRef(false);
    const pemicu = useRef<HTMLButtonElement | null>(null);

    const simpan = (catatan: string | null = null, urungkan = true) => {
        setMemproses(true);
        router.post(store.url({ trail: jalur.id }), { catatan }, {
            ...opsi,
            onFinish: () => setMemproses(false),
            onSuccess: (halaman) => {
                const ditolak = (halaman.props as unknown as PropsBersama).flash.timbanganPenuh;

                if (ditolak) {
                    setPenuh(ditolak);
                } else if (urungkan) {
                    tampilkanToast(`${jalur.name} disimpan.`, () => router.delete(remove.url({ trail: jalur.id }), opsi), { fokus: papanKetik.current });
                }
            },
        });
    };

    const lepas = () => {
        setMemproses(true);
        router.delete(remove.url({ trail: jalur.id }), {
            ...opsi,
            onFinish: () => setMemproses(false),
            onSuccess: (halaman) => {
                const terhapus = (halaman.props as unknown as PropsBersama).flash.simpananTerhapus;

                tampilkanToast(`${jalur.name} dihapus dari simpanan.`, () => simpan(terhapus?.catatan ?? null, false), { fokus: papanKetik.current });
            },
        });
    };

    const tekan = (e: MouseEvent<HTMLButtonElement>) => {
        papanKetik.current = e.detail === 0;
        pemicu.current = e.currentTarget;

        if (!jalur.ditimbang) {
            simpan();
        } else if (jalur.adaCatatan) {
            setTanya(true);
        } else {
            lepas();
        }
    };

    return (
        <>
            {bentuk === 'ikon' ? (
                <TombolIkon
                    ikon="bookmark-simple"
                    isi={jalur.ditimbang}
                    label={`Simpan ${jalur.name}`}
                    aria-pressed={jalur.ditimbang}
                    disabled={memproses}
                    onClick={tekan}
                    varian="polos"
                    className="relative z-10 text-brand-ink"
                />
            ) : (
                <Button variant="secondary" aria-pressed={jalur.ditimbang} processing={memproses} onClick={tekan}>
                    <Icon name="bookmark-simple" size={20} isi={jalur.ditimbang} />
                    {jalur.ditimbang ? 'Tersimpan' : 'Simpan'}
                </Button>
            )}
            <Dialog
                terbuka={tanya}
                onTutup={() => setTanya(false)}
                judul="Hapus dari simpanan?"
                keterangan="Catatan pribadi pada jalur ini ikut terhapus. Anda masih dapat mengurungkannya sesaat setelah ini."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setTanya(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                setTanya(false);
                                lepas();
                            }}
                        >
                            Hapus
                        </Button>
                    </>
                }
            >
                <p className="text-kecil text-secondary">{jalur.name}</p>
            </Dialog>
            <Dialog
                terbuka={penuh !== null}
                onTutup={() => setPenuh(null)}
                judul="Simpanan sudah penuh"
                keterangan={penuh ?? undefined}
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setPenuh(null)}>
                            Tutup
                        </Button>
                        <Button href={saved.url()}>Buka Simpanan</Button>
                    </>
                }
            >
                <p className="text-kecil text-secondary">{jalur.name} belum disimpan.</p>
            </Dialog>
        </>
    );
}
```

Buat `resources/js/components/trails/jelajah/KartuBelumDinilai.tsx`:

```tsx
import type { JalurMenunggu } from '@/components/trails/types';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

/**
 * Jalur yang dikenal sistem tetapi datanya belum lengkap (spec v3 bagian 6.2): kartu bergaris
 * putus-putus "Belum bisa dinilai", tanpa angka, tidak dihitung dalam jumlah hasil, dan tidak
 * pernah direkomendasikan (R-008, R-022). Setiap kartu menyebut siapa yang ditunggu dan bahwa
 * status yang belum diketahui tidak dianggap buka (§95).
 */
export function KartuBelumDinilai({ daftar }: { daftar: JalurMenunggu[] }) {
    return (
        <ul className="space-y-3">
            {daftar.map((j) => (
                <li key={j.id}>
                    <Tautan href={j.url} className={kelas('block rounded-lg border-2 border-dashed border-control bg-surface p-4 hover:bg-surface-sunken', fokusTerlihat)}>
                        <span className="text-meta font-bold text-secondary">Belum bisa dinilai</span>
                        <span className="mt-1 block text-subjudul text-primary">{j.name}</span>
                        <span className="mt-0.5 block text-meta text-secondary">{[j.mountain, j.province].filter(Boolean).join(', ')}</span>
                        <span className="mt-2 block text-kecil text-secondary">Menunggu kurator Memount melengkapi datanya. Status resmi belum diketahui dan tidak dianggap buka.</span>
                    </Tautan>
                </li>
            ))}
        </ul>
    );
}
```

Buat `resources/js/components/trails/jelajah/KartuJalur.tsx`:

```tsx
import { useId, useState } from 'react';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { GarisProfil } from '@/components/trails/GarisProfil';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { TombolSimpan } from '@/components/trails/TombolSimpan';
import type { KartuJalur as DataKartu } from '@/components/trails/types';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import type { LayananKerangka } from '@/layouts/Kerangka';
import { kelas } from '@/lib/kelas';
import { fit as ruteKecocokan } from '@/routes/trails';

interface KartuJalurProps {
    jalur: DataKartu;
    /** Kata UNKNOWN dari server, untuk pembaca yang profilnya belum lengkap. */
    kataBelumDinilai: string;
    /** Disorot dari peta atau saat penunjuk berada di atasnya. */
    disorot?: boolean;
    /** Diteruskan ke TombolSimpan: toast Urungkan milik halaman, bukan milik kartu. */
    tampilkanToast: LayananKerangka['tampilkanToast'];
}

/**
 * Kartu jalur Jelajah (spec v3 bagian 6.2, prototype 02 dan 15), urut pengungkapan PRD §89:
 * label kecocokan (ikon dan kata) dan simpan, nama, gunung dan wilayahnya, tiga angka, garis
 * profil pada skala yang sama dengan kartu lain, satu alasan, status resmi beserta asalnya,
 * lalu "Kenapa?" yang membuka alasan lengkapnya di tempat.
 *
 * Seluruh kartu menjadi sasaran tautan lewat after:absolute pada nama jalur; kontrol lain
 * (simpan, gunung, Kenapa?) berdiri di atas lapisan itu (relative z-10). Setiap kartu
 * membawa label: satu dari tiga label publik, atau "Belum dinilai" dari server bila
 * kecocokannya tidak dihitung (profil belum diisi, data kritis belum lengkap, atau jalur
 * tidak lolos syarat dasar). Pada jalur yang tidak lolos, alasannya tertulis di bawah angka,
 * jadi "Belum dinilai" tidak pernah terbaca sebagai nilai rendah (R-004, R-006, R-008).
 */
export function KartuJalur({ jalur, kataBelumDinilai, disorot = false, tampilkanToast }: KartuJalurProps) {
    const [terbuka, setTerbuka] = useState(false);
    const idAlasan = useId();
    const fit = jalur.fit;
    const layak = fit === null || fit.layak;

    return (
        <article
            id={`jalur-${jalur.id}`}
            className={kelas(
                'group relative rounded-lg border bg-surface p-4 transition-colors duration-sentuh sm:p-5',
                disorot ? 'border-brand-ink' : 'border-subtle',
                jalur.tersembunyiOleh !== null && 'opacity-75',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 pt-2.5">
                    <LabelKecocokan nilai={fit?.label ?? null} label={fit?.labelText ?? kataBelumDinilai} />
                </div>
                <TombolSimpan jalur={jalur} tampilkanToast={tampilkanToast} />
            </div>

            <h2 className="mt-1 text-subjudul text-primary">
                <Tautan href={jalur.url} className="rounded-sm after:absolute after:inset-0 after:rounded-lg group-hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    {jalur.name}
                </Tautan>
            </h2>
            <p className="mt-0.5 text-meta text-secondary">
                {jalur.gunungUrl ? (
                    <Tautan href={jalur.gunungUrl} className="relative z-10 underline decoration-subtle underline-offset-2 hover:decoration-current focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                        {jalur.mountain}
                    </Tautan>
                ) : (
                    jalur.mountain
                )}
                {jalur.province ? `, ${jalur.province}` : null}
            </p>

            <dl className="mt-3 grid grid-cols-2 gap-2 min-[360px]:grid-cols-3">
                {[
                    ['Jarak', jalur.jarak],
                    ['Tanjakan', jalur.tanjakan === '-' ? '-' : `↑${jalur.tanjakan}`],
                    ['Naik', jalur.durasi],
                ].map(([judul, nilai]) => (
                    <div key={judul}>
                        <dt className="text-meta text-secondary">{judul}</dt>
                        <dd data-angka="" className="text-isi font-bold text-primary">
                            {nilai}
                        </dd>
                    </div>
                ))}
            </dl>

            {jalur.garis ? <GarisProfil titik={jalur.garis} label={fit?.label ?? null} className="mt-3" /> : null}

            {fit ? (
                <p className={kelas('mt-3 text-kecil', layak ? 'text-primary' : 'text-warn-900')} data-fit-reason="">
                    {fit.alasan}
                </p>
            ) : null}

            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                <BarisStatus status={jalur.status} />
                {fit && layak ? (
                    <button
                        type="button"
                        aria-expanded={terbuka}
                        aria-controls={idAlasan}
                        onClick={() => setTerbuka((v) => !v)}
                        className={kelas('relative z-10 inline-flex min-h-11 items-center gap-1 rounded-tombol border border-control px-3 text-kecil font-bold text-primary hover:bg-surface-sunken', fokusTerlihat)}
                    >
                        Kenapa?
                        <Icon name="caret-down" size={16} className={kelas('transition-transform duration-cepat ease-gerak', terbuka && 'rotate-180')} />
                    </button>
                ) : null}
            </div>

            {jalur.tersembunyiOleh !== null ? <p className="mt-2 text-meta text-muted">Tidak lolos saringan {jalur.tersembunyiOleh}; tampil karena Anda memilih Tampilkan juga.</p> : null}

            {fit && terbuka ? (
                <div id={idAlasan} className="relative z-10 mt-3 space-y-3 border-t border-subtle pt-3">
                    {fit.mengapaSesuai.length > 0 ? (
                        <div>
                            <h3 className="text-meta font-bold text-fit-cocok-teks">Mengapa sesuai</h3>
                            <ul className="mt-1 space-y-1">
                                {fit.mengapaSesuai.map((baris) => (
                                    <li key={baris} className="flex gap-2 text-kecil text-primary">
                                        <Icon name="check" size={16} className="mt-0.5 text-fit-cocok-teks" />
                                        <span className="min-w-0">{baris}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                    {fit.perluDiperhatikan.length > 0 ? (
                        <div>
                            <h3 className="text-meta font-bold text-fit-perlu-teks">Yang perlu diperhatikan</h3>
                            <ul className="mt-1 space-y-1">
                                {fit.perluDiperhatikan.map((baris) => (
                                    <li key={baris} className="flex gap-2 text-kecil text-primary">
                                        <Icon name="warning" size={16} className="mt-0.5 text-fit-perlu-teks" />
                                        <span className="min-w-0">{baris}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                    <Tautan href={ruteKecocokan.url({ trail: jalur.slug })} className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                        Lihat penilaian lengkap
                    </Tautan>
                </div>
            ) : null}
        </article>
    );
}
```

Buat `resources/js/components/trails/jelajah/KotakCari.tsx`:

```tsx
import { useId } from 'react';
import { Icon } from '@/components/ui/Icon';
import { TombolIkon } from '@/components/ui/TombolIkon';

/**
 * Kotak pencarian Jelajah (FR-27): satu isian untuk nama gunung, nama jalur, atau kalimat
 * seperti "dekat Bogor untuk pemula". Namanya label tersembunyi yang dibacakan, bukan
 * placeholder saja; placeholder hanya contoh. Isian 16 px supaya Safari iOS tidak memperbesar
 * halaman saat difokus, dan tombol kosongkan 44 px menggantikan tombol bawaan peramban.
 */
export function KotakCari({ nilai, onUbah }: { nilai: string; onUbah: (nilai: string) => void }) {
    const id = useId();

    return (
        <form role="search" aria-label="Cari jalur" onSubmit={(e) => e.preventDefault()}>
            <label htmlFor={id} className="sr-only">
                Cari gunung, jalur, atau kalimat
            </label>
            <div className="relative">
                <Icon name="magnifying-glass" size={20} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-secondary" />
                <input
                    id={id}
                    type="search"
                    value={nilai}
                    onChange={(e) => onUbah(e.target.value)}
                    placeholder="Contoh: Bogor untuk pemula"
                    className="block min-h-12 w-full rounded-tombol border-control bg-surface pl-12 pr-14 text-isi text-primary placeholder:text-muted focus:border-brand-600 focus:ring-brand-600 [&::-webkit-search-cancel-button]:hidden"
                />
                {nilai !== '' ? <TombolIkon ikon="x" label="Kosongkan pencarian" varian="polos" onClick={() => onUbah('')} className="absolute right-1 top-1/2 -translate-y-1/2" /> : null}
            </div>
        </form>
    );
}
```

Buat `resources/js/components/trails/jelajah/SaringanJelajah.tsx`:

```tsx
import { useId, type ReactNode } from 'react';
import { Label, TextInput } from '@/components/ui/Field';
import { KotakCentang } from '@/components/ui/KotakCentang';
import { Sakelar } from '@/components/ui/Sakelar';
import { alihPilihan, type DrafSaringan } from './saringan';

export interface PilihanSaringan {
    nilai: string;
    label: string;
    jumlah: number;
}

/** Pilihan dan hitungannya dari server (HalamanJelajah::saringan). */
export interface OpsiSaringan {
    kecocokan: PilihanSaringan[] | null;
    jenis: PilihanSaringan[];
    teknisRendah: { aktif: boolean; jumlah: number };
    status: PilihanSaringan[];
}

interface SaringanJelajahProps {
    opsi: OpsiSaringan;
    draf: DrafSaringan;
    onUbah: (draf: DrafSaringan) => void;
}

function Kelompok({ judul, keterangan, children }: { judul: string; keterangan?: string; children: ReactNode }) {
    return (
        <fieldset className="space-y-1">
            <legend className="text-subjudul text-primary">{judul}</legend>
            {children}
            {keterangan ? <p className="text-meta text-secondary">{keterangan}</p> : null}
        </fieldset>
    );
}

function Hitungan({ jumlah }: { jumlah: number }) {
    return (
        <span data-angka="" className="text-meta font-semibold text-muted">
            {jumlah}
            <span className="sr-only"> jalur</span>
        </span>
    );
}

/**
 * Isi saringan Jelajah (spec v3 bagian 6.2, prototype 02 dan 15), dipakai lembar bawah di
 * ponsel dan panel kiri di layar lebar. Setiap pilihan menyebut hitungannya. Catatan yang
 * menjaga aturan ikut tertulis: tuntutan teknis yang belum diketahui tidak dianggap rendah
 * (R-006) dan jalur Tutup tidak pernah menjadi rekomendasi (BR-04). Rentang tanjakan diketik,
 * tidak diseret (WCAG 2.5.7), dan tanjakan lebih menentukan daripada mdpl (BR-02).
 */
export function SaringanJelajah({ opsi, draf, onUbah }: SaringanJelajahProps) {
    const id = useId();
    const semuaKecocokan = opsi.kecocokan?.map((p) => p.nilai) ?? [];
    const semuaJenis = opsi.jenis.map((p) => p.nilai);
    const meter = (teks: string): number | null => (teks.trim() === '' || Number.isNaN(Number(teks)) ? null : Math.max(0, Math.round(Number(teks))));

    return (
        <div className="space-y-6">
            {opsi.kecocokan ? (
                <Kelompok judul="Kecocokan dasar">
                    {opsi.kecocokan.map((p) => (
                        <KotakCentang
                            key={p.nilai}
                            label={p.label}
                            lencana={<Hitungan jumlah={p.jumlah} />}
                            checked={draf.kecocokan === null || draf.kecocokan.includes(p.nilai)}
                            onChange={(e) => onUbah({ ...draf, kecocokan: alihPilihan(draf.kecocokan, semuaKecocokan, p.nilai, e.target.checked) })}
                        />
                    ))}
                </Kelompok>
            ) : null}

            <Kelompok judul="Jenis trip">
                {opsi.jenis.map((p) => (
                    <KotakCentang
                        key={p.nilai}
                        label={p.label}
                        lencana={<Hitungan jumlah={p.jumlah} />}
                        checked={draf.jenis === null || draf.jenis.includes(p.nilai)}
                        onChange={(e) => onUbah({ ...draf, jenis: alihPilihan(draf.jenis, semuaJenis, p.nilai, e.target.checked) })}
                    />
                ))}
            </Kelompok>

            <Kelompok judul="Tuntutan teknis" keterangan="Jalur yang tuntutan teknisnya belum diketahui tidak dianggap rendah.">
                <Sakelar judul={`Hanya rendah (${opsi.teknisRendah.jumlah} jalur)`} nyala={draf.teknisRendah} onUbah={(nyala) => onUbah({ ...draf, teknisRendah: nyala })} />
            </Kelompok>

            <Kelompok judul="Tanjakan" keterangan="Diketik, tidak harus diseret. Tanjakan lebih menentukan daripada ketinggian puncak; jalur yang tanjakannya belum diketahui tidak ikut rentang ini.">
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <Label htmlFor={`${id}-dari`}>Dari (m)</Label>
                        <TextInput id={`${id}-dari`} type="number" inputMode="numeric" min={0} step={50} value={draf.naikDari ?? ''} onChange={(e) => onUbah({ ...draf, naikDari: meter(e.target.value) })} />
                    </div>
                    <div>
                        <Label htmlFor={`${id}-sampai`}>Sampai (m)</Label>
                        <TextInput id={`${id}-sampai`} type="number" inputMode="numeric" min={0} step={50} value={draf.naikSampai ?? ''} onChange={(e) => onUbah({ ...draf, naikSampai: meter(e.target.value) })} />
                    </div>
                </div>
            </Kelompok>

            <Kelompok judul="Status resmi" keterangan="Jalur berstatus Tutup tidak pernah muncul sebagai rekomendasi.">
                {opsi.status.map((p) => (
                    <KotakCentang
                        key={p.nilai}
                        label={p.label}
                        lencana={<Hitungan jumlah={p.jumlah} />}
                        checked={draf.status.includes(p.nilai)}
                        onChange={(e) => onUbah({ ...draf, status: e.target.checked ? [...draf.status, p.nilai] : draf.status.filter((n) => n !== p.nilai) })}
                    />
                ))}
            </Kelompok>
        </div>
    );
}
```

Buat `resources/js/components/trails/jelajah/TafsiranKalimat.tsx`:

```tsx
import { Button } from '@/components/ui/Button';
import { ChipLepas } from '@/components/ui/Chip';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import type { LayananKerangka } from '@/layouts/Kerangka';

export interface Tafsiran {
    chip: Array<{ kunci: string; label: string }>;
    dilepas: string[];
    catatan: string | null;
}

interface TafsiranKalimatProps {
    tafsiran: Tafsiran;
    /** Mengirim daftar tafsiran yang dilepas ke server (kunjungan Inertia dengan preserveState). */
    onAbaikan: (abaikan: string[]) => void;
    /** tampilkanToast milik halaman (ruling W2-5i): Urungkan bertahan selama halaman terpasang. */
    tampilkanToast: LayananKerangka['tampilkanToast'];
}

const idChip = (kunci: string) => `tafsiran-${kunci}`;

/**
 * Tafsiran kalimat pencarian (FR-27, spec v3 bagian 6.2): pembaca selalu melihat bagaimana
 * kalimatnya dibaca, sebagai chip yang dapat dilepas satu per satu dengan urungkan. Tafsiran
 * yang sudah dilepas dapat dikembalikan sekaligus. Kalimat yang tidak dikenali seluruhnya
 * disebut dengan jujur: ia dicari sebagai kata kunci biasa.
 *
 * Melepas chip dari papan ketik memindah fokus ke Urungkan (W1-F4), dan Urungkan memfokus chip
 * yang dikembalikan, bukan body.
 */
export function TafsiranKalimat({ tafsiran, onAbaikan, tampilkanToast }: TafsiranKalimatProps) {
    const fokusSesudahRender = useFokusSesudahRender();

    if (tafsiran.chip.length === 0 && tafsiran.dilepas.length === 0) {
        return tafsiran.catatan ? <p className="text-kecil text-secondary">{tafsiran.catatan}</p> : null;
    }

    const lepas = (kunci: string, label: string, dariPapanKetik: boolean) => {
        const sebelum = tafsiran.dilepas;
        onAbaikan([...sebelum, kunci]);
        tampilkanToast(
            `Tafsiran "${label}" dilepas.`,
            () => {
                onAbaikan(sebelum);
                fokusSesudahRender(idChip(kunci));
            },
            { fokus: dariPapanKetik },
        );
    };

    return (
        <div className="space-y-2">
            <p className="text-kecil text-secondary">Kalimat Anda dibaca sebagai:</p>
            <div className="flex flex-wrap items-center gap-1">
                {tafsiran.chip.map((c) => (
                    <ChipLepas key={c.kunci} id={idChip(c.kunci)} onLepas={(e) => lepas(c.kunci, c.label, e.detail === 0)}>
                        {c.label}
                    </ChipLepas>
                ))}
                {tafsiran.dilepas.length > 0 ? (
                    <Button variant="ghost" size="sm" onClick={() => onAbaikan([])}>
                        Kembalikan tafsiran kalimat
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
```

Buat `resources/js/components/trails/jelajah/saringan.ts`:

```ts
/**
 * Model saringan Jelajah di klien (spec v3 bagian 6.2).
 *
 * Server (App\Services\Trails\TrailSearch::gagalPada) adalah satu-satunya yang menentukan
 * hasil. Klien hanya menghitung pratinjau "Tampilkan N jalur" di lembar saringan dari atribut
 * yang dikirim server untuk setiap kandidat, dengan lima uji yang sama urutan dan artinya:
 * status, kecocokan, teknis, jenis, naik. Nilai yang tidak diketahui tidak lolos saringan
 * rendah atau rentang (R-006, R-007). Untuk kecocokan dan jenis, null berarti tanpa saringan
 * dan larik kosong berarti semua pilihan dilepas.
 */

export interface AtributJalur {
    id: number;
    kecocokan: string | null;
    jenis: string[];
    teknisRendah: boolean;
    status: string;
    naik: number | null;
}

/** Saringan yang sedang dikembalikan server (TrailSearch::filter). */
export interface FilterJelajah {
    search: string;
    abaikan: string[];
    technical: string;
    region: string;
    distance: number | null;
    duration: number | null;
    kecocokan: string[] | null;
    jenis: string[] | null;
    teknisRendah: boolean;
    status: string[];
    naikDari: number | null;
    naikSampai: number | null;
    tampilkanSemua: boolean;
}

/** Yang diubah pembaca di lembar saringan sebelum ditampilkan. */
export interface DrafSaringan {
    kecocokan: string[] | null;
    jenis: string[] | null;
    teknisRendah: boolean;
    status: string[];
    naikDari: number | null;
    naikSampai: number | null;
}

export const STATUS_BAWAAN = ['OPEN', 'RESTRICTED', 'UNKNOWN'];

export function drafDari(filter: FilterJelajah, teknisAktif: boolean): DrafSaringan {
    return {
        kecocokan: filter.kecocokan,
        jenis: filter.jenis,
        teknisRendah: teknisAktif,
        status: filter.status,
        naikDari: filter.naikDari,
        naikSampai: filter.naikSampai,
    };
}

export const drafKosong: DrafSaringan = { kecocokan: null, jenis: null, teknisRendah: false, status: STATUS_BAWAAN, naikDari: null, naikSampai: null };

/** Sama dengan TrailSearch::gagalPada: null bila lolos, atau kunci saringan pertama yang menolak. */
export function gagalPada(a: AtributJalur, d: DrafSaringan, dinilai: boolean): string | null {
    if (!d.status.includes(a.status)) return 'status';
    if (dinilai && d.kecocokan !== null && (a.kecocokan === null || !d.kecocokan.includes(a.kecocokan))) return 'kecocokan';
    if (d.teknisRendah && !a.teknisRendah) return 'teknis';
    if (d.jenis !== null && !d.jenis.some((j) => a.jenis.includes(j))) return 'jenis';

    if (d.naikDari !== null || d.naikSampai !== null) {
        if (a.naik === null || (d.naikDari !== null && a.naik < d.naikDari) || (d.naikSampai !== null && a.naik > d.naikSampai)) return 'naik';
    }

    return null;
}

export function hitungLolos(atribut: AtributJalur[], d: DrafSaringan, dinilai: boolean): number {
    return atribut.filter((a) => gagalPada(a, d, dinilai) === null).length;
}

/**
 * Mengubah satu pilihan kecocokan atau jenis. Semua pilihan tercentang kembali menjadi null
 * (tanpa saringan), supaya jalur yang tidak punya nilai itu tidak ikut tersembunyi.
 */
export function alihPilihan(daftar: string[] | null, semua: string[], nilai: string, dicentang: boolean): string[] | null {
    const kini = daftar ?? semua;
    const baru = dicentang ? [...kini.filter((n) => n !== nilai), nilai] : kini.filter((n) => n !== nilai);

    return baru.length === semua.length ? null : baru;
}

/**
 * Kueri URL untuk satu keadaan saringan. Nilai bawaan tidak ditulis, supaya tautan yang
 * dibagikan tetap pendek; tuntutan teknis dari tafsiran kalimat dilepas lewat abaikan,
 * bukan lewat teknisRendah=0.
 */
export function kueriDari(filter: FilterJelajah, d: DrafSaringan, tafsiranTeknis: boolean, tampilkanSemua = false): Record<string, string | number> {
    const abaikan = new Set(filter.abaikan);

    if (tafsiranTeknis && !d.teknisRendah) {
        abaikan.add('teknis');
    }

    const kueri: Record<string, string | number | null> = {
        search: filter.search || null,
        abaikan: abaikan.size > 0 ? Array.from(abaikan).join(',') : null,
        technical: filter.technical || null,
        region: filter.region || null,
        distance: filter.distance,
        duration: filter.duration,
        kecocokan: d.kecocokan === null ? null : d.kecocokan.join(','),
        jenis: d.jenis === null ? null : d.jenis.join(','),
        teknisRendah: d.teknisRendah && !tafsiranTeknis ? '1' : null,
        status: [...d.status].sort().join(',') === [...STATUS_BAWAAN].sort().join(',') ? null : d.status.join(','),
        naikDari: d.naikDari,
        naikSampai: d.naikSampai,
        tampilkanSemua: tampilkanSemua ? '1' : null,
    };

    // Hanya null yang dibuang: nilai kosong ('') berarti pembaca melepas semua pilihannya,
    // dan server membacanya sebagai pilihan kosong, bukan sebagai bawaan.
    return Object.fromEntries(Object.entries(kueri).filter(([, v]) => v !== null)) as Record<string, string | number>;
}
```

`resources/css/app.css`:

1. Ganti:

```css
|   --strip-pendakian (StripPendakian.tsx), --bar-aksi (BarAksi.tsx), --baki-banding
|   (ConsiderationTray.tsx, baki banding Jelajah), dan --pesan-jaringan (PesanJaringan.tsx).
|
| --nav-bawah (bilah tab ditambah strip) dibaca bar aksi, toast, baki banding, dan
| padding-bottom <main>. scroll-padding menjaga elemen yang difokus papan ketik dan sasaran
| jangkar tidak tertutup lapisan tetap (WCAG 2.4.11): scroll-padding-bottom menghitung
| --nav-bawah, --bar-aksi (ruling W2-2a), dan --baki-banding (perbaikan akhir W2, I-1a),
| karena tanpa itu elemen yang difokus di dekat tepi bawah berhenti tertutup penuh oleh
| bar atau baki; scroll-padding-top menghitung --kepala-atas dan --pesan-jaringan (ruling
| W2-5a), dan <main> memberi dirinya padding-top senilai --pesan-jaringan, supaya isi di
| puncak halaman tidak tertutup lapisan pesan yang fixed itu. Toast sengaja tidak masuk
```

dengan:

```css
|   --strip-pendakian (StripPendakian.tsx), --bar-aksi (BarAksi.tsx), dan --pesan-jaringan
|   (PesanJaringan.tsx). Baki banding Jelajah dan variabel tingginya dilepas W3: Jelajah
|   tidak lagi punya baki (prototype 02 dan 15), dan baki banding Simpanan adalah bar aksi.
|
| --nav-bawah (bilah tab ditambah strip) dibaca bar aksi, toast, dan padding-bottom <main>.
| scroll-padding menjaga elemen yang difokus papan ketik dan sasaran jangkar tidak tertutup
| lapisan tetap (WCAG 2.4.11): scroll-padding-bottom menghitung --nav-bawah dan --bar-aksi
| (ruling W2-2a), karena tanpa itu elemen yang difokus di dekat tepi bawah berhenti
| tertutup penuh oleh bar; scroll-padding-top menghitung --kepala-atas dan --pesan-jaringan
| (ruling W2-5a), dan <main> memberi dirinya padding-top senilai --pesan-jaringan, supaya
| isi di puncak halaman tidak tertutup lapisan pesan yang fixed itu. Toast sengaja tidak masuk
```

2. Ganti:

```css
    scroll-padding-bottom: calc(var(--nav-bawah) + var(--bar-aksi, 0px) + var(--baki-banding, 0px) + 1.25rem);
```

dengan:

```css
    scroll-padding-bottom: calc(var(--nav-bawah) + var(--bar-aksi, 0px) + 1.25rem);
```

`resources/js/components/trails/types.ts`:

Ganti:

```ts
/** Baris halaman Simpanan: baris jalur ditambah keadaan terbit dan catatan pribadi (FR-22). */
```

dengan:

```ts
/** Kecocokan di kartu Jelajah: FitRingkas ditambah isi "Kenapa?" (TrailRowResource). */
export interface FitKartu extends FitRingkas {
    /** Lolos syarat dasar; false berarti tidak dinilai, dan kartunya menyebut alasannya. */
    layak: boolean;
    mengapaSesuai: string[];
    perluDiperhatikan: string[];
}

/** Kartu jalur Jelajah (App\Http\Resources\KartuJalurResource). */
export interface KartuJalur extends Omit<BarisJalur, 'fit'> {
    fit: FitKartu | null;
    gunungUrl: string | null;
    status: StatusRingkas;
    /** Titik 0..1 pada skala naik yang sama dengan kartu lain di halaman ini; null bila tanpa profil. */
    garis: Array<[number, number]> | null;
    adaCatatan: boolean;
    /** Nama saringan yang menyembunyikannya, bila kartu ini tampil lewat "Tampilkan juga". */
    tersembunyiOleh: string | null;
}

/** Baris halaman Simpanan: baris jalur ditambah keadaan terbit dan catatan pribadi (FR-22). */
```

`resources/js/components/ui/ToastUrungkan.tsx`:

1. Ganti:

```tsx
 * dikembalikan Urungkan. Melayang di atas navigasi bawah, bar aksi (--bar-aksi), dan baki
 * banding Jelajah (--baki-banding), di bawah dialog.
```

dengan:

```tsx
 * dikembalikan Urungkan. Melayang di atas navigasi bawah dan bar aksi (--bar-aksi), di
 * bawah dialog.
```

2. Ganti:

```tsx
        <div aria-live="polite" aria-atomic="true" className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+var(--bar-aksi,0px)+var(--baki-banding,0px)+1rem)] z-40 flex justify-center px-4">
```

dengan:

```tsx
        <div aria-live="polite" aria-atomic="true" className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+var(--bar-aksi,0px)+1rem)] z-40 flex justify-center px-4">
```

`resources/js/components/ui/TombolIkon.tsx`:

1. Ganti:

```tsx
    besar?: boolean;
```

dengan:

```tsx
    besar?: boolean;
    /**
     * Varian isi ikon untuk tombol sakelar yang sedang menyala (misalnya jalur tersimpan,
     * W3): keadaannya dibawa bentuk, bukan warna saja, bersama aria-pressed dari pemanggil.
     */
    isi?: boolean;
```

2. Ganti:

```tsx
export function TombolIkon({ ikon, label, varian = 'permukaan', bentuk = 'bulat', besar = false, type = 'button', className, ...rest }: TombolIkonProps) {
```

dengan:

```tsx
export function TombolIkon({ ikon, label, varian = 'permukaan', bentuk = 'bulat', besar = false, isi = false, type = 'button', className, ...rest }: TombolIkonProps) {
```

3. Ganti:

```tsx
            <Icon name={ikon} size={besar ? 24 : 22} />
```

dengan:

```tsx
            <Icon name={ikon} size={besar ? 24 : 22} isi={isi} />
```

`resources/js/components/ui/ikon/phosphor.ts`:

1. Ganti:

```ts
    | 'list'
```

dengan:

```ts
    | 'list'
    | 'magnifying-glass'
```

2. Ganti:

```ts
    | 'question'
```

dengan:

```ts
    | 'question'
    | 'sliders-horizontal'
```

3. Ganti:

```ts
        bold: 'M184,28H72A20,20,0,0,0,52,48V224a12,12,0,0,0,18.36,10.18l57.63-36,57.65,36A12,12,0,0,0,204,224V48A20,20,0,0,0,184,28Zm-4,174.35-45.65-28.53a12,12,0,0,0-12.72,0L76,202.35V52H180Z',
```

dengan:

```ts
        bold: 'M184,28H72A20,20,0,0,0,52,48V224a12,12,0,0,0,18.36,10.18l57.63-36,57.65,36A12,12,0,0,0,204,224V48A20,20,0,0,0,184,28Zm-4,174.35-45.65-28.53a12,12,0,0,0-12.72,0L76,202.35V52H180Z',
        fill: 'M184,32H72A16,16,0,0,0,56,48V224a8,8,0,0,0,12.24,6.78L128,193.43l59.77,37.35A8,8,0,0,0,200,224V48A16,16,0,0,0,184,32Z',
```

4. Ganti:

```ts
    'map-trifold': {
```

dengan:

```ts
    'magnifying-glass': {
        regular: 'M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z',
        bold: 'M232.49,215.51,185,168a92.12,92.12,0,1,0-17,17l47.53,47.54a12,12,0,0,0,17-17ZM44,112a68,68,0,1,1,68,68A68.07,68.07,0,0,1,44,112Z',
    },
    'map-trifold': {
```

5. Ganti:

```ts
    'user': {
```

dengan:

```ts
    'sliders-horizontal': {
        regular: 'M40,88H73a32,32,0,0,0,62,0h81a8,8,0,0,0,0-16H135a32,32,0,0,0-62,0H40a8,8,0,0,0,0,16Zm64-24A16,16,0,1,1,88,80,16,16,0,0,1,104,64ZM216,168H199a32,32,0,0,0-62,0H40a8,8,0,0,0,0,16h97a32,32,0,0,0,62,0h17a8,8,0,0,0,0-16Zm-48,24a16,16,0,1,1,16-16A16,16,0,0,1,168,192Z',
        bold: 'M40,92H70.06a36,36,0,0,0,67.88,0H216a12,12,0,0,0,0-24H137.94a36,36,0,0,0-67.88,0H40a12,12,0,0,0,0,24Zm64-24A12,12,0,1,1,92,80,12,12,0,0,1,104,68Zm112,96H201.94a36,36,0,0,0-67.88,0H40a12,12,0,0,0,0,24h94.06a36,36,0,0,0,67.88,0H216a12,12,0,0,0,0-24Zm-48,24a12,12,0,1,1,12-12A12,12,0,0,1,168,188Z',
    },
    'user': {
```

`resources/js/layouts/StripPendakian.tsx`:

Ganti:

```tsx
 * bar aksi, baki banding, dan toast selalu duduk di atasnya; nilai 3,5rem di app.css hanya
 * cadangan sebelum skrip berjalan.
```

dengan:

```tsx
 * bar aksi dan toast selalu duduk di atasnya; nilai 3,5rem di app.css hanya cadangan
 * sebelum skrip berjalan.
```

Ganti seluruh isi `resources/js/pages/Trails/Index.tsx` dengan:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { KartuBelumDinilai } from '@/components/trails/jelajah/KartuBelumDinilai';
import { KartuJalur } from '@/components/trails/jelajah/KartuJalur';
import { KotakCari } from '@/components/trails/jelajah/KotakCari';
import { drafDari, drafKosong, hitungLolos, kueriDari, STATUS_BAWAAN, type AtributJalur, type DrafSaringan, type FilterJelajah } from '@/components/trails/jelajah/saringan';
import { SaringanJelajah, type OpsiSaringan } from '@/components/trails/jelajah/SaringanJelajah';
import { TafsiranKalimat, type Tafsiran } from '@/components/trails/jelajah/TafsiranKalimat';
import { PetaJelajah } from '@/components/trails/PetaJelajah';
import type { FiturPeta, JalurMenunggu, KartuJalur as DataKartu, TautanHalaman } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { LembarBawah } from '@/components/ui/Lembar';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Skeleton } from '@/components/ui/Skeleton';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { TautanSimpanan } from '@/layouts/TautanSimpanan';
import { kelas } from '@/lib/kelas';
import { onboarding } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    filter: FilterJelajah;
    tafsiran: Tafsiran;
    trails: { data: DataKartu[]; total: number; links: TautanHalaman[] };
    ringkasan: {
        jumlah: number;
        tersembunyi: Array<{ kunci: string; label: string; jumlah: number }>;
        terpotong: number | null;
        urutan: 'kecocokan' | 'nama';
    };
    saringan: OpsiSaringan;
    atribut: AtributJalur[];
    dinilai: boolean;
    /** Ringkasan profil yang menjadi dasar label, misalnya "Pemula, tanjakan tertinggi 700 m". */
    dasarPenilaian: string | null;
    jumlahTerbit: number;
    menunggu: JalurMenunggu[];
    petaSebaran: FiturPeta[];
    kataBelumDinilai: string;
    pengumuman: string;
}

const pilihanTampilan = [
    { nilai: 'daftar' as const, label: 'Daftar', ikon: 'list' as const },
    { nilai: 'peta' as const, label: 'Peta', ikon: 'map-trifold' as const },
];

/** "saringan status resmi", atau "saringan: status resmi (1), tanjakan (2)" bila lebih dari satu. */
function saringanPenyembunyi(tersembunyi: Props['ringkasan']['tersembunyi']): string {
    if (tersembunyi.length === 1) {
        return `saringan ${tersembunyi[0]?.label ?? ''}`;
    }

    return `saringan: ${tersembunyi.map((s) => `${s.label} (${s.jumlah})`).join(', ')}`;
}

/** Jumlah kelompok saringan yang sedang menyempitkan hasil, untuk tombol Saring. */
function jumlahSaringan(filter: FilterJelajah, teknisAktif: boolean): number {
    return [
        filter.kecocokan !== null,
        filter.jenis !== null,
        teknisAktif,
        [...filter.status].sort().join(',') !== [...STATUS_BAWAAN].sort().join(','),
        filter.naikDari !== null || filter.naikSampai !== null,
    ].filter(Boolean).length;
}

/**
 * Jelajah (spec v3 bagian 6.2, prototype 02 dan 15). Ponsel: pencarian kalimat dengan
 * tafsirannya, tombol Saring yang membuka lembar bawah berhitungan langsung, lalu daftar atau
 * peta. Layar lebar: saringan di panel kiri, daftar di tengah, dan mulai 1280 px peta di
 * kanan. Saringan hidup di URL (dapat dibagikan, tombol kembali bekerja) dan setiap perubahan
 * memuat ulang data lewat Inertia tanpa memuat ulang halaman.
 *
 * Keadaan kosong diperiksa berurutan (W1-F1): belum ada jalur terbit sama sekali menyebut
 * siapa yang ditunggu apa pun saringannya; baru sesudah itu hasil kosong disebut akibat
 * saringan, beserta yang disembunyikannya dan cara melepasnya.
 */
export default function Index() {
    const { filter, tafsiran, trails, ringkasan, saringan, atribut, dinilai, dasarPenilaian, jumlahTerbit, menunggu, petaSebaran, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    // Pemilik toast Urungkan adalah halaman ini, yang bertahan selama saringan dan simpan
    // berjalan dengan preserveState (ruling W2-5i); kartu dan chip hanya meneruskannya.
    const { tampilkanToast } = useKerangka();
    const tafsiranTeknis = tafsiran.chip.some((c) => c.kunci === 'teknis');
    const [cari, setCari] = useState(filter.search);
    const [tampilan, setTampilan] = useState<'daftar' | 'peta'>('daftar');
    const [sorot, setSorot] = useState<number | null>(null);
    const [lembarTerbuka, setLembarTerbuka] = useState(false);
    const [draf, setDraf] = useState<DrafSaringan>(() => drafDari(filter, saringan.teknisRendah.aktif));
    const [memuat, setMemuat] = useState(false);
    const pemicuLembar = useRef<HTMLElement | null>(null);
    const pertama = useRef(true);
    const adaPeta = petaSebaran.length > 0;
    const nSaringan = jumlahSaringan(filter, saringan.teknisRendah.aktif);
    const pratinjau = hitungLolos(atribut, draf, dinilai);

    const kunjungi = (kueri: Record<string, string | number>) =>
        router.get(trailsIndex.url({ query: kueri }), {}, { preserveState: true, preserveScroll: true, replace: true });

    const terapkan = (d: DrafSaringan, semua = filter.tampilkanSemua) => kunjungi(kueriDari(filter, d, tafsiranTeknis, semua));

    useEffect(() => setDraf(drafDari(filter, saringan.teknisRendah.aktif)), [filter, saringan.teknisRendah.aktif]);

    useEffect(() => {
        const mulai = router.on('start', () => setMemuat(true));
        const selesai = router.on('finish', () => setMemuat(false));

        return () => {
            mulai();
            selesai();
        };
    }, []);

    // Kalimat baru membaca tafsirannya sendiri, jadi tafsiran yang dilepas dan tuntutan teknis
    // dari kalimat lama tidak ikut; saringan lain tetap berlaku.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        const t = setTimeout(() => kunjungi(kueriDari({ ...filter, search: cari, abaikan: [] }, drafDari(filter, filter.teknisRendah), false)), 400);

        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cari]);

    const bukaLembar = (e: MouseEvent<HTMLElement>) => {
        pemicuLembar.current = e.currentTarget;
        setDraf(drafDari(filter, saringan.teknisRendah.aktif));
        setLembarTerbuka(true);
    };

    const lihatDiDaftar = (id: number) => {
        setTampilan('daftar');
        setSorot(id);
        requestAnimationFrame(() => document.getElementById(`jalur-${id}`)?.scrollIntoView({ block: 'center' }));
    };

    const olehSaringan = saringanPenyembunyi(ringkasan.tersembunyi);
    const jumlahTersembunyi = ringkasan.tersembunyi.reduce((n, s) => n + s.jumlah, 0);

    return (
        <div className="py-6">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Jelajah" aksi={<TautanSimpanan className="lg:hidden" />} />

                <div
                    className={kelas(
                        'lg:grid lg:gap-8',
                        adaPeta ? 'lg:grid-cols-[17rem_minmax(0,1fr)] xl:grid-cols-[18.75rem_32.5rem_minmax(0,1fr)]' : 'lg:grid-cols-[17rem_minmax(0,40rem)]',
                    )}
                >
                    <aside aria-label="Saringan jalur" className="hidden lg:block">
                        <div className="space-y-6 lg:sticky lg:top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)]">
                            <TafsiranKalimat tafsiran={tafsiran} tampilkanToast={tampilkanToast} onAbaikan={(abaikan) => kunjungi(kueriDari({ ...filter, abaikan }, drafDari(filter, filter.teknisRendah), false))} />
                            <SaringanJelajah
                                opsi={saringan}
                                draf={draf}
                                onUbah={(d) => {
                                    setDraf(d);
                                    terapkan(d);
                                }}
                            />
                        </div>
                    </aside>

                    <section aria-label="Daftar jalur" className={kelas('min-w-0 space-y-4', adaPeta && tampilan === 'peta' && 'hidden xl:block')}>
                        <KotakCari nilai={cari} onUbah={setCari} />

                        <div className="lg:hidden">
                            <TafsiranKalimat tafsiran={tafsiran} tampilkanToast={tampilkanToast} onAbaikan={(abaikan) => kunjungi(kueriDari({ ...filter, abaikan }, drafDari(filter, filter.teknisRendah), false))} />
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <Button variant="secondary" size="sm" className="lg:hidden" onClick={bukaLembar}>
                                <Icon name="sliders-horizontal" size={20} />
                                <span>
                                    Saring{nSaringan > 0 ? <span data-angka=""> ({nSaringan})</span> : null}
                                </span>
                            </Button>
                            <p aria-live="polite" className="min-w-0 flex-1 text-kecil text-secondary">
                                <span data-angka="" className="font-bold text-primary">
                                    {ringkasan.jumlah}
                                </span>{' '}
                                jalur
                                {jumlahTersembunyi > 0 ? `, ${jumlahTersembunyi} disembunyikan ${olehSaringan}` : null}
                            </p>
                            <p className="hidden text-kecil text-secondary sm:block">
                                Urut: <span className="font-bold text-primary">{ringkasan.urutan}</span>
                            </p>
                            {adaPeta ? (
                                <KontrolSegmen nama="tampilan-jelajah" label="Tampilan hasil" pilihan={pilihanTampilan} nilai={tampilan} onUbah={setTampilan} className="xl:hidden" />
                            ) : null}
                        </div>

                        {dinilai ? (
                            <p className="text-meta text-secondary">
                                Label menunjukkan kecocokan dasar dengan profil Anda{dasarPenilaian ? ` (${dasarPenilaian})` : ''}. Profil elevasi memakai skala yang sama di semua kartu.
                            </p>
                        ) : (
                            <div className="rounded-lg border border-subtle bg-surface p-4">
                                <p className="text-kecil font-bold text-primary">Jalur di sini belum dinilai untuk Anda</p>
                                <p className="mt-1 text-kecil text-secondary">Isi pengalaman pendakian Anda, lalu setiap jalur menyebut cocok atau tidaknya beserta alasannya.</p>
                                <Button href={onboarding.url()} size="sm" className="mt-3">
                                    Isi profil pendaki
                                </Button>
                            </div>
                        )}

                        {ringkasan.terpotong !== null ? (
                            <Alert variant="info">Menampilkan {ringkasan.terpotong} jalur pertama menurut nama. Persempit dengan kalimat atau wilayah untuk melihat sisanya.</Alert>
                        ) : null}

                        {jumlahTerbit === 0 ? (
                            <EmptyState
                                jenis="menunggu"
                                title="Belum ada jalur yang terbit"
                                menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"
                                description="Jalur tampil di sini setelah datanya lengkap: sumber, jarak, tanjakan, durasi, pos, status resmi, dan garis rute di peta."
                            />
                        ) : trails.data.length === 0 ? (
                            <EmptyState
                                jenis="tersaring"
                                title="Tidak ada jalur dengan saringan ini"
                                description={jumlahTersembunyi > 0 ? `${jumlahTersembunyi} jalur disembunyikan ${olehSaringan}. Lepas salah satu saringan, atau tampilkan juga jalur yang disembunyikan.` : 'Coba kalimat atau kata kunci lain.'}
                            >
                                {jumlahTersembunyi > 0 ? (
                                    <Button variant="secondary" size="sm" onClick={() => terapkan(draf, true)}>
                                        Tampilkan juga
                                    </Button>
                                ) : null}
                                <Button variant="secondary" size="sm" onClick={() => kunjungi({})}>
                                    Hapus semua saringan
                                </Button>
                            </EmptyState>
                        ) : memuat ? (
                            <div aria-busy="true" className="space-y-3">
                                {[0, 1, 2].map((i) => (
                                    <Skeleton key={i} rows={4} className="rounded-lg border border-subtle bg-surface p-5" />
                                ))}
                            </div>
                        ) : (
                            <ul className="space-y-3">
                                {trails.data.map((t) => (
                                    <li key={t.id} onMouseEnter={() => setSorot(t.id)} onMouseLeave={() => setSorot((s) => (s === t.id ? null : s))} onFocusCapture={() => setSorot(t.id)}>
                                        <KartuJalur jalur={t} kataBelumDinilai={kataBelumDinilai} disorot={sorot === t.id} tampilkanToast={tampilkanToast} />
                                    </li>
                                ))}
                            </ul>
                        )}

                        {jumlahTersembunyi > 0 && trails.data.length > 0 ? (
                            <div className="flex flex-wrap items-center gap-2 rounded-lg bg-surface-sunken p-4">
                                <p className="min-w-0 flex-1 text-kecil text-secondary">
                                    <span data-angka="">{jumlahTersembunyi}</span> jalur disembunyikan {olehSaringan}.
                                </p>
                                <Button variant="secondary" size="sm" onClick={() => terapkan(draf, !filter.tampilkanSemua)}>
                                    {filter.tampilkanSemua ? 'Sembunyikan lagi' : 'Tampilkan juga'}
                                </Button>
                            </div>
                        ) : null}

                        <Pagination links={trails.links} />

                        {menunggu.length > 0 ? (
                            <section aria-labelledby="judul-menunggu" className="space-y-3 pt-2">
                                <h2 id="judul-menunggu" className="text-bagian text-primary">
                                    Belum bisa dinilai
                                </h2>
                                <p className="text-kecil text-secondary">Jalur berikut sudah dikenali sistem, tetapi datanya belum lengkap. Tidak dihitung dan tidak direkomendasikan.</p>
                                <KartuBelumDinilai daftar={menunggu} />
                            </section>
                        ) : null}

                    </section>

                    {adaPeta ? (
                        <section aria-label="Peta jalur" className={kelas(tampilan === 'daftar' ? 'hidden xl:block' : 'block', 'mt-4 xl:sticky xl:top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] xl:mt-0 xl:self-start')}>
                            <PetaJelajah
                                fitur={petaSebaran}
                                baris={trails.data}
                                sorot={sorot}
                                onLihatDiDaftar={lihatDiDaftar}
                                className="h-[calc(100dvh-var(--nav-bawah,0px)-14rem)] min-h-[22rem] xl:h-[calc(100dvh-var(--kepala-atas)-3rem)]"
                            />
                        </section>
                    ) : null}
                </div>
            </div>

            <LembarBawah
                terbuka={lembarTerbuka}
                onTutup={() => {
                    setLembarTerbuka(false);
                    setDraf(drafDari(filter, saringan.teknisRendah.aktif));
                }}
                judul="Saring jalur"
                pemicu={pemicuLembar}
                aksi={
                    <>
                        <Button variant="ghost" onClick={() => setDraf(drafKosong)}>
                            Hapus semua
                        </Button>
                        <Button
                            disabled={pratinjau === 0}
                            onClick={() => {
                                terapkan(draf);
                                setLembarTerbuka(false);
                            }}
                        >
                            <span>
                                Tampilkan <span data-angka="">{pratinjau}</span> jalur
                            </span>
                        </Button>
                    </>
                }
            >
                <p aria-live="polite" className="sr-only">
                    {pratinjau} jalur cocok dengan saringan ini.
                </p>
                <SaringanJelajah opsi={saringan} draf={draf} onUbah={setDraf} />
            </LembarBawah>

            <p role="status" className="sr-only">
                {pengumuman}
            </p>
        </div>
    );
}

Index.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`resources/js/types/index.d.ts`:

Ganti:

```ts
    flash: { status: string | null; timbanganPenuh: string | null };
```

dengan:

```ts
    flash: {
        status: string | null;
        timbanganPenuh: string | null;
        /** Jalur yang baru dihapus dari simpanan dan catatannya, untuk Urungkan; satu kali. */
        simpananTerhapus: { trailId: number; nama: string; catatan: string | null } | null;
    };
```

Hapus berkas `resources/js/components/trails/ConsiderationTray.tsx`.

Hapus berkas `resources/js/components/trails/FilterForm.tsx`.

Hapus berkas `resources/js/components/trails/Menunggu.tsx`.

- [ ] **Step 4: Bangkitkan rute bertipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan wayfinder:generate`
Expected: tanpa galat. `resources/js/routes/trails/saved` memuat `store`; berkas bangkitan diabaikan git.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='TrailIndexSearch|TrailCard|SavedUndo|ResultListBudget|DesignPrimitives|InertiaLayout|SavedRoutes|ComparisonAsCost|RecommendationExclusion|Consideration|ShellFeedback|ActiveHikeStrip|LongInputLayout'`
Expected: PASS: 181 test, 181 lulus.

- [ ] **Step 6: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.685 test, 1.683 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus. Pada simulasi rencana: 11 test, 11 lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Peta Jelajah berpola, legenda, pemilih, pratinjau, dan panel lengket

Induk W3 butir 2; spec bagian 6.2 dan 10.5; butir W2-F3; ruling W3-R24 sampai W3-R28. `PetaJelajah` ditulis ulang:

- garis per label dibedakan pola: utuh (Cocok), putus panjang (Perlu persiapan), titik-titik (Kurang cocok), dan
  putus-titik (Belum dinilai), dengan tepi permukaan supaya terbaca di atas ubin;
- legenda bertulisan di dalam peta;
- jalur yang disorot di daftar atau dipilih digambar lebih tebal, dan jalur yang tersembunyi saringan dipudarkan;
- chip pemilih jalur di ponsel;
- kartu pratinjau berisi teks dari data dengan "Lihat detail jalur" dan "Lihat di daftar", tanpa popup HTML;
- kalimat berapa jalur di halaman ini yang belum punya garis rute.

Tanpa satu pun geometri, MapLibre tidak dimuat dan layar lebar menampilkan panel menunggu. Atribusi bawaan mengikuti
rumusan OpenTopoMap termasuk SRTM. Tinggi peta dan panel saringan yang menempel menyisakan header, pesan jaringan,
lapisan bawah, dan kepala halaman (W2-F3). `PetaSebaranJalur` dan suite Spatial-nya tidak berubah (W3-R28).

**Files:**
- Modify: `.env.example`, `config/hiking.php`, `resources/js/components/trails/PetaJelajah.tsx` (tulis ulang), `resources/js/pages/Trails/Index.tsx` (tulis ulang)
- Test: `tests/Feature/MapConfigTest.php`, `tests/Feature/TrailIndexMapTest.php`

**Interfaces:**
- Consumes: `usePetaDasar`, `WadahPeta`, `warnaToken` (W1), `LabelKecocokan`, `BarisStatus`, kartu `KartuJalur` dan props `petaSebaran` dari `HalamanJelajah` (Tugas 4), `ChipPilihan` (W1), token `--fit-*-teks` dan `--surface` di `app.css`.
- Produces:
  - `resources/js/components/trails/PetaJelajah.tsx`: `PetaJelajah({ fitur, baris, sorot, onLihatDiDaftar, kataBelumDinilai, className })`, dengan `fitur` dari `petaSebaran` (id dan geometri) dan `baris` kartu halaman ini;
  - nilai bawaan `hiking.map.attribution` dan `MAP_ATTRIBUTION` di `.env.example`: `© OpenStreetMap contributors, SRTM | Tampilan: © OpenTopoMap (CC-BY-SA)`;
  - kelas tinggi di `Trails/Index.tsx` yang memakai `var(--kepala-atas)`, `var(--pesan-jaringan,0px)`, dan `var(--nav-bawah,0px)`.

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/MapConfigTest.php`:

Ganti:

```php
    public function test_the_map_container_is_not_announced_as_an_image(): void
```

dengan:

```php
    /**
     * Rumusan atribusi OpenTopoMap termasuk SRTM (spec v3 bagian 10.5): data OpenStreetMap
     * dan SRTM, tampilan OpenTopoMap berlisensi CC-BY-SA. Bawaan di config dan contoh .env
     * sama, jadi lingkungan baru tidak mewarisi rumusan lama yang tanpa SRTM.
     */
    public function test_the_default_attribution_follows_the_opentopomap_wording(): void
    {
        $rumusan = '© OpenStreetMap contributors, SRTM | Tampilan: © OpenTopoMap (CC-BY-SA)';

        $this->assertStringContainsString("env('MAP_ATTRIBUTION', '{$rumusan}')", File::get(config_path('hiking.php')));
        $this->assertStringContainsString('MAP_ATTRIBUTION="'.$rumusan.'"', File::get(base_path('.env.example')));
    }

    public function test_the_map_container_is_not_announced_as_an_image(): void
```

Ganti seluruh isi `tests/Feature/TrailIndexMapTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Peta sebaran Jelajah digerbang data (keputusan F2, features/01).
 *
 * Yang digambar adalah bentuk jalur, bukan koordinat gunungnya. Suite ini berjalan di
 * SQLite tempat kolom geometri tidak ada, jadi di sini yang dapat dibuktikan adalah
 * sisi larangannya: koordinat gunung tidak pernah lagi menjadi penanda. Sisi
 * positifnya (garis jalur dari PostGIS) dijaga tests/Spatial/PetaSebaranJalurTest.
 */
class TrailIndexMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_map_never_falls_back_to_mountain_coordinates(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Gunung Uji', 'latitude' => -7.45, 'longitude' => 110.44]);
        Trail::factory()->published()->for($gunung)->create(['name' => 'Jalur A']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Index')
                ->has('petaSebaran', 0)
                ->where('trails.data.0.name', 'Jalur A'));
    }

    public function test_drafts_stay_in_their_own_section_and_off_the_map(): void
    {
        $gunung = Mountain::factory()->create(['latitude' => -7.45, 'longitude' => 110.44]);
        Trail::factory()->unpublished()->for($gunung)->create(['name' => 'Jalur Draft Berkoordinat']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('petaSebaran', 0)
                ->has('trails.data', 0)
                ->where('menunggu.0.name', 'Jalur Draft Berkoordinat'));
    }

    /**
     * Konfigurasi peta datang dari props bersama, dari config yang sama dengan CSP:
     * penyedia tile yang diganti operator ikut berubah di keduanya.
     */
    public function test_map_configuration_is_shared_from_config(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('peta.rasterTiles', array_values(array_filter((array) config('hiking.map.raster_tiles'))))
                ->where('peta.maxZoom', (int) config('hiking.map.max_zoom', 17)));
    }

    /**
     * Komponen peta menggambar garis jalur dengan warna dari token, dan labelnya selalu
     * tertulis di ringkasan, bukan warna saja (WCAG 1.4.1). Dirender di klien, jadi
     * dijaga dari sumbernya.
     */
    public function test_the_map_component_draws_trail_lines_coloured_by_tokens_with_text_labels(): void
    {
        $sumber = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));

        $this->assertStringContainsString("type: 'line'", $sumber);

        foreach (['--fit-cocok-teks', '--fit-perlu-teks', '--fit-kurang-teks', '--fit-belum-teks'] as $token) {
            $this->assertStringContainsString("warnaToken('{$token}'", $sumber);
        }

        $this->assertStringContainsString('fit?.labelText', $sumber, 'Pratinjau dan legenda harus menyebut label kecocokan dengan kata.');
        $this->assertStringNotContainsString('mountain.latitude', $sumber);
    }

    /**
     * Spec v3 bagian 6.2: setiap label punya pola garisnya sendiri (utuh, putus panjang,
     * titik-titik, dan titik-garis untuk yang belum dinilai), dan legenda menggambar pola yang
     * sama. line-dasharray MapLibre 4.7 tidak dapat berbeda per fitur, jadi satu lapisan per
     * label, semuanya di atas satu tepi (MapComponentTest).
     */
    public function test_each_label_draws_its_own_line_pattern_with_a_legend(): void
    {
        $sumber = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));

        foreach (["id: 'jalur-cocok'", "id: 'jalur-perlu'", "id: 'jalur-kurang'", "id: 'jalur-belum'"] as $lapisan) {
            $this->assertStringContainsString($lapisan, $sumber);
        }

        $this->assertStringContainsString("'line-dasharray': [4, 2]", $sumber);
        $this->assertStringContainsString("'line-dasharray': [0.1, 2]", $sumber);
        $this->assertStringContainsString("'line-dasharray': [3, 1.5, 0.1, 1.5]", $sumber);
        $this->assertMatchesRegularExpression('/COCOK: null,\s*PERLU_PERSIAPAN: \[4, 2\],\s*KURANG_COCOK: \[0\.1, 2\],\s*BELUM: \[3, 1\.5, 0\.1, 1\.5\],/', $sumber, 'Legenda memakai pola yang sama dengan lapisan peta.');
        $this->assertStringContainsString('aria-label="Legenda garis jalur"', $sumber);
    }

    /**
     * Jalur tanpa geometri tidak digambar dan tidak dikarang (R-007). Peta menyebut berapa
     * yang tidak tergambar; ketika tidak satu pun bergaris (data pemilik hari ini), halaman
     * tidak memuat MapLibre dan tidak menawarkan tampilan Peta, dan kolomnya menyebut siapa
     * yang ditunggu.
     */
    public function test_the_map_names_what_it_cannot_draw_and_waits_when_nothing_has_a_line(): void
    {
        Trail::factory()->published()->for(Mountain::factory())->create();

        $this->actingAs(User::factory()->create())->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('petaSebaran', 0)->has('trails.data', 1));

        $peta = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));

        $this->assertStringContainsString('Hanya jalur bergeometri yang digambar.', $peta);
        $this->assertStringContainsString('const tanpaGaris = baris.length - tergambar.length;', $peta);
        $this->assertMatchesRegularExpression('/\{adaPeta \? \(\s*<KontrolSegmen/', $halaman, 'Tanpa garis rute tidak ada tampilan Peta yang ditawarkan (DK-40).');
        $this->assertMatchesRegularExpression('/\) : kolomPeta \? \(\s*<section aria-label="Peta jalur"[^>]*>\s*<EmptyState\s+jenis="menunggu"/', $halaman);
        $this->assertStringContainsString('menunggu="Menunggu kurator Memount menggambar garis rute jalur di hasil ini"', $halaman);
    }

    /**
     * Tampilan Peta di ponsel menyembunyikan isi daftar saja: pencarian, jumlah hasil, dan
     * pemilih Daftar atau Peta tetap terlihat, jadi pembaca selalu dapat kembali ke daftar.
     */
    public function test_the_map_view_keeps_the_search_and_the_view_switch(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Index.tsx'));
        $sembunyikan = "<div className={kelas('space-y-4', adaPeta && tampilan === 'peta' && 'hidden xl:block')}>";

        $this->assertStringContainsString('<section aria-label="Daftar jalur" className="min-w-0 space-y-4">', $halaman);
        $this->assertStringContainsString($sembunyikan, $halaman);
        $this->assertLessThan(strpos($halaman, $sembunyikan), strpos($halaman, '<KotakCari'));
        $this->assertLessThan(strpos($halaman, $sembunyikan), strpos($halaman, '<KontrolSegmen'));
    }

    /**
     * Memilih jalur, lewat chip atau garisnya, membuka kartu pratinjau berisi teks dari data
     * dan "Lihat detail jalur"; tidak ada popup berisi HTML.
     */
    public function test_choosing_a_trail_opens_a_text_preview_card(): void
    {
        $sumber = File::get(resource_path('js/components/trails/PetaJelajah.tsx'));

        $this->assertStringContainsString('<ChipPilihan', $sumber);
        $this->assertStringContainsString('aria-label="Pilih jalur di peta"', $sumber);
        $this->assertStringContainsString('Lihat detail jalur', $sumber);
        $this->assertStringContainsString('label="Tutup pratinjau jalur"', $sumber);
        $this->assertStringNotContainsString('Popup', $sumber);
        $this->assertDoesNotMatchRegularExpression('/innerHTML|setHTML|dangerouslySetInnerHTML/', $sumber);
    }

    /**
     * Peta dan panel saringan yang lengket tidak tertutup lapisan tetap (tinjauan akhir W2,
     * W2-F3): tingginya menyisakan pesan jaringan dan lapisan bawah (bilah tab ditambah strip
     * pendakian, yang juga tampil mulai 1024 px), dan di layar lebar juga header. Tanpa itu
     * strip dan pesan menutup tepi peta beserta tombolnya. Dirender di klien, jadi dijaga dari
     * sumbernya; tangkapan 1440 px memeriksanya di layar.
     */
    public function test_the_sticky_map_and_filter_panel_leave_room_for_the_fixed_layers(): void
    {
        $jelajah = File::get(resource_path('js/pages/Trails/Index.tsx'));

        preg_match('/<PetaJelajah[\s\S]*?className="([^"]+)"/', $jelajah, $peta);
        preg_match('/<aside aria-label="Saringan jalur" className="hidden lg:block">\s*<div className="([^"]+)"/', $jelajah, $panel);

        $this->assertNotEmpty($peta, 'Kelas tinggi peta tidak ditemukan.');
        $this->assertNotEmpty($panel, 'Kelas panel saringan tidak ditemukan.');

        foreach (['h-[calc(' => $peta[1], 'xl:h-[calc(' => $peta[1], 'lg:max-h-[calc(' => $panel[1]] as $awalan => $kelas) {
            preg_match('/(?:^|\s)'.preg_quote($awalan, '/').'([^\]]+)\]/', $kelas, $tinggi);

            $this->assertNotEmpty($tinggi, "Tinggi {$awalan} tidak ditemukan.");
            $this->assertStringContainsString('var(--nav-bawah,0px)', $tinggi[1], "{$awalan} harus menyisakan bilah tab dan strip pendakian.");
            $this->assertStringContainsString('var(--pesan-jaringan,0px)', $tinggi[1], "{$awalan} harus menyisakan pesan jaringan.");

            if ($awalan !== 'h-[calc(') {
                $this->assertStringContainsString('var(--kepala-atas)', $tinggi[1], "{$awalan} harus menyisakan header layar lebar.");
            }
        }

        $this->assertStringContainsString('lg:overflow-y-auto', $panel[1], 'Panel yang lebih tinggi dari ruangnya harus bergulir sendiri.');
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrailIndexMap|MapConfig|MapComponent|ColourContrast'`
Expected: FAIL: 172 test, 165 lulus, 7 gagal, 0 error. Antara lain `MapConfigTest::test_the_default_attribution_follows_the_opentopomap_wording`, `TrailIndexMapTest::test_the_map_component_draws_trail_lines_coloured_by_tokens_with_text_labels`, `TrailIndexMapTest::test_each_label_draws_its_own_line_pattern_with_a_legend`.

- [ ] **Step 3: Tulis implementasinya.**

**Data, rute, dan konfigurasi**

`.env.example`:

Ganti:

```dotenv
MAP_ATTRIBUTION="© OpenTopoMap (CC-BY-SA) © OpenStreetMap contributors"
```

dengan:

```dotenv
MAP_ATTRIBUTION="© OpenStreetMap contributors, SRTM | Tampilan: © OpenTopoMap (CC-BY-SA)"
```

`config/hiking.php`:

Ganti:

```php
        'attribution' => env('MAP_ATTRIBUTION', '© OpenTopoMap (CC-BY-SA) © OpenStreetMap contributors'),
```

dengan:

```php
        // Rumusan OpenTopoMap termasuk SRTM (spec v3 bagian 10.5): data OpenStreetMap dan SRTM,
        // tampilan OpenTopoMap berlisensi CC-BY-SA.
        'attribution' => env('MAP_ATTRIBUTION', '© OpenStreetMap contributors, SRTM | Tampilan: © OpenTopoMap (CC-BY-SA)'),
```

**Antarmuka**

Ganti seluruh isi `resources/js/components/trails/PetaJelajah.tsx` dengan:

```tsx
import { useEffect, useRef, useState } from 'react';
import { kotakDari, usePetaDasar, type Pustaka } from '@/components/peta/usePetaDasar';
import { WadahPeta } from '@/components/peta/WadahPeta';
import { warnaToken } from '@/components/peta/warnaToken';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import type { FiturPeta, KartuJalur } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { ChipPilihan } from '@/components/ui/Chip';
import { TombolIkon } from '@/components/ui/TombolIkon';
import { kelas } from '@/lib/kelas';

type Pola = 'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | 'BELUM';

interface PetaJelajahProps {
    fitur: FiturPeta[];
    /** Kartu di halaman hasil yang sama; teks peta diambil dari sini, jadi angkanya sama dengan daftar. */
    baris: KartuJalur[];
    sorot: number | null;
    onLihatDiDaftar: (id: number) => void;
    /** Kata UNKNOWN dari server (RouteFitLabel), untuk legenda dan kartu pratinjau. */
    kataBelumDinilai: string;
    className?: string;
}

/**
 * Pola garis per label (spec v3 bagian 6.2, prototype 15): utuh, putus panjang, titik-titik,
 * dan titik-garis untuk yang belum dinilai. Bentuknya yang membedakan, warnanya menyertai,
 * jadi warna tidak pernah satu-satunya pembawa arti (WCAG 1.4.1). Satuannya kelipatan tebal
 * garis, sama dengan line-dasharray MapLibre.
 */
const POLA: Record<Pola, number[] | null> = {
    COCOK: null,
    PERLU_PERSIAPAN: [4, 2],
    KURANG_COCOK: [0.1, 2],
    BELUM: [3, 1.5, 0.1, 1.5],
};

const URUTAN_POLA: Pola[] = ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK', 'BELUM'];

function polaDari(b: KartuJalur | undefined): Pola {
    return b?.fit?.label ?? 'BELUM';
}

function warnaPola(pola: Pola): string {
    if (pola === 'COCOK') return warnaToken('--fit-cocok-teks', '#1e5e35');
    if (pola === 'PERLU_PERSIAPAN') return warnaToken('--fit-perlu-teks', '#7a4e0b');
    if (pola === 'KURANG_COCOK') return warnaToken('--fit-kurang-teks', '#8e2f22');

    return warnaToken('--fit-belum-teks', '#4e5551');
}

const batasDari = (maplibregl: Pustaka, fitur: FiturPeta[]) => kotakDari(maplibregl, fitur.flatMap((f) => f.geometri.coordinates));

/** Contoh garis untuk legenda: pola yang sama dengan di peta, digambar SVG. */
function ContohGaris({ pola }: { pola: Pola }) {
    const dash = POLA[pola];

    return (
        <svg aria-hidden="true" viewBox="0 0 28 8" className="h-2 w-7 shrink-0">
            <line
                x1="2"
                y1="4"
                x2="26"
                y2="4"
                strokeWidth="3"
                strokeLinecap={pola === 'KURANG_COCOK' ? 'round' : 'butt'}
                strokeDasharray={dash ? dash.map((d) => d * 3).join(' ') : undefined}
                className={kelas(
                    pola === 'COCOK' && 'stroke-fit-cocok-teks',
                    pola === 'PERLU_PERSIAPAN' && 'stroke-fit-perlu-teks',
                    pola === 'KURANG_COCOK' && 'stroke-fit-kurang-teks',
                    pola === 'BELUM' && 'stroke-fit-belum-teks',
                )}
            />
        </svg>
    );
}

/**
 * Peta sebaran Jelajah: bentuk jalur, bukan titik gunung (keputusan F2), untuk kartu di
 * halaman hasil yang sama dengan daftar.
 *
 * Petanya dibuat sekali dan hanya datanya yang diganti ketika saringan berubah, jadi posisi
 * dan zoom pembaca tidak hilang. Setiap label punya lapisan sendiri karena line-dasharray
 * MapLibre 4.7 tidak dapat berbeda per fitur; semua garis berdiri di atas satu tepi --surface
 * yang lebih lebar. Jalur yang disorot dari daftar atau dipilih lewat chip dan garisnya
 * tergambar lebih tebal; jalur yang tampil lewat "Tampilkan juga" tergambar pudar.
 *
 * Memilih jalur (chip di ponsel, atau menyentuh garisnya) membuka kartu pratinjau React
 * dengan label, angka, status, dan "Lihat detail jalur": teks dari data, bukan HTML. Jalur
 * tanpa geometri tidak digambar dan jumlahnya disebut di atas peta; daftar tetap lengkap.
 */
export function PetaJelajah({ fitur, baris, sorot, onLihatDiDaftar, kataBelumDinilai, className }: PetaJelajahProps) {
    const wadah = useRef<HTMLDivElement>(null);
    const sudahDipaskan = useRef(false);
    const sorotLama = useRef<number | null>(null);
    const [terpilih, setTerpilih] = useState<number | null>(null);
    const pilihRef = useRef(setTerpilih);

    pilihRef.current = setTerpilih;

    const { peta: instansi, pustaka, siap, gagal } = usePetaDasar(wadah, {
        saatSiap: (peta) => {
            const kosong = { type: 'FeatureCollection' as const, features: [] };
            peta.addSource('jalur', { type: 'geojson', data: kosong, promoteId: 'id' });
            peta.addSource('awal', { type: 'geojson', data: kosong, promoteId: 'id' });
            const tepi = warnaToken('--surface', '#ffffff');
            const tebal = (biasa: number, disorot: number) => ['case', ['boolean', ['feature-state', 'sorot'], false], disorot, biasa] as unknown as number;
            const pudar = ['case', ['boolean', ['get', 'pudar'], false], 0.45, 1] as unknown as number;
            const hanya = (pola: Pola) => ['==', ['get', 'pola'], pola] as import('maplibre-gl').FilterSpecification;
            peta.addLayer({ id: 'jalur-tepi', type: 'line', source: 'jalur', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': tepi, 'line-width': tebal(7, 10), 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-cocok', type: 'line', source: 'jalur', filter: hanya('COCOK'), layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaPola('COCOK'), 'line-width': tebal(4, 6), 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-perlu', type: 'line', source: 'jalur', filter: hanya('PERLU_PERSIAPAN'), layout: { 'line-cap': 'butt', 'line-join': 'round' }, paint: { 'line-color': warnaPola('PERLU_PERSIAPAN'), 'line-width': tebal(4, 6), 'line-dasharray': [4, 2], 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-kurang', type: 'line', source: 'jalur', filter: hanya('KURANG_COCOK'), layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': warnaPola('KURANG_COCOK'), 'line-width': tebal(4, 6), 'line-dasharray': [0.1, 2], 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-belum', type: 'line', source: 'jalur', filter: hanya('BELUM'), layout: { 'line-cap': 'butt', 'line-join': 'round' }, paint: { 'line-color': warnaPola('BELUM'), 'line-width': tebal(4, 6), 'line-dasharray': [3, 1.5, 0.1, 1.5], 'line-opacity': pudar } });
            peta.addLayer({ id: 'jalur-awal', type: 'circle', source: 'awal', paint: { 'circle-radius': 6, 'circle-color': ['get', 'warna'], 'circle-stroke-color': tepi, 'circle-stroke-width': 2, 'circle-opacity': pudar, 'circle-stroke-opacity': pudar } });

            for (const lapisan of ['jalur-cocok', 'jalur-perlu', 'jalur-kurang', 'jalur-belum', 'jalur-awal']) {
                peta.on('click', lapisan, (e) => pilihRef.current(Number(e.features?.[0]?.properties?.id)));
                peta.on('mouseenter', lapisan, () => (peta.getCanvas().style.cursor = 'pointer'));
                peta.on('mouseleave', lapisan, () => (peta.getCanvas().style.cursor = ''));
            }
        },
    });

    useEffect(() => {
        const peta = instansi.current;
        const maplibregl = pustaka.current;
        if (!siap || !peta || !maplibregl) return;

        const kartu = new Map(baris.map((b) => [b.id, b]));
        const properti = (id: number) => {
            const pola = polaDari(kartu.get(id));

            return { id, pola, warna: warnaPola(pola), pudar: (kartu.get(id)?.tersembunyiOleh ?? null) !== null };
        };

        (peta.getSource('jalur') as import('maplibre-gl').GeoJSONSource).setData({
            type: 'FeatureCollection',
            features: fitur.map((f) => ({ type: 'Feature' as const, id: f.id, properties: properti(f.id), geometry: f.geometri })),
        });
        (peta.getSource('awal') as import('maplibre-gl').GeoJSONSource).setData({
            type: 'FeatureCollection',
            features: fitur.map((f) => ({ type: 'Feature' as const, id: f.id, properties: properti(f.id), geometry: { type: 'Point' as const, coordinates: f.awal } })),
        });

        // Dipaskan sekali saja. Sesudahnya saringan mengganti isi tanpa memindahkan peta,
        // supaya pembaca tidak kehilangan posisinya.
        if (!sudahDipaskan.current && fitur.length > 0) {
            peta.fitBounds(batasDari(maplibregl, fitur), { padding: 48, maxZoom: 13, duration: 0 });
            sudahDipaskan.current = true;
        }
    }, [fitur, baris, siap]);

    // Jalur terpilih yang tidak lagi ada di halaman ini (saringan berubah) tidak dipratinjau.
    const pilihan = terpilih !== null && fitur.some((f) => f.id === terpilih) ? terpilih : null;
    const tebalkan = sorot ?? pilihan;

    useEffect(() => {
        const peta = instansi.current;
        if (!siap || !peta) return;
        if (sorotLama.current !== null) {
            peta.setFeatureState({ source: 'jalur', id: sorotLama.current }, { sorot: false });
        }
        if (tebalkan !== null && fitur.some((f) => f.id === tebalkan)) {
            peta.setFeatureState({ source: 'jalur', id: tebalkan }, { sorot: true });
        }
        sorotLama.current = tebalkan;
    }, [tebalkan, siap, fitur]);

    const paskan = (daftar: FiturPeta[]) => {
        const peta = instansi.current;
        const maplibregl = pustaka.current;
        if (peta && maplibregl && daftar.length > 0) {
            peta.fitBounds(batasDari(maplibregl, daftar), { padding: 48, maxZoom: 14 });
        }
    };

    const pilih = (id: number | null) => {
        setTerpilih(id);
        const f = fitur.find((x) => x.id === id);
        if (f) paskan([f]);
    };

    const tergambar = fitur.map((f) => baris.find((b) => b.id === f.id)).filter((b): b is KartuJalur => b !== undefined);
    const tanpaGaris = baris.length - tergambar.length;
    const legenda = URUTAN_POLA.map((pola) => ({ pola, contoh: tergambar.find((b) => polaDari(b) === pola) })).filter(({ contoh }) => contoh !== undefined);
    const pratinjau = pilihan === null ? null : (tergambar.find((b) => b.id === pilihan) ?? null);

    return (
        <div className={kelas('flex flex-col gap-2', className)}>
            {tergambar.length > 1 ? (
                <div role="group" aria-label="Pilih jalur di peta" className="relative -mx-1 flex gap-1 overflow-x-auto px-1 xl:hidden">
                    {tergambar.map((b) => (
                        <ChipPilihan key={b.id} dipilih={pilihan === b.id} onUbah={(dipilih) => pilih(dipilih ? b.id : null)} className="shrink-0">
                            {b.name}
                        </ChipPilihan>
                    ))}
                </div>
            ) : null}
            <WadahPeta wadahRef={wadah} label="Peta sebaran jalur" gagal={gagal} ukuran="min-h-0 flex-1" cadangan="Daftar jalur tetap dapat dipakai.">
                {!gagal ? (
                    <p className="pointer-events-none absolute left-3 top-3 z-10 max-w-[17rem] rounded-control bg-surface/95 px-3 py-2 text-meta text-secondary shadow-overlay">
                        Hanya jalur bergeometri yang digambar.
                        {tanpaGaris > 0 ? ` ${tanpaGaris} jalur di halaman ini belum punya garis rute; semuanya tetap ada di daftar.` : null}
                    </p>
                ) : null}
                {siap && fitur.length > 0 && pratinjau === null ? (
                    <Button variant="secondary" size="sm" onClick={() => paskan(fitur)} className="absolute bottom-3 left-3 z-10 shadow-overlay">
                        Tampilkan semua jalur
                    </Button>
                ) : null}
                {legenda.length > 0 ? (
                    <ul aria-label="Legenda garis jalur" className={kelas('absolute bottom-3 right-3 z-10 space-y-1 rounded-control bg-surface/95 px-3 py-2 shadow-overlay', pratinjau && 'hidden sm:block')}>
                        {legenda.map(({ pola, contoh }) => (
                            <li key={pola} className="flex items-center gap-2 text-meta text-secondary">
                                <ContohGaris pola={pola} />
                                {contoh?.fit?.labelText ?? kataBelumDinilai}
                            </li>
                        ))}
                    </ul>
                ) : null}
                {pratinjau ? (
                    <div className="absolute inset-x-3 bottom-3 z-10 rounded-lg border border-subtle bg-surface p-4 shadow-overlay sm:right-auto sm:w-80">
                        <div className="flex items-start justify-between gap-2">
                            <div className="pt-2.5">
                                <LabelKecocokan nilai={pratinjau.fit?.label ?? null} label={pratinjau.fit?.labelText ?? kataBelumDinilai} />
                            </div>
                            <TombolIkon ikon="x" label="Tutup pratinjau jalur" varian="polos" onClick={() => setTerpilih(null)} />
                        </div>
                        <p className="mt-1 text-subjudul text-primary">{pratinjau.name}</p>
                        <p data-angka="" className="mt-0.5 text-kecil text-secondary">
                            {[pratinjau.jarak, pratinjau.tanjakan === '-' ? null : `↑${pratinjau.tanjakan}`, `naik ${pratinjau.durasi}`].filter(Boolean).join(', ')}
                        </p>
                        <BarisStatus status={pratinjau.status} className="mt-2" />
                        <div className="mt-3 flex flex-wrap gap-2">
                            <Button href={pratinjau.url} size="sm">
                                Lihat detail jalur
                            </Button>
                            <Button variant="secondary" size="sm" onClick={() => onLihatDiDaftar(pratinjau.id)} className="xl:hidden">
                                Lihat di daftar
                            </Button>
                        </div>
                    </div>
                ) : null}
            </WadahPeta>
            <p className="sr-only">Jalur yang sama tercantum di daftar beserta rinciannya.</p>
        </div>
    );
}
```

Ganti seluruh isi `resources/js/pages/Trails/Index.tsx` dengan:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { KartuBelumDinilai } from '@/components/trails/jelajah/KartuBelumDinilai';
import { KartuJalur } from '@/components/trails/jelajah/KartuJalur';
import { KotakCari } from '@/components/trails/jelajah/KotakCari';
import { drafDari, drafKosong, hitungLolos, kueriDari, STATUS_BAWAAN, type AtributJalur, type DrafSaringan, type FilterJelajah } from '@/components/trails/jelajah/saringan';
import { SaringanJelajah, type OpsiSaringan } from '@/components/trails/jelajah/SaringanJelajah';
import { TafsiranKalimat, type Tafsiran } from '@/components/trails/jelajah/TafsiranKalimat';
import { PetaJelajah } from '@/components/trails/PetaJelajah';
import type { FiturPeta, JalurMenunggu, KartuJalur as DataKartu, TautanHalaman } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { LembarBawah } from '@/components/ui/Lembar';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Skeleton } from '@/components/ui/Skeleton';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { TautanSimpanan } from '@/layouts/TautanSimpanan';
import { kelas } from '@/lib/kelas';
import { onboarding } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    filter: FilterJelajah;
    tafsiran: Tafsiran;
    trails: { data: DataKartu[]; total: number; links: TautanHalaman[] };
    ringkasan: {
        jumlah: number;
        tersembunyi: Array<{ kunci: string; label: string; jumlah: number }>;
        terpotong: number | null;
        urutan: 'kecocokan' | 'nama';
    };
    saringan: OpsiSaringan;
    atribut: AtributJalur[];
    dinilai: boolean;
    /** Ringkasan profil yang menjadi dasar label, misalnya "Pemula, tanjakan tertinggi 700 m". */
    dasarPenilaian: string | null;
    jumlahTerbit: number;
    menunggu: JalurMenunggu[];
    petaSebaran: FiturPeta[];
    kataBelumDinilai: string;
    pengumuman: string;
}

const pilihanTampilan = [
    { nilai: 'daftar' as const, label: 'Daftar', ikon: 'list' as const },
    { nilai: 'peta' as const, label: 'Peta', ikon: 'map-trifold' as const },
];

/** "saringan status resmi", atau "saringan: status resmi (1), tanjakan (2)" bila lebih dari satu. */
function saringanPenyembunyi(tersembunyi: Props['ringkasan']['tersembunyi']): string {
    if (tersembunyi.length === 1) {
        return `saringan ${tersembunyi[0]?.label ?? ''}`;
    }

    return `saringan: ${tersembunyi.map((s) => `${s.label} (${s.jumlah})`).join(', ')}`;
}

/** Jumlah kelompok saringan yang sedang menyempitkan hasil, untuk tombol Saring. */
function jumlahSaringan(filter: FilterJelajah, teknisAktif: boolean): number {
    return [
        filter.kecocokan !== null,
        filter.jenis !== null,
        teknisAktif,
        [...filter.status].sort().join(',') !== [...STATUS_BAWAAN].sort().join(','),
        filter.naikDari !== null || filter.naikSampai !== null,
    ].filter(Boolean).length;
}

/**
 * Jelajah (spec v3 bagian 6.2, prototype 02 dan 15). Ponsel: pencarian kalimat dengan
 * tafsirannya, tombol Saring yang membuka lembar bawah berhitungan langsung, lalu daftar atau
 * peta. Layar lebar: saringan di panel kiri, daftar di tengah, dan mulai 1280 px peta di
 * kanan. Saringan hidup di URL (dapat dibagikan, tombol kembali bekerja) dan setiap perubahan
 * memuat ulang data lewat Inertia tanpa memuat ulang halaman.
 *
 * Keadaan kosong diperiksa berurutan (W1-F1): belum ada jalur terbit sama sekali menyebut
 * siapa yang ditunggu apa pun saringannya; baru sesudah itu hasil kosong disebut akibat
 * saringan, beserta yang disembunyikannya dan cara melepasnya.
 */
export default function Index() {
    const { filter, tafsiran, trails, ringkasan, saringan, atribut, dinilai, dasarPenilaian, jumlahTerbit, menunggu, petaSebaran, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    // Pemilik toast Urungkan adalah halaman ini, yang bertahan selama saringan dan simpan
    // berjalan dengan preserveState (ruling W2-5i); kartu dan chip hanya meneruskannya.
    const { tampilkanToast } = useKerangka();
    const tafsiranTeknis = tafsiran.chip.some((c) => c.kunci === 'teknis');
    const [cari, setCari] = useState(filter.search);
    const [tampilan, setTampilan] = useState<'daftar' | 'peta'>('daftar');
    const [sorot, setSorot] = useState<number | null>(null);
    const [lembarTerbuka, setLembarTerbuka] = useState(false);
    const [draf, setDraf] = useState<DrafSaringan>(() => drafDari(filter, saringan.teknisRendah.aktif));
    const [memuat, setMemuat] = useState(false);
    const pemicuLembar = useRef<HTMLElement | null>(null);
    const pertama = useRef(true);
    const adaPeta = petaSebaran.length > 0;
    // Kolom ketiga di layar lebar ada selama ada hasil: peta, atau kalimat menunggu bila belum
    // satu pun jalur di hasil ini bergaris rute (data pemilik hari ini).
    const kolomPeta = trails.data.length > 0;
    const nSaringan = jumlahSaringan(filter, saringan.teknisRendah.aktif);
    const pratinjau = hitungLolos(atribut, draf, dinilai);

    const kunjungi = (kueri: Record<string, string | number>) =>
        router.get(trailsIndex.url({ query: kueri }), {}, { preserveState: true, preserveScroll: true, replace: true });

    const terapkan = (d: DrafSaringan, semua = filter.tampilkanSemua) => kunjungi(kueriDari(filter, d, tafsiranTeknis, semua));

    useEffect(() => setDraf(drafDari(filter, saringan.teknisRendah.aktif)), [filter, saringan.teknisRendah.aktif]);

    useEffect(() => {
        const mulai = router.on('start', () => setMemuat(true));
        const selesai = router.on('finish', () => setMemuat(false));

        return () => {
            mulai();
            selesai();
        };
    }, []);

    // Kalimat baru membaca tafsirannya sendiri, jadi tafsiran yang dilepas dan tuntutan teknis
    // dari kalimat lama tidak ikut; saringan lain tetap berlaku.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        const t = setTimeout(() => kunjungi(kueriDari({ ...filter, search: cari, abaikan: [] }, drafDari(filter, filter.teknisRendah), false)), 400);

        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cari]);

    const bukaLembar = (e: MouseEvent<HTMLElement>) => {
        pemicuLembar.current = e.currentTarget;
        setDraf(drafDari(filter, saringan.teknisRendah.aktif));
        setLembarTerbuka(true);
    };

    const lihatDiDaftar = (id: number) => {
        setTampilan('daftar');
        setSorot(id);
        requestAnimationFrame(() => document.getElementById(`jalur-${id}`)?.scrollIntoView({ block: 'center' }));
    };

    const olehSaringan = saringanPenyembunyi(ringkasan.tersembunyi);
    const jumlahTersembunyi = ringkasan.tersembunyi.reduce((n, s) => n + s.jumlah, 0);

    return (
        <div className="py-6">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Jelajah" aksi={<TautanSimpanan className="lg:hidden" />} />

                <div
                    className={kelas(
                        'lg:grid lg:gap-8',
                        adaPeta ? 'lg:grid-cols-[17rem_minmax(0,1fr)]' : 'lg:grid-cols-[17rem_minmax(0,40rem)]',
                        kolomPeta && 'xl:grid-cols-[18.75rem_32.5rem_minmax(0,1fr)]',
                    )}
                >
                    {/* Panel lengket tidak lebih tinggi dari ruang di antara header dan lapisan bawah
                        (strip pendakian juga tampil mulai 1024 px), jadi isinya bergulir sendiri. 6rem
                        menyisakan juga kepala halaman di atas panel pada posisi gulir awal. */}
                    <aside aria-label="Saringan jalur" className="hidden lg:block">
                        <div className="space-y-6 lg:sticky lg:top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] lg:-m-1 lg:max-h-[calc(100dvh-var(--kepala-atas)-var(--pesan-jaringan,0px)-var(--nav-bawah,0px)-6rem)] lg:overflow-y-auto lg:p-1">
                            <TafsiranKalimat tafsiran={tafsiran} tampilkanToast={tampilkanToast} onAbaikan={(abaikan) => kunjungi(kueriDari({ ...filter, abaikan }, drafDari(filter, filter.teknisRendah), false))} />
                            <SaringanJelajah
                                opsi={saringan}
                                draf={draf}
                                onUbah={(d) => {
                                    setDraf(d);
                                    terapkan(d);
                                }}
                            />
                        </div>
                    </aside>

                    <section aria-label="Daftar jalur" className="min-w-0 space-y-4">
                        <KotakCari nilai={cari} onUbah={setCari} />

                        <div className="lg:hidden">
                            <TafsiranKalimat tafsiran={tafsiran} tampilkanToast={tampilkanToast} onAbaikan={(abaikan) => kunjungi(kueriDari({ ...filter, abaikan }, drafDari(filter, filter.teknisRendah), false))} />
                        </div>

                        <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <Button variant="secondary" size="sm" className="lg:hidden" onClick={bukaLembar}>
                                <Icon name="sliders-horizontal" size={20} />
                                <span>
                                    Saring{nSaringan > 0 ? <span data-angka=""> ({nSaringan})</span> : null}
                                </span>
                            </Button>
                            <p aria-live="polite" className="order-last min-w-0 basis-full text-kecil text-secondary sm:order-none sm:flex-1 sm:basis-auto">
                                <span data-angka="" className="font-bold text-primary">
                                    {ringkasan.jumlah}
                                </span>{' '}
                                jalur
                                {jumlahTersembunyi > 0 ? `, ${jumlahTersembunyi} disembunyikan ${olehSaringan}` : null}
                            </p>
                            <p className="hidden text-kecil text-secondary sm:block">
                                Urut: <span className="font-bold text-primary">{ringkasan.urutan}</span>
                            </p>
                            {adaPeta ? (
                                <KontrolSegmen nama="tampilan-jelajah" label="Tampilan hasil" pilihan={pilihanTampilan} nilai={tampilan} onUbah={setTampilan} className="ml-auto w-56 xl:hidden" />
                            ) : null}
                        </div>

                        {kolomPeta && !adaPeta ? <p className="text-meta text-secondary xl:hidden">Peta belum tersedia: belum ada jalur di hasil ini yang punya garis rute.</p> : null}

                        {/* Tampilan Peta di ponsel menyembunyikan isi daftar, bukan pencarian dan pemilih tampilannya. */}
                        <div className={kelas('space-y-4', adaPeta && tampilan === 'peta' && 'hidden xl:block')}>
                            {dinilai ? (
                                <p className="text-meta text-secondary">
                                    Label menunjukkan kecocokan dasar dengan profil Anda{dasarPenilaian ? ` (${dasarPenilaian})` : ''}. Profil elevasi memakai skala yang sama di semua kartu.
                                </p>
                            ) : (
                                <div className="rounded-lg border border-subtle bg-surface p-4">
                                    <p className="text-kecil font-bold text-primary">Jalur di sini belum dinilai untuk Anda</p>
                                    <p className="mt-1 text-kecil text-secondary">Isi pengalaman pendakian Anda, lalu setiap jalur menyebut cocok atau tidaknya beserta alasannya.</p>
                                    <Button href={onboarding.url()} size="sm" className="mt-3">
                                        Isi profil pendaki
                                    </Button>
                                </div>
                            )}

                            {ringkasan.terpotong !== null ? (
                                <Alert variant="info">Menampilkan {ringkasan.terpotong} jalur pertama menurut nama. Persempit dengan kalimat atau wilayah untuk melihat sisanya.</Alert>
                            ) : null}

                            {jumlahTerbit === 0 ? (
                                <EmptyState
                                    jenis="menunggu"
                                    title="Belum ada jalur yang terbit"
                                    menunggu="Menunggu kurator Memount menerbitkan jalur yang datanya lengkap"
                                    description="Jalur tampil di sini setelah datanya lengkap: sumber, jarak, tanjakan, durasi, pos, status resmi, dan garis rute di peta."
                                />
                            ) : trails.data.length === 0 ? (
                                <EmptyState
                                    jenis="tersaring"
                                    title="Tidak ada jalur dengan saringan ini"
                                    description={jumlahTersembunyi > 0 ? `${jumlahTersembunyi} jalur disembunyikan ${olehSaringan}. Lepas salah satu saringan, atau tampilkan juga jalur yang disembunyikan.` : 'Coba kalimat atau kata kunci lain.'}
                                >
                                    {jumlahTersembunyi > 0 ? (
                                        <Button variant="secondary" size="sm" onClick={() => terapkan(draf, true)}>
                                            Tampilkan juga
                                        </Button>
                                    ) : null}
                                    <Button variant="secondary" size="sm" onClick={() => kunjungi({})}>
                                        Hapus semua saringan
                                    </Button>
                                </EmptyState>
                            ) : memuat ? (
                                <div aria-busy="true" className="space-y-3">
                                    {[0, 1, 2].map((i) => (
                                        <Skeleton key={i} rows={4} className="rounded-lg border border-subtle bg-surface p-5" />
                                    ))}
                                </div>
                            ) : (
                                <ul className="space-y-3">
                                    {trails.data.map((t) => (
                                        <li key={t.id} onMouseEnter={() => setSorot(t.id)} onMouseLeave={() => setSorot((s) => (s === t.id ? null : s))} onFocusCapture={() => setSorot(t.id)}>
                                            <KartuJalur jalur={t} kataBelumDinilai={kataBelumDinilai} disorot={sorot === t.id} tampilkanToast={tampilkanToast} />
                                        </li>
                                    ))}
                                </ul>
                            )}

                            {jumlahTersembunyi > 0 && trails.data.length > 0 ? (
                                <div className="flex flex-wrap items-center gap-2 rounded-lg bg-surface-sunken p-4">
                                    <p className="min-w-0 flex-1 text-kecil text-secondary">
                                        <span data-angka="">{jumlahTersembunyi}</span> jalur disembunyikan {olehSaringan}.
                                    </p>
                                    <Button variant="secondary" size="sm" onClick={() => terapkan(draf, !filter.tampilkanSemua)}>
                                        {filter.tampilkanSemua ? 'Sembunyikan lagi' : 'Tampilkan juga'}
                                    </Button>
                                </div>
                            ) : null}

                            <Pagination links={trails.links} />

                            {menunggu.length > 0 ? (
                                <section aria-labelledby="judul-menunggu" className="space-y-3 pt-2">
                                    <h2 id="judul-menunggu" className="text-bagian text-primary">
                                        Belum bisa dinilai
                                    </h2>
                                    <p className="text-kecil text-secondary">Jalur berikut sudah dikenali sistem, tetapi datanya belum lengkap. Tidak dihitung dan tidak direkomendasikan.</p>
                                    <KartuBelumDinilai daftar={menunggu} />
                                </section>
                            ) : null}
                        </div>
                    </section>

                    {/* Tinggi peta menyisakan pesan jaringan dan lapisan bawah (bilah tab ditambah strip
                        pendakian), dan di panel lengket layar lebar juga header serta kepala halaman
                        pada posisi gulir awal, supaya tepi peta dan tombolnya tidak tertutup. */}
                    {adaPeta ? (
                        <section aria-label="Peta jalur" className={kelas(tampilan === 'daftar' ? 'hidden xl:block' : 'block', 'mt-4 xl:sticky xl:top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] xl:mt-0 xl:self-start')}>
                            <PetaJelajah
                                fitur={petaSebaran}
                                baris={trails.data}
                                sorot={sorot}
                                onLihatDiDaftar={lihatDiDaftar}
                                kataBelumDinilai={kataBelumDinilai}
                                className="h-[calc(100dvh-var(--nav-bawah,0px)-var(--pesan-jaringan,0px)-18rem)] min-h-[20rem] xl:h-[calc(100dvh-var(--kepala-atas)-var(--pesan-jaringan,0px)-var(--nav-bawah,0px)-6rem)]"
                            />
                        </section>
                    ) : kolomPeta ? (
                        <section aria-label="Peta jalur" className="hidden xl:sticky xl:top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] xl:block xl:self-start">
                            <EmptyState
                                jenis="menunggu"
                                title="Peta menunggu garis rute"
                                menunggu="Menunggu kurator Memount menggambar garis rute jalur di hasil ini"
                                description="Hanya jalur bergeometri yang digambar di peta. Daftar di samping memuat semua jalur hasil pencarian."
                            />
                        </section>
                    ) : null}
                </div>
            </div>

            <LembarBawah
                terbuka={lembarTerbuka}
                onTutup={() => {
                    setLembarTerbuka(false);
                    setDraf(drafDari(filter, saringan.teknisRendah.aktif));
                }}
                judul="Saring jalur"
                pemicu={pemicuLembar}
                aksi={
                    <>
                        <Button variant="ghost" onClick={() => setDraf(drafKosong)}>
                            Hapus semua
                        </Button>
                        <Button
                            disabled={pratinjau === 0}
                            onClick={() => {
                                terapkan(draf);
                                setLembarTerbuka(false);
                            }}
                        >
                            <span>
                                Tampilkan <span data-angka="">{pratinjau}</span> jalur
                            </span>
                        </Button>
                    </>
                }
            >
                <p aria-live="polite" className="sr-only">
                    {pratinjau} jalur cocok dengan saringan ini.
                </p>
                <SaringanJelajah opsi={saringan} draf={draf} onUbah={setDraf} />
            </LembarBawah>

            <p role="status" className="sr-only">
                {pengumuman}
            </p>
        </div>
    );
}

Index.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='TrailIndexMap|MapConfig|MapComponent|ColourContrast'`
Expected: PASS: 172 test, 172 lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.691 test, 1.689 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus. Pada simulasi rencana: 11 test, 11 lulus. Data contoh tanpa geometri, jadi
pratinjau Jelajah menampilkan keadaan menunggu peta; pola garis diperiksa koordinator di Tugas 12.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Rincian jalur

Induk W3 butir 3; spec bagian 6.4 dan PRD §89; butir W2-F5; ruling W3-R29 sampai W3-R38. `TrailDetailPage` ditulis ulang
dalam urutan pengungkapan PRD §89:

- peta jalur dan pos, dengan kalimat menunggu bila garis rute belum ada;
- tiga angka sekali jalan naik (W3-R29);
- "Apa tantangannya?" (W3-R30);
- "Cocokkah untuk saya?", dengan kecocokan dasar atau untuk trip terdekat (W3-R31), tiga daftar, tautan ketujuh
  faktor, dan kalimat "Dinilai dengan aturan tertulis, bukan AI, dan tanpa skor angka";
- profil elevasi dengan ruas tercuram diarsir dan kalimatnya (W3-R32);
- pos dengan elevasi, km dari garis rute (W3-R33), rentang waktu dari rekaman (W3-R34), dan aksen untuk pos berkemah
  dan tujuan (W3-R35);
- kondisi sekarang dengan status resmi bersumber, prakiraan area, dan catatan tanggal terbit prakiraan untuk trip
  pembaca;
- laporan pendaki;
- yang perlu disiapkan;
- sumber di halaman ini dalam urutan kepercayaan.

Di ponsel bar aksinya satu baris (W3-R37). Mulai 1024 px kecocokan dan aksi berdiri di kolom kanan yang menempel.
Ringkasan pendakian memakai `ProfilElevasi` dan `DaftarPos` yang sama, dan `ElevationProfile` serta
`CheckpointJourney` lama dihapus (keputusan nomor 4).

**Files:**
- Create: `app/Services/Trails/PosSepanjangJalur.php`, `app/Services/Trails/ProfilElevasiJalur.php`, `resources/js/components/trails/ProfilElevasi.tsx`, `resources/js/components/trails/rinci/AngkaNaik.tsx`, `resources/js/components/trails/rinci/CocokkahUntukSaya.tsx`, `resources/js/components/trails/rinci/DaftarPos.tsx`, `resources/js/components/trails/rinci/Persiapan.tsx`, `resources/js/components/trails/rinci/SumberHalaman.tsx`, `resources/js/components/trails/rinci/Tantangan.tsx`
- Modify: `app/Http/Resources/TrailHeaderResource.php`, `app/Models/TripPlan.php`, `app/Services/Community/HasilPendakian.php`, `app/Services/PreparationService.php`, `app/Services/TrailFitService.php`, `app/Services/Trails/CheckpointJourney.php` (tulis ulang), `app/Services/Trails/PenilaianKecocokan.php`, `app/Services/Trails/SkalaProfil.php`, `app/Services/Trails/TrailDetailPage.php` (tulis ulang), `config/hiking.php`, `resources/js/components/trails/TombolSimpan.tsx`, `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/components/trails/rinci/PetaJalur.tsx`, `resources/js/components/trails/rinci/types.ts` (tulis ulang), `resources/js/pages/Community/HikeSummary.tsx`, `resources/js/pages/Trails/Show.tsx` (tulis ulang)
- Delete: `resources/js/components/ui/CheckpointJourney.tsx`, `resources/js/components/ui/ElevationProfile.tsx`
- Test: `tests/Spatial/PosSepanjangJalurTest.php` (baru), `tests/Feature/AmbientFitTest.php`, `tests/Feature/AwaitingDataTest.php`, `tests/Feature/CheckpointJourneyTest.php`, `tests/Feature/CheckpointPaceDisplayTest.php`, `tests/Feature/ElevationProfileDisplayTest.php`, `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/HikeSummaryTest.php`, `tests/Feature/ProgressLadderOnTrailDetailTest.php`, `tests/Feature/TrailDetailMapTest.php`, `tests/Feature/TrailDetailPageTest.php`

**Interfaces:**
- Consumes: `LabelKecocokan`, `StatusAkses`, `Kesegaran`, `LabelSumber` (Tugas 1), `SkalaProfil` (Tugas 2), `PenilaianKecocokan` dan rute `trails.fit` (Tugas 3), `TombolSimpan`, `ConsiderationService::simpanan()` (Tugas 4), `CompatibilityScorer`, `TrailFitService`, `PreparationService`, `PermitService`, `CheckpointPaceService`, `WeatherService` (sudah ada), `BarAksi`, `useKerangka()`, `AppLayout bilahTab={false}` (W2), `PostGis` untuk kueri spasial.
- Produces:
  - `TrailDetailPage::props(Trail $trail, User $user): array` dengan kunci `kecocokan`, `ujung`, `angkaNaik`, `tantangan`, `profilElevasi`, `petaJalur`, `pos`, `waktuDariRekaman`, `segmen`, `statusResmi`, `perizinan`, `cuaca`, `komunitas`, `persiapan`, `sumber`, `mengikutiGunung`, `ditimbang`, `adaCatatan`, `bandingUrl`;
  - `App\Services\Trails\PosSepanjangJalur::km(Trail $trail): array` (km per id pos dari garis rute; kosong tanpa geometri atau di luar PostgreSQL);
  - `App\Services\Trails\ProfilElevasiJalur::untuk(?array $profil, Collection $checkpoints, array $posKm): ?array`, `tercuram(array $titik): ?array`, `jarangkan(array $titik): array`;
  - `CheckpointJourney::daftar(Collection $checkpoints, array $paces, array $km): array` dan `aksen(Checkpoint $pos, ?Checkpoint $terakhir): ?string`;
  - `TripPlan::tujuanPenilaian(): HikingGoal` dan `ringkasan(): string` (dipakai juga `PenilaianKecocokan`);
  - `PreparationService::khususJalur(Trail $trail): Collection` dan konstanta publik `PERMIT_LABEL`;
  - `TrailFitService::alasan(RouteFitResult $hasil): string` menjadi publik;
  - `TrailHeaderResource` menambah `mountainUrl`; `hiking.conditions.weather_forecast_days` (3);
  - komponen `ProfilElevasi({ profil, className })`, `rinci/AngkaNaik`, `rinci/CocokkahUntukSaya`, `rinci/DaftarPos({ pos, className })`, `rinci/Persiapan`, `rinci/SumberHalaman`, `rinci/Tantangan`, dan tipenya di `rinci/types.ts` (`KecocokanRinci`, `BarisTantangan`, `AksenPos`, `PosJalur`, `ProfilElevasiData`, `ButirPersiapan`, `BarisSumber`, `Cuaca`);
  - `TombolSimpan` menerima `bentuk = 'bar'` (tombol ikon 44 px bergaris).

- [ ] **Step 1: Tulis test yang gagal.** Test Spatial `PosSepanjangJalurTest` melewati dirinya tanpa
  `SPATIAL_TEST_DSN` dan baru berjalan di CI.

Buat `tests/Spatial/PosSepanjangJalurTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\Checkpoint;
use App\Models\Trail;
use App\Services\Trails\PosSepanjangJalur;
use Illuminate\Support\Facades\DB;

/**
 * Jarak pos dari awal diukur sepanjang garis rute (W3 Tugas 6), bukan garis lurus. Hanya
 * PostGIS yang dapat membuktikannya; di suite SQLite layanan ini sengaja tidak menghasilkan
 * angka apa pun (TrailDetailPageTest menjaga sisi itu).
 */
class PosSepanjangJalurTest extends SpatialTestCase
{
    public function test_a_post_is_measured_along_the_route_not_in_a_straight_line(): void
    {
        // Garis berbentuk U: tiga sisi masing-masing sekitar 1,1 km. Pos di ujung kanan atas
        // hanya sekitar 1,1 km garis lurus dari awal, tetapi sekitar 3,3 km sepanjang rute.
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', [[110.0000, -7.0000], [110.0000, -7.0100], [110.0100, -7.0100], [110.0100, -7.0000]]);
        $awal = Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        $awal->setCoordinates(-7.0000, 110.0000);
        $akhir = Checkpoint::factory()->for($trail)->create(['sequence' => 2]);
        $akhir->setCoordinates(-7.0000, 110.0100);

        $km = app(PosSepanjangJalur::class)->km($trail->fresh());

        $this->assertEqualsWithDelta(0.0, $km[$awal->id], 0.05);
        $this->assertEqualsWithDelta(3.3, $km[$akhir->id], 0.15);
    }

    public function test_a_post_without_coordinates_or_a_trail_without_a_line_gets_no_distance(): void
    {
        $bergaris = Trail::factory()->create();
        $bergaris->writeLineString('geometry', [[110.0, -7.0], [110.0, -7.01]]);
        Checkpoint::factory()->for($bergaris)->create(['sequence' => 1]);

        $tanpaGaris = Trail::factory()->create();
        $pos = Checkpoint::factory()->for($tanpaGaris)->create(['sequence' => 1]);
        $pos->setCoordinates(-7.0, 110.0);

        $this->assertSame([], app(PosSepanjangJalur::class)->km($bergaris->fresh()));
        $this->assertSame([], app(PosSepanjangJalur::class)->km($tanpaGaris->fresh()));
    }

    public function test_one_query_serves_every_post(): void
    {
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', [[110.0, -7.0], [110.0, -7.05]]);

        foreach (range(1, 12) as $i) {
            Checkpoint::factory()->for($trail)->create(['sequence' => $i])->setCoordinates(-7.0 - $i * 0.004, 110.0);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();
        $km = app(PosSepanjangJalur::class)->km($trail->fresh());
        $kueri = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(12, $km);
        $this->assertSame(1, $kueri);
    }
}
```

`tests/Feature/AmbientFitTest.php`:

1. Ganti:

```php
                ->where('fit.faktor', fn ($faktor) => collect($faktor)->contains('label', 'Kesesuaian pengalaman')));

        $this->assertStringContainsString('Mengapa demikian', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
```

dengan:

```php
                ->where('kecocokan.mengapaSesuai', fn ($butir) => collect($butir)->every(fn ($b) => is_string($b)))
                ->where('kecocokan.penilaianUrl', route('trails.fit', ['trail' => $trail->slug])));

        // Sejak W3 ketujuh faktornya di halaman penilaian kecocokan; rincian menautkannya.
        $this->assertStringContainsString('Lihat ketujuh faktor penilaian', file_get_contents(resource_path('js/components/trails/rinci/CocokkahUntukSaya.tsx')));
```

2. Ganti:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fit.denganRencana', false));

        $this->assertStringContainsString(
            "fit.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'",
            file_get_contents(resource_path('js/pages/Trails/Show.tsx'))
```

dengan:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page->where('kecocokan.rencana', null));

        $this->assertStringContainsString(
            'kecocokan.rencana ? `Untuk rencana ini: ${kecocokan.rencana}` : \'Kecocokan dasar dengan profil Anda\'',
            file_get_contents(resource_path('js/components/trails/rinci/CocokkahUntukSaya.tsx'))
```

3. Ganti:

```php
     * Item D, sisi lainnya: pengguna yang punya hiking goal melihat halaman detail
     * menilai DENGAN rencananya, dan halaman itu wajib menyebutnya -- kalimat yang sama
     * persis dengan yang dipakai halaman trip ("untuk rencana ini") supaya pembaca yang
     * pernah melihatnya di sana mengenalinya di sini.
```

dengan:

```php
     * Item D, sisi lainnya, diperbarui W3 (spec v3 bagian 6.4): halaman detail menilai
     * "untuk rencana ini" bila pembaca punya trip terencana di jalur INI, dan menyebut
     * rencananya. Tujuan rekomendasi terakhir saja tidak cukup: ia bukan rencana di jalur ini.
```

4. Ganti:

```php
        $this->actingAs($user)
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fit.denganRencana', true));
```

dengan:

```php
        $this->actingAs($user)->get(route('trails.show', $trail))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('kecocokan.rencana', null));

        $trip = TripPlan::factory()->for($user)->create(['trail_id' => $trail->id, 'planned_date' => now()->addDays(9)->toDateString()]);

        $this->actingAs($user)->get(route('trails.show', $trail))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('kecocokan.rencana', $trip->fresh()->ringkasan()));
```

`tests/Feature/AwaitingDataTest.php`:

1. Ganti:

```php
                ->missing('fit')
                ->missing('angkaKunci')
```

dengan:

```php
                ->missing('kecocokan')
                ->missing('angkaNaik')
```

2. Ganti:

```php
                ->where('angkaKunci.0.nilai', '9,4 km')
                ->where('angkaKunci.1.nilai', '10 jam')
                ->where('angkaKunci.2.nilai', '1.100 m'));

        $this->assertStringContainsString('Buat rencana trip', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
```

dengan:

```php
                ->where('angkaNaik.0.nilai', '9,4 km')
                ->where('angkaNaik.1.nilai', '1.100 m')
                ->where('angkaNaik.2.nilai', '10 jam'));

        $this->assertStringContainsString('Rencanakan trip', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
```

`tests/Feature/CheckpointJourneyTest.php`:

1. Ganti:

```php
use App\Models\User;
```

dengan:

```php
use App\Models\User;
use App\Services\Trails\CheckpointJourney;
```

2. Ganti:

```php
 * oleh Services\Trails\CheckpointJourney; halaman React hanya menggambarnya.
```

dengan:

```php
 * oleh Services\Trails\CheckpointJourney; halaman React hanya menggambarnya.
 *
 * Sejak W3 dua bentuk: susun() untuk mode lapangan dan ringkasan pendakian, dan daftar()
 * untuk rincian jalur, yang menyebut naik atau turun antarpos dari ketinggian saja dan
 * jarak dari awal hanya bila diukur sepanjang garis rute.
```

3. Ganti:

```php
        return $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->assertOk()
            ->viewData('page')['props']['pos'];
```

dengan:

```php
        return CheckpointJourney::susun($trail->fresh()->checkpoints, []);
    }

    /**
     * Daftar pos di rincian jalur: naik dan turun dari ketinggian, tanpa jarak garis lurus
     * walau koordinatnya ada, karena jarak itu jauh lebih pendek dari yang ditempuh (R-005).
     */
    public function test_the_trail_page_names_the_climb_without_a_straight_line_distance(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $this->pos($trail, 1, 'Basecamp', 1200, -7.4500, 110.4400);
        $this->pos($trail, 2, 'Pos 1', 1520, -7.4600, 110.4400);
        $this->pos($trail, 3, 'Pos 2', null);

        $pos = collect($this->actingAs(User::factory()->create())
            ->get(route('trails.show', $trail))
            ->viewData('page')['props']['pos'])->keyBy('nama');

        $this->assertSame('naik 320 m', $pos['Pos 1']['antara']);
        $this->assertNull($pos['Pos 2']['antara']);
        $this->assertNull($pos['Pos 1']['km']);
```

4. Ganti:

```php
     * Garis penghubungnya hiasan yang mengulang urutan, jadi disembunyikan dari pembaca
     * layar; padanan teksnya ("Dari pos sebelumnya: ...") tetap dibacakan.
```

dengan:

```php
     * Garis penghubung dan lingkaran nomornya hiasan yang mengulang urutan, jadi disembunyikan
     * dari pembaca layar; padanan teksnya ("Pos 2: nama") tetap dibacakan (DaftarPos, W3).
```

5. Ganti:

```php
        $komponen = file_get_contents(resource_path('js/components/ui/CheckpointJourney.tsx'));

        $this->assertStringContainsString('aria-hidden="true"', $komponen);
        $this->assertStringContainsString('Dari pos sebelumnya:', $komponen);
```

dengan:

```php
        $komponen = file_get_contents(resource_path('js/components/trails/rinci/DaftarPos.tsx'));

        $this->assertStringContainsString('<span aria-hidden="true" className="absolute left-[0.9375rem]', $komponen);
        $this->assertStringContainsString('<span className="sr-only">Pos {p.urutan}: </span>', $komponen);
```

`tests/Feature/CheckpointPaceDisplayTest.php`:

1. Ganti:

```php
 * Waktu tempuh antarpos di halaman jalur.
 *
 * Yang dijaga di sini bukan tata letaknya, melainkan kejujuran angkanya: jumlah rekaman
 * harus ikut terbaca, rentangnya harus disebut, dan tidak ada apa pun yang muncul
 * sebelum rekamannya cukup.
```

dengan:

```php
 * Waktu tempuh di halaman jalur.
 *
 * Yang dijaga di sini bukan tata letaknya, melainkan kejujuran angkanya: jumlah rekaman
 * harus ikut terbaca, rentangnya harus disebut (DK-31), dan tidak ada apa pun yang muncul
 * sebelum rekamannya cukup. Sejak W3 daftar pos menulis waktu dari awal sebagai rentang,
 * dan jumlah rekaman terkecil per ruas di kepala daftar (waktuDariRekaman).
```

2. Ganti:

```php
     * Seluruh teks tempo yang dikirim ke halaman, disambung.
```

dengan:

```php
     * @return array<string, mixed>
     */
    private function props(): array
    {
        return $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->trail))
            ->assertOk()
            ->viewData('page')['props'];
    }

    /**
     * Seluruh teks waktu dari awal yang dikirim ke halaman, disambung.
```

3. Ganti:

```php
        $pos = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->trail))
            ->assertOk()
            ->viewData('page')['props']['pos'];

        return implode(' | ', array_filter(array_column($pos, 'tempo')));
    }

    public function test_the_typical_time_is_shown_with_its_range_and_sample_size(): void
```

dengan:

```php
        return implode(' | ', array_filter(array_column($this->props()['pos'], 'waktu')));
    }

    public function test_the_time_from_the_start_is_a_range_with_its_sample_size(): void
```

4. Ganti:

```php
        $tempo = $this->tempo();

        $this->assertStringContainsString('Biasanya 1 jam', $tempo);
        $this->assertStringContainsString('terentang 40 menit sampai 1 jam 20 menit', $tempo);
        $this->assertStringContainsString('menurut 5 rekaman pendaki', $tempo);
```

dengan:

```php
        $this->assertStringContainsString('40 menit sampai 1 jam 20 menit dari awal', $this->tempo());
        $this->assertSame(5, $this->props()['waktuDariRekaman']);
        $this->assertStringContainsString('rekaman per ruas', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
```

5. Ganti:

```php
        $this->assertStringContainsString('menurut 6 rekaman pendaki', $this->tempo());
```

dengan:

```php
        $this->assertSame(6, $this->props()['waktuDariRekaman']);
```

6. Ganti:

```php
        $this->assertSame('', $this->tempo());
```

dengan:

```php
        $this->assertSame('', $this->tempo());
        $this->assertNull($this->props()['waktuDariRekaman']);
```

7. Ganti:

```php
        $pos = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->trail))
            ->assertOk()
            ->viewData('page')['props']['pos'];

        $this->assertContains('Pos 1', array_column($pos, 'name'));
        $this->assertContains('Pos 2', array_column($pos, 'name'));
        $this->assertSame([], array_filter(array_column($pos, 'tempo')));
```

dengan:

```php
        $pos = $this->props()['pos'];

        $this->assertContains('Pos 1', array_column($pos, 'nama'));
        $this->assertContains('Pos 2', array_column($pos, 'nama'));
        $this->assertSame([], array_filter(array_column($pos, 'waktu')));
```

`tests/Feature/ElevationProfileDisplayTest.php`:

1. Ganti:

```php
 * merata atau menumpuk di satu bagian. Grafiknya digambar klien (ElevationProfile.tsx)
 * dari props yang disaring server; profil yang tidak dapat digambar dikirim sebagai
 * tidak ada, dan halaman menyebutnya belum tersedia alih-alih menggambar grafik kosong.
```

dengan:

```php
 * merata atau menumpuk di satu bagian. Grafiknya digambar klien (ProfilElevasi.tsx, sejak
 * W3) dari props yang disusun server (ProfilElevasiJalur); profil yang tidak dapat digambar
 * dikirim sebagai tidak ada, dan halaman menyebutnya belum tersedia alih-alih menggambar
 * grafik kosong.
```

2. Ganti:

```php
     * @return array<int, array{km: float, m: float}>|null
```

dengan:

```php
     * @return array<string, mixed>|null
```

3. Ganti:

```php
        $this->assertCount(4, $this->profilDiHalaman($this->profil()));

        $this->assertStringContainsString('<ElevationProfile profile={profilElevasi}', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
        $this->assertStringContainsString('<polyline', file_get_contents(resource_path('js/components/ui/ElevationProfile.tsx')));
```

dengan:

```php
        $this->assertCount(4, $this->profilDiHalaman($this->profil())['titik']);

        $this->assertStringContainsString('<ProfilElevasi profil={profilElevasi} />', file_get_contents(resource_path('js/pages/Trails/Show.tsx')));
        $this->assertStringContainsString('<polyline', file_get_contents(resource_path('js/components/trails/ProfilElevasi.tsx')));
```

4. Ganti:

```php
     * Rangkuman angkanya karena itu ada sebagai teks (WCAG 1.1.1).
```

dengan:

```php
     * Padanannya kalimat dari server di figcaption (WCAG 1.1.1), jadi SVG-nya disembunyikan.
```

5. Ganti:

```php
        $this->assertSame(1200.0, min(array_column($profil, 'm')));
        $this->assertSame(2560.0, max(array_column($profil, 'm')));

        $grafik = file_get_contents(resource_path('js/components/ui/ElevationProfile.tsx'));
        $this->assertStringContainsString('role="img"', $grafik);
        $this->assertStringContainsString('<title', $grafik);
```

dengan:

```php
        $this->assertSame([0.0, 1200.0], $profil['titik'][0]);
        $this->assertSame('Dari 1.200 m sampai titik tertinggi 2.560 m dalam 4,2 km. Bagian tercuram, km 3,2 sampai 4,2, naik 403 m dalam 1,0 km (diarsir).', $profil['keterangan']);

        $grafik = file_get_contents(resource_path('js/components/trails/ProfilElevasi.tsx'));
        $this->assertStringContainsString('<p>{profil.keterangan}</p>', $grafik);
        $this->assertStringContainsString('<svg viewBox={`0 0 ${LEBAR} ${TINGGI}`} preserveAspectRatio="none" aria-hidden="true"', $grafik);
```

`tests/Feature/EmptyStateCoverageTest.php`:

Ganti:

```php
            'ringkasan' => 'sebelas baris tetap ringkasan profil, tampil hanya bila profilnya lengkap',
        ],
        'pages/Trails/Show.tsx' => [
            'angkaKunci' => 'empat angka tetap dari TrailDetailPage; nilai yang hilang ditulis belum diketahui',
            'rincian' => 'lima baris tetap dari TrailDetailPage',
```

dengan:

```php
            'ringkasan' => 'sebelas baris tetap ringkasan profil, tampil hanya bila profilnya lengkap',
```

`tests/Feature/HikeSummaryTest.php`:

1. Ganti:

```php
            ->has('profil', 3)
```

dengan:

```php
            ->has('profil.titik', 3)
```

2. Ganti:

```php
        $this->assertStringContainsString('<ElevationProfile', $sumber);
```

dengan:

```php
        $this->assertStringContainsString('<ProfilElevasi profil={profil} />', $sumber);
        $this->assertStringContainsString('<DaftarPos pos={pos} />', $sumber);
```

`tests/Feature/ProgressLadderOnTrailDetailTest.php`:

1. Ganti:

```php
                ->where('fit.tangga', fn ($kalimat) => str_contains((string) $kalimat, 'di atas tanjakan terberat yang pernah Anda tuntaskan')));
```

dengan:

```php
                ->where('kecocokan.tangga', fn ($kalimat) => str_contains((string) $kalimat, 'di atas tanjakan terberat yang pernah Anda tuntaskan')));
```

2. Ganti:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fit.tangga', null));
```

dengan:

```php
            ->assertInertia(fn (AssertableInertia $page) => $page->where('kecocokan.tangga', null));
```

`tests/Feature/TrailDetailMapTest.php`:

1. Ganti:

```php
        $this->assertSame([['lng' => 110.44, 'lat' => -7.45, 'label' => '1. Pos Bayangan']], $peta['penanda']);
```

dengan:

```php
        $this->assertSame([['lng' => 110.44, 'lat' => -7.45, 'label' => '1. Pos Bayangan', 'urutan' => 1, 'aksen' => null]], $peta['penanda']);
```

2. Ganti:

```php
        $this->assertStringContainsString('Garis jalur ini belum dimasukkan', File::get(resource_path('js/pages/Trails/Show.tsx')));
```

dengan:

```php
        $this->assertStringContainsString('<span className="font-bold text-primary">Garis rute belum dimasukkan.</span>', File::get(resource_path('js/pages/Trails/Show.tsx')));
```

3. Ganti:

```php
        $this->assertContains('Pos 1 Watu Gede', array_column($props['pos'], 'name'));
        $this->assertContains('Pos 2 Sabana', array_column($props['pos'], 'name'));
```

dengan:

```php
        $this->assertContains('Pos 1 Watu Gede', array_column($props['pos'], 'nama'));
        $this->assertContains('Pos 2 Sabana', array_column($props['pos'], 'nama'));
```

Ganti seluruh isi `tests/Feature/TrailDetailPageTest.php` dengan:

```php
<?php

namespace Tests\Feature;

use App\Enums\CheckpointType;
use App\Enums\PreparationCategory;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Checkpoint;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\Profile;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\UserExperience;
use App\Services\ConsiderationService;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman rinci jalur (React): kontrak server yang tidak dijaga test lain.
 */
class TrailDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(array $atribut = []): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create(['name' => 'Gunung Uji']))->published()->create($atribut);
    }

    private function pendakiBerprofil(): User
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create(['completed_hikes_count' => 3]);

        return $user->fresh();
    }

    public function test_an_archived_trail_answers_not_found(): void
    {
        $jalur = $this->jalur(['archived_at' => now()]);

        $this->actingAs(User::factory()->create())->get(route('trails.show', $jalur))->assertNotFound();
    }

    public function test_the_page_is_titled_after_the_trail_and_its_mountain(): void
    {
        $jalur = $this->jalur(['name' => 'Jalur Selo']);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trails/Show')
                ->where('title', 'Jalur Selo - Gunung Uji'));
    }

    /**
     * BR-09, R-005: skor, bobot, dan probabilitas tidak pernah sampai ke klien; faktor
     * hanya membawa kategorinya.
     */
    public function test_no_score_or_weight_reaches_the_page(): void
    {
        $isi = $this->actingAs($this->pendakiBerprofil())->get(route('trails.show', $this->jalur()))->assertOk();

        $kecocokan = $isi->viewData('page')['props']['kecocokan'];
        $this->assertSame(['label', 'labelText', 'deskripsi', 'layak', 'rencana', 'alasan', 'mengapaSesuai', 'perluDiperhatikan', 'celahPersiapan', 'tangga', 'penilaianUrl'], array_keys($kecocokan));
        array_walk_recursive($kecocokan, fn ($nilai, $kunci) => $this->assertIsNotFloat($nilai, "Nilai pecahan di kecocokan ({$kunci})."));

        foreach (['internal_score', 'internalScore', '"score"', '"weight"', 'probab'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $isi->getContent());
        }
    }

    /**
     * Setiap status dan kondisi menyebut sumber dan waktunya (§92, §93). Status kosong
     * bukan OPEN, dan OPEN bukan jaminan keselamatan (R-012).
     */
    public function test_each_condition_names_its_source_and_time(): void
    {
        $props = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->viewData('page')['props'];

        $this->assertNotSame('', $props['statusResmi']['source']);
        $this->assertArrayHasKey('diverifikasi', $props['statusResmi']);
        $this->assertNotNull($props['cuaca']['source']);

        foreach ($props['sumber'] as $baris) {
            $this->assertSame(['bagian', 'jenis', 'isi', 'url'], array_keys($baris));
        }

        // Sejak W3 label sumbernya mengikuti jenis sumber catatan (R-009), bukan "Resmi"
        // untuk setiap catatan di tabel status resmi.
        $this->assertArrayHasKey('jenisSumber', $props['statusResmi']);

        $kondisi = File::get(resource_path('js/components/trails/rinci/Kondisi.tsx'));
        $this->assertStringContainsString('Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.', $kondisi);
        $this->assertStringContainsString('<LabelSumber jenis={status.jenisSumber} />', $kondisi);
    }

    /**
     * R-013: kegagalan sumber cuaca tidak pernah menghasilkan cuaca karangan.
     */
    public function test_missing_weather_is_reported_not_invented(): void
    {
        $cuaca = $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->viewData('page')['props']['cuaca'];

        $this->assertFalse($cuaca['available']);
        $this->assertSame('Data cuaca tidak tersedia.', $cuaca['message']);
        $this->assertArrayNotHasKey('forecast', $cuaca);
    }

    /**
     * Tanpa profil tidak ada penilaian diam-diam; yang ditawarkan jalan melengkapinya.
     */
    public function test_a_reader_without_a_profile_is_invited_not_judged(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trails.show', $this->jalur()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('kecocokan', null)
                ->where('auth.user.hasCompletedProfile', false));

        $this->assertStringContainsString('Isi profil sekarang', File::get(resource_path('js/components/trails/rinci/CocokkahUntukSaya.tsx')));
        $this->assertStringContainsString('<CocokkahUntukSaya kecocokan={kecocokan}', File::get(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Simpan dan Banding dari halaman rinci: keadaannya dari simpanan, dan Banding hanya
     * ditawarkan ketika ada simpanan lain yang terbit untuk dibandingkan (DK-05, DK-40).
     */
    public function test_the_saved_state_and_the_compare_offer_follow_the_shortlist(): void
    {
        $user = User::factory()->create();
        $jalur = $this->jalur();

        $this->actingAs($user)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('ditimbang', false)->where('adaCatatan', false)->where('bandingUrl', null));

        $this->actingAs($user)->from(route('trails.show', $jalur))->post(route('trails.saved.store', $jalur->id))
            ->assertRedirect(route('trails.show', $jalur))
            ->assertSessionHas('pengumuman');

        $this->actingAs($user)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('ditimbang', true)->where('bandingUrl', null));

        $lain = $this->jalur();
        app(ConsiderationService::class)->toggle($user, $lain);

        $this->actingAs($user)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('bandingUrl', route('trails.compare', ['trails' => $jalur->id.','.$lain->id])));

        $this->assertStringContainsString('{bandingUrl ? (', File::get(resource_path('js/pages/Trails/Show.tsx')));
    }

    /**
     * Halaman React merender markupnya di klien; struktur judulnya dijaga dari sumber:
     * tepat satu h1 per halaman (lewat PageHeader), dan isian salin-manual berlabel.
     */
    public function test_each_trail_page_has_exactly_one_first_level_heading(): void
    {
        foreach (['Show', 'Awaiting', 'Compare', 'Saved'] as $halaman) {
            $isi = File::get(resource_path("js/pages/Trails/{$halaman}.tsx"));

            $this->assertSame(1, substr_count($isi, '<PageHeader') + substr_count($isi, '<h1'), "{$halaman} harus punya tepat satu h1.");
        }

        $salin = File::get(resource_path('js/components/ui/TombolSalin.tsx'));
        $this->assertStringContainsString('<Label htmlFor={id}>', $salin);
        $this->assertStringContainsString('<TextInput id={id}', $salin);
        $this->assertStringContainsString('role="status"', $salin);
    }

    /**
     * Angka sekali jalan naik: jarak, tanjakan, dan lama naik. Turunan tidak ditulis karena
     * data pemilik menyimpan turunan pulang (ruling W3). Angka kosong ditulis apa adanya.
     */
    public function test_missing_figures_are_stated_honestly(): void
    {
        $jalur = $this->jalur(['elevation_loss_m' => 800]);
        $jalur->forceFill(['estimated_duration_minutes' => null])->saveQuietly();

        $angka = collect($this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->viewData('page')['props']['angkaNaik'])->keyBy('label');

        $this->assertSame(['Jarak', 'Tanjakan', 'Naik'], $angka->keys()->all());
        $this->assertSame(['label' => 'Naik', 'nilai' => 'Belum tersedia', 'ada' => false], $angka['Naik']);
    }

    /**
     * Spec v3 bagian 6.4: "untuk rencana ini" bila pembaca punya trip terencana yang belum
     * lewat di jalur ini; tanpa itu kecocokan dasar. Trip lewat atau di jalur lain tidak dipakai.
     */
    public function test_the_fit_is_for_the_readers_plan_when_one_exists(): void
    {
        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur();

        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => now()->subDays(3)->toDateString(), 'status' => TripStatus::PLANNED->value]);
        TripPlan::factory()->for($user)->for($this->jalur())->create(['planned_date' => now()->addDays(5)->toDateString()]);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kecocokan.rencana', null)
            ->where('kecocokan.penilaianUrl', route('trails.fit', ['trail' => $jalur->slug])));

        $trip = TripPlan::factory()->for($user)->for($jalur)->create([
            'planned_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(21)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
        ]);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('kecocokan.rencana', $trip->fresh()->ringkasan())
            ->where('kecocokan.penilaianUrl', route('trails.fit', ['trail' => $jalur->slug, 'trip' => $trip->id])));
    }

    /**
     * R-006: tuntutan fisik yang tidak diketahui tidak diberi ruas terendah; katanya menyebut
     * belum tersedia. Keadaan lingkungan tidak dikarang karena belum ada datanya (R-007).
     */
    public function test_the_challenge_rows_never_turn_unknown_into_low(): void
    {
        $jalur = $this->jalur();
        $jalur->forceFill(['distance_km' => null, 'elevation_gain_m' => null, 'estimated_duration_minutes' => null])->saveQuietly();

        $tantangan = collect($this->actingAs(User::factory()->create())
            ->get(route('trails.show', $jalur))
            ->viewData('page')['props']['tantangan'])->keyBy('judul');

        $this->assertSame(['Tuntutan fisik', 'Tuntutan teknis', 'Navigasi', 'Medan', 'Sumber air', 'Area kemah'], $tantangan->keys()->all());
        $this->assertSame(['judul' => 'Tuntutan fisik', 'kata' => 'Belum tersedia', 'ruas' => null], $tantangan['Tuntutan fisik']);
        $this->assertSame(2, $tantangan['Tuntutan teknis']['ruas']);
    }

    /**
     * Profil elevasi membawa keterangan kalimat dan bagian tercuram yang diarsir (spec v3
     * bagian 6.4). Jalur yang tidak naik tidak diberi bagian tercuram; kurang dari dua
     * titik bukan profil.
     */
    public function test_the_profile_names_its_steepest_part(): void
    {
        $gede = $this->jalur(['elevation_profile' => [
            ['km' => 0, 'm' => 1340], ['km' => 1.5, 'm' => 1575], ['km' => 2.8, 'm' => 1620],
            ['km' => 5.3, 'm' => 2150], ['km' => 7.2, 'm' => 2395], ['km' => 9.7, 'm' => 2958],
        ]]);
        $datar = $this->jalur(['elevation_profile' => [['km' => 0, 'm' => 900], ['km' => 4, 'm' => 900]]]);
        $satuTitik = $this->jalur(['elevation_profile' => [['km' => 0, 'm' => 900]]]);
        $pembaca = User::factory()->create();

        $profil = $this->actingAs($pembaca)->get(route('trails.show', $gede))->viewData('page')['props']['profilElevasi'];

        $this->assertSame(['dariKm' => 7.3, 'sampaiKm' => 9.7, 'naik' => 546], $profil['tercuram']);
        $this->assertSame('Dari 1.340 m sampai titik tertinggi 2.958 m dalam 9,7 km. Bagian tercuram, km 7,3 sampai 9,7, naik 546 m dalam 2,4 km (diarsir).', $profil['keterangan']);
        $this->assertSame([], $profil['pos'], 'Tanpa garis rute tidak ada jarak pos, jadi tidak ada penanda tebakan.');

        $this->assertNull($this->actingAs($pembaca)->get(route('trails.show', $datar))->viewData('page')['props']['profilElevasi']['tercuram']);
        $this->assertNull($this->actingAs($pembaca)->get(route('trails.show', $satuTitik))->viewData('page')['props']['profilElevasi']);
    }

    /**
     * Pos berkemah dan tujuan membawa aksen (DESIGN.md, satu aksen). Tanpa garis rute dan
     * rekaman pendaki, jarak dan waktu dari awal tidak ditulis, bukan dikarang.
     */
    public function test_posts_carry_the_accent_for_camp_and_destination(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();

        foreach ([['Basecamp', CheckpointType::BASECAMP], ['Pos 1', CheckpointType::POS], ['Kandang', CheckpointType::CAMPGROUND], ['Puncak', CheckpointType::SUMMIT]] as $i => [$nama, $jenis]) {
            Checkpoint::factory()->for($jalur)->create(['sequence' => $i + 1, 'name' => $nama, 'checkpoint_type' => $jenis->value, 'elevation_m' => 1000 + $i * 300]);
        }

        $jalur->refresh();
        $jalur->officialStatuses()->create(['scope' => 'TRAIL', 'status' => 'OPEN', 'source' => 'Uji']);
        $jalur->forceFill(['is_published' => true])->saveQuietly();

        $pos = $this->actingAs(User::factory()->create())->get(route('trails.show', $jalur))->viewData('page')['props']['pos'];

        $this->assertSame([null, null, 'kemah', 'tujuan'], array_column($pos, 'aksen'));
        $this->assertSame(['1.000 m', '1.300 m', '1.600 m', '1.900 m'], array_column($pos, 'elevasi'));
        $this->assertSame([null, null, null, null], array_column($pos, 'km'));
        $this->assertSame([null, null, null, null], array_column($pos, 'waktu'));
    }

    /**
     * R-013: prakiraan untuk tanggal trip yang belum terbit tidak diganti prakiraan hari ini;
     * halaman menyebut kapan ia mulai terbit.
     */
    public function test_the_forecast_note_waits_for_the_trip_date(): void
    {
        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur();
        $tanggal = now('Asia/Jakarta')->addDays(15);
        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => $tanggal->toDateString()]);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cuaca.untukTrip', 'Prakiraan untuk '.$tanggal->translatedFormat('j M').' belum terbit. BMKG menerbitkan 3 hari ke depan; prakiraan tanggal Anda muncul di sini mulai '.$tanggal->copy()->subDays(3)->translatedFormat('j M').'.'));

        $dekat = $this->jalur();
        TripPlan::factory()->for($user)->for($dekat)->create(['planned_date' => now('Asia/Jakarta')->addDays(2)->toDateString()]);

        $this->actingAs($user)->get(route('trails.show', $dekat))->assertInertia(fn (AssertableInertia $page) => $page->where('cuaca.untukTrip', null));
    }

    /**
     * "Yang perlu disiapkan": izin bila ada aturannya, lalu butir templat khusus jalur ini
     * yang berlaku untuknya; templat bawaan tidak ikut karena bukan pembeda jalur ini.
     */
    public function test_preparation_lists_the_permit_and_this_trails_own_items(): void
    {
        $jalur = $this->jalur(['camping_available' => true]);
        PermitRequirement::factory()->create(['trail_id' => $jalur->id, 'authority' => 'Balai Uji']);
        $khusus = PreparationTemplate::create(['trail_id' => $jalur->id, 'name' => 'Khusus', 'is_default' => false]);
        $bawaan = PreparationTemplate::create(['name' => 'Bawaan', 'is_default' => true]);
        PreparationItem::create(['preparation_template_id' => $khusus->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Headlamp cadangan', 'is_critical' => true]);
        PreparationItem::create(['preparation_template_id' => $khusus->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Tenda', 'is_critical' => false, 'applies_when' => ['camping_available' => false]]);
        PreparationItem::create(['preparation_template_id' => $bawaan->id, 'category' => PreparationCategory::EQUIPMENT->value, 'label' => 'Air minum', 'is_critical' => true]);

        $persiapan = $this->actingAs(User::factory()->create())->get(route('trails.show', $jalur))->viewData('page')['props']['persiapan'];

        $this->assertSame([PreparationService::PERMIT_LABEL, 'Headlamp cadangan'], array_column($persiapan, 'judul'));
        $this->assertSame([true, true], array_column($persiapan, 'wajib'));
    }

    /**
     * Anggaran kueri (keputusan koordinator W3 nomor 12): pembaca berprofil dengan trip di
     * jalur ini; jumlah kueri tidak tumbuh bersama jumlah pos dan simpanannya.
     */
    public function test_the_page_does_not_grow_with_posts_or_saved_trails_for_a_rated_reader(): void
    {
        $user = $this->pendakiBerprofil();
        $jalur = $this->jalur();
        TripPlan::factory()->for($user)->for($jalur)->create(['planned_date' => now()->addDays(10)->toDateString()]);
        app(ConsiderationService::class)->toggle($user, $jalur);

        $this->actingAs($user)->get(route('trails.show', $jalur))->assertOk();
        $sedikit = $this->hitungKueri(route('trails.show', $jalur));

        foreach (range(2, 12) as $i) {
            Checkpoint::factory()->for($jalur)->create(['sequence' => $i, 'checkpoint_type' => CheckpointType::POS->value]);
            app(ConsiderationService::class)->toggle($user, $this->jalur());
        }

        $banyak = $this->hitungKueri(route('trails.show', $jalur));

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} kueri lalu {$banyak}.");
    }

    private function hitungKueri(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    /**
     * Sumber di halaman ini mengikuti urutan kepercayaan (R-009): status akses lebih dulu,
     * label kecocokan terakhir sebagai interpretasi sistem.
     */
    public function test_the_sources_follow_the_authority_order(): void
    {
        $jalur = $this->jalur();

        $sumber = $this->actingAs($this->pendakiBerprofil())->get(route('trails.show', $jalur))->viewData('page')['props']['sumber'];

        $this->assertSame(['Status akses', 'Cuaca', 'Angka, pos, dan garis rute', 'Label kecocokan'], array_column($sumber, 'bagian'));
        $this->assertSame('AI_INTERPRETATION', end($sumber)['jenis']);
    }

    /**
     * Bar aksi ponsel tetap satu baris (tinjauan akhir W2, W2-F5; prototype 04). Di 360 x 740,
     * bar yang membungkus menjadi 163 px menyisakan isi 120 px ketika pesan jaringan, toast,
     * strip, dan bar tampil bersamaan. Simpan menjadi tombol ikon 44 px yang bernama dan
     * membawa aria-pressed, Banding dan Rencanakan trip berukuran kecil, dan Rencanakan trip
     * mengisi sisa baris; labelnya membungkus di dalam tombolnya sendiri, bukan memindah
     * tombol ke baris baru. Kolom kanan layar lebar yang lengket juga menyisakan lapisan bawah.
     */
    public function test_the_phone_action_bar_stays_one_row(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Show.tsx'));
        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        preg_match('/<BarAksi label="Aksi jalur">([\s\S]*?)<\/BarAksi>/', $halaman, $bar);

        $this->assertNotEmpty($bar, 'Bar aksi rincian jalur tidak ditemukan.');
        $this->assertStringContainsString('<div className="flex w-full items-center gap-2">', $bar[1]);
        $this->assertStringContainsString('bentuk="bar"', $bar[1]);
        $this->assertStringContainsString('<Button size="sm" href={rencanaUrl} className="min-w-0 flex-1">', $bar[1]);
        $this->assertDoesNotMatchRegularExpression('/grid-cols|col-span|flex-wrap/', $bar[1], 'Bar aksi ponsel tidak boleh membungkus menjadi dua baris.');

        $this->assertMatchesRegularExpression('/bentuk === \'bar\' \? \(\s*<TombolIkon[\s\S]*?label=\{`Simpan \$\{jalur\.name\}`\}\s*aria-pressed=\{jalur\.ditimbang\}/', $tombol);

        $this->assertStringContainsString(
            'max-h-[calc(100dvh-var(--kepala-atas)-var(--pesan-jaringan,0px)-var(--nav-bawah,0px)-3rem)]',
            $halaman,
            'Kolom kanan yang lengket harus menyisakan lapisan bawah.'
        );
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrailDetailPage|CheckpointJourney|ElevationProfileDisplay|HikeSummary|TrailDetailMap|AwaitingData|AmbientFit|CheckpointPaceDisplay|ProgressLadderOnTrailDetail|EmptyStateCoverage'`
Expected: FAIL: 76 test, 43 lulus, 19 gagal, 13 error, 1 dilewati. Antara lain `AmbientFitTest::test_the_trail_detail_page_shows_the_explanation_layer`, `AmbientFitTest::test_the_trail_detail_page_states_it_is_showing_the_ambient_fit_without_a_goal`, `AmbientFitTest::test_the_trail_detail_page_states_it_is_showing_the_plan_aware_fit_with_a_goal`.

- [ ] **Step 3: Tulis implementasinya.**

**Data, rute, dan konfigurasi**

`config/hiking.php`:

Ganti:

```php
        'weather_aging_hours' => 24,
```

dengan:

```php
        'weather_aging_hours' => 24,

        // Jangkauan prakiraan BMKG: tanggal trip yang lebih jauh belum punya prakiraan, dan
        // halaman menyebut kapan ia mulai terbit (spec v3 bagian 6.1 dan 6.4).
        'weather_forecast_days' => 3,
```

**Server**

Buat `app/Services/Trails/PosSepanjangJalur.php`:

```php
<?php

namespace App\Services\Trails;

use App\Models\Trail;
use Illuminate\Support\Facades\DB;

/**
 * Jarak setiap pos dari awal jalur, diukur SEPANJANG garis rute, untuk daftar pos dan penanda
 * di profil elevasi (spec v3 bagian 6.4). Satu kueri untuk satu jalur.
 *
 * Posisi pos diproyeksikan ke garis rutenya (ST_LineLocatePoint), lalu pecahannya dikali
 * panjang geodesik garis itu. Jarak garis lurus antarpos tidak dipakai: di jalur berkelok ia
 * jauh lebih pendek dari yang ditempuh, dan angka yang tampak pasti tetapi keliru lebih buruk
 * daripada tanpa angka (R-005, R-007). Pos tanpa koordinat, jalur tanpa geometri, dan basis
 * data tanpa PostGIS (suite SQLite) tidak menghasilkan angka apa pun.
 */
final class PosSepanjangJalur
{
    /**
     * @return array<int, float> km dari awal, berkunci id pos, satu desimal
     */
    public function km(Trail $trail): array
    {
        if (! Trail::spatialSupported()) {
            return [];
        }

        $baris = DB::select(
            'SELECT c.id,
                    ST_LineLocatePoint(t.geometry::geometry, c.location::geometry) * ST_Length(t.geometry) / 1000 AS km
             FROM checkpoints c
             JOIN trails t ON t.id = c.trail_id
             WHERE c.trail_id = ? AND c.location IS NOT NULL AND t.geometry IS NOT NULL',
            [$trail->id]
        );

        $hasil = [];

        foreach ($baris as $b) {
            if ($b->km !== null) {
                $hasil[(int) $b->id] = round((float) $b->km, 1);
            }
        }

        return $hasil;
    }
}
```

Buat `app/Services/Trails/ProfilElevasiJalur.php`:

```php
<?php

namespace App\Services\Trails;

use App\Models\Checkpoint;
use Illuminate\Support\Collection;

/**
 * Profil elevasi satu jalur untuk rinciannya (spec v3 bagian 6.4, prototype 04): titik yang
 * dijarangkan, bagian tercuram yang diarsir, penanda pos yang jaraknya diketahui, dan satu
 * kalimat keterangan. Kalimatnya dibentuk di server supaya grafik punya padanan teks yang
 * sama untuk semua pembaca (WCAG 1.1.1), bukan hanya "tertinggi" dan "terendah".
 *
 * Tanpa dua titik sah tidak ada profil (R-007). Penanda pos hanya untuk pos yang jaraknya
 * dari awal diukur sepanjang garis rute; pos lain tidak ditaruh di tempat tebakan.
 */
final class ProfilElevasiJalur
{
    /** Profil diambil ulang di 20 jarak yang sama untuk mencari bagian tercuram. */
    private const SAMPEL = 20;

    /** Lebar bagian tercuram: seperempat panjang jalur (5 dari 20 sampel). */
    private const JENDELA = 5;

    /**
     * @param  array<int, mixed>|null  $profil
     * @param  Collection<int, Checkpoint>  $checkpoints
     * @param  array<int, float>  $posKm  km dari awal per id pos (PosSepanjangJalur)
     * @return array{titik: array<int, array{0: float, 1: float}>, tercuram: array{dariKm: float, sampaiKm: float, naik: int}|null, pos: array<int, array{urutan: int, nama: string, km: float, aksen: string|null}>, keterangan: string}|null
     */
    public static function untuk(?array $profil, Collection $checkpoints, array $posKm): ?array
    {
        $titik = SkalaProfil::titik($profil);

        if ($titik === null) {
            return null;
        }

        $tercuram = self::tercuram($titik);
        $urut = $checkpoints->sortBy('sequence')->values();
        $terakhir = $urut->last();

        return [
            'titik' => SkalaProfil::jarangkan(array_map(fn (array $t) => [round($t[0], 2), round($t[1])], $titik)),
            'tercuram' => $tercuram,
            'pos' => $urut
                ->filter(fn (Checkpoint $p) => array_key_exists($p->id, $posKm))
                ->map(fn (Checkpoint $p) => ['urutan' => $p->sequence, 'nama' => $p->name, 'km' => $posKm[$p->id], 'aksen' => CheckpointJourney::aksen($p, $terakhir)])
                ->values()
                ->all(),
            'keterangan' => self::keterangan($titik, $tercuram),
        ];
    }

    /**
     * Bagian tercuram: jendela seperempat panjang jalur dengan kenaikan terbesar, dari profil
     * yang diambil ulang di jarak yang sama. Jendela, bukan satu ruas rekaman, karena satu ruas
     * GPS yang pendek dapat tampak curam tanpa arti bagi pendaki. Tanpa kenaikan, tidak ada.
     *
     * @param  array<int, array{0: float, 1: float}>  $titik
     * @return array{dariKm: float, sampaiKm: float, naik: int}|null
     */
    public static function tercuram(array $titik): ?array
    {
        $awal = $titik[0][0];
        $panjang = end($titik)[0] - $awal;
        $sampel = [];

        for ($i = 0; $i <= self::SAMPEL; $i++) {
            $km = $awal + $panjang * $i / self::SAMPEL;
            $sampel[] = [$km, self::tinggiPada($titik, $km)];
        }

        $terbaik = null;

        for ($i = 0; $i + self::JENDELA <= self::SAMPEL; $i++) {
            $naik = $sampel[$i + self::JENDELA][1] - $sampel[$i][1];

            if ($terbaik === null || $naik > $terbaik[1]) {
                $terbaik = [$i, $naik];
            }
        }

        if ($terbaik === null || $terbaik[1] < 1) {
            return null;
        }

        return [
            'dariKm' => round($sampel[$terbaik[0]][0], 1),
            'sampaiKm' => round($sampel[$terbaik[0] + self::JENDELA][0], 1),
            'naik' => (int) round($terbaik[1]),
        ];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $titik
     */
    private static function tinggiPada(array $titik, float $km): float
    {
        foreach ($titik as $i => [$x, $y]) {
            if ($x >= $km) {
                if ($i === 0) {
                    return $y;
                }

                [$x0, $y0] = $titik[$i - 1];

                return $x === $x0 ? $y : $y0 + ($y - $y0) * ($km - $x0) / ($x - $x0);
            }
        }

        return end($titik)[1];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $titik
     * @param  array{dariKm: float, sampaiKm: float, naik: int}|null  $tercuram
     */
    private static function keterangan(array $titik, ?array $tercuram): string
    {
        $angka = fn (float $n, int $desimal = 0) => number_format($n, $desimal, ',', '.');
        $panjang = end($titik)[0] - $titik[0][0];
        $tertinggi = max(array_column($titik, 1));
        $kalimat = 'Dari '.$angka($titik[0][1]).' m sampai titik tertinggi '.$angka($tertinggi).' m dalam '.$angka($panjang, 1).' km.';

        if ($tercuram !== null) {
            $kalimat .= ' Bagian tercuram, km '.$angka($tercuram['dariKm'], 1).' sampai '.$angka($tercuram['sampaiKm'], 1)
                .', naik '.$angka($tercuram['naik']).' m dalam '.$angka($tercuram['sampaiKm'] - $tercuram['dariKm'], 1).' km (diarsir).';
        }

        return $kalimat;
    }
}
```

`app/Http/Resources/TrailHeaderResource.php`:

1. Ganti:

```php
     * @return array{id: int, name: string, description: ?string, mountain: string, mountainId: int, province: ?string, mdpl: ?string}
```

dengan:

```php
     * @return array{id: int, name: string, description: ?string, mountain: string, mountainId: int, mountainUrl: string, province: ?string, mdpl: ?string}
```

2. Ganti:

```php
            'mountainId' => $this->mountain->id,
```

dengan:

```php
            'mountainId' => $this->mountain->id,
            // Remah ke halaman gunung (spec v3 bagian 6.4, DK-44).
            'mountainUrl' => route('mountains.show', $this->mountain),
```

`app/Models/TripPlan.php`:

Ganti:

```php
    public function preparationItems(): HasMany
```

dengan:

```php
    /**
     * Tujuan yang dipakai menilai kecocokan "untuk rencana ini" (spec v3 bagian 6.4 dan 6.5):
     * tujuan tersimpan trip ini, atau untuk trip lama yang dibuat tanpa tujuan, jenis dan
     * tanggal tripnya sebagai tujuan sementara yang tidak pernah disimpan.
     */
    public function tujuanPenilaian(): HikingGoal
    {
        return $this->hikingGoal ?? new HikingGoal([
            'trip_type' => $this->trip_type,
            'target_date' => $this->planned_date,
        ]);
    }

    /**
     * Satu baris rencana untuk kalimat "untuk rencana ini": jenis trip, tanggal (rentang
     * bila lebih dari sehari), dan jumlah peserta bila diisi.
     */
    public function ringkasan(): string
    {
        $mulai = $this->planned_date;
        $selesai = $this->end_date;
        $tanggal = $selesai !== null && ! $selesai->isSameDay($mulai)
            ? $mulai->translatedFormat('j').' sampai '.$selesai->translatedFormat('j M Y')
            : $mulai->translatedFormat('j M Y');

        return implode(', ', array_filter([
            $this->trip_type->label(),
            $tanggal,
            $this->participants_count ? $this->participants_count.' orang' : null,
        ]));
    }

    public function preparationItems(): HasMany
```

`app/Services/Community/HasilPendakian.php`:

1. Ganti:

```php
use App\Services\Trails\CheckpointJourney;
```

dengan:

```php
use App\Services\Trails\CheckpointJourney;
use App\Services\Trails\ProfilElevasiJalur;
```

2. Ganti:

```php
            'profil' => $jalur->elevation_profile ?: null,
            'pos' => CheckpointJourney::susun($jalur->checkpoints, []),
```

dengan:

```php
            // Bentuk yang sama dengan rincian jalur (W3): ProfilElevasi dan DaftarPos. Tanpa
            // jarak pos sepanjang garis rute dan tanpa waktu dari awal di sini: yang dicatat
            // adalah pendakian ini, bukan perkiraan.
            'profil' => ProfilElevasiJalur::untuk($jalur->elevation_profile, $jalur->checkpoints, []),
            'pos' => CheckpointJourney::daftar($jalur->checkpoints, [], []),
```

`app/Services/PreparationService.php`:

1. Ganti:

```php
use App\Models\PreparationTemplate;
```

dengan:

```php
use App\Models\PreparationTemplate;
use App\Models\Trail;
```

2. Ganti:

```php
    private const PERMIT_LABEL = 'Booking izin pendakian (SIMAKSI)';
```

dengan:

```php
    /** Publik: rincian jalur menyebut butir izin dengan nama yang sama dengan daftar persiapan trip. */
    public const PERMIT_LABEL = 'Booking izin pendakian (SIMAKSI)';
```

3. Ganti:

```php
    }

    /**
     * @return Collection<int, PreparationItem>
```

dengan:

```php
    }

    /**
     * Butir khusus satu jalur untuk bagian "Yang perlu disiapkan" di rinciannya (spec v3
     * bagian 6.4): hanya butir templat jalur itu yang berlaku untuknya, bukan templat bawaan,
     * karena bagian itu menjawab apa yang berbeda di jalur ini. Butir yang sama masuk ke
     * daftar persiapan trip lewat generateFor().
     *
     * @return Collection<int, PreparationItem>
     */
    public function khususJalur(Trail $trail): Collection
    {
        $template = PreparationTemplate::query()
            ->where('trail_id', $trail->id)
            ->with('items')
            ->first();

        return ($template?->items ?? collect())
            ->filter(fn (PreparationItem $item) => $item->appliesToTrail($trail))
            ->values();
    }

    /**
     * @return Collection<int, PreparationItem>
```

`app/Services/TrailFitService.php`:

Ganti:

```php
     * kalimat cukup, dan §89 memang hanya menyediakan satu baris di tingkat daftar.
     */
    private function alasan(RouteFitResult $hasil): string
```

dengan:

```php
     * kalimat cukup, dan §89 memang hanya menyediakan satu baris di tingkat daftar. Publik
     * karena rincian jalur memakai kalimat yang sama untuk penilaiannya (W3).
     */
    public function alasan(RouteFitResult $hasil): string
```

Ganti seluruh isi `app/Services/Trails/CheckpointJourney.php` dengan:

```php
<?php

namespace App\Services\Trails;

use App\Enums\CheckpointType;
use App\Models\Checkpoint;
use App\Support\Durasi;
use App\Support\GpxTrack;
use Illuminate\Support\Collection;

/**
 * Pos sebagai perjalanan, bukan daftar: apa yang terjadi di ANTARA dua pos, karena di
 * situlah pertanyaan pendaki berada (bagian mana yang curam, seberapa jauh sampai pos
 * berikutnya). Dipindahkan dari komponen Blade checkpoint-journey supaya halaman React
 * menerima bentuk yang sudah jadi; klien hanya menggambar.
 *
 * Jarak memakai haversine lewat GpxTrack::lengthKm yang sudah dipakai mode pendakian,
 * supaya tidak ada rumus kedua yang bisa berbeda jawabannya. Null bukan nol: "naik 0 m"
 * adalah pernyataan bahwa jalurnya datar, dan itu bukan yang diketahui sistem ketika
 * ketinggiannya belum dicatat (PRD §91).
 */
class CheckpointJourney
{
    /**
     * @param  Collection<int, Checkpoint>  $checkpoints
     * @param  array<int, array{dari: int, ke: int, rekaman: int, median_menit: int, min_menit: int, maks_menit: int}>  $paces
     * @return array<int, array{id: int, sequence: int, name: string, typeLabel: string, elevasi: ?string, notes: ?string, antara: ?string, tempo: ?string}>
     */
    public static function susun(Collection $checkpoints, array $paces): array
    {
        $pos = $checkpoints->sortBy('sequence')->values();
        $tempo = collect($paces)->keyBy(fn (array $t) => $t['dari'].'-'.$t['ke']);

        return $pos->map(function (Checkpoint $kini, int $i) use ($pos, $tempo) {
            $sebelum = $i === 0 ? null : $pos[$i - 1];
            $antara = $sebelum === null ? null : self::antara($sebelum, $kini);
            $t = $sebelum === null ? null : $tempo->get($sebelum->id.'-'.$kini->id);

            return [
                'id' => $kini->id,
                'sequence' => $kini->sequence,
                'name' => $kini->name,
                'typeLabel' => $kini->checkpoint_type->label(),
                'elevasi' => $kini->elevation_m ? number_format($kini->elevation_m, 0, ',', '.').' mdpl' : null,
                'notes' => $kini->notes,
                'antara' => $antara,
                // Median beserta rentang teramati dan jumlah rekamannya, supaya pembaca
                // dapat menimbang sendiri seberapa jauh angka itu dipercaya.
                'tempo' => ($antara !== null && $t !== null)
                    ? 'Biasanya '.Durasi::panjang($t['median_menit']).', terentang '.Durasi::panjang($t['min_menit'])
                        .' sampai '.Durasi::panjang($t['maks_menit']).' menurut '.$t['rekaman'].' rekaman pendaki'
                    : null,
            ];
        })->all();
    }

    /**
     * Daftar pos untuk rincian jalur dan ringkasan pendakian (spec v3 bagian 6.4): nomor,
     * nama, jenis, ketinggian, naik atau turun dari pos sebelumnya, jarak dari awal SEPANJANG
     * garis rute (PosSepanjangJalur), waktu dari awal sebagai perkiraan dari rekaman pendaki,
     * dan catatan pos.
     *
     * Waktu dari awal adalah RENTANG bersumber (DK-31): jumlah waktu tercepat dan terlama yang
     * teramati di setiap ruas sebelumnya, dari rekaman pendaki (CheckpointPaceService, yang
     * sudah menahan ruas dengan rekaman terlalu sedikit). Bila satu ruas saja belum punya
     * rekaman, waktu pos itu dan sesudahnya tidak ditulis: jumlah yang bolong terbaca lebih
     * cepat dari kenyataan (R-007). Jarak dari awal hanya ada bila garis rute dan koordinat pos
     * ada; jarak garis lurus tidak dipakai (R-005).
     *
     * @param  Collection<int, Checkpoint>  $checkpoints
     * @param  array<int, array{dari: int, ke: int, rekaman: int, median_menit: int, min_menit: int, maks_menit: int}>  $paces
     * @param  array<int, float>  $km  km dari awal, berkunci id pos
     * @return array<int, array{id: int, urutan: int, nama: string, jenis: string, aksen: string|null, elevasi: string|null, antara: string|null, km: string|null, waktu: string|null, catatan: string|null}>
     */
    public static function daftar(Collection $checkpoints, array $paces, array $km): array
    {
        $pos = $checkpoints->sortBy('sequence')->values();
        $tempo = collect($paces)->keyBy(fn (array $t) => $t['dari'].'-'.$t['ke']);
        $terakhir = $pos->last();
        $tercepat = 0;
        $terlama = 0;
        $utuh = true;
        $hasil = [];

        foreach ($pos as $i => $kini) {
            if ($i > 0) {
                $t = $tempo->get($pos[$i - 1]->id.'-'.$kini->id);
                $utuh = $utuh && $t !== null;
                $tercepat += $t['min_menit'] ?? 0;
                $terlama += $t['maks_menit'] ?? 0;
            }

            $hasil[] = [
                'id' => $kini->id,
                'urutan' => $kini->sequence,
                'nama' => $kini->name,
                'jenis' => $kini->checkpoint_type->label(),
                'aksen' => self::aksen($kini, $terakhir),
                'elevasi' => $kini->elevation_m !== null ? number_format($kini->elevation_m, 0, ',', '.').' m' : null,
                // Hanya dari ketinggian: jarak garis lurus antarpos tidak dipakai di sini.
                'antara' => $i === 0 ? null : self::beda($pos[$i - 1], $kini),
                'km' => array_key_exists($kini->id, $km) ? number_format($km[$kini->id], 1, ',', '.').' km' : null,
                'waktu' => $i > 0 && $utuh ? self::rentang($tercepat, $terlama).' dari awal' : null,
                'catatan' => $kini->notes,
            ];
        }

        return $hasil;
    }

    /**
     * Pos yang membawa aksen kuning (DESIGN.md "Satu aksen", ruling W1-12b): area kemah, dan
     * tujuan, yaitu puncak atau pos terakhir. Aksen tidak pernah sendirian: pemanggil selalu
     * menggambarnya bersama bentuk dan label.
     */
    public static function aksen(Checkpoint $pos, ?Checkpoint $terakhir): ?string
    {
        return match (true) {
            $pos->checkpoint_type === CheckpointType::CAMPGROUND => 'kemah',
            $pos->checkpoint_type === CheckpointType::SUMMIT, $terakhir !== null && $pos->is($terakhir) => 'tujuan',
            default => null,
        };
    }

    private static function antara(Checkpoint $sebelum, Checkpoint $kini): ?string
    {
        $bagian = [];

        if ($sebelum->latitude !== null && $sebelum->longitude !== null
            && $kini->latitude !== null && $kini->longitude !== null) {
            $km = GpxTrack::lengthKm([
                [(float) $sebelum->longitude, (float) $sebelum->latitude],
                [(float) $kini->longitude, (float) $kini->latitude],
            ]);

            if ($km >= 0.05) {
                $bagian[] = number_format($km, 1, ',', '.').' km';
            }
        }

        $beda = self::beda($sebelum, $kini);

        if ($beda !== null) {
            $bagian[] = $beda;
        }

        return $bagian === [] ? null : implode(' · ', $bagian);
    }

    private static function rentang(int $tercepat, int $terlama): string
    {
        return $tercepat === $terlama ? Durasi::panjang($tercepat) : Durasi::panjang($tercepat).' sampai '.Durasi::panjang($terlama);
    }

    /**
     * Naik atau turun dari pos sebelumnya. Null bukan nol: ketinggian yang belum dicatat
     * tidak menjadi "naik 0 m" (PRD §91).
     */
    private static function beda(Checkpoint $sebelum, Checkpoint $kini): ?string
    {
        if ($sebelum->elevation_m === null || $kini->elevation_m === null || $sebelum->elevation_m === $kini->elevation_m) {
            return null;
        }

        $beda = $kini->elevation_m - $sebelum->elevation_m;

        return ($beda > 0 ? 'naik ' : 'turun ').number_format(abs($beda), 0, ',', '.').' m';
    }
}
```

`app/Services/Trails/PenilaianKecocokan.php`:

1. Ganti:

```php
        $goal = $trip === null ? null : ($trip->hikingGoal ?? $this->tujuanDariTrip($trip));
```

dengan:

```php
        $goal = $trip?->tujuanPenilaian();
```

2. Ganti:

```php
                'rencana' => $trip === null ? null : $this->ringkasRencana($trip),
```

dengan:

```php
                'rencana' => $trip?->ringkasan(),
```

3. Ganti:

```php
        ];
    }

    /**
     * Trip lama yang dibuat tanpa tujuan pendakian tetap dinilai untuk rencananya: jenis dan
     * tanggal tripnya dipakai sebagai tujuan sementara yang tidak pernah disimpan.
     */
    private function tujuanDariTrip(TripPlan $trip): HikingGoal
    {
        return new HikingGoal([
            'trip_type' => $trip->trip_type,
            'target_date' => $trip->planned_date,
        ]);
```

dengan:

```php
        ];
```

4. Ganti:

```php
    private function ringkasRencana(TripPlan $trip): string
    {
        $mulai = $trip->planned_date;
        $selesai = $trip->end_date;
        $tanggal = $selesai !== null && ! $selesai->isSameDay($mulai)
            ? $mulai->translatedFormat('j').' sampai '.$selesai->translatedFormat('j M Y')
            : $mulai->translatedFormat('j M Y');

        return implode(', ', array_filter([
            $trip->trip_type->label(),
            $tanggal,
            $trip->participants_count ? $trip->participants_count.' orang' : null,
        ]));
    }

    private function kalimatSyarat(string $aturan): string
```

dengan:

```php
    private function kalimatSyarat(string $aturan): string
```

`app/Services/Trails/SkalaProfil.php`:

Ganti:

```php
    private static function jarangkan(array $titik): array
```

dengan:

```php
    public static function jarangkan(array $titik): array
```

Ganti seluruh isi `app/Services/Trails/TrailDetailPage.php` dengan:

```php
<?php

namespace App\Services\Trails;

use App\Enums\FreshnessState;
use App\Enums\NavigationComplexity;
use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\TripStatus;
use App\Models\PermitRequirement;
use App\Models\ReportThank;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\CheckpointPaceService;
use App\Services\ConditionAggregatorService;
use App\Services\ConsiderationService;
use App\Services\PermitService;
use App\Services\PreparationService;
use App\Services\ProgressLadderService;
use App\Services\RouteFit\CompatibilityScorer;
use App\Services\RouteFit\RouteFitResult;
use App\Services\RouteFitService;
use App\Services\TrailFitService;
use App\Services\TrailNewsService;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Props halaman rinci jalur (FR-07, spec v3 bagian 6.4), sudah diformat di server: angka,
 * tantangan, kecocokan, profil elevasi, pos, kondisi, laporan, persiapan, dan sumber,
 * masing-masing dengan sumbernya sendiri (PRD §34, §60, §92).
 *
 * Waktu ditampilkan dalam zona gunungnya lengkap dengan penanda (§93). Skor internal,
 * bobot, dan probabilitas tidak pernah ikut (BR-09): kecocokan sampai ke klien sebagai
 * label, alasan, dan daftar kalimat; ketujuh faktor ada di halaman penilaian kecocokan.
 */
class TrailDetailPage
{
    public function __construct(
        private readonly ConditionAggregatorService $conditions,
        private readonly RouteFitService $routeFit,
        private readonly ProgressLadderService $progressLadder,
        private readonly PermitService $permits,
        private readonly CheckpointPaceService $paces,
        private readonly TrailNewsService $news,
        private readonly ConsiderationService $consideration,
        private readonly CompatibilityScorer $penilai,
        private readonly TrailFitService $ringkasFit,
        private readonly PreparationService $persiapan,
        private readonly PosSepanjangJalur $posKm,
    ) {}

    /**
     * Jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak berwenang. Yang
     * ditampilkan keadaan menunggu beserta siapa yang ditunggu, bukan rincian setengah
     * jadi yang terbaca seperti rincian utuh.
     *
     * @return array<string, mixed>
     */
    public function menunggu(Trail $trail): array
    {
        $badan = $trail->mountain->responsibleAuthority();

        return [
            'menunggu' => $trail->awaitingData(),
            'badan' => $badan ? ['nama' => $badan->displayName(), 'website' => $badan->website] : null,
        ];
    }

    /**
     * Isi rincian jalur dalam urutan pengungkapan PRD §89 (spec v3 bagian 6.4): angka sekali
     * jalan naik, tantangan, kecocokan, profil elevasi, pos, kondisi sekarang, laporan
     * pendaki, yang perlu disiapkan, lalu sumber di halaman ini.
     *
     * Kecocokan dinilai "untuk rencana ini" bila pembaca punya trip terencana yang belum lewat
     * di jalur ini (trip terdekat), dengan tujuan trip itu; tanpa trip, kecocokan dasar tanpa
     * tujuan. Tujuan rekomendasi terakhir tidak dipakai: ia bukan rencana di jalur ini.
     *
     * @return array<string, mixed>
     */
    public function props(Trail $trail, User $user): array
    {
        $profilLengkap = $user->hasCompletedProfile();
        $zona = Timezone::forTrail($trail);
        $trip = $profilLengkap ? $this->tripTerdekat($user, $trail, $zona) : null;
        // Izin diambil sekali dan dioper ke mesin; tanpa itu evaluate() mencarinya lagi.
        $izin = $this->permits->requirementFor($trail);
        $fit = $profilLengkap ? $this->routeFit->evaluate($user, $trip?->tujuanPenilaian(), $trail, permit: $izin) : null;

        $kondisi = $this->conditions->forTrail($trail);
        $laporan = $kondisi['community_context']['reports'] ?? [];
        $tersimpan = $this->consideration->simpanan($user);
        $simpananIni = $tersimpan->firstWhere('trail_id', $trail->id);
        $paces = $this->paces->forTrail($trail);
        $posKm = $this->posKm->km($trail);

        return [
            'kecocokan' => $fit === null ? null : $this->kecocokan($fit, $trail, $user, $trip),
            'ujung' => $this->ujung($trail),
            // Tiga angka sekali jalan naik. Turunan tidak ikut: elevation_loss_m di data pemilik
            // (MvpDatasetSeeder, DemoSeeder) sama dengan tanjakan, yaitu turunan pulang, jadi
            // menulisnya sebagai angka sekali jalan akan keliru (R-005; ruling W3, OPEN ISSUE).
            'angkaNaik' => [
                $this->angka('Jarak', TrailFigures::jarak($trail->distance_km)),
                $this->angka('Tanjakan', TrailFigures::meter($trail->elevation_gain_m)),
                $this->angka('Naik', TrailFigures::durasi($trail->estimated_duration_minutes)),
            ],
            'tantangan' => $this->tantangan($trail),
            'profilElevasi' => ProfilElevasiJalur::untuk($trail->elevation_profile, $trail->checkpoints, $posKm),
            // Bukan 'peta': nama itu milik props bersama (konfigurasi basemap).
            'petaJalur' => [
                'geometry' => $trail->readGeoJson('geometry'),
                'penanda' => $this->penandaPos($trail),
            ],
            'pos' => CheckpointJourney::daftar($trail->checkpoints, $paces, $posKm),
            'waktuDariRekaman' => $paces === [] ? null : (int) min(array_column($paces, 'rekaman')),
            'segmen' => $trail->segments->map(fn ($s) => [
                'id' => $s->id,
                'judul' => $s->sequence.'. '.$s->name,
                'ringkas' => implode(', ', [
                    $s->distance_km !== null ? number_format((float) $s->distance_km, 1, ',', '.').' km' : 'jarak belum tersedia',
                    $s->elevation_gain_m !== null ? 'naik '.number_format($s->elevation_gain_m, 0, ',', '.').' m' : 'tanjakan belum tersedia',
                    'teknis '.($s->technical_demand?->label() ?? 'belum tersedia'),
                ]),
                'description' => $s->description,
            ])->values()->all(),
            'statusResmi' => $this->statusResmi($kondisi['official_status'], $zona),
            'perizinan' => $this->perizinan($izin, $zona),
            'cuaca' => $this->cuaca($kondisi['weather_context']) + ['untukTrip' => $this->prakiraanTrip($trip, $zona)],
            'komunitas' => $this->komunitas($kondisi['community_context'], $laporan, $user),
            'persiapan' => $this->persiapan($trail, $izin),
            'sumber' => $this->sumber($trail, $kondisi, $izin, $fit !== null, count($laporan), $zona),
            'mengikutiGunung' => $this->news->follows($user, $trail->mountain),
            'ditimbang' => $simpananIni !== null,
            'adaCatatan' => $simpananIni?->note !== null,
            'bandingUrl' => $this->bandingUrl($trail, $tersimpan),
        ];
    }

    /**
     * Trip terencana pembaca di jalur ini yang tanggalnya belum lewat di zona gunungnya,
     * yang terdekat lebih dulu.
     */
    private function tripTerdekat(User $user, Trail $trail, string $zona): ?TripPlan
    {
        return TripPlan::query()
            ->whereBelongsTo($user)
            ->where('trail_id', $trail->id)
            ->where('status', TripStatus::PLANNED->value)
            ->whereDate('planned_date', '>=', Carbon::now($zona)->toDateString())
            ->with('hikingGoal')
            ->orderBy('planned_date')
            ->first();
    }

    /**
     * "Cocokkah untuk saya?": label dan definisinya, satu alasan, lalu tiga daftar dari
     * penjelasan mesin yang sama dengan halaman rekomendasi (mengapa sesuai, yang perlu
     * diperhatikan, celah persiapan), tanpa skor atau bobot (BR-09, R-005).
     *
     * @return array<string, mixed>
     */
    private function kecocokan(RouteFitResult $fit, Trail $trail, User $user, ?TripPlan $trip): array
    {
        return [
            'label' => $fit->label?->value,
            'labelText' => RouteFitLabel::textFor($fit->label),
            'deskripsi' => $fit->label?->description(),
            'layak' => $fit->eligible,
            'rencana' => $trip?->ringkasan(),
            'alasan' => $this->ringkasFit->alasan($fit),
            // Paling banyak tiga per daftar; seluruh faktornya di halaman penilaian kecocokan.
            'mengapaSesuai' => $fit->eligible ? array_slice($fit->explanation['why_it_fits'] ?? [], 0, 3) : [],
            'perluDiperhatikan' => array_slice($fit->explanation['what_to_watch'] ?? [], 0, 3),
            'celahPersiapan' => array_slice($fit->explanation['preparation_gap'] ?? [], 0, 3),
            // Acuannya diri sendiri, bukan pendaki lain (R-015).
            'tangga' => $this->progressLadder->bandingkanDenganRiwayat($user, $trail),
            'penilaianUrl' => route('trails.fit', ['trail' => $trail->slug] + ($trip === null ? [] : ['trip' => $trip->id])),
        ];
    }

    /**
     * Pos pertama dan terakhir, untuk kalimat "dari ... ke ..." di atas angka sekali jalan.
     *
     * @return array{awal: string, akhir: string}|null
     */
    private function ujung(Trail $trail): ?array
    {
        $pos = $trail->checkpoints->sortBy('sequence')->values();

        return $pos->count() < 2 ? null : ['awal' => $pos->first()->name, 'akhir' => $pos->last()->name];
    }

    /**
     * "Apa tantangannya?" (spec v3 bagian 6.4). Tuntutan fisik, teknis, dan navigasi masing-
     * masing sebagai kata dan tiga ruas; yang belum diketahui tidak diberi ruas, bukan ruas
     * terendah (R-006). Medan, air, dan area kemah sebagai kata dari data jalur. Keadaan
     * lingkungan (suhu, angin) tidak ditulis karena belum ada datanya (R-007).
     *
     * @return array<int, array{judul: string, kata: string, ruas: int|null}>
     */
    private function tantangan(Trail $trail): array
    {
        $fisik = $this->penilai->physicalDemand($trail);
        $medan = array_map(fn ($t) => $t->label(), $trail->terrainTypes());

        return [
            [
                'judul' => 'Tuntutan fisik',
                'kata' => $fisik['known'] ? ['Ringan', 'Sedang', 'Berat', 'Sangat berat'][$fisik['rank'] - 1] : TrailFigures::BELUM,
                'ruas' => $fisik['known'] ? min($fisik['rank'], 3) : null,
            ],
            [
                'judul' => 'Tuntutan teknis',
                'kata' => $trail->technical_demand?->label() ?? TrailFigures::BELUM,
                'ruas' => $trail->technical_demand === null ? null : min($trail->technical_demand->rank(), 3),
            ],
            [
                'judul' => 'Navigasi',
                'kata' => $trail->navigation_complexity?->label() ?? TrailFigures::BELUM,
                'ruas' => match ($trail->navigation_complexity) {
                    NavigationComplexity::LOW => 1,
                    NavigationComplexity::MODERATE => 2,
                    NavigationComplexity::HIGH => 3,
                    null => null,
                },
            ],
            ['judul' => 'Medan', 'kata' => $medan === [] ? TrailFigures::BELUM : implode(', ', $medan), 'ruas' => null],
            ['judul' => 'Sumber air', 'kata' => $trail->water_availability?->label() ?? TrailFigures::BELUM, 'ruas' => null],
            ['judul' => 'Area kemah', 'kata' => $trail->camping_available ? 'Tersedia' : 'Tidak tersedia', 'ruas' => null],
        ];
    }

    /**
     * BMKG menerbitkan prakiraan beberapa hari ke depan. Untuk trip yang lebih jauh, halaman
     * menyebut kapan prakiraan tanggal trip mulai muncul, bukan menampilkan prakiraan hari
     * ini seolah untuk tanggal itu (R-013).
     */
    private function prakiraanTrip(?TripPlan $trip, string $zona): ?string
    {
        if ($trip === null) {
            return null;
        }

        $hari = (int) config('hiking.conditions.weather_forecast_days');
        $terbit = $trip->planned_date->copy()->subDays($hari);

        if ($terbit->toDateString() <= Carbon::now($zona)->toDateString()) {
            return null;
        }

        return 'Prakiraan untuk '.$trip->planned_date->translatedFormat('j M').' belum terbit. BMKG menerbitkan '.$hari
            .' hari ke depan; prakiraan tanggal Anda muncul di sini mulai '.$terbit->translatedFormat('j M').'.';
    }

    /**
     * "Yang perlu disiapkan": izin bila jalurnya punya aturan tercatat, lalu butir templat
     * khusus jalur ini. Tanpa butir khusus, bagian ini menyebutnya dan daftar umum dipakai saat
     * trip direncanakan.
     *
     * @return array<int, array{judul: string, keterangan: string|null, wajib: bool}>
     */
    private function persiapan(Trail $trail, ?PermitRequirement $izin): array
    {
        $butir = $izin === null ? [] : [[
            'judul' => PreparationService::PERMIT_LABEL,
            'keterangan' => $izin->summary(),
            'wajib' => true,
        ]];

        foreach ($this->persiapan->khususJalur($trail) as $item) {
            $butir[] = ['judul' => $item->label, 'keterangan' => $item->description, 'wajib' => (bool) $item->is_critical];
        }

        return $butir;
    }

    /**
     * "Sumber di halaman ini" dalam urutan kepercayaan (R-009): setiap bagian menyebut jenis
     * dan nama sumbernya. Jenisnya dibaca dari data_sources, jadi "Resmi" hanya untuk catatan
     * bersumber resmi; label kecocokan selalu "Interpretasi sistem".
     *
     * @param  array<string, mixed>  $kondisi
     * @return array<int, array{bagian: string, jenis: string|null, isi: string, url: string|null}>
     */
    private function sumber(Trail $trail, array $kondisi, ?PermitRequirement $izin, bool $dinilai, int $jumlahLaporan, string $zona): array
    {
        $status = $kondisi['official_status'];
        $baris = [
            [
                'bagian' => 'Status akses',
                'jenis' => $status['source_type'] ?? null,
                'isi' => implode(', ', array_filter([$status['source'] ?? 'Belum tercatat', $this->waktu($status['verified_at'] ?? $status['published_at'] ?? null, $zona)])),
                'url' => null,
            ],
        ];

        if ($izin !== null) {
            $baris[] = [
                'bagian' => 'Izin',
                'jenis' => $izin->dataSource?->source_type?->value,
                'isi' => implode(', ', array_filter([$izin->source ?? $izin->authority, $izin->verified_at ? $this->waktu($izin->verified_at->toIso8601String(), $zona) : null])),
                'url' => $izin->booking_url,
            ];
        }

        $baris[] = ['bagian' => 'Cuaca', 'jenis' => null, 'isi' => (string) $kondisi['weather_context']['source'], 'url' => null];
        $baris[] = [
            'bagian' => 'Angka, pos, dan garis rute',
            'jenis' => $trail->dataSource?->source_type?->value,
            'isi' => $trail->dataSource?->source_name ?? 'Belum tercatat',
            'url' => $trail->dataSource?->source_url,
        ];

        if ($jumlahLaporan > 0) {
            $baris[] = ['bagian' => 'Kondisi lapangan', 'jenis' => SourceType::COMMUNITY->value, 'isi' => $jumlahLaporan.' laporan pendaki, termoderasi', 'url' => null];
        }

        if ($dinilai) {
            $baris[] = ['bagian' => 'Label kecocokan', 'jenis' => SourceType::AI_INTERPRETATION->value, 'isi' => 'aturan Route Fit '.str_replace('route-fit-', '', RouteFitService::ENGINE_VERSION), 'url' => null];
        }

        return $baris;
    }

    /**
     * Banding dari rincian: jalur ini bersama paling banyak dua simpanan terbaru lain yang
     * masih terbit (DK-05). Tanpa simpanan lain tidak ada yang dapat dibandingkan, jadi
     * tautannya tidak ada, bukan tombol mati (DK-40).
     *
     * @param  Collection<int, TrailConsideration>  $tersimpan
     */
    private function bandingUrl(Trail $trail, Collection $tersimpan): ?string
    {
        $lain = $tersimpan
            ->filter(fn (TrailConsideration $s) => $s->trail_id !== $trail->id && $s->trail?->is_published)
            ->take($this->consideration->batasBanding() - 1)
            ->pluck('trail_id');

        return $lain->isEmpty() ? null : route('trails.compare', ['trails' => $lain->prepend($trail->id)->join(',')]);
    }

    /**
     * @return array{label: string, nilai: string, ada: bool}
     */
    private function angka(string $label, string $nilai): array
    {
        return ['label' => $label, 'nilai' => $nilai, 'ada' => $nilai !== TrailFigures::BELUM];
    }

    /**
     * Penanda pos di peta rincian. Pos berkemah dan tujuan membawa aksen (DESIGN.md, satu
     * aksen), selalu bersama bentuk dan nomornya.
     *
     * @return array<int, array{lng: float, lat: float, label: string, urutan: int, aksen: string|null}>
     */
    private function penandaPos(Trail $trail): array
    {
        $pos = $trail->checkpoints->sortBy('sequence')->values();
        $terakhir = $pos->last();

        return $pos
            ->filter(fn ($p) => $p->latitude !== null && $p->longitude !== null)
            ->map(fn ($p) => [
                'lng' => (float) $p->longitude,
                'lat' => (float) $p->latitude,
                'label' => $p->sequence.'. '.$p->name,
                'urutan' => $p->sequence,
                'aksen' => CheckpointJourney::aksen($p, $terakhir),
            ])
            ->values()
            ->all();
    }

    /**
     * Status kosong bukan OPEN (§95): UNKNOWN dikirim tanpa warna status, dengan labelnya.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function statusResmi(array $snapshot, string $zona): array
    {
        $nilai = OfficialStatusValue::tryFrom((string) $snapshot['status']);

        return [
            'status' => $nilai !== null && $nilai !== OfficialStatusValue::UNKNOWN ? $nilai->value : null,
            'statusLabel' => $snapshot['status_label'],
            'scope' => StatusScope::tryFrom((string) $snapshot['scope'])?->label(),
            'source' => $snapshot['source'] ?? 'Belum tercatat',
            'jenisSumber' => $snapshot['source_type'] ?? null,
            'reason' => $snapshot['reason'],
            'diverifikasi' => $this->waktu($snapshot['verified_at'] ?? $snapshot['published_at'], $zona),
        ];
    }

    /**
     * Sistem mencatat aturan pihak lain, tidak menggantikannya (PRD §36).
     *
     * @return array<string, mixed>|null
     */
    private function perizinan(?PermitRequirement $permit, string $zona): ?array
    {
        if ($permit === null) {
            return null;
        }

        return [
            'authority' => $permit->authority,
            'kuotaHarian' => $permit->daily_quota ? $permit->daily_quota.' pendaki' : null,
            'batasPemesanan' => $permit->booking_closes_days_before !== null ? 'H-'.$permit->booking_closes_days_before : null,
            'durasiMaksimum' => $permit->max_duration_days ? $permit->max_duration_days.' hari' : null,
            'pemandu' => $permit->guide_required ? 'Wajib pemandu terdaftar' : 'Tidak diwajibkan',
            'notes' => $permit->notes,
            'bookingUrl' => $permit->booking_url,
            'diverifikasi' => $this->waktu($permit->verified_at?->toIso8601String(), $zona),
            'source' => $permit->source ?? 'Belum dicatat',
            'jenisSumber' => $permit->dataSource?->source_type?->value,
        ];
    }

    /**
     * Kegagalan BMKG dilaporkan apa adanya, tidak pernah diganti cuaca karangan (R-013).
     *
     * @param  array<string, mixed>  $cuaca
     * @return array<string, mixed>
     */
    private function cuaca(array $cuaca): array
    {
        if (! $cuaca['available']) {
            return ['available' => false, 'message' => $cuaca['message'], 'source' => $cuaca['source']];
        }

        $zona = $cuaca['timezone'] ?? Timezone::DEFAULT;

        return [
            'available' => true,
            'referenceArea' => $cuaca['reference_area'] ?? 'Tidak dicatat',
            'message' => $cuaca['message'],
            'timezoneLabel' => $cuaca['timezone_label'] ?? 'WIB',
            'forecast' => array_map(fn (WeatherSnapshot $s) => [
                'id' => $s->id,
                'waktu' => $s->forecast_at->copy()->setTimezone($zona)->translatedFormat('d M H:i'),
                'cuaca' => $s->weather_description ?? '-',
                'suhu' => $s->temperature_c !== null ? number_format((float) $s->temperature_c, 1, ',', '.').' °C' : '-',
                'kelembapan' => $s->humidity_percent !== null ? $s->humidity_percent.'%' : '-',
            ], array_slice($cuaca['forecast'], 0, 8)),
            'source' => $cuaca['source'],
            'freshnessLabel' => FreshnessState::tryFrom((string) $cuaca['freshness'])?->label(),
            'diperbarui' => $this->waktu($cuaca['fetched_at'], $zona),
        ];
    }

    /**
     * Pelapor disebut namanya: laporan bertanda tangan dinilai berbeda dari laporan
     * anonim. Status sudah-berterima-kasih dimuat dalam satu query untuk seluruh daftar,
     * bukan satu per laporan, supaya jumlah query tidak tumbuh bersama jumlah laporan.
     *
     * @param  array<string, mixed>  $komunitas
     * @param  array<int, TrailConditionReport>  $laporan
     * @return array<string, mixed>
     */
    private function komunitas(array $komunitas, array $laporan, User $pembaca): array
    {
        if (! $komunitas['available']) {
            return ['available' => false, 'message' => $komunitas['message'], 'reports' => []];
        }

        $sudah = $laporan === [] ? [] : ReportThank::query()
            ->where('user_id', $pembaca->id)
            ->whereIn('trail_condition_report_id', array_map(fn ($r) => $r->id, $laporan))
            ->pluck('trail_condition_report_id')
            ->all();

        return [
            'available' => true,
            'message' => null,
            'reports' => array_map(fn (TrailConditionReport $r) => [
                'id' => $r->id,
                'tags' => array_map(fn ($tag) => $tag->label(), $r->tags()),
                'note' => $r->note,
                'photoUrl' => $r->photo_path ? route('reports.photo', $r) : null,
                'photoAlt' => 'Foto kondisi jalur dari pendakian '.$r->hike_date->translatedFormat('d M Y'),
                // Tombol terima kasih tidak tampil pada laporan sendiri: angka yang dapat
                // dinaikkan sendirian tidak mengukur apa-apa.
                'milikSaya' => $r->user_id === $pembaca->id,
                'sudahTerimaKasih' => in_array($r->id, $sudah, true),
                'thanksCount' => (int) $r->thanks_count,
                'pelapor' => $r->user?->name ?? 'pendaki yang akunnya sudah dihapus',
                'dilaporkan' => $r->created_at->diffForHumans(),
                'tanggalPendakian' => $r->hike_date->translatedFormat('d M Y'),
                // Kesegaran dihitung dari tanggal pendakian, bukan tanggal kirim (§51),
                // supaya laporan yang mulai lama terbaca sebagai info lama.
                'kesegaran' => $this->conditions->reportFreshness($r)->label(),
                'segmen' => $r->segment?->name,
            ], $laporan),
        ];
    }

    private function waktu(?string $iso, string $zona): ?string
    {
        return $iso ? Timezone::display(Carbon::parse($iso), $zona) : null;
    }
}
```

**Antarmuka**

Buat `resources/js/components/trails/ProfilElevasi.tsx`:

```tsx
import { useId } from 'react';
import type { ProfilElevasiData } from '@/components/trails/rinci/types';
import { kelas } from '@/lib/kelas';

const LEBAR = 1000;
const TINGGI = 240;

const angka = (n: number, desimal = 0) => n.toLocaleString('id-ID', { minimumFractionDigits: desimal, maximumFractionDigits: desimal });

/** Tiga sampai empat angka bulat di antara dua batas, untuk garis bantu sumbu. */
function garisBantu(min: number, maks: number, langkahDasar: number[]): number[] {
    const rentang = Math.max(maks - min, 1);
    const langkah = langkahDasar.find((l) => rentang / l <= 4) ?? langkahDasar[langkahDasar.length - 1] ?? 1;
    const hasil: number[] = [];

    for (let n = Math.ceil(min / langkah) * langkah; n <= maks; n += langkah) {
        hasil.push(n);
    }

    return hasil;
}

/**
 * Profil elevasi (spec v3 bagian 6.4, prototype 04), dipakai rincian jalur dan ringkasan
 * pendakian. Menggantikan ElevationProfile lama di components/ui: komponen ini tahu domain
 * (pos, bagian tercuram), jadi tempatnya di components/trails (keputusan koordinator W3).
 *
 * Bagian tercuram diarsir dengan pola garis miring, bukan warna saja. Penanda pos hanya
 * untuk pos yang jaraknya dari awal diukur sepanjang garis rute; pos berkemah dan tujuan
 * membawa aksen kuning, selalu bersama nomornya dan keterangan di bawah grafik (DESIGN.md,
 * satu aksen). Padanan teksnya kalimat dari server di figcaption, jadi SVG-nya disembunyikan
 * dari pembaca layar (WCAG 1.1.1).
 */
export function ProfilElevasi({ profil, className }: { profil: ProfilElevasiData; className?: string }) {
    const idPola = useId();
    const titik = profil.titik;
    const kmAwal = titik[0]?.[0] ?? 0;
    const kmAkhir = titik.at(-1)?.[0] ?? 1;
    const tinggi = titik.map(([, m]) => m);
    const mMin = Math.min(...tinggi);
    const mMaks = Math.max(...tinggi);
    const bawah = mMin - (mMaks - mMin) * 0.1;
    const atas = mMaks + (mMaks - mMin) * 0.1;
    const x = (km: number) => ((km - kmAwal) / Math.max(kmAkhir - kmAwal, 0.001)) * LEBAR;
    const y = (m: number) => TINGGI - ((m - bawah) / Math.max(atas - bawah, 1)) * TINGGI;
    const garis = titik.map(([km, m]) => `${x(km).toFixed(1)},${y(m).toFixed(1)}`).join(' ');
    const tinggiPada = (km: number) => {
        const i = titik.findIndex(([k]) => k >= km);
        if (i <= 0) return titik[0]?.[1] ?? mMin;
        const [k0, m0] = titik[i - 1] ?? [km, mMin];
        const [k1, m1] = titik[i] ?? [km, mMin];

        return k1 === k0 ? m1 : m0 + ((m1 - m0) * (km - k0)) / (k1 - k0);
    };
    const adaAksen = profil.pos.some((p) => p.aksen !== null);

    return (
        <figure className={className}>
            <div className="grid grid-cols-[auto_minmax(0,1fr)] gap-x-2">
                <div aria-hidden="true" className="relative h-44 w-11 text-meta text-muted">
                    {garisBantu(bawah, atas, [100, 200, 250, 500, 1000]).map((m) => (
                        <span key={m} data-angka="" className="absolute right-0 -translate-y-1/2" style={{ top: `${(y(m) / TINGGI) * 100}%` }}>
                            {angka(m)}
                        </span>
                    ))}
                </div>
                <div className="relative h-44">
                    <svg viewBox={`0 0 ${LEBAR} ${TINGGI}`} preserveAspectRatio="none" aria-hidden="true" className="absolute inset-0 h-full w-full">
                        <defs>
                            <pattern id={idPola} width="12" height="12" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                                <line x1="0" y1="0" x2="0" y2="12" strokeWidth="5" className="stroke-fit-perlu-teks/40" />
                            </pattern>
                        </defs>
                        {garisBantu(bawah, atas, [100, 200, 250, 500, 1000]).map((m) => (
                            <line key={m} x1="0" x2={LEBAR} y1={y(m)} y2={y(m)} strokeWidth="1" vectorEffect="non-scaling-stroke" className="stroke-subtle" />
                        ))}
                        {profil.tercuram ? (
                            <g>
                                <rect x={x(profil.tercuram.dariKm)} y="0" width={x(profil.tercuram.sampaiKm) - x(profil.tercuram.dariKm)} height={TINGGI} className="fill-fit-perlu/60" />
                                <rect x={x(profil.tercuram.dariKm)} y="0" width={x(profil.tercuram.sampaiKm) - x(profil.tercuram.dariKm)} height={TINGGI} fill={`url(#${idPola})`} />
                            </g>
                        ) : null}
                        <polygon points={`${x(kmAwal)},${TINGGI} ${garis} ${x(kmAkhir)},${TINGGI}`} className="fill-brand-100/70" />
                        <polyline points={garis} fill="none" strokeWidth="3" strokeLinejoin="round" vectorEffect="non-scaling-stroke" className="stroke-brand-ink" />
                    </svg>
                    {profil.pos.map((p) => (
                        <span
                            key={p.urutan}
                            aria-hidden="true"
                            data-angka=""
                            className={kelas(
                                'absolute flex size-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 text-meta font-bold',
                                p.aksen ? 'border-primary bg-aksen-pos text-primary' : 'border-brand-ink bg-surface text-brand-ink',
                            )}
                            style={{ left: `${(x(p.km) / LEBAR) * 100}%`, top: `${(y(tinggiPada(p.km)) / TINGGI) * 100}%` }}
                        >
                            {p.urutan}
                        </span>
                    ))}
                </div>
                <div />
                <div aria-hidden="true" className="relative mt-1 h-4 text-meta text-muted">
                    {garisBantu(kmAwal, kmAkhir, [1, 2, 5, 10]).map((km) => (
                        <span key={km} data-angka="" className="absolute -translate-x-1/2" style={{ left: `${(x(km) / LEBAR) * 100}%` }}>
                            {angka(km)}
                        </span>
                    ))}
                    <span data-angka="" className="absolute right-0">
                        {angka(kmAkhir, 1)} km
                    </span>
                </div>
            </div>
            <figcaption className="mt-3 space-y-1 text-kecil text-secondary">
                <p>{profil.keterangan}</p>
                {adaAksen ? (
                    <p className="flex items-center gap-2 text-meta">
                        <span aria-hidden="true" className="inline-block size-3 rounded-full border-2 border-primary bg-aksen-pos" />
                        Pos berkemah dan tujuan
                    </p>
                ) : null}
            </figcaption>
        </figure>
    );
}
```

Buat `resources/js/components/trails/rinci/AngkaNaik.tsx`:

```tsx
import { kelas } from '@/lib/kelas';
import type { AngkaBerlabel } from './types';

const tanda: Record<string, string> = { Tanjakan: '↑' };

/**
 * Angka sekali jalan naik (spec v3 bagian 6.4): jarak, tanjakan, dan lama naik, dari pos
 * pertama ke pos terakhir. Turunan tidak ditulis karena data turunannya adalah turunan pulang
 * (ruling W3). Angka yang belum ada ditulis "Belum tersedia" dengan huruf biasa, tidak
 * dikarang dan tidak dibiarkan kosong (PRD §91).
 */
export function AngkaNaik({ angka, ujung }: { angka: AngkaBerlabel[]; ujung: { awal: string; akhir: string } | null }) {
    return (
        <section aria-labelledby="judul-angka" className="space-y-3">
            <h2 id="judul-angka" className="sr-only">
                Angka jalur
            </h2>
            <p className="text-kecil text-secondary">
                {ujung ? `${ujung.awal} ke ${ujung.akhir}. ` : null}Angka di bawah untuk sekali jalan naik.
            </p>
            <dl className="grid grid-cols-2 gap-3 min-[360px]:grid-cols-3">
                {angka.map((a) => (
                    <div key={a.label} className="rounded-lg border border-subtle bg-surface px-3 py-2.5">
                        <dt className="text-meta text-secondary">{a.label}</dt>
                        <dd data-angka="" className={kelas('mt-0.5', a.ada ? 'text-subjudul text-primary min-[400px]:text-judul-kartu' : 'text-kecil text-secondary')}>
                            {a.ada && tanda[a.label] ? `${tanda[a.label]}${a.nilai}` : a.nilai}
                        </dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
```

Buat `resources/js/components/trails/rinci/CocokkahUntukSaya.tsx`:

```tsx
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { Button } from '@/components/ui/Button';
import { Icon, type NamaIkon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { onboarding } from '@/routes';
import type { KecocokanRinci } from './types';

function Daftar({ judul, butir, ikon, warna }: { judul: string; butir: string[]; ikon: NamaIkon; warna: string }) {
    if (butir.length === 0) {
        return null;
    }

    return (
        <div>
            <h3 className={`text-meta font-bold ${warna}`}>{judul}</h3>
            <ul className="mt-1 space-y-1">
                {butir.map((b) => (
                    <li key={b} className="flex gap-2 text-kecil text-primary">
                        <Icon name={ikon} size={16} className={`mt-0.5 shrink-0 ${warna}`} />
                        <span className="min-w-0">{b}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * "Cocokkah untuk saya?" (spec v3 bagian 6.4, prototype 04): label dan definisinya, untuk
 * rencana mana penilaian ini dibuat, satu alasan, lalu mengapa sesuai, yang perlu
 * diperhatikan, dan celah persiapan, semuanya kalimat mesin yang sama dengan halaman
 * rekomendasi. Ketujuh faktor ada di halaman penilaian kecocokan. Tanpa profil, yang
 * ditawarkan jalan mengisinya, bukan penilaian diam-diam.
 *
 * R-005 dan R-014: tanpa skor, dan kalimat penutupnya menyebut ini dukungan keputusan.
 */
export function CocokkahUntukSaya({ kecocokan, idJudul }: { kecocokan: KecocokanRinci | null; idJudul: string }) {
    return (
        <section aria-labelledby={idJudul} className="space-y-3 rounded-lg border border-subtle bg-surface p-4">
            <h2 id={idJudul} className="text-bagian text-primary">
                Cocokkah untuk saya?
            </h2>
            {kecocokan ? (
                <>
                    <div className="flex flex-wrap items-center gap-2">
                        <LabelKecocokan nilai={kecocokan.label} label={kecocokan.labelText} />
                        <span className="text-meta text-secondary">{kecocokan.rencana ? `Untuk rencana ini: ${kecocokan.rencana}` : 'Kecocokan dasar dengan profil Anda'}</span>
                    </div>
                    {kecocokan.deskripsi ? <p className="text-kecil text-secondary">{kecocokan.deskripsi}</p> : null}
                    <p className="text-isi text-primary">{kecocokan.alasan}</p>
                    <Daftar judul="Mengapa sesuai" butir={kecocokan.mengapaSesuai} ikon="check" warna="text-fit-cocok-teks" />
                    <Daftar judul="Yang perlu diperhatikan" butir={kecocokan.perluDiperhatikan} ikon="warning" warna="text-fit-perlu-teks" />
                    <Daftar judul="Celah persiapan" butir={kecocokan.celahPersiapan} ikon="backpack" warna="text-secondary" />
                    {kecocokan.tangga ? <p className="text-meta text-muted">{kecocokan.tangga}</p> : null}
                    <Tautan href={kecocokan.penilaianUrl} className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                        Lihat ketujuh faktor penilaian
                    </Tautan>
                    <p className="text-meta text-muted">Dinilai dengan aturan tertulis, bukan AI, dan tanpa skor angka. Ini dukungan keputusan, bukan jaminan keselamatan.</p>
                </>
            ) : (
                <>
                    <p className="text-kecil text-secondary">Kecocokan jalur ini dengan Anda, beserta alasannya, muncul setelah profil pendakian Anda lengkap.</p>
                    <Button href={onboarding.url()} size="sm">
                        Isi profil sekarang
                    </Button>
                </>
            )}
        </section>
    );
}
```

Buat `resources/js/components/trails/rinci/DaftarPos.tsx`:

```tsx
import { Lencana } from '@/components/ui/Lencana';
import { kelas } from '@/lib/kelas';
import type { PosJalur } from './types';

const kataAksen = { kemah: 'Kemah', tujuan: 'Tujuan' } as const;

/**
 * Pos di jalur ini (spec v3 bagian 6.4, prototype 04), dipakai rincian jalur dan ringkasan
 * pendakian. Menggantikan CheckpointJourney lama di components/ui (keputusan koordinator W3).
 *
 * Setiap pos: nomor dalam lingkaran, nama, jenisnya, lalu satu baris waktu dari awal
 * (perkiraan dari rekaman pendaki), naik atau turun dari pos sebelumnya, dan catatan pos;
 * ketinggian dan jarak dari awal di kanan. Pos berkemah dan tujuan membawa aksen kuning
 * bersama lencana kata "Kemah" atau "Tujuan" (DESIGN.md, satu aksen: tidak pernah warna
 * saja). Garis penghubung antarlingkaran hiasan, disembunyikan dari pembaca layar.
 */
export function DaftarPos({ pos, className }: { pos: PosJalur[]; className?: string }) {
    return (
        <ol className={kelas('relative', className)}>
            {pos.map((p, i) => {
                const keterangan = [p.waktu, p.antara, p.catatan].filter(Boolean).join(', ');

                return (
                    <li key={p.id} className="relative flex gap-3 pb-4 last:pb-0">
                        {i < pos.length - 1 ? <span aria-hidden="true" className="absolute left-[0.9375rem] top-8 h-[calc(100%-2rem)] w-0.5 bg-subtle" /> : null}
                        <span
                            aria-hidden="true"
                            data-angka=""
                            className={kelas(
                                'relative flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-kecil font-bold',
                                p.aksen ? 'border-primary bg-aksen-pos text-primary' : 'border-brand-ink bg-surface text-brand-ink',
                            )}
                        >
                            {p.urutan}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span className="sr-only">Pos {p.urutan}: </span>
                                <span className="text-isi font-bold text-primary">{p.nama}</span>
                                <span className="text-meta text-muted">{p.jenis}</span>
                                {p.aksen ? (
                                    <Lencana nada="netral" bentuk="pil">
                                        {kataAksen[p.aksen]}
                                    </Lencana>
                                ) : null}
                            </p>
                            {keterangan ? <p className="mt-0.5 text-kecil text-secondary">{keterangan}</p> : null}
                        </div>
                        <div data-angka="" className="shrink-0 text-right">
                            <p className="text-kecil font-bold text-primary">{p.elevasi ?? 'Ketinggian belum tercatat'}</p>
                            {p.km ? <p className="text-meta text-muted">{p.km}</p> : null}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
```

Buat `resources/js/components/trails/rinci/Persiapan.tsx`:

```tsx
import { Lencana } from '@/components/ui/Lencana';
import type { ButirPersiapan } from './types';

/**
 * "Yang perlu disiapkan" khusus jalur ini (spec v3 bagian 6.4): izin bila jalurnya punya
 * aturan tercatat, lalu butir templat jalur ini. Butir yang sama masuk ke daftar persiapan
 * trip. Tanpa butir khusus, kalimatnya jujur: daftar umum dipakai saat trip direncanakan.
 */
export function Persiapan({ butir }: { butir: ButirPersiapan[] }) {
    return (
        <section aria-labelledby="judul-persiapan" className="space-y-3">
            <h2 id="judul-persiapan" className="text-bagian text-primary">
                Yang perlu disiapkan
            </h2>
            {butir.length === 0 ? (
                <p className="text-kecil text-secondary">Belum ada butir khusus jalur ini. Daftar persiapan umum dipakai saat Anda merencanakan trip.</p>
            ) : (
                <>
                    <p className="text-kecil text-secondary">Khusus jalur ini. Butir yang sama masuk ke daftar persiapan trip Anda.</p>
                    <ul className="divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                        {butir.map((b) => (
                            <li key={b.judul} className="px-4 py-3">
                                <p className="flex flex-wrap items-center gap-2 text-isi font-bold text-primary">
                                    {b.judul}
                                    {b.wajib ? (
                                        <Lencana nada="peringatan" bentuk="pil">
                                            Wajib
                                        </Lencana>
                                    ) : null}
                                </p>
                                {b.keterangan ? <p className="mt-0.5 text-kecil text-secondary">{b.keterangan}</p> : null}
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </section>
    );
}
```

Buat `resources/js/components/trails/rinci/SumberHalaman.tsx`:

```tsx
import { LabelSumber } from '@/components/trails/LabelSumber';
import type { BarisSumber } from './types';

/**
 * "Sumber di halaman ini" (spec v3 bagian 6.4, prototype 04), dalam urutan kepercayaan:
 * setiap bagian menyebut jenis sumbernya dengan LabelSumber (glyph resmi hanya untuk sumber
 * OFFICIAL, R-009) dan nama sumbernya. Kalimat penutup menyebut urutannya dan bahwa yang lebih
 * rendah tidak pernah menimpa yang lebih tinggi (R-010, R-011).
 */
export function SumberHalaman({ sumber }: { sumber: BarisSumber[] }) {
    return (
        <section aria-labelledby="judul-sumber" className="space-y-3">
            <h2 id="judul-sumber" className="text-bagian text-primary">
                Sumber di halaman ini
            </h2>
            <dl className="divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                {sumber.map((s) => (
                    <div key={s.bagian} className="flex flex-wrap items-start justify-between gap-x-4 gap-y-1 px-4 py-3">
                        <dt className="text-kecil font-bold text-primary">{s.bagian}</dt>
                        <dd className="flex flex-wrap items-center gap-2 text-kecil text-secondary">
                            <LabelSumber jenis={s.jenis} />
                            {s.url ? (
                                <a href={s.url} target="_blank" rel="noopener noreferrer" className="py-1 text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                    {s.isi}
                                    <span className="sr-only"> (membuka tab baru)</span>
                                </a>
                            ) : (
                                <span>{s.isi}</span>
                            )}
                        </dd>
                    </div>
                ))}
            </dl>
            <p className="text-meta text-muted">Urutan kepercayaan: Resmi, Terverifikasi admin, Ahli bersertifikat, Komunitas, Interpretasi sistem. Yang lebih rendah tidak pernah menimpa yang lebih tinggi.</p>
        </section>
    );
}
```

Buat `resources/js/components/trails/rinci/Tantangan.tsx`:

```tsx
import { kelas } from '@/lib/kelas';
import type { BarisTantangan, Segmen } from './types';

/**
 * "Apa tantangannya?" (spec v3 bagian 6.4). Tuntutan fisik, teknis, dan navigasi masing-masing
 * dengan tiga ruas dan kata: kata membawa artinya, ruas hanya bentuk pembantu, jadi pembaca
 * layar mendengar katanya saja. Baris tanpa ruas (belum diketahui, atau kata saja seperti medan)
 * tidak digambar ruas kosong yang terbaca "rendah" (R-006). Bagian jalur dari data segmen
 * menyusul bila ada.
 */
export function Tantangan({ baris, segmen }: { baris: BarisTantangan[]; segmen: Segmen[] }) {
    return (
        <section aria-labelledby="judul-tantangan" className="space-y-3">
            <h2 id="judul-tantangan" className="text-bagian text-primary">
                Apa tantangannya?
            </h2>
            <dl className="divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                {baris.map((b) => (
                    <div key={b.judul} className="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3">
                        <dt className="min-w-0 flex-1 text-kecil text-secondary">{b.judul}</dt>
                        <dd className="flex items-center gap-3 text-kecil font-bold text-primary">
                            {b.ruas !== null ? (
                                <span aria-hidden="true" className="flex gap-1">
                                    {[1, 2, 3].map((r) => (
                                        <span key={r} className={kelas('h-2 w-5 rounded-full', r <= (b.ruas ?? 0) ? 'bg-brand-ink' : 'bg-surface-sunken ring-1 ring-inset ring-control')} />
                                    ))}
                                </span>
                            ) : null}
                            <span className="text-right">{b.kata}</span>
                        </dd>
                    </div>
                ))}
            </dl>
            {segmen.length > 0 ? (
                <div className="space-y-2">
                    <h3 className="text-subjudul text-primary">Bagian jalur</h3>
                    <ol className="space-y-2">
                        {segmen.map((s) => (
                            <li key={s.id} className="rounded-lg border border-subtle bg-surface px-4 py-3 text-kecil">
                                <span className="font-bold text-primary">{s.judul}</span>
                                <span className="text-secondary">, {s.ringkas}</span>
                                {s.description ? <p className="mt-1 text-secondary">{s.description}</p> : null}
                            </li>
                        ))}
                    </ol>
                </div>
            ) : null}
        </section>
    );
}
```

`resources/js/components/trails/TombolSimpan.tsx`:

1. Ganti:

```tsx
    /** "ikon" untuk kartu Jelajah (44 px), "tombol" untuk bar aksi rincian jalur. */
    bentuk?: 'ikon' | 'tombol';
```

dengan:

```tsx
    /**
     * "ikon" untuk kartu Jelajah (44 px), "bar" untuk bar aksi ponsel rincian jalur (tombol
     * ikon 44 px bergaris, supaya barnya tetap satu baris), "tombol" untuk kolom kanan layar
     * lebar.
     */
    bentuk?: 'ikon' | 'bar' | 'tombol';
```

2. Ganti:

```tsx
                />
```

dengan:

```tsx
                />
            ) : bentuk === 'bar' ? (
                <TombolIkon
                    ikon="bookmark-simple"
                    isi={jalur.ditimbang}
                    label={`Simpan ${jalur.name}`}
                    aria-pressed={jalur.ditimbang}
                    disabled={memproses}
                    onClick={tekan}
                    bentuk="kotak"
                    className="text-brand-ink"
                />
```

`resources/js/components/trails/rinci/Kondisi.tsx`:

1. Ganti:

```tsx
import type { Cuaca, Perizinan, StatusResmiRinci, SumberData } from './types';
```

dengan:

```tsx
import type { Cuaca, Perizinan, StatusResmiRinci } from './types';
```

2. Ganti:

```tsx
 */

// py-1 pada tautan sebaris memperbesar bidang sentuh tanpa mengubah tinggi baris.
const tautanLuar = 'py-1 text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';
```

dengan:

```tsx
 */
```

3. Ganti:

```tsx
            )}
```

dengan:

```tsx
            )}
            {cuaca.untukTrip ? <p className="mt-3 rounded-control bg-surface-sunken px-3 py-2 text-kecil text-secondary">{cuaca.untukTrip}</p> : null}
```

4. Ganti:

```tsx
}

export function SumberDataKartu({ sumber }: { sumber: SumberData }) {
    return (
        <Card title="Sumber data">
            <dl className="space-y-2 text-sm">
                <div>
                    <dt className="text-muted">Data jalur</dt>
                    <dd className="text-primary">
                        {sumber.jalur}
                        {sumber.jalurUrl ? (
                            <>
                                {' '}&middot;{' '}
                                <a className={tautanLuar} href={sumber.jalurUrl} rel="noopener noreferrer" target="_blank">
                                    tautan sumber<span className="sr-only"> (membuka tab baru)</span>
                                </a>
                            </>
                        ) : null}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted">Status resmi</dt>
                    <dd className="text-primary">{sumber.statusResmi}</dd>
                </div>
                <div>
                    <dt className="text-muted">Cuaca</dt>
                    <dd className="text-primary">{sumber.cuaca}</dd>
                </div>
            </dl>
        </Card>
    );
}
```

dengan:

```tsx
}
```

`resources/js/components/trails/rinci/PetaJalur.tsx`:

1. Ganti:

```tsx
import type { PetaJalurData } from './types';
```

dengan:

```tsx
import type { AksenPos, PetaJalurData } from './types';

/**
 * Penanda pos bernomor, disusun lewat textContent. Pos berkemah dan tujuan membawa aksen
 * kuning (DESIGN.md, satu aksen), selalu bersama nomornya dan nama pos di keterangan penanda.
 */
function elemenPos(urutan: number, aksen: AksenPos): HTMLElement {
    const el = document.createElement('span');
    el.className = aksen
        ? 'flex size-7 items-center justify-center rounded-full border-2 border-primary bg-aksen-pos text-meta font-bold text-primary shadow-overlay'
        : 'flex size-7 items-center justify-center rounded-full border-2 border-brand-ink bg-surface text-meta font-bold text-brand-ink shadow-overlay';
    el.textContent = String(urutan);

    return el;
}
```

2. Ganti:

```tsx
 * Peta rinci jalur: garis jalur beserta titik awal dan akhirnya, dan penanda pos.
 * Padanan non-visualnya daftar penanda di bawah (sr-only) dan daftar pos di halaman yang
 * sama.
```

dengan:

```tsx
 * Peta rinci jalur: garis jalur beserta titik awal dan akhirnya, dan penanda pos bernomor
 * yang sama dengan daftar pos (pos berkemah dan tujuan beraksen). Padanan non-visualnya
 * daftar penanda di bawah (sr-only) dan daftar pos di halaman yang sama.
```

3. Ganti:

```tsx
    const penanda: Array<Penanda & { ujung?: boolean }> = [
```

dengan:

```tsx
    const penanda: Array<Penanda & { ujung?: boolean; urutan?: number; aksen?: AksenPos }> = [
```

4. Ganti:

```tsx
                tambahPenanda(peta, maplibregl, p, p.ujung ? warna : undefined);
```

dengan:

```tsx
                if (p.urutan !== undefined) {
                    new maplibregl.Marker({ element: elemenPos(p.urutan, p.aksen ?? null) })
                        .setLngLat([p.lng, p.lat])
                        .setPopup(new maplibregl.Popup({ offset: 18 }).setText(p.label))
                        .addTo(peta);
                } else {
                    tambahPenanda(peta, maplibregl, p, p.ujung ? warna : undefined);
                }
```

Ganti seluruh isi `resources/js/components/trails/rinci/types.ts` dengan:

```ts
import type { LineString, MultiLineString } from 'geojson';
import type { JenisSumber } from '@/components/trails/LabelSumber';
import type { Penanda } from '@/components/trails/types';
import type { LabelFit } from '@/components/trails/LabelKecocokan';
import type { StatusResmi } from '@/components/trails/StatusAkses';

/** Bentuk yang dikirim TrailHeaderResource dan TrailDetailPage: sudah diformat server, tanpa skor (BR-09). */
export interface IdentitasJalur {
    name: string;
    mountain: string;
    mountainId: number;
    /** Halaman gunung (DK-44), untuk remah di kepala rincian. */
    mountainUrl: string;
    province: string | null;
    mdpl: string | null;
}

export interface AngkaBerlabel {
    label: string;
    nilai: string;
    ada: boolean;
}

/** "Cocokkah untuk saya?" (TrailDetailPage::kecocokan): label, alasan, dan tiga daftar tanpa skor. */
export interface KecocokanRinci {
    label: LabelFit;
    labelText: string;
    deskripsi: string | null;
    layak: boolean;
    /** Ringkasan trip terencana di jalur ini; null berarti kecocokan dasar. */
    rencana: string | null;
    alasan: string;
    mengapaSesuai: string[];
    perluDiperhatikan: string[];
    celahPersiapan: string[];
    tangga: string | null;
    penilaianUrl: string;
}

/** Satu baris "Apa tantangannya?"; ruas null berarti tanpa bilah (kata saja, atau belum diketahui). */
export interface BarisTantangan {
    judul: string;
    kata: string;
    ruas: number | null;
}

export type AksenPos = 'kemah' | 'tujuan' | null;

/** Satu pos di daftar pos rincian (CheckpointJourney::daftar). */
export interface PosJalur {
    id: number;
    urutan: number;
    nama: string;
    jenis: string;
    aksen: AksenPos;
    elevasi: string | null;
    antara: string | null;
    km: string | null;
    waktu: string | null;
    catatan: string | null;
}

/** Profil elevasi rincian (ProfilElevasiJalur): titik [km, m], bagian tercuram, penanda pos. */
export interface ProfilElevasiData {
    titik: Array<[number, number]>;
    tercuram: { dariKm: number; sampaiKm: number; naik: number } | null;
    pos: Array<{ urutan: number; nama: string; km: number; aksen: AksenPos }>;
    keterangan: string;
}

export interface ButirPersiapan {
    judul: string;
    keterangan: string | null;
    wajib: boolean;
}

export interface BarisSumber {
    bagian: string;
    jenis: JenisSumber | null;
    isi: string;
    url: string | null;
}

export interface PetaJalurData {
    geometry: LineString | MultiLineString | null;
    penanda: Array<Penanda & { urutan: number; aksen: AksenPos }>;
}

export interface Segmen {
    id: number;
    judul: string;
    ringkas: string;
    description: string | null;
}

export interface StatusResmiRinci {
    status: StatusResmi;
    statusLabel: string;
    scope: string | null;
    source: string;
    /** Jenis sumber catatan penentu (SourceType); null bila tidak tercatat. */
    jenisSumber: JenisSumber | null;
    reason: string | null;
    diverifikasi: string | null;
}

export interface Perizinan {
    authority: string;
    kuotaHarian: string | null;
    batasPemesanan: string | null;
    durasiMaksimum: string | null;
    pemandu: string;
    notes: string | null;
    bookingUrl: string | null;
    diverifikasi: string | null;
    source: string;
    jenisSumber: JenisSumber | null;
}

export interface BarisCuaca {
    id: number;
    waktu: string;
    cuaca: string;
    suhu: string;
    kelembapan: string;
}

export type Cuaca = (
    | { available: false; message: string; source: string }
    | {
          available: true;
          referenceArea: string;
          message: string | null;
          timezoneLabel: string;
          forecast: BarisCuaca[];
          source: string;
          freshnessLabel: string | null;
          diperbarui: string | null;
      }
) & {
    /** Kalimat kapan prakiraan tanggal trip pembaca mulai terbit; null bila sudah atau tanpa trip. */
    untukTrip: string | null;
};

export interface LaporanKomunitas {
    id: number;
    tags: string[];
    note: string | null;
    photoUrl: string | null;
    photoAlt: string;
    milikSaya: boolean;
    sudahTerimaKasih: boolean;
    thanksCount: number;
    pelapor: string;
    dilaporkan: string;
    tanggalPendakian: string;
    kesegaran: string;
    segmen: string | null;
}

export interface Komunitas {
    available: boolean;
    message: string | null;
    reports: LaporanKomunitas[];
}
```

`resources/js/pages/Community/HikeSummary.tsx`:

1. Ganti:

```tsx
import type { GarisGeoJson } from '@/components/community/types';
```

dengan:

```tsx
import type { GarisGeoJson } from '@/components/community/types';
import { ProfilElevasi } from '@/components/trails/ProfilElevasi';
import { DaftarPos } from '@/components/trails/rinci/DaftarPos';
import type { PosJalur, ProfilElevasiData } from '@/components/trails/rinci/types';
```

2. Ganti:

```tsx
import { Card } from '@/components/ui/Card';
import { CheckpointJourney, type PosPerjalanan } from '@/components/ui/CheckpointJourney';
import { ElevationProfile, type TitikElevasi } from '@/components/ui/ElevationProfile';
```

dengan:

```tsx
import { Card } from '@/components/ui/Card';
```

3. Ganti:

```tsx
    profil: TitikElevasi[] | null;
    pos: PosPerjalanan[];
```

dengan:

```tsx
    profil: ProfilElevasiData | null;
    pos: PosJalur[];
```

4. Ganti:

```tsx
                        <ElevationProfile profile={profil} />
```

dengan:

```tsx
                        <ProfilElevasi profil={profil} />
```

5. Ganti:

```tsx
                        <CheckpointJourney checkpoints={pos} className="space-y-1" />
```

dengan:

```tsx
                        <DaftarPos pos={pos} />
```

Ganti seluruh isi `resources/js/pages/Trails/Show.tsx` dengan:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { ProfilElevasi } from '@/components/trails/ProfilElevasi';
import { AngkaNaik } from '@/components/trails/rinci/AngkaNaik';
import { CocokkahUntukSaya } from '@/components/trails/rinci/CocokkahUntukSaya';
import { DaftarPos } from '@/components/trails/rinci/DaftarPos';
import { CuacaKartu, PerizinanKartu, StatusResmiKartu } from '@/components/trails/rinci/Kondisi';
import { LaporanKomunitas } from '@/components/trails/rinci/LaporanKomunitas';
import { Persiapan } from '@/components/trails/rinci/Persiapan';
import { PetaJalur } from '@/components/trails/rinci/PetaJalur';
import { SumberHalaman } from '@/components/trails/rinci/SumberHalaman';
import { Tantangan } from '@/components/trails/rinci/Tantangan';
import type {
    AngkaBerlabel,
    BarisSumber,
    BarisTantangan,
    ButirPersiapan,
    Cuaca,
    IdentitasJalur,
    KecocokanRinci,
    Komunitas,
    Perizinan,
    PetaJalurData,
    PosJalur,
    ProfilElevasiData,
    Segmen,
    StatusResmiRinci,
} from '@/components/trails/rinci/types';
import { TombolSimpan } from '@/components/trails/TombolSimpan';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { follow } from '@/routes/mountains';
import { create as reportsCreate } from '@/routes/reports';
import { index as trailsIndex } from '@/routes/trails';
import { create as tripsCreate } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trail: IdentitasJalur & { id: number; description: string | null };
    kecocokan: KecocokanRinci | null;
    ujung: { awal: string; akhir: string } | null;
    angkaNaik: AngkaBerlabel[];
    tantangan: BarisTantangan[];
    profilElevasi: ProfilElevasiData | null;
    petaJalur: PetaJalurData;
    pos: PosJalur[];
    waktuDariRekaman: number | null;
    segmen: Segmen[];
    statusResmi: StatusResmiRinci;
    perizinan: Perizinan | null;
    cuaca: Cuaca;
    komunitas: Komunitas;
    persiapan: ButirPersiapan[];
    sumber: BarisSumber[];
    mengikutiGunung: boolean;
    ditimbang: boolean;
    adaCatatan: boolean;
    bandingUrl: string | null;
    pengumuman: string;
}

/**
 * Rincian satu jalur (FR-07, spec v3 bagian 6.4, prototype 04), dalam urutan pengungkapan
 * PRD §89: peta, angka sekali jalan naik, tantangan, kecocokan, profil elevasi, pos, kondisi
 * sekarang, laporan pendaki, yang perlu disiapkan, lalu sumber di halaman ini.
 *
 * Halaman tugas: bilah tab disembunyikan dan bar aksi tetap (Simpan, Banding bila ada simpanan
 * lain, Rencanakan trip) di ponsel; mulai 1024 px kecocokan dan aksinya berdiri di kolom kanan
 * yang lengket (spec v3 bagian 5.1 dan 5.2). Toast Urungkan simpan dimiliki halaman ini
 * (ruling W2-5i).
 */
export default function Show() {
    const {
        trail,
        kecocokan,
        ujung,
        angkaNaik,
        tantangan,
        profilElevasi,
        petaJalur,
        pos,
        waktuDariRekaman,
        segmen,
        statusResmi,
        perizinan,
        cuaca,
        komunitas,
        persiapan,
        sumber,
        mengikutiGunung,
        ditimbang,
        adaCatatan,
        bandingUrl,
        pengumuman,
    } = usePage<Props>().props;
    const { tampilkanToast } = useKerangka();
    const [mengikutiMemproses, setMengikutiMemproses] = useState(false);
    const adaPeta = petaJalur.geometry !== null || petaJalur.penanda.length > 0;
    const jalurSimpan = { id: trail.id, name: trail.name, ditimbang, adaCatatan };

    // Gunungnya yang diikuti, bukan jalurnya, dan tombolnya menyebutnya begitu: penutupan
    // hampir selalu diumumkan untuk kawasan.
    const ikuti = () => {
        setMengikutiMemproses(true);
        router.post(follow.url({ mountain: trail.mountainId }), { ya: !mengikutiGunung }, { preserveScroll: true, onFinish: () => setMengikutiMemproses(false) });
    };

    const rencanaUrl = tripsCreate.url({ query: { trail: trail.id } });

    const aksiLebar = (
        <>
            <TombolSimpan jalur={jalurSimpan} tampilkanToast={tampilkanToast} bentuk="tombol" />
            {bandingUrl ? (
                <Button variant="secondary" href={bandingUrl}>
                    Banding
                </Button>
            ) : null}
            <Button href={rencanaUrl}>Rencanakan trip</Button>
        </>
    );

    return (
        <div className="py-6">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: trailsIndex.url(), label: 'Jelajah' }} title={trail.name} description={trail.description ?? undefined}>
                    <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                        <p className="text-kecil text-secondary">
                            <Tautan href={trail.mountainUrl} className="font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                {trail.mountain}
                            </Tautan>
                            {[trail.mdpl, trail.province].filter(Boolean).map((t) => `, ${t}`).join('')}
                        </p>
                        <Button variant="secondary" size="sm" aria-pressed={mengikutiGunung} processing={mengikutiMemproses} onClick={ikuti}>
                            {mengikutiGunung ? `Berhenti mengikuti ${trail.mountain}` : `Ikuti kabar ${trail.mountain}`}
                        </Button>
                    </div>
                </PageHeader>

                <div className="lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-8">
                    <div className="min-w-0 space-y-10">
                        <section aria-labelledby="judul-peta" className="space-y-2">
                            <h2 id="judul-peta" className="sr-only">
                                Peta jalur
                            </h2>
                            {adaPeta ? (
                                <PetaJalur id="peta-jalur" data={petaJalur} label={`Peta jalur ${trail.name} di ${trail.mountain}`} />
                            ) : (
                                <p data-kosong="menunggu" className="rounded-lg border-2 border-dashed border-control bg-surface px-4 py-3 text-kecil text-secondary">
                                    <span className="font-bold text-primary">Garis rute belum dimasukkan.</span> Menunggu kurator Memount memasukkannya dari rekaman di jalurnya sendiri;
                                    angka, pos, dan kondisi di bawah tetap dapat dipakai.
                                </p>
                            )}
                        </section>

                        <AngkaNaik angka={angkaNaik} ujung={ujung} />

                        <Tantangan baris={tantangan} segmen={segmen} />

                        <div className="lg:hidden">
                            <CocokkahUntukSaya kecocokan={kecocokan} idJudul="judul-cocok-ponsel" />
                        </div>

                        <section aria-labelledby="judul-profil" className="space-y-3">
                            <h2 id="judul-profil" className="text-bagian text-primary">
                                Profil elevasi
                            </h2>
                            {profilElevasi ? <ProfilElevasi profil={profilElevasi} /> : <p className="text-kecil text-secondary">Data ketinggian belum tersedia.</p>}
                        </section>

                        <section aria-labelledby="judul-pos" className="space-y-3">
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <h2 id="judul-pos" className="text-bagian text-primary">
                                    Pos di jalur ini
                                </h2>
                                {waktuDariRekaman !== null ? <p className="text-meta text-muted">Waktu dari awal: rentang dari rekaman pendaki, paling sedikit {waktuDariRekaman} rekaman per ruas</p> : null}
                            </div>
                            {pos.length === 0 ? <p className="text-kecil text-secondary">Daftar pos jalur ini belum dimasukkan.</p> : <DaftarPos pos={pos} />}
                        </section>

                        <section aria-labelledby="judul-kondisi" className="space-y-4">
                            <h2 id="judul-kondisi" className="text-bagian text-primary">
                                Kondisi sekarang
                            </h2>
                            <StatusResmiKartu status={statusResmi} />
                            {perizinan ? <PerizinanKartu izin={perizinan} /> : null}
                            <CuacaKartu cuaca={cuaca} />
                        </section>

                        <section className="space-y-3">
                            <LaporanKomunitas komunitas={komunitas} />
                            <Tautan
                                href={reportsCreate.url({ query: { trail: trail.id } })}
                                className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                            >
                                Laporkan kondisi setelah mendaki
                            </Tautan>
                        </section>

                        <Persiapan butir={persiapan} />

                        <SumberHalaman sumber={sumber} />
                    </div>

                    {/* Aksi lebih dulu dan kolomnya bergulir sendiri: kolom lengket yang lebih tinggi dari
                        layar tidak boleh menyembunyikan tombolnya. */}
                    <aside aria-label="Kecocokan dan aksi" className="hidden lg:block">
                        <div className="sticky top-[calc(var(--kepala-atas)+var(--pesan-jaringan,0px)+1.5rem)] max-h-[calc(100dvh-var(--kepala-atas)-var(--pesan-jaringan,0px)-var(--nav-bawah,0px)-3rem)] space-y-4 overflow-y-auto p-1">
                            <div className="flex flex-wrap gap-2">{aksiLebar}</div>
                            <CocokkahUntukSaya kecocokan={kecocokan} idJudul="judul-cocok-lebar" />
                        </div>
                    </aside>
                </div>

                <div className="lg:hidden">
                    <BarAksi label="Aksi jalur">
                        {/* Satu baris sampai 320 px (tinjauan akhir W2, W2-F5; prototype 04): simpan berupa
                            tombol ikon 44 px, Banding dan Rencanakan trip berukuran kecil, dan Rencanakan trip
                            mengisi sisa baris dengan label yang membungkus di dalam tombolnya sendiri. */}
                        <div className="flex w-full items-center gap-2">
                            <TombolSimpan jalur={jalurSimpan} tampilkanToast={tampilkanToast} bentuk="bar" />
                            {bandingUrl ? (
                                <Button variant="secondary" size="sm" href={bandingUrl} className="shrink-0">
                                    Banding
                                </Button>
                            ) : null}
                            <Button size="sm" href={rencanaUrl} className="min-w-0 flex-1">
                                Rencanakan trip
                            </Button>
                        </div>
                    </BarAksi>
                </div>

                <p role="status" className="sr-only">
                    {pengumuman}
                </p>
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
```

Hapus berkas `resources/js/components/ui/CheckpointJourney.tsx`.

Hapus berkas `resources/js/components/ui/ElevationProfile.tsx`.

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='TrailDetailPage|CheckpointJourney|ElevationProfileDisplay|HikeSummary|TrailDetailMap|AwaitingData|AmbientFit|CheckpointPaceDisplay|ProgressLadderOnTrailDetail|EmptyStateCoverage'`
Expected: PASS: 76 test, 75 lulus, 1 dilewati. Test yang dilewati adalah `TrailDetailMapTest::test_a_trail_with_geometry_is_drawn`, yang
butuh PostGIS dan melewati dirinya di SQLite sejak sebelum W3.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.701 test, 1.699 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_detail_jalur`. Pada simulasi rencana: 11 test, 11 lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Simpanan

Induk W3 butir 6 (bagian Simpanan); spec bagian 6.6; keputusan nomor 5; butir W2-F1; ruling W3-R39 sampai W3-R41. Kartu
Simpanan ditulis ulang dengan:

- `KotakCentang` berlabel nama jalur;
- label kecocokan;
- tiga angka;
- catatan pribadi "Hanya Anda" dengan "Tambah catatan pribadi" dan "Ubah catatan";
- "Lihat rincian";
- "Hapus dari simpanan" lewat `TombolSimpan` bentuk `hapus`, dengan toast Urungkan milik halaman.

Baki banding mengambang menjadi satu baris ringkas tanpa nama (W3-R40). Halaman menyebut jumlah dan batasnya ("N jalur
tersimpan, maksimal 50. Pilih sampai 3 untuk dibandingkan."). Penilaian kecocokan seluruh simpanan dihitung dalam
satu panggilan.

**Files:**
- Modify: `app/Http/Controllers/Trails/SavedRoutesController.php`, `resources/js/components/trails/TombolSimpan.tsx`, `resources/js/components/trails/banding/AksiPilihan.tsx` (tulis ulang), `resources/js/components/trails/banding/KartuSimpanan.tsx` (tulis ulang), `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/pages/Trails/Saved.tsx` (tulis ulang)
- Test: `tests/Feature/SavedRoutesTest.php`, `tests/Feature/SavedTrailNoteTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `ConsiderationService::simpanan()`, `batasSimpanan()`, `batasBanding()`, `TrailFitService::forTrails()`, rute catatan `trails.saved.note.update` dan `destroy` (W0), `TombolSimpan` (Tugas 4 dan 6), `LabelKecocokan` (Tugas 1), `KotakCentang`, `BarAksi`, `useKerangka()` (W1, W2).
- Produces:
  - `SavedRoutesController::__invoke(Request $request, ConsiderationService $consideration, TrailFitService $fit): Response` dengan props `trails` (setiap baris membawa `fit`, `terbit`, `catatan`), `batas`, `batasBanding`, `batasCatatan`, `kataBelumDinilai`, `pengumuman`, dan judul "Simpanan";
  - `KartuSimpanan({ jalur, batasCatatan, pilihan, onPilih, kataBelumDinilai, tampilkanToast })`;
  - `AksiPilihan({ dipilih, maks, tersedia, pesan = null })`;
  - `TombolSimpan` menerima `bentuk = 'hapus'`;
  - ikon `lock-simple` di registri.

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/SavedRoutesTest.php`:

1. Ganti:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

dengan:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
```

2. Ganti:

```php
            ->where('title', 'Rute Tersimpan')
```

dengan:

```php
            ->where('title', 'Simpanan')
```

3. Ganti:

```php
        $this->assertStringContainsString('Belum ada rute tersimpan', $this->sumber());
        $this->assertStringContainsString('Cari jalur untuk disimpan', $this->sumber());
```

dengan:

```php
        $this->assertStringContainsString('Belum ada jalur tersimpan', $this->sumber());
        $this->assertStringContainsString('Cari jalur di Jelajah', $this->sumber());
```

4. Ganti:

```php
        $this->assertStringContainsString('rute tersimpan, maksimal {batas}.', $this->sumber());
```

dengan:

```php
        $this->assertStringContainsString('`${trails.length} jalur tersimpan, maksimal ${batas}.', $this->sumber());
```

5. Ganti:

```php
        $this->assertStringContainsString('disabled={alasanTidakDapatDipilih !== null}', $kartu);
```

dengan:

```php
        $this->assertStringContainsString('disabled={!jalur.terbit}', $kartu);
```

6. Ganti:

```php
        $this->assertStringContainsString("import { remove } from '@/routes/trails/saved';", $kartu);
```

dengan:

```php
        // Sejak W3 penghapusan lewat TombolSimpan (bentuk "hapus"), rute yang sama dengan Jelajah.
        $this->assertStringContainsString('bentuk="hapus"', $kartu);
        $this->assertStringContainsString("import { remove, store } from '@/routes/trails/saved';", $this->sumber('js/components/trails/TombolSimpan.tsx'));
```

7. Ganti:

```php
    }

    /**
     * Tanpa centang, tautannya telanjang: halaman Banding lalu membandingkan tiga simpanan
```

dengan:

```php
    }

    /**
     * Baki banding di layar sempit adalah ringkasan satu baris (tinjauan akhir W2, W2-F1). Baki
     * Jelajah lama yang mendaftar semua nama tersimpan membuat lapisan bawah menutup 40 sampai
     * 57 persen layar 320 sampai 360 px. Baki Simpanan hanya membawa hitungan, ruasnya, pesan
     * batas, dan satu aksi pada satu waktu; nama jalur yang dipilih sudah terlihat dari centang
     * di kartunya, jadi tidak diulang dan baki tidak membungkus menjadi daftar.
     */
    public function test_the_compare_tray_is_a_one_row_summary_without_names(): void
    {
        $aksi = $this->sumber('js/components/trails/banding/AksiPilihan.tsx');

        $this->assertStringContainsString('<div className="flex w-full items-center gap-3">', $aksi);
        $this->assertStringNotContainsString('flex-wrap', $aksi, 'Baki tidak boleh membungkus aksinya ke baris baru.');
        $this->assertStringContainsString('dipilih: number[];', $aksi, 'Baki hanya menerima id pilihan, bukan nama.');
        $this->assertDoesNotMatchRegularExpression('/\{[^}]*\.(?:name|nama)\b/', $aksi, 'Nama jalur tidak diulang di baki.');
        $this->assertSame(3, substr_count($aksi, 'className="shrink-0"') + substr_count($aksi, 'shrink-0 text-meta'), 'Setiap keadaan menampilkan tepat satu aksi atau petunjuk yang tidak menyusut.');
        $this->assertStringContainsString('<BarAksi label="Pilihan banding">', $this->sumber());
    }

    /**
     * Tanpa centang, tautannya telanjang: halaman Banding lalu membandingkan tiga simpanan
```

8. Ganti:

```php
        $this->assertStringContainsString('remove.url({ trail: ', $this->sumber('js/components/trails/banding/KartuSimpanan.tsx'));
```

dengan:

```php
        $this->assertStringContainsString('remove.url({ trail: ', $this->sumber('js/components/trails/TombolSimpan.tsx'));
```

9. Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Prototype 06: setiap kartu membawa label kecocokan dasar pembacanya, dinilai sekaligus
     * untuk seluruh simpanan, tanpa skor (BR-09). Jalur yang ditarik dari terbit tidak dinilai.
     */
    public function test_each_saved_trail_carries_the_readers_fit_label(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);
        $user = $user->fresh();
        $terbit = $this->jalur();
        $ditarik = $this->jalur();
        app(ConsiderationService::class)->toggle($user, $terbit);
        app(ConsiderationService::class)->toggle($user, $ditarik);
        $ditarik->forceFill(['is_published' => false])->saveQuietly();

        $trails = collect($this->daftar($user)->viewData('page')['props']['trails'])->keyBy('id');

        $this->assertContains($trails[$terbit->id]['fit']['label'], ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK']);
        $this->assertNull($trails[$ditarik->id]['fit']);
        $this->assertStringNotContainsString('internalScore', json_encode($trails->all()));

        $this->daftar(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page->where('kataBelumDinilai', 'Belum dinilai'));
    }

    /**
     * Ruling W2-5i: kartu hilang oleh penghapusannya sendiri, jadi halaman yang memiliki toast
     * Urungkan dan meneruskannya ke kartu.
     */
    public function test_the_page_owns_the_undo_toast_for_removals(): void
    {
        $this->assertStringContainsString('const { tampilkanToast } = useKerangka();', $this->sumber());
        $this->assertStringContainsString('tampilkanToast={tampilkanToast}', $this->sumber());
        $this->assertStringContainsString('tampilkanToast={tampilkanToast} bentuk="hapus"', $this->sumber('js/components/trails/banding/KartuSimpanan.tsx'));
    }

    /**
     * Melewati batas banding tidak dimatikan diam-diam: centangnya tetap aktif dan bakinya
     * menyebut batasnya (prototype 06). Centang memakai KotakCentang berlabel nama jalur di
     * dalam kelompok yang menyebut batasnya (keputusan koordinator W3 nomor 5). Pesannya
     * pendek supaya baki tetap satu baris di 320 px (W2-F1).
     */
    public function test_going_past_the_comparison_limit_is_explained(): void
    {
        $halaman = $this->sumber();
        $kartu = $this->sumber('js/components/trails/banding/KartuSimpanan.tsx');

        $this->assertStringContainsString('setPesan(`Maksimal ${batasBanding}. Lepas satu dulu.`);', $halaman);
        $this->assertStringContainsString('<AksiPilihan dipilih={pilihanBerlaku} maks={batasBanding} tersedia={tersedia} pesan={pesan} />', $halaman);
        $this->assertStringContainsString('<legend className="sr-only">Pilih sampai {batasBanding} jalur untuk dibandingkan</legend>', $halaman);
        $this->assertStringContainsString('<KotakCentang', $kartu);
        $this->assertStringContainsString('label={jalur.name}', $kartu);
        $this->assertStringNotContainsString('<Checkbox', $kartu);
    }

    /**
     * Anggaran kueri (keputusan koordinator W3 nomor 12): penilaian kecocokan seluruh simpanan
     * batch, jadi jumlah kueri tidak tumbuh bersama jumlah simpanan.
     */
    public function test_the_page_does_not_grow_with_the_number_of_saved_trails(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);
        $user = $user->fresh();
        app(ConsiderationService::class)->toggle($user, $this->jalur());
        app(ConsiderationService::class)->toggle($user, $this->jalur());

        $this->daftar($user);
        $sedikit = $this->hitungKueri($user);

        foreach (range(1, 10) as $i) {
            app(ConsiderationService::class)->toggle($user, $this->jalur());
        }

        $this->assertSame($sedikit, $this->hitungKueri($user));
    }

    private function hitungKueri(User $user): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->daftar($user);
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
```

`tests/Feature/SavedTrailNoteTest.php`:

1. Ganti:

```php
        $this->assertStringContainsString('grid-cols-[2.75rem_minmax(0,1fr)]', $kartu);
```

dengan:

```php
        $this->assertStringContainsString('grid grid-cols-[minmax(0,1fr)]', $kartu);
```

2. Ganti:

```php
     * note. Ketika jalur ini punya catatan, "Hapus dari simpanan" harus bertanya dulu dan
     * menyebut bahwa catatannya ikut terhapus, mengikuti pola window.confirm yang sudah
     * ada di HapusRencana.tsx. Tanpa catatan, perilaku lama (langsung hapus) tetap dijaga
     * oleh SavedRoutesTest::test_a_trail_can_be_removed_from_the_list.
```

dengan:

```php
     * note. Ketika jalur ini punya catatan, "Hapus dari simpanan" bertanya dulu dan menyebut
     * bahwa catatannya ikut terhapus. Sejak W3 pertanyaannya dialog TombolSimpan, dan
     * Urungkan mengembalikan catatannya (SavedUndoTest); tanpa catatan, langsung hapus.
```

3. Ganti:

```php
        $mulai = strpos($kartu, 'const hapus = () => {');

        $this->assertNotFalse($mulai, 'hapus() tidak ditemukan.');

        $isiHapus = substr($kartu, $mulai, strpos($kartu, '};', $mulai) - $mulai);

        $this->assertStringContainsString('jalur.catatan && !window.confirm(', $isiHapus);
        $this->assertStringContainsString('ikut terhapus', $isiHapus);
```

dengan:

```php
        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        $this->assertStringContainsString('adaCatatan: jalur.catatan !== null', $kartu);
        $this->assertStringContainsString('} else if (jalur.adaCatatan) {', $tombol);
        $this->assertStringContainsString('keterangan="Catatan pribadi pada jalur ini ikut terhapus.', $tombol);
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

Ganti:

```php
    public function test_jelajah_tanpa_jalur_terbit(): void
```

dengan:

```php
    public function test_simpanan(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('simpanan', $this->get(self::ASAL.'/trails/saved'), 'Trails/Saved');
    }

    public function test_jelajah_tanpa_jalur_terbit(): void
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='SavedRoutes|SavedTrailNote|DesignSystem'`
Expected: FAIL: 58 test, 47 lulus, 10 gagal, 1 error. Antara lain `SavedRoutesTest::test_the_list_shows_each_saved_trail_with_distance_duration_and_difficulty`, `SavedRoutesTest::test_an_empty_list_explains_how_to_save_a_trail`, `SavedRoutesTest::test_the_list_states_its_size_against_the_configured_limits`.

- [ ] **Step 3: Tulis implementasinya.**

**Server**

`app/Http/Controllers/Trails/SavedRoutesController.php`:

1. Ganti:

```php
use App\Http\Controllers\Controller;
```

dengan:

```php
use App\Enums\RouteFitLabel;
use App\Http\Controllers\Controller;
```

2. Ganti:

```php
use App\Services\ConsiderationService;
```

dengan:

```php
use App\Services\ConsiderationService;
use App\Services\TrailFitService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
```

3. Ganti:

```php
 * yang ditolak server.
```

dengan:

```php
 * yang ditolak server.
 *
 * Sejak W3 setiap kartu membawa label kecocokan dasar pembacanya (prototype 06), dinilai
 * sekaligus untuk seluruh simpanan (TrailFitService, batch), tanpa skor (BR-09).
```

4. Ganti:

```php
    public function __invoke(Request $request, ConsiderationService $consideration): Response
    {
        $simpanan = $consideration->simpanan($request->user());

        return Inertia::render('Trails/Saved', [
            'title' => 'Rute Tersimpan',
            'trails' => $simpanan->map(fn (TrailConsideration $baris) => [
                ...(new TrailRowResource($baris->trail, null, true))->resolve($request),
```

dengan:

```php
    public function __invoke(Request $request, ConsiderationService $consideration, TrailFitService $fit): Response
    {
        $pembaca = $request->user();
        $simpanan = $consideration->simpanan($pembaca);
        $jalur = $simpanan->pluck('trail')->filter(fn ($t) => $t->is_published)->values();
        $kecocokan = $pembaca->hasCompletedProfile() ? $fit->forTrails($pembaca, new EloquentCollection($jalur->all())) : [];

        return Inertia::render('Trails/Saved', [
            'title' => 'Simpanan',
            'trails' => $simpanan->map(fn (TrailConsideration $baris) => [
                ...(new TrailRowResource($baris->trail, $kecocokan[$baris->trail_id] ?? null, true))->resolve($request),
```

5. Ganti:

```php
            'batasCatatan' => TrailConsideration::CATATAN_MAKS,
```

dengan:

```php
            'batasCatatan' => TrailConsideration::CATATAN_MAKS,
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
```

**Antarmuka**

`resources/js/components/trails/TombolSimpan.tsx`:

1. Ganti:

```tsx
     * lebar.
     */
    bentuk?: 'ikon' | 'bar' | 'tombol';
```

dengan:

```tsx
     * lebar, "hapus" untuk kartu Simpanan (setiap jalur di sana sudah tersimpan, jadi aksinya
     * menyebut dirinya penghapusan).
     */
    bentuk?: 'ikon' | 'bar' | 'tombol' | 'hapus';
```

2. Ganti:

```tsx
            {bentuk === 'ikon' ? (
```

dengan:

```tsx
            {bentuk === 'hapus' ? (
                <Button variant="ghost" size="sm" className="-ml-4" processing={memproses} onClick={tekan}>
                    Hapus dari simpanan<span className="sr-only">: {jalur.name}</span>
                </Button>
            ) : bentuk === 'ikon' ? (
```

Ganti seluruh isi `resources/js/components/trails/banding/AksiPilihan.tsx` dengan:

```tsx
import { Button } from '@/components/ui/Button';
import { compare } from '@/routes/trails';

interface AksiPilihanProps {
    /** Id jalur yang dicentang, dalam urutan dicentang: urutan kolom di halaman Banding. */
    dipilih: number[];
    /** Batas banding dari server (hiking.saved_trails.compare_max, DK-05). */
    maks: number;
    /** Jalur terbit yang dibandingkan bila tidak ada yang dicentang. */
    tersedia: number;
    /** Pesan pendek sesudah pembaca mencoba melewati batas; null bila tidak ada. */
    pesan?: string | null;
}

/**
 * Baki banding Simpanan (DK-05, spec v3 bagian 6.6, prototype 06), satu baris ringkas di
 * dalam bar aksi. Baki Jelajah lama yang mendaftar semua nama tersimpan membuat lapisan bawah
 * menutup 40 sampai 57 persen layar 320 sampai 360 px (tinjauan akhir W2, W2-F1), jadi di
 * sini tidak ada nama: centangnya sudah terlihat di kartu. Isinya hitungan "Dipilih N dari 3"
 * dengan ruasnya, dan satu aksi; pesan batas yang pendek menggantikan hitungan itu sesaat,
 * supaya bakinya tidak bertambah tinggi. Dengan dua centang atau lebih, pilihannya dibawa di
 * ?trails= dalam urutan dicentang. Dengan satu centang, aksinya diganti petunjuk "Pilih
 * minimal 2 jalur". Tanpa centang, tautannya telanjang (`compare.url()`) dan halaman Banding
 * membandingkan simpanan terbaru yang masih terbit.
 *
 * Hitungan, petunjuk, dan pesannya ada di satu pengumuman sebaris <p role="status">, supaya
 * pembaca layar mendengar setiap perubahan sebelum batasnya ditabrak.
 */
export function AksiPilihan({ dipilih, maks, tersedia, pesan = null }: AksiPilihanProps) {
    const jumlah = dipilih.length;
    const petunjuk = jumlah === 1 ? 'Pilih minimal 2 jalur untuk membandingkan.' : jumlah >= maks ? 'Batas banding tercapai.' : null;

    return (
        <div className="flex w-full items-center gap-3">
            <p role="status" className="min-w-0 flex-1 text-kecil font-bold text-primary">
                {pesan ? (
                    pesan
                ) : (
                    <>
                        Dipilih {jumlah} dari {maks}.{petunjuk ? <span className="sr-only"> {petunjuk}</span> : null}
                    </>
                )}
                {/* Ruas hitungan: bentuk pembantu, hitungannya sudah tertulis di atas. */}
                <span aria-hidden="true" className="mt-1.5 flex gap-1">
                    {Array.from({ length: maks }, (_, i) => (
                        <span key={i} className={i < jumlah ? 'h-1.5 w-6 rounded-full bg-brand-ink' : 'h-1.5 w-6 rounded-full bg-surface-sunken ring-1 ring-inset ring-control'} />
                    ))}
                </span>
            </p>

            {jumlah >= 2 ? (
                <Button size="sm" href={compare.url({ query: { trails: dipilih.join(',') } })} className="shrink-0">
                    Bandingkan {jumlah} jalur
                </Button>
            ) : jumlah === 1 ? (
                <p aria-hidden="true" className="max-w-36 shrink-0 text-meta text-secondary">
                    Pilih minimal 2 jalur
                </p>
            ) : tersedia >= 2 ? (
                <Button size="sm" variant="secondary" href={compare.url()} className="shrink-0">
                    Bandingkan {Math.min(tersedia, maks)} terbaru
                </Button>
            ) : null}
        </div>
    );
}
```

Ganti seluruh isi `resources/js/components/trails/banding/KartuSimpanan.tsx` dengan:

```tsx
import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { TombolSimpan } from '@/components/trails/TombolSimpan';
import type { JalurTersimpan } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { InputError, Label, Textarea } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import { KotakCentang } from '@/components/ui/KotakCentang';
import { Tautan } from '@/components/ui/Tautan';
import type { LayananKerangka } from '@/layouts/Kerangka';
import { kelas } from '@/lib/kelas';
import { destroy as ruteHapusCatatan, update as ruteSimpanCatatan } from '@/routes/trails/saved/note';

interface KartuSimpananProps {
    jalur: JalurTersimpan;
    /** Batas panjang catatan dari server (TrailConsideration::CATATAN_MAKS). */
    batasCatatan: number;
    /** Keadaan centang banding, atau null bila simpanan belum cukup untuk dibandingkan. */
    pilihan: { dipilih: boolean; penuh: boolean } | null;
    onPilih: (id: number) => void;
    /** Kata UNKNOWN dari server, untuk jalur yang kecocokannya tidak dihitung. */
    kataBelumDinilai: string;
    /** Toast Urungkan milik halaman Simpanan: kartunya hilang oleh penghapusannya sendiri (W2-5i). */
    tampilkanToast: LayananKerangka['tampilkanToast'];
}

/**
 * Satu jalur tersimpan (spec v3 bagian 6.6, prototype 06): centang banding berlabel nama
 * jalurnya (KotakCentang, bukan kotak 28 px tanpa label), label kecocokan, tiga angka,
 * catatan pribadi (FR-22), lalu aksi: tulis atau ubah catatan, lihat rincian, dan hapus dari
 * simpanan dengan urungkan.
 *
 * Catatan hanya milik pemiliknya: tampil di sini, tidak pernah ikut ke halaman Banding
 * maupun tautan bagikan, dan dirender sebagai teks. Kolom isi boleh menyempit sampai nol
 * (minmax(0,1fr)) dan catatannya memakai overflow-wrap: anywhere, jadi untaian tanpa spasi
 * tidak melebarkan halaman di layar 360 px.
 *
 * Jalur yang ditarik dari terbit tetap tampil supaya catatannya tidak hilang, tetapi tidak
 * dapat dicentang: halaman Banding hanya membandingkan jalur terbit (R-022). Ketika batas
 * banding tercapai, centang lain tidak dimatikan: menekannya menyebut batasnya di baki (halaman
 * yang memutuskan), karena kontrol yang mati tidak dapat menjelaskan dirinya.
 *
 * Fokus mengikuti status sunting (WCAG 2.4.3): membuka editor memindahkan fokus ke textarea,
 * dan menutupnya (batal, simpan sukses, atau hapus catatan sukses) mengembalikan fokus ke
 * tombol pemicu. Menghapus jalur yang bercatatan bertanya dulu (TombolSimpan), dan Urungkan
 * mengembalikan jalur beserta catatannya.
 */
export function KartuSimpanan({ jalur, batasCatatan, pilihan, onPilih, kataBelumDinilai, tampilkanToast }: KartuSimpananProps) {
    const [menyunting, setMenyunting] = useState(false);
    const [menghapusCatatan, setMenghapusCatatan] = useState(false);
    const form = useForm({ catatan: jalur.catatan ?? '' });
    const pertama = useRef(true);

    const idCatatan = `catatan-${jalur.id}`;
    const idPemicu = `pemicu-catatan-${jalur.id}`;
    const idAlasan = `pilih-${jalur.id}-hint`;
    const panjang = Array.from(form.data.catatan).length;
    const wilayah = [jalur.mountain, jalur.province].filter(Boolean).join(', ');
    const alasanTidakDapatDipilih = pilihan === null ? null : !jalur.terbit ? 'Jalur ini sedang tidak terbit, jadi tidak dapat dibandingkan.' : null;
    const angka = [jalur.jarak, jalur.tanjakan === '-' ? null : `↑${jalur.tanjakan}`, jalur.durasi].filter(Boolean).join(', ');

    // Baru berjalan mulai perubahan kedua (pertama.current): render pertama tidak boleh
    // mencuri fokus halaman ketika Simpanan baru dibuka.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;

            return;
        }

        document.getElementById(menyunting ? idCatatan : idPemicu)?.focus();
    }, [menyunting, idCatatan, idPemicu]);

    const mulaiSunting = () => {
        form.setData('catatan', jalur.catatan ?? '');
        form.clearErrors();
        setMenyunting(true);
    };

    const batalSunting = () => {
        form.setData('catatan', jalur.catatan ?? '');
        form.clearErrors();
        setMenyunting(false);
    };

    const simpanCatatan = (e: FormEvent) => {
        e.preventDefault();
        form.put(ruteSimpanCatatan.url({ trail: jalur.id }), {
            preserveScroll: true,
            onSuccess: () => setMenyunting(false),
            onError: (galat) => fokusGalat(galat, [['catatan', idCatatan]]),
        });
    };

    const hapusCatatan = () => {
        setMenghapusCatatan(true);
        router.delete(ruteHapusCatatan.url({ trail: jalur.id }), {
            preserveScroll: true,
            onSuccess: () => setMenyunting(false),
            onFinish: () => setMenghapusCatatan(false),
        });
    };

    return (
        <li className={kelas('grid grid-cols-[minmax(0,1fr)] rounded-lg border bg-surface p-4', pilihan?.dipilih ? 'border-brand-ink' : 'border-subtle')}>
            <div className="min-w-0 space-y-2">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        {pilihan === null ? (
                            <>
                                <p className="text-subjudul text-primary">{jalur.name}</p>
                                <p className="text-meta text-secondary">{wilayah}</p>
                            </>
                        ) : (
                            <KotakCentang
                                label={jalur.name}
                                keterangan={wilayah}
                                checked={pilihan.dipilih}
                                disabled={!jalur.terbit}
                                onChange={() => onPilih(jalur.id)}
                                aria-describedby={alasanTidakDapatDipilih ? idAlasan : undefined}
                                className="py-0"
                            />
                        )}
                    </div>
                    <div className="shrink-0 pt-0.5">
                        <LabelKecocokan nilai={jalur.fit?.label ?? null} label={jalur.fit?.labelText ?? kataBelumDinilai} />
                    </div>
                </div>

                <p data-angka="" className="text-kecil text-primary">
                    {angka}
                </p>

                {alasanTidakDapatDipilih ? (
                    <p id={idAlasan} className="text-meta text-muted">
                        {alasanTidakDapatDipilih}
                    </p>
                ) : null}

                {menyunting ? (
                    <form onSubmit={simpanCatatan} className="space-y-2" noValidate>
                        <Label htmlFor={idCatatan}>Catatan pribadi</Label>
                        <Textarea
                            id={idCatatan}
                            name="catatan"
                            rows={3}
                            value={form.data.catatan}
                            onChange={(e) => form.setData('catatan', e.target.value)}
                            aria-invalid={form.errors.catatan ? true : undefined}
                            aria-describedby={form.errors.catatan ? `catatan-${jalur.id}-hint catatan-${jalur.id}-error` : `catatan-${jalur.id}-hint`}
                        />
                        <p id={`catatan-${jalur.id}-hint`} className={kelas('text-meta', panjang > batasCatatan ? 'font-bold text-danger-900' : 'text-muted')}>
                            Hanya Anda yang dapat membaca catatan ini. {panjang} dari {batasCatatan} karakter.
                        </p>
                        <InputError id={`catatan-${jalur.id}-error`} messages={form.errors.catatan} />
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" size="sm" processing={form.processing}>
                                Simpan catatan
                            </Button>
                            {jalur.catatan ? (
                                <Button variant="ghost" size="sm" processing={menghapusCatatan} onClick={hapusCatatan}>
                                    Hapus catatan
                                </Button>
                            ) : null}
                            <Button variant="ghost" size="sm" onClick={batalSunting}>
                                Batal
                            </Button>
                        </div>
                    </form>
                ) : jalur.catatan ? (
                    <div className="rounded-control bg-surface-sunken px-3 py-2">
                        <p className="flex items-center gap-1.5 text-meta font-bold text-secondary">
                            <Icon name="lock-simple" size={16} />
                            Catatan pribadi, hanya Anda
                        </p>
                        <p className="mt-1 whitespace-pre-line text-kecil text-primary [overflow-wrap:anywhere]">{jalur.catatan}</p>
                    </div>
                ) : null}

                <div className="flex flex-wrap items-center gap-x-2">
                    {menyunting ? null : (
                        <Button id={idPemicu} variant="ghost" size="sm" className="-ml-4" onClick={mulaiSunting}>
                            {jalur.catatan ? 'Ubah catatan' : 'Tambah catatan pribadi'}
                            <span className="sr-only"> untuk {jalur.name}</span>
                        </Button>
                    )}
                    <Tautan href={jalur.url} className="inline-flex min-h-11 items-center px-2 text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                        Lihat rincian<span className="sr-only"> {jalur.name}</span>
                    </Tautan>
                    <TombolSimpan jalur={{ id: jalur.id, name: jalur.name, ditimbang: true, adaCatatan: jalur.catatan !== null }} tampilkanToast={tampilkanToast} bentuk="hapus" />
                </div>
            </div>
        </li>
    );
}
```

`resources/js/components/ui/ikon/phosphor.ts`:

1. Ganti:

```ts
    | 'list'
```

dengan:

```ts
    | 'list'
    | 'lock-simple'
```

2. Ganti:

```ts
    'magnifying-glass': {
```

dengan:

```ts
    'lock-simple': {
        regular: 'M208,80H176V56a48,48,0,0,0-96,0V80H48A16,16,0,0,0,32,96V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V96A16,16,0,0,0,208,80ZM96,56a32,32,0,0,1,64,0V80H96ZM208,208H48V96H208V208Z',
        bold: 'M208,76H180V56A52,52,0,0,0,76,56V76H48A20,20,0,0,0,28,96V208a20,20,0,0,0,20,20H208a20,20,0,0,0,20-20V96A20,20,0,0,0,208,76ZM100,56a28,28,0,0,1,56,0V76H100ZM204,204H52V100H204Z',
    },
    'magnifying-glass': {
```

Ganti seluruh isi `resources/js/pages/Trails/Saved.tsx` dengan:

```tsx
import { usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { AksiPilihan } from '@/components/trails/banding/AksiPilihan';
import { KartuSimpanan } from '@/components/trails/banding/KartuSimpanan';
import type { JalurTersimpan } from '@/components/trails/types';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { useKerangka } from '@/layouts/Kerangka';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trails: JalurTersimpan[];
    batas: number;
    batasBanding: number;
    batasCatatan: number;
    kataBelumDinilai: string;
    pengumuman: string;
}

/**
 * Simpanan (spec v3 bagian 6.6, prototype 06; DK-05: simpan longgar, banding sempit), dari
 * simpanan di basis data: tetap ada setelah keluar dan masuk lagi, juga di perangkat lain.
 *
 * Setiap kartu membawa centang banding berlabel nama jalurnya, di dalam satu kelompok yang
 * menyebut batasnya. Baki banding melayang di atas bilah tab sebagai satu baris ringkas
 * (AksiPilihan): hitungan "Dipilih N dari 3", lalu "Bandingkan N jalur", atau tanpa centang
 * "Bandingkan N terbaru". Mencentang lebih dari batas tidak dimatikan diam-diam: bakinya
 * menyebut batasnya dengan kalimat pendek. Centang baru ditawarkan bila ada minimal dua
 * simpanan yang masih terbit (jalur yang ditarik dari terbit tidak dapat dicentang, jadi tidak
 * dihitung).
 *
 * Halaman ini pemilik toast Urungkan penghapusan (ruling W2-5i): kartunya hilang oleh
 * aksinya sendiri, jadi useKerangka dipanggil di sini dan diteruskan ke kartu.
 */
export default function Saved() {
    const { trails, batas, batasBanding, batasCatatan, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const { tampilkanToast } = useKerangka();
    const [dipilih, setDipilih] = useState<number[]>([]);
    const [pesan, setPesan] = useState<string | null>(null);
    const tersedia = trails.filter((t) => t.terbit).length;
    const berlaku = (ids: number[]) => ids.filter((id) => trails.some((t) => t.id === id && t.terbit));
    // Jalur yang baru dihapus atau ditarik dari terbit tidak boleh tertinggal di pilihan.
    const pilihanBerlaku = berlaku(dipilih);

    const pilih = (id: number) => {
        const d = berlaku(dipilih);

        if (d.includes(id)) {
            setDipilih(d.filter((x) => x !== id));
            setPesan(null);
        } else if (d.length >= batasBanding) {
            setPesan(`Maksimal ${batasBanding}. Lepas satu dulu.`);
        } else {
            setDipilih([...d, id]);
            setPesan(null);
        }
    };

    return (
        <div className="py-6">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: trailsIndex.url(), label: 'Jelajah' }}
                    title="Simpanan"
                    description={`${trails.length} jalur tersimpan, maksimal ${batas}. Pilih sampai ${batasBanding} untuk dibandingkan.`}
                />

                {trails.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada jalur tersimpan"
                        description="Tekan tombol simpan pada kartu jalur di Jelajah. Jalur yang Anda simpan terkumpul di sini, bisa diberi catatan pribadi, dan dibandingkan berdampingan."
                    >
                        <Button href={trailsIndex.url()}>Cari jalur di Jelajah</Button>
                    </EmptyState>
                ) : (
                    <>
                        {tersedia >= 2 ? null : <p className="mb-3 text-kecil text-secondary">Simpan satu jalur lagi untuk membandingkan.</p>}

                        <fieldset>
                            <legend className="sr-only">Pilih sampai {batasBanding} jalur untuk dibandingkan</legend>
                            <ul className="space-y-3">
                                {trails.map((t) => (
                                    <KartuSimpanan
                                        key={t.id}
                                        jalur={t}
                                        batasCatatan={batasCatatan}
                                        pilihan={tersedia >= 2 ? { dipilih: pilihanBerlaku.includes(t.id), penuh: pilihanBerlaku.length >= batasBanding } : null}
                                        onPilih={pilih}
                                        kataBelumDinilai={kataBelumDinilai}
                                        tampilkanToast={tampilkanToast}
                                    />
                                ))}
                            </ul>
                        </fieldset>

                        {tersedia >= 2 ? (
                            <BarAksi label="Pilihan banding">
                                <AksiPilihan dipilih={pilihanBerlaku} maks={batasBanding} tersedia={tersedia} pesan={pesan} />
                            </BarAksi>
                        ) : null}
                    </>
                )}

                <p role="status" className="sr-only">
                    {pengumuman}
                </p>
            </div>
        </div>
    );
}

Saved.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='SavedRoutes|SavedTrailNote|DesignSystem'`
Expected: PASS: 58 test, 58 lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.706 test, 1.704 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_simpanan`. Pada simulasi rencana: 12 test, 12 lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Tautan bagikan perbandingan (FR-23)

Induk W3 butir 6 (bagian tautan bagikan); spec bagian 9.3; PRD §148; keputusan nomor 10; ruling W3-R42 sampai W3-R44.
Bagiannya:

- tabel `comparison_shares` (hash token, pemilik, daftar jalur, `expires_at`, `revoked_at`) dengan RLS di pgsql;
- `TautanBagikan` untuk membuat, mendaftar tautan aktif, membuka, dan mencabut;
- `POST trails/shares` untuk membuat dan `DELETE trails/shares/{share}` untuk mencabut, milik pemilik saja;
- halaman publik `GET /b/{token}` yang tidak diindeks, tanpa catatan, label kecocokan, atau nama pembuatnya, dengan
  ajakan mendaftar atau masuk;
- batas laju pembuatan dan pembukaan.

Tombol "Bagikan" baru tampil di Simpanan setelah rutenya ada (DK-40). Lembar bagikan menangani galat jaringannya
sendiri (W2-5e), dan token hanya ditunjukkan sekali lewat flash `tautanBagikan`. Migrasinya diuji di SQLite dan
PostgreSQL CI, dan tidak dijalankan di Supabase.

**Files:**
- Create: `app/Http/Controllers/Trails/ComparisonShareController.php`, `app/Http/Requests/Trails/StoreComparisonShareRequest.php`, `app/Models/ComparisonShare.php`, `app/Services/Trails/TautanBagikan.php`, `database/factories/ComparisonShareFactory.php`, `database/migrations/2026_09_28_000100_create_comparison_shares_table.php`, `resources/js/components/trails/banding/LembarBagikan.tsx`, `resources/js/pages/Public/SharedComparison.tsx`
- Modify: `app/Http/Controllers/Trails/SavedRoutesController.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `config/hiking.php`, `database/README.md`, `resources/js/components/ui/ikon/phosphor.ts`, `resources/js/lib/bukanHalamanReact.json`, `resources/js/pages/Trails/Saved.tsx`, `resources/js/types/index.d.ts`, `routes/web.php`
- Test: `tests/Feature/ComparisonShareTest.php` (baru), `tests/Spatial/ComparisonShareSchemaTest.php` (baru), `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/PageHeaderContractTest.php`, `tests/Feature/RouteAccessMatrixTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `RouteComparisonTable::baris()` dan `StatusJalurRingkas::untuk()` (Tugas 1 dan sebelumnya), `ConsiderationService::simpanan()`, `SavedRoutesController` (Tugas 7), `LembarBawah`, `TombolSalin` atau salin papan klip, `InputError` (W1), `Tautan` dan registri rute non-Inertia `resources/js/lib/bukanHalamanReact.json`.
- Produces:
  - migrasi `database/migrations/2026_09_28_000100_create_comparison_shares_table.php` dan `App\Models\ComparisonShare` (`token_hash` tersembunyi, cast `trail_ids` larik, `user()`, `scopeBerlaku()`), dengan `ComparisonShareFactory`;
  - `App\Services\Trails\TautanBagikan::buat(User $user, Collection $trails): ?array` (token dan barisnya, atau null bila batas tautan aktif tercapai), `aktif(User $user): Collection`, `buka(string $token): ?ComparisonShare`, `cabut(ComparisonShare $share): void`;
  - `App\Http\Requests\Trails\StoreComparisonShareRequest` (`trails` larik 2 sampai `compare_max` id);
  - `ComparisonShareController::store()`, `destroy(Request $request, int $share, TautanBagikan $tautan)`, dan `show(Request $request, string $token, ...)`;
  - rute `shares.show` (`GET b/{token}`, publik, token `[A-Za-z0-9_-]{32}`), `shares.store`, dan `shares.destroy` (grup `auth`);
  - flash bersama `tautanBagikan: { id: number; url: string; berlakuSampai: string } | null`;
  - prop Simpanan `tautanAktif: TautanAktif[]` dengan `interface TautanAktif { id; jumlahJalur; dibuat; berlakuSampai }`;
  - `LembarBagikan({ terbuka, onTutup, pemicu, jalur, tautanAktif })`;
  - halaman `resources/js/pages/Public/SharedComparison.tsx`;
  - `hiking.share_links.ttl_days` menjadi 7.

- [ ] **Step 1: Tulis test yang gagal.** Test Spatial `ComparisonShareSchemaTest` melewati dirinya tanpa
  `SPATIAL_TEST_DSN` dan baru berjalan di CI.

Buat `tests/Feature/ComparisonShareTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ComparisonShare;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Tautan bagikan perbandingan (FR-23, spec v3 bagian 9.3, PRD §148; W3 Tugas 8).
 *
 * Yang dijaga: token acak minimal 128 bit yang tidak pernah tersimpan (hanya hash-nya);
 * tautan kedaluwarsa, dicabut, atau salah tidak dapat dibuka dan dijawab sama; hanya
 * pemiliknya yang dapat mencabut; halaman publik tidak diindeks, tidak membawa catatan
 * pribadi, label kecocokan, atau nama pembuatnya; pembuatan dan pembukaan dibatasi laju.
 */
class ComparisonShareTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(string $nama): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create(['name' => 'Gunung '.$nama]))->published()->create(['name' => $nama]);
    }

    private function pendakiBerprofil(): User
    {
        $user = User::factory()->create(['name' => 'Pembuat Rahasia']);
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);

        return $user->fresh();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array{0: string, 1: TestResponse}
     */
    private function buat(User $user, array $ids): array
    {
        $respons = $this->actingAs($user)->from(route('trails.saved'))->post(route('shares.store'), ['trails' => $ids]);
        $url = session('tautan-bagikan')['url'] ?? '';

        return [$url, $respons];
    }

    public function test_the_token_is_long_random_and_only_its_hash_is_stored(): void
    {
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('Jalur A');
        $b = $this->jalur('Jalur B');

        [$url, $respons] = $this->buat($user, [$a->id, $b->id]);

        $respons->assertRedirect(route('trails.saved'))->assertSessionHas('pengumuman', 'Tautan bagikan dibuat.');
        $this->assertMatchesRegularExpression('#/b/[A-Za-z0-9_-]{32}$#', $url);

        $token = basename($url);
        $baris = ComparisonShare::sole();

        $this->assertGreaterThanOrEqual(128, strlen(base64_decode(strtr($token, '-_', '+/'))) * 8, 'Token harus paling sedikit 128 bit.');
        $this->assertSame(hash('sha256', $token), $baris->token_hash);
        $this->assertStringNotContainsString($token, json_encode($baris->getAttributes()));
        $this->assertSame([$a->id, $b->id], $baris->trail_ids);
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $baris->expires_at->timestamp, 5);
    }

    /**
     * PRD §148: halaman publik hanya memuat data publik jalur beserta sumber dan waktunya;
     * tanpa catatan pribadi, tanpa label kecocokan, tanpa nama atau email pembuatnya, dan
     * tidak diindeks.
     */
    public function test_the_public_page_is_read_only_unindexed_and_carries_nothing_private(): void
    {
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('Jalur A');
        $b = $this->jalur('Jalur B');
        app(ConsiderationService::class)->toggle($user, $a);
        TrailConsideration::where('trail_id', $a->id)->first()->forceFill(['note' => 'Catatan rahasia saya'])->save();
        [$url] = $this->buat($user, [$b->id, $a->id]);
        auth()->logout();

        $respons = $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/SharedComparison')
            ->where('berlaku', true)
            ->where('jalur.0.nama', 'Jalur B')
            ->where('jalur.1.nama', 'Jalur A')
            ->has('jalur.0.status.label')
            ->where('bandingUrl', null)
            ->has('baris'));

        $isi = $respons->getContent();

        foreach (['Catatan rahasia saya', 'Pembuat Rahasia', $user->email, '"fit"', 'labelText', 'COCOK', 'KURANG_COCOK', 'PERLU_PERSIAPAN'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $isi, "Halaman publik membawa {$terlarang}.");
        }

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow" />', File::get(resource_path('js/pages/Public/SharedComparison.tsx')));
    }

    /**
     * Membuka tautan tidak pernah menulis apa pun: tidak ke simpanan pembacanya, tidak ke
     * tautannya sendiri.
     */
    public function test_opening_a_link_writes_nothing(): void
    {
        [$url] = $this->buat($this->pendakiBerprofil(), [$this->jalur('A')->id, $this->jalur('B')->id]);
        $pembaca = User::factory()->create();
        $sebelum = ComparisonShare::sole()->updated_at;

        $this->actingAs($pembaca)->get($url)->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('bandingUrl', fn ($u) => str_contains((string) $u, '/trails/compare')));

        $this->assertSame(0, TrailConsideration::where('user_id', $pembaca->id)->count());
        $this->assertEquals($sebelum, ComparisonShare::sole()->updated_at);
    }

    /**
     * Kedaluwarsa, dicabut, dan tidak dikenal dijawab sama (404, tidak diindeks), dengan
     * kalimat cara memperoleh tautan baru.
     */
    public function test_an_expired_revoked_or_unknown_link_cannot_be_opened(): void
    {
        $user = $this->pendakiBerprofil();
        [$kedaluwarsa] = $this->buat($user, [$this->jalur('A')->id, $this->jalur('B')->id]);
        [$dicabut] = $this->buat($user, [$this->jalur('C')->id, $this->jalur('D')->id]);
        auth()->logout();

        ComparisonShare::query()->oldest('id')->first()->forceFill(['expires_at' => now()->subMinute()])->save();
        ComparisonShare::query()->latest('id')->first()->forceFill(['revoked_at' => now()])->save();

        foreach ([$kedaluwarsa, $dicabut, url('/b/'.str_repeat('A', 32))] as $alamat) {
            $this->get($alamat)->assertNotFound()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/SharedComparison')->where('berlaku', false));
        }

        $this->get('/b/pendek')->assertNotFound();
        $this->assertStringContainsString('Minta tautan baru dari orang yang membagikannya.', File::get(resource_path('js/pages/Public/SharedComparison.tsx')));
    }

    public function test_only_the_owner_can_revoke_and_others_get_not_found(): void
    {
        $pemilik = $this->pendakiBerprofil();
        $this->buat($pemilik, [$this->jalur('A')->id, $this->jalur('B')->id]);
        $share = ComparisonShare::sole();

        $this->actingAs(User::factory()->create())->delete(route('shares.destroy', $share->id))->assertNotFound();
        $this->assertNull($share->fresh()->revoked_at);

        $this->actingAs($pemilik)->from(route('trails.saved'))->delete(route('shares.destroy', $share->id))->assertRedirect(route('trails.saved'));
        $this->assertNotNull($share->fresh()->revoked_at);
    }

    /**
     * R-022: hanya jalur terbit; dua sampai batas banding; pemilik dari sesi, bukan dari
     * badan permintaan.
     */
    public function test_a_link_holds_two_to_three_published_trails_of_the_signed_in_owner(): void
    {
        $user = $this->pendakiBerprofil();
        $lain = User::factory()->create();
        $draf = Trail::factory()->for(Mountain::factory())->unpublished()->create();
        $a = $this->jalur('A');
        $b = $this->jalur('B');

        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id]])->assertSessionHasErrors('trails');
        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id, $b->id, $this->jalur('C')->id, $this->jalur('D')->id]])->assertSessionHasErrors('trails');
        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id, $draf->id]])->assertSessionHasErrors('trails');
        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id, $a->id]])->assertSessionHasErrors('trails.0');
        $this->assertSame(0, ComparisonShare::count());

        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id, $b->id], 'user_id' => $lain->id])->assertSessionHasNoErrors();
        $this->assertSame($user->id, ComparisonShare::sole()->user_id);
    }

    public function test_a_guest_cannot_create_or_revoke(): void
    {
        $this->post(route('shares.store'), ['trails' => [1, 2]])->assertRedirect(route('login'));
        $this->delete(route('shares.destroy', 1))->assertRedirect(route('login'));
    }

    public function test_active_links_per_account_are_capped(): void
    {
        config()->set('hiking.share_links.max_active_per_user', 1);
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('A');
        $b = $this->jalur('B');

        $this->buat($user, [$a->id, $b->id]);
        [, $respons] = $this->buat($user, [$b->id, $a->id]);

        $respons->assertSessionHasErrors(['trails' => 'Tautan aktif Anda sudah 1. Cabut satu dulu sebelum membuat yang baru.']);
        $this->assertSame(1, ComparisonShare::count());
    }

    public function test_opening_links_is_rate_limited_per_address(): void
    {
        [$url] = $this->buat($this->pendakiBerprofil(), [$this->jalur('A')->id, $this->jalur('B')->id]);
        auth()->logout();
        $batas = (int) config('hiking.share_links.views_per_minute');

        foreach (range(1, $batas) as $i) {
            $this->get($url)->assertOk();
        }

        $this->get($url)->assertStatus(429);
    }

    /**
     * Pemiliknya dapat mencabut kapan saja: Simpanan membawa tautan yang masih berlaku, tanpa
     * token (yang tidak tersimpan), dan tombol Bagikan baru ada bila ada yang dapat dibagikan
     * atau dicabut (DK-40).
     */
    public function test_the_saved_page_lists_active_links_and_offers_sharing_only_when_useful(): void
    {
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('A');
        $b = $this->jalur('B');
        app(ConsiderationService::class)->toggle($user, $a);
        app(ConsiderationService::class)->toggle($user, $b);
        $this->buat($user, [$a->id, $b->id]);

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('tautanAktif', 1)
            ->where('tautanAktif.0.jumlahJalur', 2)
            ->missing('tautanAktif.0.url'));

        $halaman = File::get(resource_path('js/pages/Trails/Saved.tsx'));
        $this->assertStringContainsString('tersedia >= 2 || tautanAktif.length > 0 ? (', $halaman);
        $this->assertStringContainsString('onNetworkError', File::get(resource_path('js/components/trails/banding/LembarBagikan.tsx')));
    }

    /**
     * Kebijakan data pribadi §3: data tanpa dasar retensi dihapus bersama akunnya. Tautan
     * bagikan menunjuk pilihan pemilik akun, jadi ikut hilang, dan tautannya tidak lagi
     * terbuka.
     */
    public function test_deleting_the_account_removes_its_links(): void
    {
        $user = $this->pendakiBerprofil();
        [$url] = $this->buat($user, [$this->jalur('A')->id, $this->jalur('B')->id]);

        $user->delete();
        auth()->logout();

        $this->assertSame(0, ComparisonShare::count());
        $this->get($url)->assertNotFound();
    }

    /**
     * Anggaran kueri halaman publik (keputusan koordinator W3 nomor 12): tautan berisi tiga
     * jalur, masing-masing dengan status resmi, tidak lebih mahal daripada tautan berisi dua.
     */
    public function test_the_public_page_does_not_grow_with_the_number_of_trails(): void
    {
        $user = $this->pendakiBerprofil();
        [$dua] = $this->buat($user, [$this->jalur('Jalur A')->id, $this->jalur('Jalur B')->id]);
        [$tiga] = $this->buat($user, [$this->jalur('Jalur C')->id, $this->jalur('Jalur D')->id, $this->jalur('Jalur E')->id]);
        auth()->logout();

        $this->get($dua)->assertOk();
        $sedikit = $this->hitungKueri($dua);
        $banyak = $this->hitungKueri($tiga);

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} kueri lalu {$banyak}.");
    }

    private function hitungKueri(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
```

Buat `tests/Spatial/ComparisonShareSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\ComparisonShare;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\Trails\TautanBagikan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi tautan bagikan di PostgreSQL (keputusan koordinator W3 nomor 10, R-024): suite
 * SQLite tidak membuktikan RLS, indeks unik, JSON, dan cascade seperti di produksi. Migrasinya
 * tidak dijalankan di Supabase sebelum izin pemilik (spec v3 Q2).
 */
class ComparisonShareSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_row_level_security_and_a_unique_hash(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'comparison_shares'")->relrowsecurity);

        $user = User::factory()->create();
        ComparisonShare::factory()->for($user)->create(['token_hash' => str_repeat('a', 64)]);

        $this->expectException(UniqueConstraintViolationException::class);
        ComparisonShare::factory()->for($user)->create(['token_hash' => str_repeat('a', 64)]);
    }

    public function test_a_link_round_trips_and_disappears_with_its_account(): void
    {
        $user = User::factory()->create();
        $trails = new Collection([
            Trail::factory()->for(Mountain::factory())->published()->create(),
            Trail::factory()->for(Mountain::factory())->published()->create(),
        ]);

        [$token, $share] = app(TautanBagikan::class)->buat($user, $trails->reverse()->values());

        $this->assertTrue(app(TautanBagikan::class)->buka($token)->is($share));
        $this->assertSame($trails->reverse()->pluck('id')->values()->all(), $share->fresh()->trail_ids);

        $user->delete();

        $this->assertSame(0, ComparisonShare::count());
    }
}
```

`tests/Feature/EmptyStateCoverageTest.php`:

Ganti:

```php
            'ringkasan' => 'sebelas baris tetap ringkasan profil, tampil hanya bila profilnya lengkap',
```

dengan:

```php
            'ringkasan' => 'sebelas baris tetap ringkasan profil, tampil hanya bila profilnya lengkap',
        ],
        'pages/Public/SharedComparison.tsx' => [
            'props.baris' => 'sembilan baris tetap RouteComparisonTable::baris(); tabelnya hanya tampil bila ada jalur',
```

`tests/Feature/PageHeaderContractTest.php`:

Ganti:

```php
            if (str_starts_with($nama, 'Auth/') || $nama === 'Welcome.tsx') {
```

dengan:

```php
            // Halaman tamu dan halaman publik tanpa kerangka aplikasi (tautan bagikan FR-23):
            // tanpa tombol kembali dan tanpa avatar, jadi di luar kontrak ini.
            if (str_starts_with($nama, 'Auth/') || str_starts_with($nama, 'Public/') || $nama === 'Welcome.tsx') {
```

`tests/Feature/RouteAccessMatrixTest.php`:

Ganti:

```php
        'pendakian/{trail:slug}',
```

dengan:

```php
        'pendakian/{trail:slug}',

        /*
         * Tautan bagikan perbandingan (FR-23): dibuka tanpa akun menurut rancangannya,
         * hanya membaca, tidak diindeks, dan dibatasi laju. ComparisonShareTest menjaga
         * bahwa isinya hanya data publik jalur.
         */
        'b/{token}',
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use App\Models\TripPlan;
use App\Models\User;
```

dengan:

```php
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trails\TautanBagikan;
```

2. Ganti:

```php
    public function test_jelajah_tanpa_jalur_terbit(): void
```

dengan:

```php
    /**
     * Halaman publik tautan bagikan (FR-23), dibuka sebagai tamu. Tautannya dibuat lewat
     * TautanBagikan dari dua simpanan Budi.
     */
    public function test_tautan_bagikan(): void
    {
        $budi = $this->sebagaiBudi();
        $jalur = Trail::query()->whereIn('slug', ['gede-via-cibodas', 'gede-via-gunung-putri'])->orderBy('slug')->get();
        [$token] = app(TautanBagikan::class)->buat($budi, $jalur);
        auth()->logout();

        $this->tulisPratinjau('bagikan', $this->get(self::ASAL.'/b/'.$token), 'Public/SharedComparison');
    }

    public function test_jelajah_tanpa_jalur_terbit(): void
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='ComparisonShare|RouteAccessMatrix|PageHeaderContract|EmptyStateCoverage|InertiaLinkRegistry|KeyboardAndScreenReader|RepoPolicyFiles'`
Expected: FAIL: 41 test, 26 lulus, 1 gagal, 12 error, 2 dilewati. Antara lain `EmptyStateCoverageTest::test_every_list_says_something_when_it_has_nothing`, `ComparisonShareTest::test_the_token_is_long_random_and_only_its_hash_is_stored`, `ComparisonShareTest::test_the_public_page_is_read_only_unindexed_and_carries_nothing_private`.

- [ ] **Step 3: Tulis implementasinya.** Buat migrasi dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php
  artisan make:migration --no-interaction create_comparison_shares_table`, ganti nama berkasnya menjadi
  `2026_09_28_000100_create_comparison_shares_table.php`, lalu ganti isinya. Jangan menjalankan migrasi di luar PHPUnit.

**Data, rute, dan konfigurasi**

Buat `database/factories/ComparisonShareFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\ComparisonShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComparisonShare>
 */
class ComparisonShareFactory extends Factory
{
    protected $model = ComparisonShare::class;

    /**
     * Tanpa token asli: factory hanya untuk baris yang tidak perlu dibuka lewat tautannya.
     * Test yang membuka tautan membuatnya lewat TautanBagikan::buat().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'trail_ids' => [],
            'expires_at' => now()->addDays(7),
        ];
    }
}
```

Buat `database/migrations/2026_09_28_000100_create_comparison_shares_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tautan bagikan perbandingan (FR-23, spec v3 bagian 9.3).
 *
 * Tokennya tidak pernah disimpan: yang disimpan hanya SHA-256-nya (64 karakter heksa, unik),
 * jadi salinan basis data tidak cukup untuk membuka tautan siapa pun. Daftar jalur disimpan
 * sebagai larik id dalam urutan kolom. Tautan berumur (expires_at) dan dapat dicabut
 * (revoked_at); keduanya diperiksa setiap kali dibuka. Akun yang dihapus menghapus tautannya
 * (cascade), karena tautan menunjuk ke pilihan pemilik akun itu.
 *
 * Diuji di SQLite dan PostgreSQL CI; tidak dijalankan di Supabase sebelum izin pemilik
 * (R-019, spec v3 Q2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparison_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->json('trail_ids');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });

        // Lapis kedua penutupan PostgREST (2026_09_20_000100): RLS tanpa policy menolak
        // REST API, sedangkan Laravel sebagai pemilik tabel tidak terpengaruh.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE public."comparison_shares" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_shares');
    }
};
```

`config/hiking.php`:

Ganti:

```php
        // sehingga tautan yang tercecer di grup percakapan tidak hidup selamanya.
        // Status resmi dan cuaca di halamannya dibaca ulang setiap kali dibuka, jadi
        // umur ini tidak membuat isinya basi.
        'ttl_days' => 30,
```

dengan:

```php
        // sehingga tautan yang tercecer di grup percakapan tidak hidup selamanya. Tujuh
        // hari mengikuti spec v3 bagian 9.3 dan prototype 06 (sebelumnya 30, dari
        // spesifikasi fitur 03). Status resmi di halamannya dibaca ulang setiap kali
        // dibuka, jadi umur ini tidak membuat isinya basi.
        'ttl_days' => 7,
```

`database/README.md`:

1. Ganti:

```markdown
| Pertimbangkan | `trail_considerations` | Simpanan pribadi dan catatannya (FR-22) |
```

dengan:

```markdown
| Pertimbangkan | `trail_considerations`, `comparison_shares` | Simpanan pribadi dan catatannya (FR-22); tautan bagikan perbandingan, hanya hash tokennya yang disimpan (FR-23) |
```

2. Ganti:

```markdown
`2026_09_25_000918_create_trip_reminders_table`.
```

dengan:

```markdown
`2026_09_25_000918_create_trip_reminders_table`; `comparison_shares` (W3) mengikutinya.
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\ThemePreferenceController;
```

dengan:

```php
use App\Http\Controllers\ThemePreferenceController;
use App\Http\Controllers\Trails\ComparisonShareController;
```

2. Ganti:

```php
Route::middleware(['auth'])->group(function () {
```

dengan:

```php
/*
 * Tautan bagikan perbandingan (FR-23): dibuka tanpa akun, hanya membaca, tidak diindeks,
 * dan dibatasi laju per alamat. Token 32 karakter base64url; bentuk lain tidak pernah
 * sampai ke controller.
 */
Route::get('b/{token}', [ComparisonShareController::class, 'show'])
    ->where('token', '[A-Za-z0-9_-]{32}')
    ->middleware('throttle:'.config('hiking.share_links.views_per_minute').',1')
    ->name('shares.show');

Route::middleware(['auth'])->group(function () {
```

3. Ganti:

```php
    // tidak pernah dari badan permintaan; dibatasi laju seperti tulisan pribadi lain.
```

dengan:

```php
    // tidak pernah dari badan permintaan; dibatasi laju seperti tulisan pribadi lain.
    // Tautan bagikan perbandingan (FR-23): hanya pemiliknya yang membuat dan mencabut.
    Route::post('trails/shares', [ComparisonShareController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,tautan-bagikan')->name('shares.store');
    Route::delete('trails/shares/{share}', [ComparisonShareController::class, 'destroy'])->whereNumber('share')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,tautan-bagikan')->name('shares.destroy');
```

**Server**

Buat `app/Http/Controllers/Trails/ComparisonShareController.php`:

```php
<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trails\StoreComparisonShareRequest;
use App\Models\ComparisonShare;
use App\Models\Trail;
use App\Services\Trails\RouteComparisonTable;
use App\Services\Trails\StatusJalurRingkas;
use App\Services\Trails\TautanBagikan;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tautan bagikan perbandingan (FR-23, spec v3 bagian 9.3, PRD §148).
 *
 * Membuat dan mencabut hanya untuk pemiliknya. Halaman publik /b/{token} dibuka tanpa akun,
 * hanya membaca, dan hanya memuat data publik jalur beserta sumber dan waktunya: tanpa
 * catatan pribadi, tanpa label kecocokan pembuatnya, dan tanpa nama pembuatnya. Halamannya
 * tidak diindeks (X-Robots-Tag dan meta robots) dan dibatasi laju per alamat.
 */
class ComparisonShareController extends Controller
{
    public function store(StoreComparisonShareRequest $request, TautanBagikan $tautan): RedirectResponse
    {
        $ids = array_map('intval', $request->validated('trails'));
        $trails = Trail::query()->published()->whereKey($ids)->get()
            ->sortBy(fn (Trail $t) => array_search($t->id, $ids, true))
            ->values();

        if ($trails->count() !== count($ids)) {
            throw ValidationException::withMessages(['trails' => 'Hanya jalur yang sedang terbit yang dapat dibagikan.']);
        }

        $hasil = $tautan->buat($request->user(), new Collection($trails->all()));

        if ($hasil === null) {
            throw ValidationException::withMessages(['trails' => 'Tautan aktif Anda sudah '.(int) config('hiking.share_links.max_active_per_user').'. Cabut satu dulu sebelum membuat yang baru.']);
        }

        [$token, $share] = $hasil;

        // Token hanya ditunjukkan sekali, di sesi pembuatnya sendiri; basis data menyimpan hash-nya.
        return back()->with('tautan-bagikan', [
            'id' => $share->id,
            'url' => route('shares.show', $token),
            'berlakuSampai' => $share->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M Y'),
        ])->with('pengumuman', 'Tautan bagikan dibuat.');
    }

    /**
     * Mencabut tautan milik pengguna yang masuk. Tautan orang lain dan yang tidak ada dijawab
     * sama (404), supaya jawaban tidak membocorkan tautan siapa pun.
     */
    public function destroy(Request $request, int $share, TautanBagikan $tautan): RedirectResponse
    {
        $baris = ComparisonShare::query()->whereBelongsTo($request->user())->whereNull('revoked_at')->findOrFail($share);

        $tautan->cabut($baris);

        return back()->with('pengumuman', 'Tautan bagikan dicabut. Siapa pun yang membukanya kini melihat bahwa tautan itu tidak berlaku.');
    }

    public function show(Request $request, string $token, TautanBagikan $tautan, RouteComparisonTable $tabel, StatusJalurRingkas $status): Response
    {
        $share = $tautan->buka($token);

        if ($share === null) {
            return $this->tanpaIndeks(
                Inertia::render('Public/SharedComparison', ['title' => 'Tautan tidak berlaku', 'berlaku' => false])->toResponse($request)->setStatusCode(404)
            );
        }

        $urutan = $share->trail_ids;
        $trails = new Collection(Trail::query()->published()->whereKey($urutan)->with(['mountain', 'dataSource'])->get()
            ->sortBy(fn (Trail $t) => array_search($t->id, $urutan, true))
            ->values()
            ->all());
        $ringkas = $status->untuk($trails);

        return $this->tanpaIndeks(Inertia::render('Public/SharedComparison', [
            'title' => 'Perbandingan jalur',
            'berlaku' => true,
            'berlakuSampai' => $share->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M Y'),
            'jalur' => $trails->map(fn (Trail $t) => [
                'id' => $t->id,
                'nama' => $t->name,
                'gunung' => $t->mountain?->name,
                'provinsi' => $t->mountain?->province,
                'status' => $ringkas[$t->id],
                'sumber' => $t->dataSource?->source_name,
                'diperbarui' => $t->updated_at?->copy()->setTimezone(Timezone::forTrail($t))->translatedFormat('j M Y'),
            ])->values()->all(),
            'baris' => $tabel->baris($trails),
            'ditarik' => count($urutan) - $trails->count(),
            'bandingUrl' => $request->user() ? route('trails.compare', ['trails' => $trails->pluck('id')->join(',')]) : null,
        ])->toResponse($request));
    }

    private function tanpaIndeks(Response $respons): Response
    {
        $respons->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $respons;
    }
}
```

Buat `app/Http/Requests/Trails/StoreComparisonShareRequest.php`:

```php
<?php

namespace App\Http\Requests\Trails;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Membuat tautan bagikan (FR-23): dua sampai batas banding id jalur, berbeda satu sama lain,
 * dalam urutan kolom. Pemilik dari sesi; jalur yang tidak terbit ditolak controller.
 */
class StoreComparisonShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'trails' => ['required', 'array', 'min:2', 'max:'.(int) config('hiking.saved_trails.compare_max')],
            'trails.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trails.required' => 'Pilih jalur yang akan dibagikan.',
            'trails.min' => 'Pilih minimal 2 jalur untuk dibagikan sebagai perbandingan.',
            'trails.max' => 'Paling banyak '.(int) config('hiking.saved_trails.compare_max').' jalur dalam satu tautan.',
        ];
    }
}
```

Buat `app/Models/ComparisonShare.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu tautan bagikan perbandingan (FR-23). Tidak ada kolom yang fillable: pemilik, hash
 * token, daftar jalur, dan umurnya ditulis TautanBagikan lewat forceFill sesudah divalidasi,
 * tidak pernah dari badan permintaan. Hash token disembunyikan dari serialisasi.
 */
#[Hidden(['token_hash'])]
class ComparisonShare extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'trail_ids' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Belum dicabut dan belum kedaluwarsa.
     *
     * @param  Builder<ComparisonShare>  $query
     * @return Builder<ComparisonShare>
     */
    public function scopeBerlaku(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
```

Buat `app/Services/Trails/TautanBagikan.php`:

```php
<?php

namespace App\Services\Trails;

use App\Models\ComparisonShare;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tautan bagikan perbandingan (FR-23, spec v3 bagian 9.3, PRD §148).
 *
 * Token 192 bit dari random_bytes, ditulis base64url (32 karakter) dan hanya ditunjukkan
 * sekali kepada pembuatnya; yang disimpan SHA-256-nya. Tautan berlaku
 * hiking.share_links.ttl_days hari, dapat dicabut pembuatnya kapan saja, dan jumlah tautan
 * aktif per akun dibatasi supaya pembuatan tanpa ujung tidak menumpuk baris. Isinya dua
 * sampai hiking.saved_trails.compare_max jalur TERBIT (R-022); yang tidak terbit ditolak
 * pembuat permintaan, bukan dibuang diam-diam.
 */
final class TautanBagikan
{
    /**
     * @param  Collection<int, Trail>  $trails  jalur terbit dalam urutan kolom
     * @return array{0: string, 1: ComparisonShare}|null token dan barisnya, atau null bila batas tautan aktif tercapai
     */
    public function buat(User $user, Collection $trails): ?array
    {
        return DB::transaction(function () use ($user, $trails) {
            // Kunci per pengguna, sama dengan simpanan: dua permintaan serentak tidak dapat
            // sama-sama lolos di bawah batas.
            User::whereKey($user->id)->lockForUpdate()->first();

            if ($this->aktif($user)->count() >= (int) config('hiking.share_links.max_active_per_user')) {
                return null;
            }

            $token = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
            $share = new ComparisonShare;
            $share->forceFill([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'trail_ids' => $trails->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'expires_at' => now()->addDays((int) config('hiking.share_links.ttl_days')),
            ])->save();

            return [$token, $share];
        });
    }

    /**
     * Tautan milik $user yang masih berlaku, terbaru dulu.
     *
     * @return Collection<int, ComparisonShare>
     */
    public function aktif(User $user): Collection
    {
        return ComparisonShare::query()->whereBelongsTo($user)->berlaku()->latest()->latest('id')->get();
    }

    /**
     * Tautan yang dibuka dari URL: null bila token salah bentuk, tidak dikenal, dicabut, atau
     * kedaluwarsa. Keempatnya dijawab sama, supaya jawaban tidak membocorkan tautan mana
     * yang pernah ada.
     */
    public function buka(string $token): ?ComparisonShare
    {
        if (preg_match('/^[A-Za-z0-9_-]{32}$/', $token) !== 1) {
            return null;
        }

        return ComparisonShare::query()->berlaku()->where('token_hash', hash('sha256', $token))->first();
    }

    public function cabut(ComparisonShare $share): void
    {
        $share->forceFill(['revoked_at' => now()])->save();
    }
}
```

`app/Http/Controllers/Trails/SavedRoutesController.php`:

1. Ganti:

```php
use App\Services\TrailFitService;
```

dengan:

```php
use App\Services\TrailFitService;
use App\Services\Trails\TautanBagikan;
use App\Support\Timezone;
```

2. Ganti:

```php
    public function __invoke(Request $request, ConsiderationService $consideration, TrailFitService $fit): Response
```

dengan:

```php
    public function __invoke(Request $request, ConsiderationService $consideration, TrailFitService $fit, TautanBagikan $tautan): Response
```

3. Ganti:

```php
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
```

dengan:

```php
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
            // Tautan bagikan yang masih berlaku, supaya pembuatnya dapat mencabut kapan saja
            // (FR-23). Tokennya tidak tersimpan, jadi yang ditunjukkan isinya dan umurnya.
            'tautanAktif' => $tautan->aktif($pembaca)->map(fn ($s) => [
                'id' => $s->id,
                'jumlahJalur' => count($s->trail_ids),
                'dibuat' => $s->created_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M'),
                'berlakuSampai' => $s->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M Y'),
            ])->values()->all(),
```

`app/Http/Middleware/HandleInertiaRequests.php`:

Ganti:

```php
                'simpananTerhapus' => fn () => $request->session()->get('simpanan-terhapus'),
```

dengan:

```php
                'simpananTerhapus' => fn () => $request->session()->get('simpanan-terhapus'),
                // Tautan bagikan yang baru dibuat: tokennya hanya ditunjukkan sekali ini (FR-23).
                'tautanBagikan' => fn () => $request->session()->get('tautan-bagikan'),
```

**Antarmuka**

Buat `resources/js/components/trails/banding/LembarBagikan.tsx`:

```tsx
import { router, usePage } from '@inertiajs/react';
import { useState, type RefObject } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { InputError } from '@/components/ui/Field';
import { LembarBawah } from '@/components/ui/Lembar';
import { TombolSalin } from '@/components/ui/TombolSalin';
import { destroy, store } from '@/routes/shares';
import type { PropsBersama } from '@/types';

export interface TautanAktif {
    id: number;
    jumlahJalur: number;
    dibuat: string;
    berlakuSampai: string;
}

interface LembarBagikanProps {
    terbuka: boolean;
    onTutup: () => void;
    pemicu: RefObject<HTMLElement | null>;
    /** Jalur yang akan dibagikan, dalam urutan kolom: pilihan banding, atau simpanan terbaru. */
    jalur: Array<{ id: number; name: string }>;
    tautanAktif: TautanAktif[];
}

const opsi = { preserveScroll: true, preserveState: true } as const;

/**
 * Bagikan perbandingan (FR-23, spec v3 bagian 6.6 dan 9.3, prototype 06). Lembar ini menyebut
 * apa yang ikut dan apa yang tidak sebelum tautan dibuat: data jalur, sumber, dan waktu
 * pembaruannya ikut; catatan pribadi dan label kecocokan tidak, karena label dihitung dari
 * profil pembuatnya. Tautan baru ditunjukkan sekali bersama tombol salin; tautan lain yang
 * masih berlaku dapat dicabut kapan saja.
 *
 * Membuat dan mencabut adalah kiriman dari dalam dialog modal, jadi galat jaringan ditulis di
 * dalam lembar ini (ruling W2-5e): pesan jaringan kerangka menjadi inert di bawah dialog.
 */
export function LembarBagikan({ terbuka, onTutup, pemicu, jalur, tautanAktif }: LembarBagikanProps) {
    const { flash, errors } = usePage<PropsBersama>().props;
    const [memproses, setMemproses] = useState(false);
    const [galatJaringan, setGalatJaringan] = useState(false);
    const baru = flash.tautanBagikan;

    const kirim = (aksi: () => void) => {
        setGalatJaringan(false);
        setMemproses(true);
        aksi();
    };

    const selesai = {
        onFinish: () => setMemproses(false),
        onNetworkError: () => {
            setGalatJaringan(true);

            return false;
        },
    };

    const buat = () => kirim(() => router.post(store.url(), { trails: jalur.map((j) => j.id) }, { ...opsi, ...selesai }));

    const cabut = (id: number) => kirim(() => router.delete(destroy.url({ share: id }), { ...opsi, ...selesai }));

    return (
        <LembarBawah
            terbuka={terbuka}
            onTutup={onTutup}
            judul="Bagikan perbandingan"
            keterangan="Tautan hanya-baca, berlaku 7 hari. Penerima melihat data jalur, sumber, dan waktu pembaruannya. Catatan pribadi dan label kecocokan Anda tidak ikut, karena label itu dihitung dari profil Anda."
            pemicu={pemicu}
        >
            <div className="space-y-4">
                {galatJaringan ? (
                    <Alert variant="warning">Koneksi terputus sebelum jawaban server sampai. Periksa daftar tautan di bawah sebelum mencoba lagi, supaya tidak terbuat dua.</Alert>
                ) : null}

                {baru ? (
                    <div className="space-y-2 rounded-lg border border-subtle bg-surface-sunken p-3">
                        <p className="text-kecil font-bold text-primary">Tautan baru, berlaku sampai {baru.berlakuSampai}</p>
                        <TombolSalin teks={baru.url} label="Salin tautan" />
                        <p className="text-meta text-muted">Tautan ini hanya ditunjukkan sekarang. Simpan atau kirim sebelum menutup lembar ini.</p>
                    </div>
                ) : jalur.length >= 2 ? (
                    <div className="space-y-2">
                        <p className="text-kecil text-secondary">Yang dibagikan: {jalur.map((j) => j.name).join(', ')}.</p>
                        <Button onClick={buat} processing={memproses} aria-describedby={errors.trails ? 'galat-bagikan' : undefined}>
                            Buat tautan
                        </Button>
                        <InputError id="galat-bagikan" messages={errors.trails} />
                    </div>
                ) : (
                    <p className="text-kecil text-secondary">Pilih minimal 2 jalur yang masih terbit untuk dibagikan sebagai perbandingan.</p>
                )}

                {tautanAktif.length > 0 ? (
                    <section aria-labelledby="judul-tautan-aktif" className="space-y-2">
                        <h3 id="judul-tautan-aktif" className="text-subjudul text-primary">
                            Tautan yang masih berlaku
                        </h3>
                        <ul className="divide-y divide-subtle rounded-lg border border-subtle">
                            {tautanAktif.map((t) => (
                                <li key={t.id} className="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                    <span className="text-kecil text-secondary">
                                        {t.jumlahJalur} jalur, dibuat {t.dibuat}, berlaku sampai {t.berlakuSampai}
                                    </span>
                                    <Button variant="ghost" size="sm" processing={memproses} onClick={() => cabut(t.id)}>
                                        Cabut<span className="sr-only"> tautan yang dibuat {t.dibuat}</span>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}
            </div>
        </LembarBawah>
    );
}
```

Buat `resources/js/pages/Public/SharedComparison.tsx`:

```tsx
import { Head, usePage } from '@inertiajs/react';
import { Lockup } from '@/components/brand/Lockup';
import { BarisStatus } from '@/components/trails/BarisStatus';
import type { StatusRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { login, register, welcome } from '@/routes';
import type { PropsBersama } from '@/types';

interface KolomJalur {
    id: number;
    nama: string;
    gunung: string | null;
    provinsi: string | null;
    status: StatusRingkas;
    sumber: string | null;
    diperbarui: string | null;
}

type Props = PropsBersama &
    (
        | { berlaku: false }
        | {
              berlaku: true;
              berlakuSampai: string;
              jalur: KolomJalur[];
              baris: Array<{ label: string; nilai: string[]; berbeda: boolean | null }>;
              ditarik: number;
              bandingUrl: string | null;
          }
    );

/**
 * Halaman publik tautan bagikan perbandingan (FR-23, PRD §148). Dibuka tanpa akun dan hanya
 * membaca: data publik jalur, status resmi dengan sumber dan waktunya, dan sumber data jalur.
 * Tanpa catatan pribadi, tanpa label kecocokan, dan tanpa nama pembuatnya; tanpa pemenang
 * (R-016). Tidak diindeks: server mengirim X-Robots-Tag, dan halaman ini meta robots.
 *
 * Tautan yang tidak berlaku (salah, kedaluwarsa, dicabut) dijawab sama, dengan cara
 * memperoleh tautan baru.
 */
export default function SharedComparison() {
    const props = usePage<Props>().props;

    return (
        <main id="konten" tabIndex={-1} className="min-h-dvh bg-canvas px-4 py-6 sm:px-6">
            <JudulHalaman />
            <Head>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <div className="mx-auto max-w-4xl space-y-6">
                <Lockup href={welcome.url()} />

                {props.berlaku ? (
                    <>
                        <div className="space-y-2">
                            <h1 className="text-balance text-judul text-primary">Perbandingan jalur</h1>
                            <p className="text-kecil text-secondary">
                                Dibagikan lewat Memount, hanya-baca, berlaku sampai {props.berlakuSampai}. Label kecocokan tidak ikut karena dihitung dari profil pembuatnya.
                            </p>
                            {props.ditarik > 0 ? <p className="text-kecil text-secondary">{props.ditarik} jalur di tautan ini tidak lagi terbit, jadi tidak ditampilkan.</p> : null}
                        </div>

                        {props.jalur.length === 0 ? (
                            <p className="rounded-lg border-2 border-dashed border-control bg-surface px-4 py-3 text-isi text-secondary">
                                Semua jalur di tautan ini tidak lagi terbit, jadi tidak ada yang dapat ditampilkan. Minta tautan baru dari orang yang membagikannya.
                            </p>
                        ) : (
                            <div className="relative overflow-x-auto rounded-lg border border-subtle bg-surface">
                                <table className="min-w-full text-kecil">
                                    <caption className="sr-only">Perbandingan {props.jalur.length} jalur, tanpa pemenang</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col" className="sr-only">
                                                Keterangan
                                            </th>
                                            {props.jalur.map((j) => (
                                                <th key={j.id} scope="col" className="min-w-[10rem] px-3 py-3 text-left align-top">
                                                    <span className="block text-subjudul text-primary">{j.nama}</span>
                                                    <span className="block text-meta font-normal text-secondary">{[j.gunung, j.provinsi].filter(Boolean).join(', ')}</span>
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-subtle">
                                        <tr>
                                            <th scope="row" className="px-3 py-3 text-left align-top font-normal text-secondary">
                                                Status resmi
                                            </th>
                                            {props.jalur.map((j) => (
                                                <td key={j.id} className="px-3 py-3 align-top">
                                                    <BarisStatus status={j.status} />
                                                </td>
                                            ))}
                                        </tr>
                                        {props.baris.map((b) => (
                                            <tr key={b.label}>
                                                <th scope="row" className="px-3 py-3 text-left align-top font-normal text-secondary">
                                                    {b.label}
                                                </th>
                                                {b.nilai.map((n, i) => (
                                                    <td key={props.jalur[i]?.id ?? i} data-angka="" className="px-3 py-3 align-top text-primary">
                                                        {n}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                        <tr>
                                            <th scope="row" className="px-3 py-3 text-left align-top font-normal text-secondary">
                                                Sumber data jalur
                                            </th>
                                            {props.jalur.map((j) => (
                                                <td key={j.id} className="px-3 py-3 align-top text-secondary">
                                                    {j.sumber ?? 'Belum tercatat'}
                                                    {j.diperbarui ? `, diperbarui ${j.diperbarui}` : null}
                                                </td>
                                            ))}
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        )}

                        <div className="space-y-2 rounded-lg border border-subtle bg-surface p-4">
                            <p className="text-kecil text-secondary">Cocok atau tidaknya jalur ini bergantung pada pengalaman Anda sendiri. Memount menilainya dengan aturan tertulis, tanpa skor angka.</p>
                            <div className="flex flex-wrap gap-2">
                                {props.bandingUrl ? (
                                    <Button href={props.bandingUrl}>Nilai kecocokannya untuk saya</Button>
                                ) : (
                                    <>
                                        <Button href={register.url()}>Daftar untuk menilai kecocokannya</Button>
                                        <Button variant="secondary" href={login.url()}>
                                            Masuk
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="space-y-3">
                        <h1 className="text-balance text-judul text-primary">Tautan ini tidak berlaku lagi</h1>
                        <p className="text-isi text-secondary">Tautan bagikan berlaku 7 hari dan dapat dicabut pembuatnya. Minta tautan baru dari orang yang membagikannya.</p>
                        <Button variant="secondary" href={welcome.url()}>
                            Kenali Memount
                        </Button>
                    </div>
                )}
            </div>
        </main>
    );
}
```

`resources/js/components/ui/ikon/phosphor.ts`:

1. Ganti:

```ts
    | 'house'
```

dengan:

```ts
    | 'house'
    | 'link-simple'
```

2. Ganti:

```ts
    'list': {
```

dengan:

```ts
    'link-simple': {
        regular: 'M165.66,90.34a8,8,0,0,1,0,11.32l-64,64a8,8,0,0,1-11.32-11.32l64-64A8,8,0,0,1,165.66,90.34ZM215.6,40.4a56,56,0,0,0-79.2,0L106.34,70.45a8,8,0,0,0,11.32,11.32l30.06-30a40,40,0,0,1,56.57,56.56l-30.07,30.06a8,8,0,0,0,11.31,11.32L215.6,119.6a56,56,0,0,0,0-79.2ZM138.34,174.22l-30.06,30.06a40,40,0,1,1-56.56-56.57l30.05-30.05a8,8,0,0,0-11.32-11.32L40.4,136.4a56,56,0,0,0,79.2,79.2l30.06-30.07a8,8,0,0,0-11.32-11.31Z',
        bold: 'M87.5,151.52l64-64a12,12,0,0,1,17,17l-64,64a12,12,0,0,1-17-17Zm131-114a60.08,60.08,0,0,0-84.87,0L103.51,67.61a12,12,0,0,0,17,17l30.07-30.06a36,36,0,0,1,50.93,50.92L171.4,135.52a12,12,0,1,0,17,17l30.08-30.06A60.09,60.09,0,0,0,218.45,37.55ZM135.52,171.4l-30.07,30.08a36,36,0,0,1-50.92-50.93l30.06-30.07a12,12,0,0,0-17-17L37.55,133.58a60,60,0,0,0,84.88,84.87l30.06-30.07a12,12,0,0,0-17-17Z',
    },
    'list': {
```

`resources/js/lib/bukanHalamanReact.json`:

Ganti:

```json
        "^/up$"
```

dengan:

```json
        "^/up$",
        "^/b/[^/]+$"
```

`resources/js/pages/Trails/Saved.tsx`:

1. Ganti:

```tsx
import { useState, type ReactNode } from 'react';
import { AksiPilihan } from '@/components/trails/banding/AksiPilihan';
import { KartuSimpanan } from '@/components/trails/banding/KartuSimpanan';
```

dengan:

```tsx
import { useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { AksiPilihan } from '@/components/trails/banding/AksiPilihan';
import { KartuSimpanan } from '@/components/trails/banding/KartuSimpanan';
import { LembarBagikan, type TautanAktif } from '@/components/trails/banding/LembarBagikan';
```

2. Ganti:

```tsx
import { EmptyState } from '@/components/ui/EmptyState';
```

dengan:

```tsx
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
```

3. Ganti:

```tsx
    kataBelumDinilai: string;
```

dengan:

```tsx
    kataBelumDinilai: string;
    tautanAktif: TautanAktif[];
```

4. Ganti:

```tsx
    const { trails, batas, batasBanding, batasCatatan, kataBelumDinilai, pengumuman } = usePage<Props>().props;
```

dengan:

```tsx
    const { trails, batas, batasBanding, batasCatatan, kataBelumDinilai, tautanAktif, pengumuman } = usePage<Props>().props;
```

5. Ganti:

```tsx
    const [pesan, setPesan] = useState<string | null>(null);
```

dengan:

```tsx
    const [pesan, setPesan] = useState<string | null>(null);
    const [bagikanTerbuka, setBagikanTerbuka] = useState(false);
    const pemicuBagikan = useRef<HTMLElement | null>(null);
```

6. Ganti:

```tsx
    const pilihanBerlaku = berlaku(dipilih);
```

dengan:

```tsx
    const pilihanBerlaku = berlaku(dipilih);

    // Yang dibagikan: pilihan banding bila ada dua atau lebih, selain itu simpanan terbaru
    // yang masih terbit, sama dengan pilihan bawaan halaman Banding.
    const dibagikan = (pilihanBerlaku.length >= 2 ? pilihanBerlaku.map((id) => trails.find((t) => t.id === id)) : trails.filter((t) => t.terbit).slice(0, batasBanding))
        .filter((t): t is JalurTersimpan => t !== undefined)
        .map((t) => ({ id: t.id, name: t.name }));

    const bukaBagikan = (e: MouseEvent<HTMLElement>) => {
        pemicuBagikan.current = e.currentTarget;
        setBagikanTerbuka(true);
    };
```

7. Ganti:

```tsx
                    description={`${trails.length} jalur tersimpan, maksimal ${batas}. Pilih sampai ${batasBanding} untuk dibandingkan.`}
```

dengan:

```tsx
                    description={`${trails.length} jalur tersimpan, maksimal ${batas}. Pilih sampai ${batasBanding} untuk dibandingkan.`}
                    aksi={
                        tersedia >= 2 || tautanAktif.length > 0 ? (
                            <Button variant="secondary" size="sm" onClick={bukaBagikan}>
                                <Icon name="link-simple" size={20} />
                                Bagikan
                            </Button>
                        ) : undefined
                    }
```

8. Ganti:

```tsx
                <p role="status" className="sr-only">
```

dengan:

```tsx
                <LembarBagikan terbuka={bagikanTerbuka} onTutup={() => setBagikanTerbuka(false)} pemicu={pemicuBagikan} jalur={dibagikan} tautanAktif={tautanAktif} />

                <p role="status" className="sr-only">
```

`resources/js/types/index.d.ts`:

Ganti:

```ts
        simpananTerhapus: { trailId: number; nama: string; catatan: string | null } | null;
```

dengan:

```ts
        simpananTerhapus: { trailId: number; nama: string; catatan: string | null } | null;
        /** Tautan bagikan yang baru dibuat; tokennya hanya ada di sini, sekali (FR-23). */
        tautanBagikan: { id: number; url: string; berlakuSampai: string } | null;
```

- [ ] **Step 4: Bangkitkan rute bertipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan wayfinder:generate`
Expected: tanpa galat. `resources/js/routes/shares` memuat `store` dan `destroy`; berkas bangkitan diabaikan git.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='ComparisonShare|RouteAccessMatrix|PageHeaderContract|EmptyStateCoverage|InertiaLinkRegistry|KeyboardAndScreenReader|RepoPolicyFiles'`
Expected: PASS: 41 test, 39 lulus, 2 dilewati. Dua test yang dilewati adalah `tests/Spatial/ComparisonShareSchemaTest.php`, yang butuh
PostgreSQL dan baru berjalan di CI.

- [ ] **Step 6: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.718 test, 1.716 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_tautan_bagikan`. Pada simulasi rencana: 13 test, 13 lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 9: Bandingkan

Induk W3 butir 7; spec bagian 6.7; butir W2-F2; ruling W3-R45 dan W3-R46. `RouteComparisonTable` menambah baris dan kolom
berikut:

- status resmi bersumber dan waktunya (`StatusJalurRingkas`);
- laporan terbaru dalam 30 hari (warna komunitas);
- izin;
- bilah tanjakan pada skala yang sama;
- garis profil pada skala tinggi yang sama, dengan kalimat pembanding;
- tautan "Jadikan dasar rencana" per jalur.

Sakelar "Sorot yang berbeda" menandai baris yang berbeda tanpa menyebut pemenang (R-016). Tombol "Jadikan dasar
rencana" sama rupa untuk setiap jalur dan tanpa panah (DK-31, DK-39). Halaman ini memakai `LembarBagikan` yang sama
dengan Simpanan. Banding milik tab Trip, jadi tombol kembalinya ke Trip; tautan "Lihat simpanan" di bawah judul tetap
membawa ke Simpanan. Test baru memastikan induk setiap halaman menyalakan tab yang sama dengan halamannya.

**Files:**
- Modify: `app/Http/Controllers/Trails/RouteComparisonController.php`, `app/Services/Trails/RouteComparisonTable.php` (tulis ulang), `resources/js/components/trails/banding/TabelBanding.tsx` (tulis ulang), `resources/js/pages/Trails/Compare.tsx` (tulis ulang)
- Test: `tests/Feature/NavigationConsistencyTest.php`, `tests/Feature/RouteComparisonTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `StatusJalurRingkas` (Tugas 1), `SkalaProfil`, `BarisStatus`, `TrailFigures` (Tugas 2), `LabelKecocokan`, `GarisProfil` (Tugas 1 dan 4), `TautanBagikan::aktif()` dan `LembarBagikan` (Tugas 8), `TrailFitService`, `PermitService`, `Sakelar` (W1), `SumberReact::tagBernama()` dan `berkasTsx()` untuk test sumber.
- Produces:
  - `RouteComparisonTable::susun(Collection $trails, User $user): array{jalur, baris, beda, profil}` dengan kolom `KolomJalur` (`id`, `name`, `mountain`, `url`, `fit`, `status`, `laporan`, `izin`, `rasioTanjakan`, `garis`, `rencanaUrl`, `tersimpan`, `hapus`) dan `beda` untuk `fit`, `status`, `laporan`, `izin`;
  - `RouteComparisonController::show(Request $request, ConsiderationService $consideration, RouteComparisonTable $tabel, TautanBagikan $tautan): Response` dengan prop `tautanAktif`;
  - `TabelBanding({ jalur, baris, beda, profil, sorotBeda, kataBelumDinilai })`;
  - induk Banding `{ href: tripsIndex.url(), label: 'Trip' }`;
  - `NavigationConsistencyTest::test_a_page_and_its_parent_light_the_same_tab` dengan cermin aturan tab `AKTIF` dan `INDUK_DARI_PROPS`.

- [ ] **Step 1: Tulis test yang gagal.**

`tests/Feature/NavigationConsistencyTest.php`:

1. Ganti:

```php
    use RefreshDatabase;
```

dengan:

```php
    use RefreshDatabase;
    use SumberReact;

    /**
     * Cermin aturan `aktif` setiap tab di NavLinks.tsx, kata demi kata, untuk
     * test_a_page_and_its_parent_light_the_same_tab. Bila aturan di NavLinks berubah, test itu
     * gagal sampai cermin ini dan tabUntuk() diperbarui bersama.
     *
     * @var array<string, string>
     */
    private const AKTIF = [
        'Beranda' => "aktif: (p: string) => p === '/dashboard' || p.startsWith('/kabar')",
        'Jelajah' => "aktif: (p: string) => ((p === '/trails' || p.startsWith('/trails/')) && !p.startsWith('/trails/compare')) || p.startsWith('/mountains/'),",
        'Trip' => "(p.startsWith('/trips') && !modePendakian(p)) || p.startsWith('/goals') || p.startsWith('/recommendations') || p.startsWith('/trails/compare'),",
        'Mendaki' => "aktif: (p: string) => p.startsWith('/mendaki') || modePendakian(p)",
        'Progres' => "aktif: (p: string) => p.startsWith('/progres') || p.startsWith('/history') || p.startsWith('/reports'),",
    ];

    /**
     * Induk yang datang dari props server, bukan dari rute Wayfinder: penilaian kecocokan
     * kembali ke rincian jalurnya (props jalur.url).
     *
     * @var array<string, string>
     */
    private const INDUK_DARI_PROPS = ['Trails/Fit.tsx' => 'trails.show'];
```

2. Ganti:

```php
    }

    /**
     * Daftar tersimpan milik Jelajah (EXPLORE > Saved), bukan tab Trip.
```

dengan:

```php
    }

    /**
     * Tombol kembali tidak pernah memindah tab (tinjauan akhir W2, W2-F2): bila sebuah halaman
     * menyalakan tab, induk logisnya menyalakan tab yang sama. Sebelum W3, Banding menyalakan
     * Trip tetapi kembali ke Simpanan milik Jelajah. Halaman yang tidak menyalakan tab mana pun
     * (Profil, konsol, onboarding, kontribusi) bebas memilih induknya.
     *
     * Jalur halaman dibaca dari rute GET controller yang merendernya, dan jalur induknya dari
     * impor Wayfinder di halaman itu (`saved.url()` dari '@/routes/trails' adalah rute
     * trails.saved). Tab dihitung dari cermin aturan NavLinks.tsx, yang dicocokkan dulu dengan
     * sumbernya kata demi kata.
     */
    public function test_a_page_and_its_parent_light_the_same_tab(): void
    {
        foreach (self::AKTIF as $label => $sumber) {
            $this->assertStringContainsString($sumber, $this->aturan($label), "Aturan tab {$label} berubah; perbarui cermin AKTIF dan tabUntuk() di test ini.");
        }

        $this->assertStringContainsString('const modePendakian = (p: string) => /^\/trips\/[^/]+\/hike/.test(p);', File::get(resource_path('js/layouts/NavLinks.tsx')));

        $pelanggar = [];
        $diperiksa = [];
        $tanpaRute = [];

        foreach ($this->berkasTsx('pages') as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());
            $isi = $this->tanpaKomentarJs($berkas->getContents());
            $kepala = $this->tagBernama($isi, 'PageHeader');

            if ($kepala === [] || ! str_contains($kepala[0][0], ' induk={')) {
                continue;
            }

            $tabHalaman = $this->tabHalaman(substr($nama, 0, -4));

            if ($tabHalaman === false) {
                $tanpaRute[] = $nama;

                continue;
            }

            $diperiksa[] = $nama;

            if ($tabHalaman === null) {
                continue;
            }

            foreach ($this->ruteInduk($nama, $isi, $kepala[0][0]) as $rute) {
                $uri = app('router')->getRoutes()->getByName($rute)?->uri();

                if ($uri === null) {
                    $pelanggar[] = "{$nama}: rute induk {$rute} tidak ada";
                } elseif (($tabInduk = $this->tabUntuk($uri)) !== $tabHalaman) {
                    $pelanggar[] = "{$nama}: menyalakan {$tabHalaman}, tetapi induknya /{$uri} menyalakan ".($tabInduk ?? 'tidak ada tab');
                }
            }
        }

        $this->assertSame(['Dev/Primitif.tsx'], $tanpaRute, 'Hanya pameran primitif yang rutenya tidak terdaftar di test (hanya APP_ENV=local).');
        $this->assertContains('Trails/Compare.tsx', $diperiksa);
        $this->assertGreaterThan(30, count($diperiksa), 'Sapuan tidak menemukan halaman berinduk.');
        $this->assertSame([], $pelanggar, implode("\n", $pelanggar));
    }

    /**
     * Daftar tersimpan milik Jelajah (EXPLORE > Saved), bukan tab Trip.
```

3. Ganti:

```php
    }
}
```

dengan:

```php
    }

    /**
     * Tab yang menyala di sebuah path, menurut cermin AKTIF; null bila tidak ada.
     */
    private function tabUntuk(string $uri): ?string
    {
        $p = '/'.ltrim((string) preg_replace('/\{[^}]+\}/', 'x', $uri), '/');
        $mendaki = preg_match('#^/trips/[^/]+/hike#', $p) === 1;

        $nyala = array_keys(array_filter([
            'Beranda' => $p === '/dashboard' || str_starts_with($p, '/kabar'),
            'Jelajah' => (($p === '/trails' || str_starts_with($p, '/trails/')) && ! str_starts_with($p, '/trails/compare')) || str_starts_with($p, '/mountains/'),
            'Trip' => (str_starts_with($p, '/trips') && ! $mendaki) || str_starts_with($p, '/goals') || str_starts_with($p, '/recommendations') || str_starts_with($p, '/trails/compare'),
            'Mendaki' => str_starts_with($p, '/mendaki') || $mendaki,
            'Progres' => str_starts_with($p, '/progres') || str_starts_with($p, '/history') || str_starts_with($p, '/reports'),
        ]));

        $this->assertLessThanOrEqual(1, count($nyala), "Dua tab menyala di {$p}.");

        return $nyala[0] ?? null;
    }

    /**
     * Tab halaman dari rute GET controller yang merendernya: null bila rutenya tidak
     * menyalakan tab, false bila tidak ada controller atau rute yang merendernya.
     */
    private function tabHalaman(string $komponen): string|false|null
    {
        $kelas = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $berkas) {
            if (str_contains($berkas->getContents(), "'{$komponen}'")) {
                $kelas[] = 'App\\Http\\Controllers\\'.str_replace(['/', '.php'], ['\\', ''], str_replace('\\', '/', $berkas->getRelativePathname()));
            }
        }

        $tab = [];

        foreach (app('router')->getRoutes()->getRoutes() as $rute) {
            if (in_array('GET', $rute->methods(), true) && in_array($rute->getControllerClass(), $kelas, true)) {
                $tab[] = $this->tabUntuk($rute->uri()) ?? 'tanpa tab';
            }
        }

        $tab = array_values(array_unique($tab));

        if ($tab === []) {
            return false;
        }

        $this->assertCount(1, $tab, "{$komponen} dirender controller yang rutenya menyalakan tab berbeda: ".implode(', ', $tab));

        return $tab[0] === 'tanpa tab' ? null : $tab[0];
    }

    /**
     * Nama rute induk sebuah halaman: setiap `x.url(...)` di prop induk dipetakan lewat
     * impornya ('@/routes/trails' dengan `saved` adalah trails.saved), atau dari
     * INDUK_DARI_PROPS bila induknya URL dari props server.
     *
     * @return list<string>
     */
    private function ruteInduk(string $nama, string $isi, string $tag): array
    {
        $mulai = strpos($tag, ' induk={') + 7;
        $kedalaman = 0;
        $akhir = $mulai;

        for ($i = $mulai; $i < strlen($tag); $i++) {
            $kedalaman += $tag[$i] === '{' ? 1 : ($tag[$i] === '}' ? -1 : 0);

            if ($kedalaman === 0) {
                $akhir = $i;

                break;
            }
        }

        preg_match_all('/\b([A-Za-z_]\w*)\.url\(/', substr($tag, $mulai, $akhir - $mulai + 1), $pemanggil);

        if ($pemanggil[1] === []) {
            $this->assertArrayHasKey($nama, self::INDUK_DARI_PROPS, "{$nama}: induk tanpa rute Wayfinder; sebut rutenya di INDUK_DARI_PROPS.");

            return [self::INDUK_DARI_PROPS[$nama]];
        }

        $impor = [];
        preg_match_all("/import \{([^}]*)\} from '@\/routes((?:\/[\w-]+)*)';/", $isi, $baris, PREG_SET_ORDER);

        foreach ($baris as [, $daftar, $modul]) {
            foreach (array_map('trim', explode(',', $daftar)) as $satu) {
                [$ekspor, $lokal] = array_pad(array_map('trim', explode(' as ', $satu)), 2, null);
                $impor[$lokal ?? $ekspor] = ltrim(str_replace('/', '.', $modul).'.'.$ekspor, '.');
            }
        }

        return array_values(array_unique(array_map(function (string $lokal) use ($impor, $nama): string {
            $this->assertArrayHasKey($lokal, $impor, "{$nama}: {$lokal}.url() tidak diimpor dari @/routes.");

            return $impor[$lokal];
        }, $pemanggil[1])));
    }
}
```

`tests/Feature/RouteComparisonTest.php`:

1. Ganti:

```php
use App\Models\Trail;
```

dengan:

```php
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
```

2. Ganti:

```php
        $this->assertStringContainsString('Lihat daftar rute tersimpan ({jumlahTersimpan})', $halaman);
```

dengan:

```php
        $this->assertStringContainsString('Lihat simpanan ({jumlahTersimpan})', $halaman);
```

3. Ganti:

```php
        $this->assertStringContainsString('Centang sampai {pilihan.maks} jalur', $halaman);
```

dengan:

```php
        $this->assertStringContainsString('Centang sampai ${pilihan.maks} jalur', $halaman);
```

4. Ganti:

```php
            $this->assertSame(['id', 'name', 'mountain', 'url', 'fit', 'status', 'tersimpan', 'hapus'], array_keys($kolom));
        }
    }
```

dengan:

```php
            $this->assertSame(['id', 'name', 'mountain', 'url', 'fit', 'status', 'laporan', 'izin', 'rasioTanjakan', 'garis', 'rencanaUrl', 'tersimpan', 'hapus'], array_keys($kolom));
        }
    }

    /**
     * DK-31: "Jadikan dasar rencana" berbobot setara untuk setiap jalur, dalam urutan kolom:
     * satu rupa tombol untuk semuanya, jadi halaman tidak menyarankan jalur mana pun (R-016).
     */
    public function test_every_trail_can_be_the_plan_base_with_equal_weight(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])));

        $this->assertSame([route('trips.create', ['trail' => $a->id]), route('trips.create', ['trail' => $b->id])], array_column($props['jalur'], 'rencanaUrl'));

        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));
        $this->assertStringContainsString('<Button key={j.id} variant="secondary" href={j.rencanaUrl}', $halaman);
        $this->assertSame(1, substr_count($halaman, 'href={j.rencanaUrl}'));
    }

    /**
     * Bilah tanjakan dan garis profil memakai satu skala untuk semua kolom (spec v3 bagian
     * 6.7): pecahan dari tanjakan terbesar di tabel ini, dan profil pada skala tinggi yang
     * sama, dengan kalimatnya. Tanpa tanjakan tidak ada bilah (R-007).
     */
    public function test_climb_bars_and_profiles_share_one_scale(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur(['elevation_gain_m' => 800, 'distance_km' => 4, 'elevation_profile' => [['km' => 0, 'm' => 1000], ['km' => 4, 'm' => 1800]]]);
        $b = $this->jalur(['elevation_gain_m' => 1600, 'distance_km' => 8, 'elevation_profile' => [['km' => 0, 'm' => 1000], ['km' => 8, 'm' => 2600]]]);

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])));
        [$kolomA, $kolomB] = $props['jalur'];

        $this->assertSame(0.5, $kolomA['rasioTanjakan']);
        $this->assertSame(1.0, (float) $kolomB['rasioTanjakan']);
        $this->assertSame(0.5, max(array_column($kolomA['garis'], 1)));
        $this->assertSame(1.0, (float) max(array_column($kolomB['garis'], 1)));
        $this->assertStringStartsWith('Skala tinggi sama untuk semua jalur: ', $props['profil']);
        $this->assertStringNotContainsString('terbaik', $props['profil']);
    }

    /**
     * Laporan pendaki terbaru: hanya yang termoderasi dan dalam 30 hari, sebagai lapisan
     * komunitas (R-010); status resmi membawa sumber dan waktunya; izin dari aturan tercatat
     * atau pengakuan bahwa belum ada.
     */
    public function test_status_reports_and_permits_carry_their_origin(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];
        TrailConditionReport::factory()->approved()->create(['trail_id' => $a->id, 'hike_date' => now()->subDays(5)->toDateString()]);
        TrailConditionReport::factory()->create(['trail_id' => $b->id, 'hike_date' => now()->subDays(1)->toDateString()]);
        TrailConditionReport::factory()->approved()->create(['trail_id' => $b->id, 'hike_date' => now()->subDays(40)->toDateString()]);
        PermitRequirement::factory()->create(['trail_id' => $a->id, 'authority' => 'Balai Uji', 'booking_closes_days_before' => 2]);

        $props = $this->props($this->actingAs($user)->get(route('trails.compare', ['trails' => $a->id.','.$b->id])));
        [$kolomA, $kolomB] = $props['jalur'];

        $this->assertNotNull($kolomA['laporan']);
        $this->assertNull($kolomB['laporan']);
        $this->assertSame('Pesan lewat Balai Uji, tutup H-2', $kolomA['izin']);
        $this->assertSame('Belum ada aturan tercatat', $kolomB['izin']);
        $this->assertSame(['status', 'label', 'cakupan', 'sumber', 'waktu', 'kesegaran'], array_keys($kolomA['status']));
        $this->assertTrue($props['beda']['izin']);
    }

    /**
     * "Sorot yang berbeda" menandai baris yang berbeda tanpa menyembunyikan apa pun
     * (prototype 07), memakai sakelar, bukan kotak centang 28 px (keputusan koordinator W3
     * nomor 5). Bagikan (FR-23) baru ada bila ada yang dapat dibagikan atau dicabut (DK-40).
     */
    public function test_differing_rows_can_be_highlighted_and_the_comparison_shared(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));
        $tabel = File::get(resource_path('js/components/trails/banding/TabelBanding.tsx'));

        $this->assertStringContainsString('<Sakelar judul="Sorot yang berbeda" nyala={sorotBeda} onUbah={setSorotBeda}', $halaman);
        $this->assertStringNotContainsString('<Checkbox', $halaman);
        $this->assertStringNotContainsString('sembunyikanSama', $tabel);
        $this->assertStringContainsString('jalur.length >= 2 || tautanAktif.length > 0 ? (', $halaman);
        $this->assertStringContainsString('<LembarBagikan', $halaman);
    }
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

Ganti:

```php
    public function test_jelajah_tanpa_jalur_terbit(): void
```

dengan:

```php
    public function test_bandingkan(): void
    {
        $this->sebagaiBudi();

        $this->tulisPratinjau('bandingkan', $this->get(self::ASAL.'/trails/compare'), 'Trails/Compare');
    }

    public function test_jelajah_tanpa_jalur_terbit(): void
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='RouteComparison|ComparisonAsCost|NavigationConsistency'`
Expected: FAIL: 42 test, 34 lulus, 6 gagal, 2 error. Antara lain `NavigationConsistencyTest::test_a_page_and_its_parent_light_the_same_tab`, `RouteComparisonTest::test_the_page_names_what_was_left_out_and_why_nothing_is_compared`, `RouteComparisonTest::test_the_empty_selection_message_states_the_limit_from_config`.

- [ ] **Step 3: Tulis implementasinya.**

**Server**

`app/Http/Controllers/Trails/RouteComparisonController.php`:

1. Ganti:

```php
use App\Services\Trails\RouteComparisonTable;
```

dengan:

```php
use App\Services\Trails\RouteComparisonTable;
use App\Services\Trails\TautanBagikan;
use App\Support\Timezone;
```

2. Ganti:

```php
 * Berbagi ke orang tanpa akun menunggu tautan bagikan FR-23 (spec v3 §9.3); sampai itu
 * halaman ini tidak menawarkan tombol bagikan (DK-40).
```

dengan:

```php
 * Berbagi ke orang tanpa akun lewat tautan bagikan FR-23 (ComparisonShareController); halaman
 * ini membawa tautan yang masih berlaku supaya pembuatnya dapat mencabutnya.
```

3. Ganti:

```php
    public function show(Request $request, ConsiderationService $consideration, RouteComparisonTable $tabel): Response
```

dengan:

```php
    public function show(Request $request, ConsiderationService $consideration, RouteComparisonTable $tabel, TautanBagikan $tautan): Response
```

4. Ganti:

```php
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
```

dengan:

```php
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
            'tautanAktif' => $tautan->aktif($user)->map(fn ($s) => [
                'id' => $s->id,
                'jumlahJalur' => count($s->trail_ids),
                'dibuat' => $s->created_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M'),
                'berlakuSampai' => $s->expires_at->copy()->setTimezone(Timezone::DEFAULT)->translatedFormat('j M Y'),
            ])->values()->all(),
```

Ganti seluruh isi `app/Services/Trails/RouteComparisonTable.php` dengan:

```php
<?php

namespace App\Services\Trails;

use App\Enums\RouteFitLabel;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\PermitService;
use App\Services\TrailFitService;
use Illuminate\Support\Collection;

/**
 * Tabel banding (FR-06, spec v3 bagian 6.7, prototype 07). Sistem tidak pernah menyatakan
 * pemenang (PRD §33, R-016): yang ditandai hanya apakah suatu aspek berbeda antarjalur,
 * tidak pernah mana yang lebih baik. Tidak ada urutan menurut nilai dan tidak ada sorotan
 * nilai terkecil/terbesar.
 *
 * Barisnya dimensi ongkos, bukan daftar kolom basis data: tujuan pengguna yang sebenarnya,
 * menurut riset AllTrails, adalah memperkirakan waktu dan tenaga yang harus ia keluarkan.
 * Urutannya mengikuti itu, yang paling menentukan keputusan lebih dulu. Sejak W3 kolomnya
 * juga membawa status resmi beserta sumber dan waktunya, laporan pendaki terbaru (lapisan
 * komunitas, 30 hari), izin, dan garis profil pada skala tinggi yang sama; semuanya batch,
 * jadi kueri tidak tumbuh bersama jumlah kolom.
 */
class RouteComparisonTable
{
    public function __construct(
        private readonly TrailFitService $fit,
        private readonly StatusJalurRingkas $status,
        private readonly PermitService $permits,
    ) {}

    /**
     * @param  Collection<int, Trail>  $trails
     * @return array{jalur: array<int, array<string, mixed>>, baris: array<int, array{label: string, nilai: array<int, string>, berbeda: bool|null}>, beda: array<string, bool|null>, profil: string|null}
     */
    public function susun(Collection $trails, User $user): array
    {
        // Kecocokan dinilai hanya untuk profil yang cukup: menilai tanpa profil
        // menghasilkan label yang terlihat pasti dan berdasar ketiadaan (§91).
        $ringkasanFit = $user->hasCompletedProfile() ? $this->fit->forTrails($user, $trails) : [];
        $status = $this->status->untuk($trails);
        $izin = $this->permits->requirementsForTrails($trails);
        $laporan = $this->laporanTerbaru($trails);
        $garis = SkalaProfil::naikBersama($trails->mapWithKeys(fn (Trail $t) => [$t->id => $t->elevation_profile])->all())['garis'];
        $tanjakanTerbesar = max(1, (int) $trails->max('elevation_gain_m'));

        $jalur = $trails->values()->map(function (Trail $t) use ($ringkasanFit, $status, $izin, $laporan, $garis, $tanjakanTerbesar) {
            $fit = $ringkasanFit[$t->id] ?? null;

            return [
                'id' => $t->id,
                'name' => $t->name,
                'mountain' => $t->mountain?->name,
                'url' => route('trails.show', $t),
                'fit' => $fit === null ? null : [
                    'label' => $fit->label?->value,
                    'labelText' => RouteFitLabel::textFor($fit->label),
                    'alasan' => $fit->alasan,
                    'denganRencana' => $fit->denganRencana,
                ],
                'status' => $status[$t->id],
                'laporan' => $laporan[$t->id] ?? null,
                'izin' => $this->kataIzin($izin[$t->id] ?? null),
                // Bilah tanjakan pada skala yang sama: pecahan dari tanjakan terbesar di tabel
                // ini, bukan peringkat; tanpa tanjakan tidak ada bilah (R-007).
                'rasioTanjakan' => $t->elevation_gain_m === null ? null : round($t->elevation_gain_m / $tanjakanTerbesar, 3),
                'garis' => $garis[$t->id] ?? null,
                'rencanaUrl' => route('trips.create', ['trail' => $t->id]),
            ];
        })->all();

        return [
            'jalur' => $jalur,
            'baris' => $this->baris($trails),
            'beda' => [
                'fit' => self::berbeda(array_map(fn ($j) => $j['fit']['labelText'] ?? RouteFitLabel::UNRATED_TEXT, $jalur)),
                'status' => self::berbeda(array_map(fn ($j) => (string) $j['status']['label'], $jalur)),
                'laporan' => self::berbeda(array_map(fn ($j) => implode(', ', $j['laporan']['tags'] ?? []), $jalur)),
                'izin' => self::berbeda(array_column($jalur, 'izin')),
            ],
            'profil' => $this->keteranganProfil($trails),
        ];
    }

    /**
     * Baris ongkos: karakteristik jalur yang sama bagi setiap pembaca, tanpa kecocokan.
     * Dipakai juga halaman tautan bagikan, yang pembacanya tidak punya profil.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, array{label: string, nilai: array<int, string>, berbeda: bool|null}>
     */
    public function baris(Collection $trails): array
    {
        $baris = [
            'Waktu' => fn (Trail $t) => TrailFigures::durasi($t->estimated_duration_minutes),
            'Jarak' => fn (Trail $t) => TrailFigures::jarak($t->distance_km),
            'Tanjakan' => fn (Trail $t) => TrailFigures::meter($t->elevation_gain_m),
            'Kecuraman' => fn (Trail $t) => TrailFigures::kecuraman($t),
            'Tuntutan teknis' => fn (Trail $t) => $t->technical_demand?->label() ?? TrailFigures::BELUM,
            'Kerumitan navigasi' => fn (Trail $t) => $t->navigation_complexity?->label() ?? TrailFigures::BELUM,
            'Air' => fn (Trail $t) => $t->water_availability?->label() ?? TrailFigures::BELUM,
            'Berkemah' => fn (Trail $t) => $t->camping_available ? 'Bisa' : 'Tidak',
            'Medan' => fn (Trail $t) => TrailFigures::medan($t),
        ];

        return collect($baris)->map(function (callable $nilai, string $label) use ($trails) {
            $isi = $trails->values()->map($nilai)->all();

            return ['label' => $label, 'nilai' => $isi, 'berbeda' => self::berbeda($isi)];
        })->values()->all();
    }

    /**
     * Null ketika belum ada yang dapat dibandingkan: satu kolom tidak berbeda dari apa pun.
     *
     * @param  array<int, string>  $nilai
     */
    public static function berbeda(array $nilai): ?bool
    {
        return count($nilai) < 2 ? null : count(array_unique($nilai)) > 1;
    }

    /**
     * Laporan termoderasi terbaru per jalur dalam 30 hari (R-010: pelengkap status resmi,
     * bukan penggantinya), satu kueri untuk semua kolom. Kesegarannya dihitung dari tanggal
     * pendakian (§51).
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, array{tags: array<int, string>, kapan: string}>
     */
    private function laporanTerbaru(Collection $trails): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        return TrailConditionReport::query()
            ->visibleToPublic()
            ->whereIn('trail_id', $trails->pluck('id'))
            ->where('hike_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('hike_date')
            ->orderByDesc('id')
            ->get()
            ->unique('trail_id')
            ->mapWithKeys(fn (TrailConditionReport $r) => [$r->trail_id => [
                'tags' => array_map(fn ($tag) => $tag->label(), $r->tags()),
                'kapan' => 'pendakian '.$r->hike_date->translatedFormat('j M'),
            ]])
            ->all();
    }

    /**
     * Izin dalam kata: aturan yang tercatat, atau pengakuan bahwa belum ada sumbernya.
     * Aturan pihak lain dicatat, tidak digantikan (PRD §36).
     */
    private function kataIzin(?PermitRequirement $izin): string
    {
        if ($izin === null) {
            return 'Belum ada aturan tercatat';
        }

        return 'Pesan lewat '.$izin->authority.($izin->booking_closes_days_before !== null ? ', tutup H-'.$izin->booking_closes_days_before : '');
    }

    /**
     * Keterangan profil pada skala tinggi yang sama, dalam kalimat, tanpa menyebut mana yang
     * lebih baik (R-016).
     *
     * @param  Collection<int, Trail>  $trails
     */
    private function keteranganProfil(Collection $trails): ?string
    {
        $bagian = $trails
            ->filter(fn (Trail $t) => $t->elevation_gain_m !== null && $t->distance_km !== null)
            ->map(fn (Trail $t) => $t->name.' naik ↑'.number_format($t->elevation_gain_m, 0, ',', '.').' m dalam '.number_format((float) $t->distance_km, 1, ',', '.').' km')
            ->values();

        return $bagian->count() < 2 ? null : 'Skala tinggi sama untuk semua jalur: '.$bagian->join('; ').'.';
    }
}
```

**Antarmuka**

Ganti seluruh isi `resources/js/components/trails/banding/TabelBanding.tsx` dengan:

```tsx
import type { ReactNode } from 'react';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { FitLine } from '@/components/trails/FitLine';
import { GarisProfil } from '@/components/trails/GarisProfil';
import type { FitRingkas, StatusRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

export interface KolomJalur {
    id: number;
    name: string;
    mountain: string | null;
    url: string;
    fit: FitRingkas | null;
    /** Status akses beserta sumber dan waktunya (StatusJalurRingkas). */
    status: StatusRingkas;
    /** Laporan pendaki termoderasi terbaru dalam 30 hari, atau null. */
    laporan: { tags: string[]; kapan: string } | null;
    izin: string;
    /** Pecahan dari tanjakan terbesar di tabel ini, untuk bilah pada skala yang sama; null tanpa tanjakan. */
    rasioTanjakan: number | null;
    /** Garis profil pada skala tinggi yang sama dengan kolom lain; null tanpa profil. */
    garis: Array<[number, number]> | null;
    rencanaUrl: string;
    /** Jalur ini ada di simpanan pembaca; tautan lama dapat membawa jalur yang tidak ia simpan. */
    tersimpan: boolean;
    /** Tautan GET ke perbandingan yang sama tanpa kolom ini. Tidak pernah menghapus simpanan. */
    hapus: string;
}

export interface BarisBanding {
    label: string;
    nilai: string[];
    berbeda: boolean | null;
}

interface TabelBandingProps {
    jalur: KolomJalur[];
    baris: BarisBanding[];
    beda: { fit: boolean | null; status: boolean | null; laporan: boolean | null; izin: boolean | null };
    /** Kalimat profil pada skala yang sama (RouteComparisonTable), atau null. */
    profil: string | null;
    sorotBeda: boolean;
    /** Kata UNKNOWN dari server (RouteFitLabel::UNRATED_TEXT), untuk pembaca tanpa profil. */
    kataBelumDinilai: string;
}

const sel = 'p-4 align-top';
// Label aspek menempel di kiri ketika tabel digeser di layar sempit, supaya pembaca tidak
// kehilangan baris mana yang sedang ia baca.
const kepalaBaris = 'sticky left-0 z-20 bg-surface p-4 text-left align-top font-medium text-muted';

/**
 * Jalur berdampingan tanpa pemenang (R-016, spec v3 bagian 6.7, prototype 07): yang ditandai
 * hanya apakah sebuah aspek berbeda antarjalur, tidak pernah nilai mana yang lebih baik.
 * Aspek yang sama diberi tanda "sama". "Sorot yang berbeda" menebalkan baris yang berbeda dan
 * memberinya garis tepi, tanpa menyembunyikan apa pun dan tanpa warna yang bermakna baik atau
 * buruk.
 *
 * Tanjakan diberi bilah pada skala yang sama dan profil elevasi digambar pada skala tinggi yang
 * sama, dengan kalimatnya. Laporan pendaki ditulis dengan warna komunitas (lapisan komunitas,
 * R-010). "Hapus dari perbandingan" adalah tautan GET ke perbandingan tanpa kolom itu; halaman
 * ini tidak pernah menulis ke simpanan.
 */
export function TabelBanding({ jalur, baris, beda, profil, sorotBeda, kataBelumDinilai }: TabelBandingProps) {
    const barisSorot = (berbeda: boolean | null) => kelas('border-b border-subtle', sorotBeda && berbeda === true && 'bg-surface-sunken [&>*]:font-bold');

    const kepala = (label: string, berbeda: boolean | null): ReactNode => (
        <th scope="row" className={kelas(kepalaBaris, sorotBeda && berbeda === true && 'border-l-4 border-l-brand-ink bg-surface-sunken')}>
            {label}
            {berbeda === false ? <span className="ml-1 text-meta font-normal text-secondary">, sama</span> : null}
        </th>
    );

    return (
        <div className="relative overflow-x-auto rounded-lg border border-subtle bg-surface">
            <table className="min-w-full text-kecil">
                <caption className="sr-only">Perbandingan karakteristik jalur, tanpa pemenang</caption>
                <thead>
                    <tr className="border-b border-subtle text-left">
                        <th scope="col" className={kelas(kepalaBaris, 'font-normal')}>
                            Aspek
                        </th>
                        {jalur.map((j) => (
                            <th key={j.id} scope="col" className={kelas(sel, 'min-w-44')}>
                                <Tautan
                                    href={j.url}
                                    className="block rounded-sm py-1 text-subjudul text-primary underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    {j.name}
                                </Tautan>
                                <span className="block text-meta font-normal text-secondary">{j.mountain}</span>
                                {j.tersimpan ? null : <span className="block text-meta font-normal text-muted">Bukan simpanan Anda</span>}
                                <Button variant="ghost" size="sm" className="-ml-4 font-normal" href={j.hapus}>
                                    Hapus dari perbandingan<span className="sr-only">: {j.name}</span>
                                </Button>
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    <tr className={barisSorot(beda.fit)}>
                        {kepala('Kecocokan dasar', beda.fit)}
                        {jalur.map((j) => (
                            <td key={j.id} className={sel}>
                                {j.fit ? <FitLine fit={j.fit} trailId={j.id} /> : <span className="text-muted">{kataBelumDinilai}</span>}
                            </td>
                        ))}
                    </tr>

                    {baris.map((b) => (
                        <tr key={b.label} className={barisSorot(b.berbeda)}>
                            {kepala(b.label, b.berbeda)}
                            {b.nilai.map((nilai, i) => {
                                const kolom = jalur[i];

                                return (
                                    <td key={kolom?.id ?? i} data-angka="" className={kelas(sel, 'text-primary')}>
                                        {nilai}
                                        {b.label === 'Tanjakan' && kolom?.rasioTanjakan != null ? (
                                            <span aria-hidden="true" className="mt-2 block h-1.5 w-full rounded-full bg-surface-sunken">
                                                <span className="block h-1.5 rounded-full bg-brand-ink" style={{ width: `${Math.round(kolom.rasioTanjakan * 100)}%` }} />
                                            </span>
                                        ) : null}
                                    </td>
                                );
                            })}
                        </tr>
                    ))}

                    <tr className={barisSorot(beda.status)}>
                        {kepala('Status resmi', beda.status)}
                        {jalur.map((j) => (
                            <td key={j.id} className={sel}>
                                <BarisStatus status={j.status} />
                            </td>
                        ))}
                    </tr>

                    <tr className={barisSorot(beda.laporan)}>
                        {kepala('Laporan terbaru', beda.laporan)}
                        {jalur.map((j) => (
                            <td key={j.id} className={sel}>
                                {j.laporan ? (
                                    <>
                                        <span className="block font-bold text-community-900">{j.laporan.tags.join(', ') || 'Tanpa tanda kondisi'}</span>
                                        <span className="block text-meta text-muted">Komunitas, {j.laporan.kapan}</span>
                                    </>
                                ) : (
                                    <span className="text-secondary">Belum ada dalam 30 hari</span>
                                )}
                            </td>
                        ))}
                    </tr>

                    <tr className={barisSorot(beda.izin)}>
                        {kepala('Izin', beda.izin)}
                        {jalur.map((j) => (
                            <td key={j.id} className={kelas(sel, 'text-primary')}>
                                {j.izin}
                            </td>
                        ))}
                    </tr>

                    <tr>
                        <th scope="row" className={kepalaBaris}>
                            Profil elevasi, skala sama
                        </th>
                        {jalur.map((j) => (
                            <td key={j.id} className={sel}>
                                {j.garis ? <GarisProfil titik={j.garis} label={j.fit?.label ?? null} /> : <span className="text-secondary">Belum tersedia</span>}
                            </td>
                        ))}
                    </tr>
                </tbody>
            </table>
            {profil ? <p className="border-t border-subtle px-4 py-3 text-kecil text-secondary">{profil}</p> : null}
        </div>
    );
}
```

Ganti seluruh isi `resources/js/pages/Trails/Compare.tsx` dengan:

```tsx
import { usePage } from '@inertiajs/react';
import { useRef, useState, type MouseEvent, type ReactNode } from 'react';
import { LembarBagikan, type TautanAktif } from '@/components/trails/banding/LembarBagikan';
import { PemilihBanding, type OpsiBanding } from '@/components/trails/banding/PemilihBanding';
import { TabelBanding, type BarisBanding, type KolomJalur } from '@/components/trails/banding/TabelBanding';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Icon } from '@/components/ui/Icon';
import { PageHeader } from '@/components/ui/PageHeader';
import { Sakelar } from '@/components/ui/Sakelar';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex, saved } from '@/routes/trails';
import { index as tripsIndex } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    pilihan: { maks: number; opsi: OpsiBanding[] };
    jumlahTersimpan: number;
    pesan: string[];
    jalur: KolomJalur[];
    baris: BarisBanding[];
    beda: { fit: boolean | null; status: boolean | null; laporan: boolean | null; izin: boolean | null };
    profil: string | null;
    kataBelumDinilai: string;
    tautanAktif: TautanAktif[];
    pengumuman: string;
}

/**
 * Bandingkan (FR-06, DK-05, spec v3 bagian 6.7, prototype 07): dua sampai tiga jalur
 * berdampingan, tanpa pemenang (R-016). Halaman ini hanya membaca. Tanpa ?trails= yang
 * dibandingkan simpanan terbaru yang masih terbit; dengan ?trails= yang dibandingkan pilihan
 * pembaca. Yang tidak ikut dibandingkan disebut lewat `pesan`, tidak dibuang diam-diam.
 *
 * "Jadikan dasar rencana" berbobot setara untuk setiap jalur (DK-31): tombol yang sama rupanya,
 * dalam urutan kolom, jadi halaman tidak menyarankan jalur mana pun. Bagikan (FR-23) membuat
 * tautan hanya-baca untuk jalur yang sedang dibandingkan.
 *
 * Halaman ini milik tab Trip (DK-25, spec v3 bagian 5.3), jadi tombol kembalinya ke Trip dan
 * tidak memindah tab (tinjauan akhir W2, W2-F2). Jalan ke Simpanan, tempat pilihannya diubah,
 * tetap ada lewat "Lihat simpanan" di bawah judul.
 */
export default function Compare() {
    const { pilihan, jumlahTersimpan, pesan, jalur, baris, beda, profil, kataBelumDinilai, tautanAktif, pengumuman } = usePage<Props>().props;
    const [sorotBeda, setSorotBeda] = useState(false);
    const [bagikanTerbuka, setBagikanTerbuka] = useState(false);
    const pemicuBagikan = useRef<HTMLElement | null>(null);

    const bukaBagikan = (e: MouseEvent<HTMLElement>) => {
        pemicuBagikan.current = e.currentTarget;
        setBagikanTerbuka(true);
    };

    return (
        <div className="py-6">
            <div className="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: tripsIndex.url(), label: 'Trip' }}
                    title={jalur.length >= 2 ? `Bandingkan ${jalur.length} jalur` : 'Bandingkan jalur'}
                    description="Perbandingan ini tidak memilih pemenang. Ia menunjukkan bedanya, Anda yang memutuskan."
                    aksi={
                        jalur.length >= 2 || tautanAktif.length > 0 ? (
                            <Button variant="secondary" size="sm" onClick={bukaBagikan}>
                                <Icon name="link-simple" size={20} />
                                Bagikan
                            </Button>
                        ) : undefined
                    }
                >
                    {jumlahTersimpan > 0 ? (
                        <div className="mt-3">
                            <Button variant="ghost" size="sm" className="-ml-4" href={saved.url()}>
                                Lihat simpanan ({jumlahTersimpan})
                            </Button>
                        </div>
                    ) : null}
                </PageHeader>

                {pesan.length > 0 ? (
                    <Alert variant="info">
                        <ul className="space-y-1">
                            {pesan.map((p) => (
                                <li key={p}>{p}</li>
                            ))}
                        </ul>
                    </Alert>
                ) : null}

                {pilihan.opsi.length >= 2 ? <PemilihBanding pilihan={pilihan} urutan={jalur.map((j) => j.id)} /> : null}

                {jalur.length === 0 ? (
                    pilihan.opsi.length >= 2 ? (
                        <EmptyState jenis="pertama" title="Belum ada jalur yang dipilih" description={`Centang sampai ${pilihan.maks} jalur, lalu tekan Bandingkan.`} />
                    ) : (
                        <EmptyState jenis="pertama" title="Belum ada yang dapat dibandingkan" description="Perbandingan memakai jalur terbit yang Anda simpan. Simpan minimal dua jalur dari Jelajah lebih dulu.">
                            <Button variant="secondary" href={trailsIndex.url()}>
                                Jelajahi jalur
                            </Button>
                        </EmptyState>
                    )
                ) : (
                    <>
                        {jalur.length >= 2 ? <Sakelar judul="Sorot yang berbeda" nyala={sorotBeda} onUbah={setSorotBeda} className="max-w-xs" /> : null}

                        <TabelBanding jalur={jalur} baris={baris} beda={beda} profil={profil} sorotBeda={sorotBeda} kataBelumDinilai={kataBelumDinilai} />

                        <section aria-labelledby="judul-dasar-rencana" className="space-y-3">
                            <h2 id="judul-dasar-rencana" className="text-bagian text-primary">
                                Jadikan dasar rencana
                            </h2>
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {jalur.map((j) => (
                                    <Button key={j.id} variant="secondary" href={j.rencanaUrl} className="w-full">
                                        {j.name}
                                    </Button>
                                ))}
                            </div>
                            <p className="text-kecil text-secondary">Jalur yang tidak dipilih tetap ada di simpanan, jadi Anda bisa kembali tanpa mencari ulang.</p>
                        </section>
                    </>
                )}

                <LembarBagikan
                    terbuka={bagikanTerbuka}
                    onTutup={() => setBagikanTerbuka(false)}
                    pemicu={pemicuBagikan}
                    jalur={jalur.map((j) => ({ id: j.id, name: j.name }))}
                    tautanAktif={tautanAktif}
                />

                <p role="status" className="sr-only">
                    {pengumuman}
                </p>
            </div>
        </div>
    );
}

Compare.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='RouteComparison|ComparisonAsCost|NavigationConsistency'`
Expected: PASS: 42 test, 42 lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.723 test, 1.721 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus termasuk `test_bandingkan`. Pada simulasi rencana: 14 test, 14 lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 10: Tombol dan judul v3 di halaman publik jalur (Blade)

Temuan W1-F2; keputusan nomor 2; ruling W3-R47. Halaman publik `/pendakian/{slug}` masih Blade. Tombolnya memakai
komponen `x-ui.button` yang menyalin kelas `Button.tsx` (varian `primary`, `secondary`, dan `ghost`; ukuran `md` dan
`sm`), dan judulnya memakai skala `text-judul` dan `text-bagian`. Test sumber menjaga kedua salinan kelas tetap
sama.

**Files:**
- Create: `resources/views/components/ui/button.blade.php`
- Modify: `resources/views/public/trail.blade.php`
- Test: `tests/Feature/ButtonBladeParityTest.php` (baru)

**Interfaces:**
- Consumes: `resources/js/components/ui/Button.tsx` dan `gaya.ts` (`umpanTekan`) sebagai sumber kelas; komponen Blade halaman publik (`x-brand.head`, lencana Data contoh).
- Produces: komponen Blade `resources/views/components/ui/button.blade.php` (`<x-ui.button :href="..." variant="primary" size="md">`), dipakai `resources/views/public/trail.blade.php`.

- [ ] **Step 1: Tulis test yang gagal.**

Buat `tests/Feature/ButtonBladeParityTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Permukaan Blade memakai bentuk tombol dan judul v3 yang sama dengan React (W1-F2, W3
 * Tugas 10). Satu komponen Blade, x-ui.button, menyalin kelas Button.tsx; test ini menjaga
 * kedua salinan tetap sama, supaya perubahan bentuk tombol di satu tempat tidak diam-diam
 * tertinggal di tempat lain.
 */
class ButtonBladeParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_blade_button_carries_every_class_of_the_react_button(): void
    {
        $react = File::get(resource_path('js/components/ui/Button.tsx'));
        $gaya = File::get(resource_path('js/components/ui/gaya.ts'));
        $blade = File::get(resource_path('views/components/ui/button.blade.php'));

        preg_match_all("/(?:primary|secondary|ghost): '([^']+)'/", $react, $varian);
        preg_match("/const ukuran = size === 'sm' \\? '([^']+)' : '([^']+)';/", $react, $ukuran);
        preg_match("/export const umpanTekan = '([^']+)';/", $gaya, $tekan);

        $this->assertCount(3, $varian[1]);
        $this->assertCount(3, $ukuran);
        $this->assertCount(2, $tekan);

        foreach ([...$varian[1], $ukuran[1], $ukuran[2], $tekan[1], 'inline-flex items-center justify-center gap-2 rounded-tombol min-h-11 text-center font-bold', 'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50'] as $kelas) {
            $this->assertStringContainsString($kelas, $blade, "Kelas tombol React tidak ada di Blade: {$kelas}");
            $this->assertStringContainsString($kelas, $react.$gaya);
        }
    }

    /**
     * Halaman publik jalur: judul v3 seperti h1 PageHeader.tsx dan tombol lewat x-ui.button,
     * bukan kelas tombol lama yang ditulis tangan.
     */
    public function test_the_public_trail_page_uses_the_v3_title_and_button(): void
    {
        $trail = Trail::factory()->for(Mountain::factory()->create(['name' => 'Merbabu']))->published()->create(['name' => 'Jalur Selo']);

        $halaman = $this->get(route('public.trail', $trail))->assertOk();

        $halaman->assertSee('<h1 class="mt-1 text-balance text-judul text-primary">Jalur Selo</h1>', escape: false);
        $halaman->assertSee('rounded-tombol min-h-11', escape: false);
        $halaman->assertDontSee('text-3xl font-semibold', escape: false);

        $sumber = File::get(resource_path('views/public/trail.blade.php'));
        $this->assertStringContainsString('<x-ui.button :href="route(\'login\')"', $sumber);
        $this->assertStringNotContainsString('rounded-control bg-brand-700', $sumber);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='ButtonBladeParity'`
Expected: FAIL: 2 test, 0 lulus, 1 gagal, 1 error. Antara lain `ButtonBladeParityTest::test_the_public_trail_page_uses_the_v3_title_and_button`, `ButtonBladeParityTest::test_the_blade_button_carries_every_class_of_the_react_button`.

- [ ] **Step 3: Tulis implementasinya.**

**Blade**

Buat `resources/views/components/ui/button.blade.php`:

```blade
@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

{{--
    Tombol v3 untuk permukaan Blade (halaman publik jalur; W1-F2), salinan kelas
    resources/js/components/ui/Button.tsx supaya halaman tanpa React memakai bentuk yang sama:
    radius 16 px, label tebal, target sentuh 44 px, fokus terlihat, dan tekan 0,98.
    ButtonBladeParityTest menjaga kedua salinan tetap sama.
--}}
@php
    $ukuran = $size === 'sm' ? 'px-4 py-2 text-kecil' : 'px-5 py-3 text-isi';
    $gaya = [
        'primary' => 'bg-brand-700 text-on-brand hover:bg-brand-800 focus-visible:ring-brand-600',
        'secondary' => 'border border-control bg-surface text-primary hover:bg-surface-sunken focus-visible:ring-brand-600',
        'ghost' => 'text-brand-ink underline underline-offset-2 hover:text-brand-900 focus-visible:ring-brand-600',
    ][$variant];
    $kelas = 'inline-flex items-center justify-center gap-2 rounded-tombol min-h-11 text-center font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-[transform,background-color,border-color,color] duration-sentuh ease-gerak active:scale-[0.98] disabled:active:scale-100 '.$ukuran.' '.$gaya;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</button>
@endif
```

`resources/views/public/trail.blade.php`:

1. Ganti:

```blade
                <p class="text-sm font-medium text-muted">{{ $trail->mountain->name }} &middot; {{ $trail->mountain->province }}</p>
```

dengan:

```blade
                <p class="text-kecil font-bold text-secondary">{{ $trail->mountain->name }}, {{ $trail->mountain->province }}</p>
```

2. Ganti:

```blade
                <h1 class="mt-1 text-3xl font-semibold text-primary">{{ $trail->name }}</h1>

                @if ($trail->description)
                    <p class="mt-3 text-base text-secondary">{{ $trail->description }}</p>
```

dengan:

```blade
                {{-- Judul v3, sama dengan h1 PageHeader.tsx (W1-F2). --}}
                <h1 class="mt-1 text-balance text-judul text-primary">{{ $trail->name }}</h1>

                @if ($trail->description)
                    <p class="mt-3 text-isi text-secondary">{{ $trail->description }}</p>
```

3. Ganti:

```blade
                <h2 class="text-lg font-semibold text-primary">Karakteristik jalur</h2>
```

dengan:

```blade
                <h2 class="text-bagian text-primary">Karakteristik jalur</h2>
```

4. Ganti:

```blade
                    <h2 class="text-lg font-semibold text-primary">Profil elevasi</h2>
```

dengan:

```blade
                    <h2 class="text-bagian text-primary">Profil elevasi</h2>
```

5. Ganti:

```blade
                    <h2 class="text-lg font-semibold text-primary">Pos</h2>
```

dengan:

```blade
                    <h2 class="text-bagian text-primary">Pos</h2>
```

6. Ganti:

```blade
                <h2 class="text-base font-semibold text-warn-900">Sebelum berangkat</h2>
```

dengan:

```blade
                <h2 class="text-subjudul text-warn-900">Sebelum berangkat</h2>
```

7. Ganti:

```blade
                <a href="{{ route('login') }}"
                    class="mt-3 inline-flex min-h-11 items-center rounded-control bg-brand-700 px-4 py-2 text-sm font-medium text-on-brand hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    Lihat status terkini di aplikasi
                </a>
```

dengan:

```blade
                <x-ui.button :href="route('login')" class="mt-3">Lihat status terkini di aplikasi</x-ui.button>
```

- [ ] **Step 4: Jalankan test dan pastikan lulus.**

Run: `php artisan test --compact --filter='ButtonBladeParity'`
Expected: PASS: 2 test, 2 lulus.

- [ ] **Step 5: Suite, tipe, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.725 test, 1.723 lulus, 2 dilewati.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `php artisan test --compact --testsuite=Pratinjau`
Expected: build `✓ built`; Pratinjau lulus. Pada simulasi rencana: 14 test, 14 lulus.

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 11: Dokumen klaster Jelajah

Ruling W3-R48. Dokumen yang menyebut komponen lama atau "menyusul W3" diperbarui supaya sesuai kode sesudah Tugas 1
sampai 10:

- `DESIGN.md` bagian Turunan: aksen pos yang kini terpasang, urutan dan anggaran tinggi lapisan bawah tanpa baki
  Jelajah, letak komponen domain dan tata bahasa kepercayaan, halaman tugas, pemilik tab, dan peta Jelajah;
- `docs/ARCHITECTURE.md`: alur produk, tempat mengubah atribusi, tautan bagikan, kandidat Jelajah, dan tata bahasa
  kepercayaan, kelas halaman dan service baru, flash bersama, serta kontrak SEO halaman tautan bagikan;
- `CONTRIBUTING.md`: tabel aturan dan penjaganya;
- `docs/fitur/01`, `02`, `03`, dan `05`: status sub-fitur dan jalur berkasnya.

Bagian Arah dan aturan canonical di `DESIGN.md` dipaku hash dan tidak disentuh. `docs/PRD.md` dan
`docs/KEBIJAKAN-DATA-PRIBADI.md` tidak diubah: PRD butuh catatan tata kelola §146 (Konflik 5), dan tautan bagikan
mengikuti bawaan kebijakan bagian 3 (data tanpa dasar retensi dihapus bersama akun).

**Files:**
- Modify: `CONTRIBUTING.md`, `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur/01-jelajah-rute.md`, `docs/fitur/02-detail-rute.md`, `docs/fitur/03-pertimbangkan.md`, `docs/fitur/05-kabar-kondisi.md`

**Interfaces:**
- Consumes: nama berkas, kelas, rute, dan test dari Tugas 1 sampai 10.
- Produces: dokumen yang cocok dengan kode; `DesignDirectionTest` tetap memaku hash bagian Arah dan aturan canonical.

- [ ] **Step 1: Perbarui dokumen.** Tugas ini tanpa test merah baru: dokumennya dijaga test yang sudah ada
  (`DesignDirectionTest`, `TeamDocsIndexTest`, `PrdReferenceIntegrityTest`, `RepoHygieneTest`, `SkillGovernanceTest`,
  `SemanticTokenTest`), dan setiap klaim di dokumen menunjuk berkas atau test yang ada.

**Dokumen**

`CONTRIBUTING.md`:

Ganti:

```markdown
| Skor kecocokan tidak pernah sampai ke halaman | `FactorBarsDoNotLeakScoreTest`, `LockedBusinessRulesTest` |
```

dengan:

```markdown
| Skor kecocokan tidak pernah sampai ke halaman | `FactorBarsDoNotLeakScoreTest`, `LockedBusinessRulesTest`, `FitAssessmentPageTest`, `TrailCardTest` |
| Label kecocokan selalu ikon dan kata; "Belum dinilai" datang dari server; glyph resmi hanya untuk sumber OFFICIAL | `TrustGrammarTest` |
| Tombol kembali tidak memindah tab: induk halaman menyalakan tab yang sama dengan halamannya | `NavigationConsistencyTest` |
| Tautan bagikan: token hanya disimpan hash-nya; halaman publik noindex, tanpa catatan dan label kecocokan; hanya pemilik yang mencabut | `ComparisonShareTest` |
| Jumlah kueri halaman Jelajah, gunung, rincian jalur, penilaian kecocokan, Simpanan, Bandingkan, dan tautan bagikan tidak tumbuh bersama jumlah data | `ResultListBudgetTest`, `MountainPageTest`, `TrailDetailPageTest`, `FitAssessmentPageTest`, `SavedRoutesTest`, `PageQueryBudgetTest`, `ComparisonShareTest` |
```

`DESIGN.md`:

1. Ganti:

```markdown
dan label. Aksen ini dicadangkan untuk pos berkemah dan tujuan di profil elevasi, peta, dan bilah langkah (W3 sampai
W5), serta untuk cincin fokus lapangan gelap (W6). Tanda merek tetap `currentColor` dengan token `logo-mark` sampai
gerbang DK-16.
```

dengan:

```markdown
dan label. Sejak W3 aksen ini menandai pos berkemah dan tujuan di profil elevasi, peta jalur, dan daftar pos rincian
jalur, selalu bersama lingkaran bernomor dan kata jenis posnya. Bilah langkah (W4 dan W5) dan cincin fokus lapangan
gelap (W6) menyusul. Tanda merek tetap `currentColor` dengan token `logo-mark` sampai gerbang DK-16.
```

2. Ganti:

```markdown
Skala lapisan: bar aksi, baki banding, bilah tab, strip pendakian aktif, dan header layar lebar `z-30`; toast dan
```

dengan:

```markdown
Skala lapisan: bar aksi, bilah tab, strip pendakian aktif, dan header layar lebar `z-30`; toast dan
```

3. Ganti:

```markdown
aksi 0,75rem di atasnya (halaman tugas) atau baki banding tepat di atasnya (Jelajah); lalu toast 1rem di atas navigasi
ditambah tinggi bar aksi dan baki. `--nav-bawah` adalah jumlah tinggi bilah tab dan strip, diturunkan `app.css`
dari elemen yang benar-benar ada di halaman (W2). Selama terpasang atau tampil, bar aksi, strip, dan baki banding
mengukur tingginya sendiri dan menuliskannya ke `--bar-aksi`, `--strip-pendakian`, dan `--baki-banding` di `<html>`.
Pengganjal bar aksi di bawah isi halaman membaca `--bar-aksi`, dan jarak bawah toast membaca `--nav-bawah`,
`--bar-aksi`, dan `--baki-banding`, jadi toast tidak menutupi tombol bar aksi, maupun tombol baki selagi baki
menempel di tepi bawah, juga ketika bar, strip, atau baki membungkus ke dua baris di layar sempit
(`DesignPrimitivesTest`, `ActiveHikeStripTest`, `InertiaLayoutTest`).
```

dengan:

```markdown
aksi 0,75rem di atasnya; lalu toast 1rem di atas navigasi ditambah tinggi bar aksi. `--nav-bawah` adalah jumlah tinggi
bilah tab dan strip, diturunkan `app.css` dari elemen yang benar-benar ada di halaman (W2). Selama terpasang atau
tampil, bar aksi dan strip mengukur tingginya sendiri dan menuliskannya ke `--bar-aksi` dan `--strip-pendakian` di
`<html>`. Pengganjal bar aksi di bawah isi halaman membaca `--bar-aksi`, dan jarak bawah toast membaca `--nav-bawah`
dan `--bar-aksi`, jadi toast tidak menutupi tombol bar aksi, juga ketika bar atau strip membungkus ke dua baris di
layar sempit (`DesignPrimitivesTest`, `ActiveHikeStripTest`, `InertiaLayoutTest`).

Anggaran tinggi lapisan bawah di 320 sampai 360 px (tinjauan akhir W2, W2-F1 dan W2-F5): bar aksi rincian jalur satu
baris (simpan berupa tombol ikon 44 px, Banding dan Rencanakan trip berukuran kecil), dan baki banding Simpanan satu
baris ringkas (hitungan, ruas, pesan pendek, satu aksi) tanpa daftar nama. Jelajah tidak punya baki; hitungan dan
pintu Simpanan ada di kepala halaman (prototype 02 dan 15). Panel yang menempel di layar lebar (peta dan saringan
Jelajah, kolom kanan rincian jalur) menyisakan header, pesan jaringan, dan lapisan bawah dari tingginya (W2-F3).
```

4. Ganti:

```markdown
terpotong (`DesignPrimitivesTest`, `LongInputLayoutTest`). Enam komponen domain yang masih di folder itu
(CheckpointJourney, ElevationProfile, FactorBars, FitBadge, StatusBadge, Freshness; 24 impor dan 5 berkas test yang
menunjuk jalurnya) dipindah W3 bersama penulisan ulangnya.
```

dengan:

```markdown
terpotong (`DesignPrimitivesTest`, `LongInputLayoutTest`). Komponen domain tidak tinggal di folder itu: W3 memindah
dan menulis ulang enam yang tersisa ke `resources/js/components/trails/` (FitBadge menjadi `LabelKecocokan`,
StatusBadge menjadi `StatusAkses`, Freshness menjadi `Kesegaran`, FactorBars menjadi `FaktorKecocokan`,
ElevationProfile menjadi `ProfilElevasi`, CheckpointJourney menjadi `rinci/DaftarPos`), bersama `LabelSumber` yang
naik dari `rinci/`.

**Tata bahasa kepercayaan** (W3, spec v3 bagian 7). Label kecocokan selalu ikon dan kata, tanpa angka: tiga label
publik, ditambah "Belum dinilai" untuk UNKNOWN yang katanya datang dari server (`RouteFitLabel`). Status akses dibawa
bentuk dan kata, beserta sumber dan kesegarannya. Tanpa catatan yang berlaku, statusnya Belum diketahui, bukan Buka.
Glyph `resmi` hanya menemani sumber OFFICIAL (`TrustGrammarTest`). Sumber di rincian jalur diurutkan menurut otoritas
(R-009), dengan interpretasi sistem terakhir (`TrailDetailPageTest`).
```

5. Ganti:

```markdown
  tugas dengan bar aksi tetap (detail jalur, rencanakan trip; penilaian kecocokan menyusul di W3) memasang
```

dengan:

```markdown
  tugas dengan bar aksi tetap (detail jalur, penilaian kecocokan, rencanakan trip) memasang
```

6. Ganti:

```markdown
  lapangan). Strip pendakian, bar aksi, baki banding Jelajah, dan pesan jaringan mengukur tingginya sendiri lewat
  `ResizeObserver` dan menuliskannya ke `--strip-pendakian`, `--bar-aksi`, `--baki-banding`, dan `--pesan-jaringan` di
  `<html>` selama masing-masing terpasang atau tampil.
- Fokus papan ketik dan sasaran jangkar tidak berhenti di balik lapisan tetap (WCAG 2.4.11): `scroll-padding-bottom`
  menghitung bilah tab dan strip (`--nav-bawah`), bar aksi (ruling W2-2a), dan baki banding (perbaikan akhir W2);
```

dengan:

```markdown
  lapangan). Strip pendakian, bar aksi, dan pesan jaringan mengukur tingginya sendiri lewat `ResizeObserver` dan
  menuliskannya ke `--strip-pendakian`, `--bar-aksi`, dan `--pesan-jaringan` di `<html>` selama masing-masing
  terpasang atau tampil. Baki banding Jelajah beserta variabel tingginya dilepas W3.
- Fokus papan ketik dan sasaran jangkar tidak berhenti di balik lapisan tetap (WCAG 2.4.11): `scroll-padding-bottom`
  menghitung bilah tab dan strip (`--nav-bawah`) dan bar aksi (ruling W2-2a);
```

7. Ganti:

```markdown
Jelajah berbasis peta tetap mengikuti keputusan F2 (peta menggambar bentuk jalur, bukan titik gunung) sampai W3
menulis ulang klasternya.
```

dengan:

```markdown
**Pemilik tab** (spec v3 bagian 5.3, DK-25). Halaman gunung menyalakan Jelajah. Banding milik Trip, jadi tombol
kembalinya ke Trip, dan Simpanan tetap terjangkau lewat "Lihat simpanan" di bawah judulnya. Tombol kembali tidak
pernah memindah tab: bila sebuah halaman menyalakan tab, induknya menyalakan tab yang sama
(`NavigationConsistencyTest`, tinjauan akhir W2, W2-F2).

**Peta Jelajah** (W3) tetap mengikuti keputusan F2: yang digambar bentuk jalur bergeometri, bukan titik gunung. Label
kecocokan dibedakan pola garis (utuh untuk Cocok, putus panjang untuk Perlu persiapan, titik-titik untuk Kurang cocok,
putus-titik untuk Belum dinilai) dan legenda bertulisan, bukan warna saja. Jalur tanpa geometri disebut jumlahnya di
pojok peta. Bila tidak satu pun jalur punya geometri, MapLibre tidak dimuat dan panelnya menyebut yang ditunggu.
```

`docs/ARCHITECTURE.md`:

1. Ganti:

```markdown
| Jelajah jalur | **[React]** `Http\Controllers\Trails\TrailIndexController` → `pages/Trails/Index.tsx`; `Trails\TrailDetail`, `Trails\RouteComparison` (Livewire) | `/trails*` |
```

dengan:

```markdown
| Jelajah jalur | **[React]** Jelajah, rincian jalur, penilaian kecocokan, Simpanan, dan Bandingkan di `pages/Trails/`; halaman gunung `pages/Mountains/Show.tsx`; tautan bagikan publik `pages/Public/SharedComparison.tsx` | `/trails*`, `/mountains/{mountain:slug}`, `/b/{token}` |
```

2. Ganti:

```markdown
| Sumber tile peta | `config/hiking.php` → `map`, atau variabel `MAP_*` di `.env` |
```

dengan:

```markdown
| Sumber tile peta dan atribusinya | `config/hiking.php` → `map`, atau variabel `MAP_*` di `.env`; atribusi bawaan OpenTopoMap termasuk SRTM (`MapConfigTest`) |
| Umur, jumlah, dan batas laju tautan bagikan perbandingan | `config/hiking.php` → `share_links` (`ttl_days`, `max_active_per_user`, `views_per_minute` per alamat IP); membuat dan mencabut memakai `rate_limits.personal_writes_per_minute` |
| Jumlah kandidat yang dinilai Jelajah per kunjungan | `config/hiking.php` → `jelajah.batas_kandidat`; halaman menyebut bila hasilnya terpotong |
| Label kecocokan, status akses, kesegaran, dan label sumber di layar | `resources/js/components/trails/` (`LabelKecocokan`, `StatusAkses`, `Kesegaran`, `LabelSumber`); ringkasan status bersumber dari `app/Services/Trails/StatusJalurRingkas.php` |
```

3. Ganti:

```markdown
| `AuditLogService`, `AnalyticsRecorder` | Jejak audit dan funnel |
```

dengan:

```markdown
| `AuditLogService`, `AnalyticsRecorder` | Jejak audit dan funnel |
| `Trails\HalamanJelajah`, `Trails\TrailDetailPage`, `Trails\PenilaianKecocokan`, `Trails\RouteComparisonTable`, `Mountains\HalamanGunung` | Kelas halaman (spec v3 bagian 9.2): satu kelas merakit props satu halaman React dari service domain, dengan anggaran kueri yang tidak tumbuh bersama jumlah data |
| `Trails\TautanBagikan` | Tautan bagikan perbandingan (FR-23): token acak 192 bit yang hanya disimpan hash SHA-256-nya, masa berlaku, batas tautan aktif, dan cabut oleh pemiliknya |
| `Trails\ProfilElevasiJalur`, `Trails\PosSepanjangJalur` | Profil elevasi dengan ruas tercuram, dan km pos di sepanjang garis rute (PostGIS); tanpa geometri tidak ada km yang ditebak |
```

4. Ganti:

```markdown
- **Props bersama** (`Http\Middleware\HandleInertiaRequests`): `auth.user` secukupnya untuk navigasi (peran hanya menentukan apa yang DITAMPILKAN), `merek`, `demo` (lencana Data contoh), `jumlahSimpanan` dan `pendakianAktif` (kerangka; null untuk tamu dan di mode lapangan), `daftarPengingat` (opsional, hanya lewat muat ulang parsial lembar Pengingat), `tema`, `tampilan` (efek dan gerak), `peta`, `adminNav` (hanya admin), dan `flash`, ditambah `errors` dari middleware dasar Inertia. Biaya kueri props kerangka ada di baris "Props bersama kerangka" pada tabel di atas. Bentuknya dikunci di `resources/js/types/index.d.ts`.
```

dengan:

```markdown
- **Props bersama** (`Http\Middleware\HandleInertiaRequests`): `auth.user` secukupnya untuk navigasi (peran hanya menentukan apa yang DITAMPILKAN), `merek`, `demo` (lencana Data contoh), `jumlahSimpanan` dan `pendakianAktif` (kerangka; null untuk tamu dan di mode lapangan), `daftarPengingat` (opsional, hanya lewat muat ulang parsial lembar Pengingat), `tema`, `tampilan` (efek dan gerak), `peta`, `adminNav` (hanya admin), dan `flash` (termasuk `simpananTerhapus` untuk Urungkan dan `tautanBagikan` yang membawa token sekali saja), ditambah `errors` dari middleware dasar Inertia. Biaya kueri props kerangka ada di baris "Props bersama kerangka" pada tabel di atas. Bentuknya dikunci di `resources/js/types/index.d.ts`.
```

5. Ganti:

```markdown
| `apps/frontend/src` (components, core, features, views) | Komponen fitur di `resources/js/components`, inti di `resources/js/lib`, halaman di `resources/js/pages`; primitif desain di `resources/js/components/ui/` (komponen domain yang masih di sana dipindah W3) |
```

dengan:

```markdown
| `apps/frontend/src` (components, core, features, views) | Komponen fitur di `resources/js/components`, inti di `resources/js/lib`, halaman di `resources/js/pages`; primitif desain di `resources/js/components/ui/`, komponen domain jalur di `resources/js/components/trails/` (W3) |
```

6. Ganti:

```markdown
- Larangan konten tipis hasil AI dalam skala besar ada di `docs/PRD.md` BR-16.
```

dengan:

```markdown
- Larangan konten tipis hasil AI dalam skala besar ada di `docs/PRD.md` BR-16.
- Halaman tautan bagikan `/b/{token}` tidak diindeks (meta `robots` dan header `X-Robots-Tag: noindex`), tidak masuk sitemap, dan tidak memuat catatan pribadi maupun label kecocokan (`ComparisonShareTest`).
```

`docs/fitur/01-jelajah-rute.md`:

1. Ganti:

```markdown
| Kosong karena saringan | Penyebab dan saringan yang dapat dilonggarkan | Ada: `EmptyState` "Tidak ada jalur yang cocok" di `resources/js/pages/Trails/Index.tsx` |
| Pertama kali, profil belum lengkap | Tanpa label tebakan; "Belum dinilai" dan ajakan mengisi profil | Ada: tombol "Isi profil sekarang" di `resources/js/pages/Trails/Index.tsx`; kecocokan tidak dihitung tanpa profil |
| Data jalur belum lengkap | Tidak dihitung, tidak direkomendasikan, tampil terpisah | Ada: daftar `menunggu` dari `TrailIndexController`, kartu `resources/js/components/trails/Menunggu.tsx` |
| Memuat | Kerangka kartu, bukan area kosong | Sebagian: kunjungan parsial Inertia mempertahankan daftar lama; skeleton kartu menyusul W3 |
| Galat peta | Kalimat jujur, daftar tetap tersedia | Ada: pengakuan gagal gaya peta di `resources/js/components/peta/usePetaDasar.ts` |
| Belum ada jalur bergeometri | Penjelasan, daftar tetap tampil | Sebagian: pilihan Peta hanya muncul bila ada data sebaran; kalimat jumlah jalur yang belum tergambar menyusul W3 |
```

dengan:

```markdown
| Kosong karena saringan | Penyebab dan saringan yang dapat dilonggarkan | Ada (W3): "Tidak ada jalur dengan saringan ini" dengan jumlah yang disembunyikan, "Tampilkan juga", dan "Hapus semua saringan"; katalog yang belum terbit diperiksa lebih dulu sebagai keadaan menunggu (W1-F1, `tests/Feature/TrailIndexSearchTest.php`) |
| Pertama kali, profil belum lengkap | Tanpa label tebakan; "Belum dinilai" dan ajakan mengisi profil | Ada (W3): setiap kartu berlabel "Belum dinilai" dan kotak "Jalur di sini belum dinilai untuk Anda" dengan "Isi profil pendaki" di `resources/js/pages/Trails/Index.tsx`; kecocokan tidak dihitung tanpa profil |
| Data jalur belum lengkap | Tidak dihitung, tidak direkomendasikan, tampil terpisah | Ada (W3): bagian "Belum bisa dinilai" dengan kartu bergaris putus-putus `resources/js/components/trails/jelajah/KartuBelumDinilai.tsx` yang menyebut yang ditunggu (`tests/Feature/TrailCardTest.php`) |
| Memuat | Kerangka kartu, bukan area kosong | Ada (W3): skeleton kartu selama kunjungan saringan di `resources/js/pages/Trails/Index.tsx` |
| Galat peta | Kalimat jujur, daftar tetap tersedia | Ada: pengakuan gagal gaya peta di `resources/js/components/peta/usePetaDasar.ts` |
| Belum ada jalur bergeometri | Penjelasan, daftar tetap tampil | Ada (W3): kalimat jumlah jalur tanpa garis rute di pojok peta; tanpa satu pun geometri, MapLibre tidak dimuat dan panel peta menyebut yang ditunggu (`tests/Feature/TrailIndexMapTest.php`) |
```

2. Ganti:

```markdown
| Peta rute interaktif | Ada | `resources/js/components/trails/PetaJelajah.tsx`, `app/Services/Trails/PetaSebaranJalur.php` (hanya jalur bergeometri, PostGIS), `tests/Feature/TrailIndexMapTest.php`, `tests/Spatial/PetaSebaranJalurTest.php` |
| Daftar dan kartu rute | Ada | `resources/js/pages/Trails/Index.tsx`, `resources/js/components/trails/TrailRow.tsx`, `app/Http/Resources/TrailRowResource.php`, `tests/Feature/TrailRowTest.php`; 12 jalur per halaman (`TrailSearch::PER_HALAMAN`) |
| Pencarian dan saringan | Ada untuk kata kunci, wilayah, jarak, durasi, tuntutan teknis | `app/Services/Trails/TrailSearch.php`, `resources/js/components/trails/FilterForm.tsx`, `tests/Feature/TrailIndexSearchTest.php` |
| Pencarian kalimat (FR-27) | Sebagian (temuan F3) | `TrailSearch` mengenali lima alias kota (Malang, Surabaya, Bandung, Bogor, Semarang) dan membuang teks pencarian ketika alias cocok; chip tafsiran yang dapat dilepas menyusul W3 |
| Label kecocokan dan "Kenapa?" | Ada | `resources/js/components/trails/FitLine.tsx`, `app/Services/TrailFitService.php`, `tests/Feature/AmbientFitTest.php`, `tests/Feature/FactorBarsDoNotLeakScoreTest.php` |
| Ajakan lengkapi profil | Ada | `resources/js/pages/Trails/Index.tsx` ("Isi profil sekarang") |
| Simpan rute | Ada | Rute `trails.timbang`, `app/Http/Controllers/Trails/ConsiderationController.php`, baki `resources/js/components/trails/ConsiderationTray.tsx`, `tests/Feature/ConsiderationTest.php` |
```

dengan:

```markdown
| Peta rute interaktif | Ada | `resources/js/components/trails/PetaJelajah.tsx`, `app/Services/Trails/PetaSebaranJalur.php` (hanya jalur bergeometri, PostGIS), `tests/Feature/TrailIndexMapTest.php`, `tests/Spatial/PetaSebaranJalurTest.php`; sejak W3 pola garis per label dengan legenda, chip pemilih, dan kartu pratinjau |
| Daftar dan kartu rute | Ada | `resources/js/pages/Trails/Index.tsx`, `resources/js/components/trails/jelajah/KartuJalur.tsx`, `app/Http/Resources/KartuJalurResource.php`, `app/Services/Trails/HalamanJelajah.php`, `tests/Feature/TrailCardTest.php`, `tests/Feature/ResultListBudgetTest.php`; 12 jalur per halaman (`TrailSearch::PER_HALAMAN`) |
| Pencarian dan saringan | Ada untuk kata kunci, wilayah, kecocokan dasar, jenis trip, tuntutan teknis, status resmi, dan rentang tanjakan; lembar saringan menghitung "Tampilkan N jalur" | `app/Services/Trails/TrailSearch.php`, `resources/js/components/trails/jelajah/SaringanJelajah.tsx`, `resources/js/components/trails/jelajah/saringan.ts`, `tests/Feature/TrailIndexSearchTest.php` |
| Pencarian kalimat (FR-27) | Sebagian (temuan F3) | `TrailSearch` mengenali lima alias kota (Malang, Surabaya, Bandung, Bogor, Semarang) dan membuang teks pencarian ketika alias cocok; sejak W3 tafsirannya tampil sebagai chip yang dapat dilepas dengan "Kembalikan tafsiran kalimat" (`resources/js/components/trails/jelajah/TafsiranKalimat.tsx`) |
| Label kecocokan dan "Kenapa?" | Ada | `resources/js/components/trails/LabelKecocokan.tsx` di `KartuJalur.tsx` ("Kenapa?" dan "Lihat penilaian lengkap"), `app/Services/TrailFitService.php`, `tests/Feature/TrailCardTest.php`, `tests/Feature/TrustGrammarTest.php`, `tests/Feature/FactorBarsDoNotLeakScoreTest.php` |
| Ajakan lengkapi profil | Ada | `resources/js/pages/Trails/Index.tsx` ("Isi profil sekarang") |
| Simpan rute | Ada | Sejak W3 tombol simpan 44 px dengan Urungkan (`resources/js/components/trails/TombolSimpan.tsx`) lewat rute idempoten `trails.saved.store` dan `trails.saved.remove`, `app/Http/Controllers/Trails/ConsiderationController.php`, `tests/Feature/SavedUndoTest.php`, `tests/Feature/ConsiderationTest.php`; Jelajah tanpa baki, hitungan dan pintu Simpanan di kepala halaman |
```

3. Ganti:

```markdown
| 4 | Buat pencarian kata kunci dan filter jarak waktu kesulitan | `TrailSearch`, `FilterForm.tsx` | Ada |
```

dengan:

```markdown
| 4 | Buat pencarian kata kunci dan filter jarak waktu kesulitan | `TrailSearch`, `SaringanJelajah.tsx` | Ada |
```

4. Ganti:

```markdown
- **Temuan:** F2 (peta memakai koordinat gunung, ditutup DK-06), F3 (alias kota, terbuka), F6 (daftar jauh di bawah layar pertama ponsel, redesain W3), F7 (baki simpanan sticky berpotensi menutupi fokus, hipotesis yang belum diverifikasi di peramban).
```

dengan:

```markdown
- **Temuan:** F2 (peta memakai koordinat gunung, ditutup DK-06), F3 (alias kota, terbuka), F6 (daftar jauh di bawah layar pertama ponsel, ditata ulang W3), F7 (baki simpanan sticky berpotensi menutupi fokus; baki Jelajah dilepas W3).
```

`docs/fitur/02-detail-rute.md`:

1. Ganti:

```markdown
| Ringkasan rute | Ada | `app/Services/Trails/TrailDetailPage.php` (`angkaKunci`, `rincian`), `app/Services/Trails/TrailFigures.php`, `resources/js/pages/Trails/Show.tsx`, `tests/Feature/TrailDetailPageTest.php` |
| Peta dan profil ketinggian | Ada | `resources/js/components/trails/rinci/PetaJalur.tsx`, `resources/js/components/ui/ElevationProfile.tsx`; ruas tercuram diarsir dan keterangan kalimat menyusul W3 |
| Titik penting | Ada | `app/Services/Trails/CheckpointJourney.php`, `app/Services/CheckpointPaceService.php`, `resources/js/components/ui/CheckpointJourney.tsx`, `tests/Feature/CheckpointJourneyTest.php`, `tests/Feature/CheckpointPaceDisplayTest.php` |
| Kondisi dan sumber info | Ada, dengan catatan | `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/components/trails/rinci/LabelSumber.tsx`, `app/Services/OfficialStatusService.php`, `app/Services/ConditionAggregatorService.php`, `app/Services/WeatherService.php`. Label kepercayaan memakai `SourceType` (Resmi, Terverifikasi admin, Ahli bersertifikat, Komunitas, Interpretasi sistem), bukan "Pakar" (K6). Prakiraan menampilkan waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin dan curah hujan belum tampil walau kolom angin ada |
| Laporan pendaki | Ada | `LaporanKomunitas.tsx`, `app/Http/Controllers/Trails/ReportThanksController.php`, `tests/Feature/ReportThanksTest.php` |
| Alasan kecocokan | Ada | `fit.faktor` lewat `app/Support/FaktorKategori.php`, `tests/Feature/AmbientFitTest.php`; halaman penilaian lengkap `/trails/{slug}/kecocokan` menyusul W3 (spec v3 bagian 6.5) |
```

dengan:

```markdown
| Ringkasan rute | Ada | `app/Services/Trails/TrailDetailPage.php` (`angkaNaik`, `tantangan`), `app/Services/Trails/TrailFigures.php`, `resources/js/pages/Trails/Show.tsx`, `tests/Feature/TrailDetailPageTest.php` |
| Peta dan profil ketinggian | Ada | `resources/js/components/trails/rinci/PetaJalur.tsx`, `resources/js/components/trails/ProfilElevasi.tsx`, `app/Services/Trails/ProfilElevasiJalur.php`; sejak W3 ruas tercuram diarsir dengan keterangan kalimat (`tests/Feature/ElevationProfileDisplayTest.php`) |
| Titik penting | Ada | `app/Services/Trails/CheckpointJourney.php`, `app/Services/CheckpointPaceService.php`, `app/Services/Trails/PosSepanjangJalur.php` (km dari garis rute), `resources/js/components/trails/rinci/DaftarPos.tsx`, `tests/Feature/CheckpointJourneyTest.php`, `tests/Feature/CheckpointPaceDisplayTest.php`, `tests/Spatial/PosSepanjangJalurTest.php` |
| Kondisi dan sumber info | Ada, dengan catatan | `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/components/trails/LabelSumber.tsx`, `resources/js/components/trails/rinci/SumberHalaman.tsx`, `app/Services/OfficialStatusService.php`, `app/Services/ConditionAggregatorService.php`, `app/Services/WeatherService.php`. Label kepercayaan memakai `SourceType` (Resmi, Terverifikasi admin, Ahli bersertifikat, Komunitas, Interpretasi sistem), bukan "Pakar" (K6). Prakiraan menampilkan waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin dan curah hujan belum tampil walau kolom angin ada |
| Laporan pendaki | Ada | `LaporanKomunitas.tsx`, `app/Http/Controllers/Trails/ReportThanksController.php`, `tests/Feature/ReportThanksTest.php` |
| Alasan kecocokan | Ada | "Cocokkah untuk saya?" di `resources/js/components/trails/rinci/CocokkahUntukSaya.tsx`, `tests/Feature/AmbientFitTest.php`; halaman penilaian lengkap `/trails/{slug}/kecocokan` sejak W3 (`app/Http/Controllers/Trails/TrailFitAssessmentController.php`, `app/Services/Trails/PenilaianKecocokan.php`, `tests/Feature/FitAssessmentPageTest.php`) |
```

2. Ganti:

```markdown
| Halaman gunung | Belum ada | Hanya `POST mountains/{mountain}/follow`; `/mountains/{slug}` menyusul W3 (DK-44) |
```

dengan:

```markdown
| Halaman gunung | Ada (W3) | `/mountains/{slug}` lewat `app/Http/Controllers/Trails/MountainPageController.php`, `app/Services/Mountains/HalamanGunung.php`, `resources/js/pages/Mountains/Show.tsx`, `tests/Feature/MountainPageTest.php` (DK-44); foto berlisensi dan status gunung api menunggu W8 (DK-37, DK-43) |
```

3. Ganti:

```markdown
| 2 | Buat panel ringkasan rute | `angkaKunci`, `rincian` di `TrailDetailPage` | Ada |
| 3 | Buat peta jalur dan titik akhir | `PetaJalur.tsx` | Ada |
| 4 | Buat grafik profil ketinggian jalur | `ElevationProfile.tsx` | Ada |
| 5 | Buat daftar titik penting di jalur | `CheckpointJourney` | Ada |
```

dengan:

```markdown
| 2 | Buat panel ringkasan rute | `angkaNaik`, `tantangan` di `TrailDetailPage` | Ada |
| 3 | Buat peta jalur dan titik akhir | `PetaJalur.tsx` | Ada |
| 4 | Buat grafik profil ketinggian jalur | `ProfilElevasi.tsx` | Ada |
| 5 | Buat daftar titik penting di jalur | `CheckpointJourney`, `DaftarPos.tsx` | Ada |
```

4. Ganti:

```markdown
| 9 | Terapkan tata letak responsif mobile desktop | `Show.tsx` | Sebagian (dua kolom lengket menyusul W3) |
```

dengan:

```markdown
| 9 | Terapkan tata letak responsif mobile desktop | `Show.tsx` | Ada (W3: dua kolom dengan kolom kanan lengket mulai 1024 px, bar aksi satu baris di ponsel) |
```

`docs/fitur/03-pertimbangkan.md`:

1. Ganti:

```markdown
- **Tab:** Simpanan di Jelajah (`/trails/saved`); Banding di Trip (`/trails/compare`), sesuai pemetaan permukaan lama "Pertimbangkan" di §145.
- **Tautan bagikan:** halaman publik baca-saja `/b/{token}` (FR-23), belum dibangun.
```

dengan:

```markdown
- **Tab:** Simpanan di Jelajah (`/trails/saved`); Banding di Trip (`/trails/compare`), sesuai pemetaan permukaan lama "Pertimbangkan" di §145. Sejak W3 tombol kembali Banding menuju Trip, bukan Simpanan (tinjauan akhir W2, W2-F2).
- **Tautan bagikan:** halaman publik baca-saja `/b/{token}` (FR-23), dibangun W3; akses tanpa akun masih menunggu persetujuan pemilik (spec v2 P8).
```

2. Ganti:

```markdown
| Tabel tautan bagikan | hash token, pemilik, daftar jalur, `expires_at` 7 hari, `revoked_at` | usulan spec v3 bagian 9.3; belum ada migrasi |
```

dengan:

```markdown
| `comparison_shares` | hash token, pemilik, daftar jalur, `expires_at` 7 hari, `revoked_at` | `2026_09_28_000100_create_comparison_shares_table` (W3); diuji SQLite dan PostgreSQL CI, belum dijalankan di Supabase (Q2) |
```

3. Ganti:

```markdown
| Belum ada simpanan | Cara menambah dan tautan ke Jelajah | Ada: "Belum ada rute tersimpan" di `resources/js/pages/Trails/Saved.tsx` |
```

dengan:

```markdown
| Belum ada simpanan | Cara menambah dan tautan ke Jelajah | Ada: "Belum ada jalur tersimpan" dan "Cari jalur di Jelajah" di `resources/js/pages/Trails/Saved.tsx` |
```

4. Ganti:

```markdown
| Batas laju tercapai | Pesan jujur | Sebagian: server menjawab 429; pesan khusus di kartu menyusul W3 |
```

dengan:

```markdown
| Batas laju tercapai | Pesan jujur | Sebagian: server menjawab 429; pesan khusus di kartu belum ada (OPEN ISSUE rencana W3) |
```

5. Ganti:

```markdown
| Pilih untuk banding | Ada (W0) | `resources/js/components/trails/banding/AksiPilihan.tsx`, `resources/js/components/trails/banding/PemilihBanding.tsx`; baki banding mengambang ala prototype 06 menyusul W3 |
| Bandingkan rute | Ada | `app/Http/Controllers/Trails/RouteComparisonController.php`, `app/Services/Trails/RouteComparisonTable.php`, `resources/js/components/trails/banding/TabelBanding.tsx`, `tests/Feature/RouteComparisonTest.php`, `tests/Feature/ComparisonAsCostTest.php`; profil elevasi skala sama, sakelar "Sorot yang berbeda", dan "Jadikan dasar rencana" berbobot setara menyusul W3 |
| Catatan pribadi (FR-22) | Ada (W0) | Rute `trails.saved.note.update` dan `trails.saved.note.destroy`, `app/Http/Controllers/Trails/SavedTrailNoteController.php`, `app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php`, `ConsiderationService::simpananMilik()` dan `simpanCatatan()`, `app/Policies/TrailConsiderationPolicy.php`, `tests/Feature/SavedTrailNoteTest.php`. Migrasi kolom `note` kemungkinan belum dijalankan di Supabase (INFERENCE, menunggu Q22) |
| Bagikan pilihan (FR-23) | Belum ada | Rancangan di spec v3 bagian 9.3; akses tanpa akun menunggu keputusan pemilik (spec v2 P8); tombol bagikan disembunyikan (DK-40) |
```

dengan:

```markdown
| Pilih untuk banding | Ada (W0, rupa W3) | `resources/js/components/trails/banding/AksiPilihan.tsx`, `resources/js/components/trails/banding/PemilihBanding.tsx`; baki banding mengambang satu baris ala prototype 06 (W3, `tests/Feature/SavedRoutesTest.php`) |
| Bandingkan rute | Ada | `app/Http/Controllers/Trails/RouteComparisonController.php`, `app/Services/Trails/RouteComparisonTable.php`, `resources/js/components/trails/banding/TabelBanding.tsx`, `tests/Feature/RouteComparisonTest.php`, `tests/Feature/ComparisonAsCostTest.php`; sejak W3 status resmi bersumber, laporan terbaru, izin, profil elevasi skala sama, sakelar "Sorot yang berbeda", dan "Jadikan dasar rencana" berbobot setara |
| Catatan pribadi (FR-22) | Ada (W0) | Rute `trails.saved.note.update` dan `trails.saved.note.destroy`, `app/Http/Controllers/Trails/SavedTrailNoteController.php`, `app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php`, `ConsiderationService::simpananMilik()` dan `simpanCatatan()`, `app/Policies/TrailConsiderationPolicy.php`, `tests/Feature/SavedTrailNoteTest.php`. Migrasi kolom `note` kemungkinan belum dijalankan di Supabase (INFERENCE, menunggu Q22) |
| Bagikan pilihan (FR-23) | Ada (W3), menunggu P8 | `app/Services/Trails/TautanBagikan.php`, `app/Http/Controllers/Trails/ComparisonShareController.php`, `resources/js/components/trails/banding/LembarBagikan.tsx`, `resources/js/pages/Public/SharedComparison.tsx`, `tests/Feature/ComparisonShareTest.php`, `tests/Spatial/ComparisonShareSchemaTest.php`; tombol bagikan tampil di Simpanan dan Banding (DK-40); akses tanpa akun menunggu keputusan pemilik (spec v2 P8) |
```

6. Ganti:

```markdown
| 10 | Buat tombol bagikan dan salin tautan | Tidak ada | Belum ada (FR-23) |
| 11 | Bangun halaman publik hasil bagikan | Tidak ada | Belum ada |
| 12 | Tangani tampilan tautan kedaluwarsa dan tidak valid | Tidak ada | Belum ada |
```

dengan:

```markdown
| 10 | Buat tombol bagikan dan salin tautan | `LembarBagikan.tsx` | Ada (W3) |
| 11 | Bangun halaman publik hasil bagikan | `resources/js/pages/Public/SharedComparison.tsx` | Ada (W3), menunggu P8 |
| 12 | Tangani tampilan tautan kedaluwarsa dan tidak valid | `ComparisonShareController::show()` menjawab 404 dengan halaman "Tautan tidak berlaku" yang sama untuk tautan kedaluwarsa, dicabut, atau tidak valid | Ada (W3) |
```

7. Ganti:

```markdown
| 17 | Buat skema dan endpoint tautan bagikan | Tidak ada | Belum ada |
| 18 | Buat endpoint publik tautan bagikan | Tidak ada | Belum ada |
| 19 | Tangani validasi kedaluwarsa tautan bagikan | Tidak ada | Belum ada |
```

dengan:

```markdown
| 17 | Buat skema dan endpoint tautan bagikan | `comparison_shares`, rute `shares.store` dan `shares.destroy` | Ada (W3); migrasi belum dijalankan di Supabase (Q2) |
| 18 | Buat endpoint publik tautan bagikan | Rute `shares.show` (`/b/{token}`), noindex dan batas laju per alamat IP | Ada (W3), menunggu P8 |
| 19 | Tangani validasi kedaluwarsa tautan bagikan | `ComparisonShare::scopeBerlaku()`, `TautanBagikan::buka()` | Ada (W3) |
```

`docs/fitur/05-kabar-kondisi.md`:

1. Ganti:

```markdown
| Kabar resmi | Ada | `app/Services/OfficialStatusService.php`, `resources/js/components/ui/StatusBadge.tsx`, `app/Http/Controllers/Admin/OfficialStatusController.php`, `app/Console/Commands/CheckDataFreshness.php`, `tests/Feature/OfficialStatusTargetTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Feature/DataFreshnessReviewTest.php` |
| Prakiraan cuaca | Sebagian | `app/Services/WeatherService.php`, `app/Console/Commands/RefreshWeatherSnapshots.php`, `tests/Feature/WeatherRefreshTest.php`; tampil waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin, curah hujan, dan penanda jam berisiko belum tampil |
| Laporan pendaki | Ada | `app/Http/Controllers/Community/ConditionReportController.php`, `app/Http/Requests/StoreConditionReportRequest.php`, `app/Support/ImageSanitizer.php`, `resources/js/pages/Community/ConditionReport.tsx`, `resources/js/components/trails/rinci/LaporanKomunitas.tsx`, `tests/Feature/CommunityReportModerationTest.php`, `tests/Feature/CommunityPhotoTest.php`; saring per jenis cerita atau tips tidak dibangun (DK-11) |
| Sumber dan kesegaran | Ada | `app/Services/DataFreshnessService.php`, `resources/js/components/community/LabelKesegaran.tsx`, `resources/js/components/ui/Freshness.tsx`, `resources/js/components/trails/rinci/LabelSumber.tsx` |
```

dengan:

```markdown
| Kabar resmi | Ada | `app/Services/OfficialStatusService.php`, `resources/js/components/trails/StatusAkses.tsx`, `app/Http/Controllers/Admin/OfficialStatusController.php`, `app/Console/Commands/CheckDataFreshness.php`, `tests/Feature/OfficialStatusTargetTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Feature/DataFreshnessReviewTest.php` |
| Prakiraan cuaca | Sebagian | `app/Services/WeatherService.php`, `app/Console/Commands/RefreshWeatherSnapshots.php`, `tests/Feature/WeatherRefreshTest.php`; tampil waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin, curah hujan, dan penanda jam berisiko belum tampil |
| Laporan pendaki | Ada | `app/Http/Controllers/Community/ConditionReportController.php`, `app/Http/Requests/StoreConditionReportRequest.php`, `app/Support/ImageSanitizer.php`, `resources/js/pages/Community/ConditionReport.tsx`, `resources/js/components/trails/rinci/LaporanKomunitas.tsx`, `tests/Feature/CommunityReportModerationTest.php`, `tests/Feature/CommunityPhotoTest.php`; saring per jenis cerita atau tips tidak dibangun (DK-11) |
| Sumber dan kesegaran | Ada | `app/Services/DataFreshnessService.php`, `resources/js/components/community/LabelKesegaran.tsx`, `resources/js/components/trails/Kesegaran.tsx`, `resources/js/components/trails/LabelSumber.tsx`, `tests/Feature/TrustGrammarTest.php` |
```

2. Ganti:

```markdown
| 2 | Buat kartu status resmi jalur dengan label warna | `StatusBadge.tsx` (bentuk dan kata, bukan warna saja) | Ada |
```

dengan:

```markdown
| 2 | Buat kartu status resmi jalur dengan label warna | `StatusAkses.tsx` (bentuk dan kata, bukan warna saja) | Ada |
```

- [ ] **Step 2: Jalankan test dokumen.**

Run: `php artisan test --compact --filter='DesignDirection|TeamDocsIndex|PrdReferenceIntegrity|RepoHygiene|SkillGovernance|SemanticToken'`
Expected: PASS: 65 test, 65 lulus.

- [ ] **Step 3: Periksa teksnya.**

Run: `LC_ALL=C grep -c -P '\xE2\x80\x94' DESIGN.md CONTRIBUTING.md docs/ARCHITECTURE.md docs/fitur/01-jelajah-rute.md docs/fitur/02-detail-rute.md docs/fitur/03-pertimbangkan.md docs/fitur/05-kabar-kondisi.md`
Expected: setiap berkas `0`.

- [ ] **Step 4: Suite.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.725 test, 1.723 lulus, 2 dilewati.

- [ ] **Step 5: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 12: Verifikasi gelombang (koordinator)

Keputusan koordinator nomor 9 dan 11; ruling W3-R49; "Selesai bila" induk W3. Dikerjakan koordinator sesudah Tugas 1
sampai 11 selesai dan lolos tinjauan per tugas. Setiap klaim di laporan disertai keluaran perintah, jalur berkas, atau
tangkapan (R-023). Angka "pada simulasi rencana" adalah hasil menjalankan kode rencana ini di salinan repo W2 selesai
di scratchpad, bukan hasil di repo, dan menjadi pembanding. Harness pratinjau memakai port 8100 milik koordinator.

**Files:**
- Create: `docs/desain/tangkapan/w3/*.webp` (tangkapan), `docs/desain/tangkapan/w3/README.md` (perbandingan)
- Modify: `HANDOVER.md` (bagian 0: baris "Terakhir diperbarui", subbagian W3, dan baris 455 daftar komponen), `.superpowers/sdd/2026-09-26-memount-v3-program.md` (baris W3; berkas ini diabaikan git). `docs/README.md` sudah mengindeks `desain/tangkapan/`, jadi tidak diubah.

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 11; harness pratinjau (W1 Tugas 10, diperluas W2 dan Tugas 2, 3, 7, 8, 9); gambar acuan `docs/desain/prototype/acuan/02-jelajah.png`, `03-gunung.png`, `04-detail-jalur.png`, `05-route-fit.png`, `06-simpanan.png`, `07-bandingkan.png`, `15-jelajah-lebar.png`.
- Produces: bukti akhir W3, daftar izin akhir yang diperbarui, dan titik lanjut untuk W4.

- [ ] **Step 1: Catat keadaan kerja.**

Run: `git status --short | wc -l; git diff --stat | tail -1`
Expected: selisih terhadap catatan akhir W2 cocok dengan gabungan daftar Files Tugas 1 sampai 11. Pada simulasi
rencana: 169 berkas (64 baru, 95 diubah, 10 dihapus; +9.664 dan −1.657 baris), 40 di antaranya berkas test.

- [ ] **Step 2: Suite penuh, tipe, format.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 1.725 test, 1.723 lulus, 2 dilewati (W2 selesai: 1.622 test).

Run: `php artisan test --compact`
Expected: pada simulasi rencana 1.750 test, 1.723 lulus, 27 dilewati (W2 selesai: 1.642 test, 1.620 lulus, 22
dilewati). Suite Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`; lima test Spatial baru W3 berjalan di CI.

Run: `npx tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

- [ ] **Step 3: Build dan ukuran bundel.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built` dengan peringatan yang sama seperti W2 (dua `/fonts/plus-jakarta-sans/... didn't resolve at build
time` dan ukuran potongan maplibre-gl). Catat ukuran `app-*.css`, `app-*.js`, dan potongan halaman W3. Pada simulasi
rencana, dengan himpunan view terkompilasi yang sama (88 view): `app-*.js` 354.780 → 355.471 bita (+691) dan
`app-*.css` 63,12 → 68,40 kB (+5,28 kB). Potongan halaman: Jelajah 15.030 → 26.661 bita, rincian jalur 15.371 →
18.797, Simpanan 5.750 → 6.914, dan Bandingkan 6.157 → 8.885; yang baru penilaian kecocokan 7.603, halaman gunung
10.570, dan tautan bagikan 4.202. Ukuran mutlak CSS ikut view terkompilasi (W2-C10), jadi yang dilaporkan selisihnya.

Run: `grep -l "Copyright (c) 2023 Phosphor Icons" public/build/assets/*.js | wc -l; grep -l "@license 3-Clause BSD" public/build/assets/*.js | wc -l`
Expected: dua baris, masing-masing paling sedikit `1`.

- [ ] **Step 4: Suite pratinjau dan server statis.**

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 14 test, 14 lulus (W2: 9 test; W3 menambah `test_gunung`, `test_kecocokan`,
`test_simpanan`, `test_tautan_bagikan`, dan `test_bandingkan`).

Run (di latar belakang): `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`
Expected: server statis tanpa basis data. Buka `http://127.0.0.1:8100/_pratinjau/<nama>.html`, dan hentikan server
sesudah Step 8.

- [ ] **Step 5: Tangkapan.** Pakai Chrome lewat MCP chrome-devtools. Ukuran 390 x 844 memakai emulasi perangkat seluler
dengan sentuh dan rasio piksel 1, ukuran 1440 x 900 rasio piksel 1, dan tema dipilih lewat cookie `tema` seperti W1.
Simpan sebagai WebP di `docs/desain/tangkapan/w3/`, masing-masing 390 dan 1440, terang dan gelap:

- Jelajah (`jelajah.html`, acuan 02 dan 15) dan Jelajah tanpa jalur terbit (`jelajah-kosong.html`);
- halaman gunung (`gunung.html`, acuan 03);
- rincian jalur (`detail-jalur.html`, acuan 04);
- penilaian kecocokan (`kecocokan.html`, acuan 05);
- Simpanan (`simpanan.html`, acuan 06), ditambah satu tangkapan 390 dengan tiga centang;
- Bandingkan (`bandingkan.html`, acuan 07);
- halaman publik tautan bagikan (`bagikan.html`, tanpa acuan: bandingkan susunannya dengan 07).

Data contoh tidak punya geometri, jadi tangkapan Jelajah menampilkan keadaan menunggu peta. Pola garis, legenda, chip,
dan kartu pratinjau diperiksa dengan data yang punya geometri bila koordinator menyediakannya lewat suite Spatial;
bila tidak, tulis "belum dipotret" di README, jangan mengarang tangkapan.

- [ ] **Step 6: Perbandingan tertulis.** Tulis `docs/desain/tangkapan/w3/README.md` dengan susunan README tangkapan W2:
paragraf cara pengambilan; satu paragraf cakupan W3; dan tabel
`| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |` untuk setiap
tangkapan. Pemeriksaan perencanaan menemukan hal-hal di bawah ini. Pastikan masing-masing pada tangkapan, jangan disalin
tanpa melihat.

- **Sudah sesuai.**
  - Jelajah 390 px (acuan 02): kepala "Jelajah" dengan Simpanan berhitungan dan avatar, kotak cari berikon, "Saring"
    dan hitungan hasil, kalimat dasar penilaian, dan kartu dengan label ikon dan kata, simpan 44 px, nama, gunung dan
    wilayah, tiga angka berlabel, garis profil, satu alasan, status bersumber, serta "Kenapa?".
  - Rincian jalur 390 px (acuan 04): tombol kembali, gunung berpautan, "Ikuti kabar", kalimat garis rute yang menunggu,
    angka sekali jalan naik, "Apa tantangannya?" dengan ruas dan kata, "Cocokkah untuk saya?" dengan tiga daftar dan
    kalimat aturan tertulis, profil dengan ruas tercuram diarsir dan kalimatnya, pos bernomor dengan aksen dan kata
    "Kemah" serta "Tujuan", dan bar aksi satu baris.
  - Penilaian kecocokan 390 px (acuan 05): label dan definisinya, catatan tiga label tanpa skor, "Dinilai dari" dengan
    "Ubah", lapis syarat dasar, dan bar aksi "Ke jalur" serta "Lanjutkan persiapan".
  - Simpanan 390 px (acuan 06): "Bagikan" di kepala, kalimat jumlah dan batas, kartu dengan kotak centang, label,
    angka, dan catatan atau "Tambah catatan pribadi", serta baki "Dipilih 3 dari 3" dengan ruas dan "Bandingkan 3
    jalur".
  - Halaman gunung 390 px (acuan 03): judul, "Ikuti kabar", puncak dengan kalimat BR-02, status tingkat gunung dengan
    kalimat R-012, daftar jalur berlabel dan berstatus, izin dengan kalimat tautan resmi, dan kondisi dari pendaki.
  - Bandingkan 390 px (acuan 07): judul "Bandingkan 3 jalur" dengan kalimat tanpa pemenang, sakelar "Sorot yang
    berbeda", label per kolom, dan "Jadikan dasar rencana" yang setara.
- **Belum sesuai.**
  - Bandingkan 390 px: tabel memakai kolom kepala baris yang menempel dan gulir mendatar, sehingga sekitar satu
    setengah kolom terlihat. Prototype 07 menaruh label aspek di atas nilainya sehingga tiga kolom terlihat. Pilihan:
    pertahankan (semantik tabel dari W0) atau tata ulang di bawah 640 px dengan baris label `th scope="rowgroup"` di
    putaran perbaikan W3.
  - Halaman gunung: foto hero dan kartu PVMBG prototype 03 menunggu W8 (DK-37, DK-43); yang tampil visual profil.
    Nama taman nasional dan kabupaten belum ada datanya, hanya provinsi.
  - Kartu Jelajah: kartu Cocok dapat membawa alasan perhatian (Konflik 7).
  - Rincian jalur: tiga angka, bukan empat (W3-R29). Durasi ditulis panjang ("7 jam 30 menit") dan membungkus dua baris
    di 390 px, sedangkan prototype memakai rentang pendek ("7-8 j") yang butuh data rentang (DK-31).
  - Kalimat mesin di "Cocokkah untuk saya?" memuat istilah dan format mesin ("Elevation gain 1650 m") (OPEN ISSUE).
  - Jelajah: pemilih Daftar dan Peta, pola garis, legenda, dan kartu pratinjau tidak tampil karena data contoh tanpa
    geometri. Periksa dengan data Spatial bila tersedia; bila tidak, tulis "belum dipotret".
  - Simpanan: judul tanpa angka di sebelahnya (prototype "Simpanan 5"), dan kalimatnya menyebut batas 50, bukan "Simpan
    sebanyak yang Anda mau" (DK-05: batas teknis tetap disebut).
  - Jarak atas halaman `py-6`, sedangkan acuan sekitar 16 px (W3 sampai W7, sama dengan catatan W2).

- [ ] **Step 7: Ukur lapisan bawah dan panel lengket** (butir W2-F1, W2-F3, W2-F5). Dengan `evaluate_script` di
pratinjau:

1. `detail-jalur.html` di 360 x 740 dan 320 x 568: tinggi `section[aria-label="Aksi jalur"] .chrome`. Pada simulasi
   rencana 69 px dan 82 px, satu baris, tanpa gulir mendatar.
2. `simpanan.html` di 360 x 740 dan 320 x 568, dengan nol sampai tiga centang dan satu centang keempat: tinggi
   `section[aria-label="Pilihan banding"] .chrome` dan persen layar yang tertutup baki ditambah bilah tab. Pada
   simulasi rencana 58 sampai 78 px (20 sampai 23 persen) di 360 x 740, dan 58 sampai 98 px (27 sampai 34 persen) di
   320 x 568.
3. `jelajah-mendaki.html` di 1440 x 900: tepi bawah panel saringan dan peta (atau panel menunggu peta) di atas tepi atas
   strip pendakian, pada posisi gulir awal dan sesudah menggulir.

- [ ] **Step 8: Jalur papan ketik** (keputusan nomor 11; e2e menunggu Playwright, Q17). Pada pratinjau, tanpa tetikus:

1. **390 px, Jelajah.** Tab dari awal: tautan lewati, Simpanan di kepala, kotak cari, Saring, chip tafsiran, lalu
   kartu pertama (tautan nama, simpan, "Kenapa?"). Enter pada "Saring" membuka lembar dengan fokus di dalamnya; Escape
   menutupnya, dan fokus kembali ke "Saring". Enter pada simpan memindah fokus ke Urungkan di toast.
2. **390 px, rincian jalur.** Tab pertama sesudah tautan lewati berhenti di "Kembali ke Jelajah". Tab terakhir
   mencapai simpan, Banding (bila ada), dan Rencanakan trip di bar aksi tanpa tertutup lapisan mana pun.
3. **390 px, Simpanan.** Spasi mencentang kotak; hitungan di baki berubah dan terdengar lewat `role="status"`. Bagikan
   membuka lembar; Escape mengembalikan fokus ke Bagikan.
4. **1440 px, Bandingkan.** Tab mencapai "Kembali ke Trip", "Lihat simpanan", sakelar "Sorot yang berbeda" (Spasi
   menyalakannya), lalu tombol "Jadikan dasar rencana" dengan cincin fokus terlihat.

Simpan satu tangkapan fokus di bar aksi rincian jalur sebagai `fokus-bar-aksi-390-light.webp`. Hentikan server port
8100.

- [ ] **Step 9: Delivery Gate antislop, empat blok** (keputusan nomor 9). Tulis laporan PASS/FAIL, satu baris per
butir, dan setiap PASS disertai bukti:

- **Salinan:** R-02 tanpa em dash. Run: `LC_ALL=C grep -rlP '\xE2\x80\x94' app resources tests database DESIGN.md CONTRIBUTING.md docs/ARCHITECTURE.md docs/fitur`. Expected: tanpa keluaran. Teks bahasa Indonesia dengan sapaan "Anda", tanpa "aman" atau "layak berangkat" sebagai klaim, dan keadaan kosong menyebut siapa yang ditunggu.
- **Desain:** R-20 per layar (satu fokus, hierarki lewat bobot, tanpa kartu di dalam kartu kecuali pratinjau peta), R-09 satu lencana, R-10 kaca paling banyak dua elemen dan tidak di atas peta, aksen pos selalu bersama bentuk dan label.
- **Interaksi:** R-26 setiap kendali berfungsi (simpan, Urungkan, Saring, Tampilkan juga, Kenapa?, Bagikan, Cabut, Sorot yang berbeda, Jadikan dasar rencana, Lihat simpanan); R-27 keadaan pertama kali, tersaring, memuat, galat peta, dan menunggu; R-32 jalur papan ketik Step 8.
- **Kebenaran:** tanpa skor, persen, atau bobot angka; UNKNOWN tidak pernah terbaca rendah atau Buka; status bersumber dan bertanggal; R-35 build dijalankan dan kendali diklik.

- [ ] **Step 10: HANDOVER, ledger, dan daftar izin akhir.** Di `HANDOVER.md` bagian 0, perbarui baris "Terakhir
diperbarui", ganti daftar komponen lama di baris 455, dan tambahkan subbagian "W3 Klaster Jelajah (selesai <tanggal>,
menunggu izin commit)" dengan susunan subbagian W2:

- rujukan rencana ini, jumlah tugas, dan letak ledger;
- tabel `| Butir | Nilai | Bukti |` berisi suite Unit dan Feature, suite bawaan, tsc, Pint, build dan selisih bundel,
  suite Pratinjau, anggaran kueri per halaman baru, tangkapan, pengukuran lapisan, dan jalur papan ketik;
- ruling koordinator atas Konflik 1 sampai 8;
- OPEN ISSUE: pesan batas laju simpan, `elevation_loss_m`, istilah Inggris di label faktor, dan `TrailRow.tsx` yang
  tidak lagi dipakai halaman;
- daftar izin akhir yang bertambah: migrasi `comparison_shares` di Supabase (Q2) dan akses tautan bagikan tanpa akun
  (spec v2 P8);
- titik lanjut W4.

Perbarui baris W3 di `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

- [ ] **Step 11: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

## Tinjauan diri

### Cakupan induk W3, keputusan koordinator, dan butir W2

| Butir | Tugas | Bukti di rencana |
|---|---|---|
| Butir 1: Jelajah daftar (chip tafsiran dan "Kembalikan tafsiran kalimat"; lembar saringan dan "Tampilkan N jalur"; kartu dengan label, simpan 44 px dan Urungkan, tiga angka, garis profil satu skala, satu alasan, status bersumber, "Kenapa?"; kartu "Belum bisa dinilai"; tersembunyi saringan; keadaan pertama kali, tersaring, memuat) | Tugas 4 | `TrailIndexSearchTest` (18 test), `TrailCardTest`, `ResultListBudgetTest`, `SavedUndoTest` |
| Butir 2: peta Jelajah (pola per label, legenda, jalur terpilih lebih tebal, chip, kartu pratinjau, kalimat jalur tanpa geometri, atribusi termasuk SRTM) | Tugas 5 | `TrailIndexMapTest`, `MapConfigTest::test_the_default_attribution_follows_the_opentopomap_wording`; `PetaSebaranJalurTest` Spatial tidak berubah (W3-R28) |
| Butir 3: rincian jalur (urutan §89, bar aksi tetap, ruas tercuram diarsir dengan kalimat, gunung api hanya bila ada datanya) | Tugas 6 | `TrailDetailPageTest`, `ElevationProfileDisplayTest`, `TrailDetailMapTest`, `CheckpointJourneyTest`, `CheckpointPaceDisplayTest`; gunung api W3-R36 |
| Butir 4: penilaian kecocokan (tujuh faktor diurut bobot, bobot dalam kata, keadaan termasuk Belum diketahui, "Kenapa bukan Cocok?", "Yang bisa Anda lakukan", versi aturan; tanpa angka; trip orang lain ditolak; anggaran kueri) | Tugas 3 | `FitAssessmentPageTest` (12 test), `FactorBarsTest` |
| Butir 5: halaman gunung (hero visual data, BR-02, R-012, hanya jalur terbit, izin, kondisi komunitas, provenans; draf tidak tampil, tamu ke masuk, keadaan kosong, anggaran kueri) | Tugas 2 | `MountainPageTest` (12 test) |
| Butir 6: Simpanan dan tautan bagikan FR-23 (rupa prototype 06, simpan dengan Urungkan, tabel token, buat, cabut, halaman publik tanpa catatan dan label, noindex, batas laju, tombol bagikan sesudah rutenya ada) | Tugas 4 (simpan), Tugas 7 (Simpanan), Tugas 8 (tautan bagikan) | `SavedRoutesTest`, `SavedTrailNoteTest`, `ComparisonShareTest` (12 test), `ComparisonShareSchemaTest` |
| Butir 7: Bandingkan (dasar rencana setara, "Sorot yang berbeda", bilah dan profil satu skala, bagikan; tanpa pemenang) | Tugas 9 | `RouteComparisonTest`, `ComparisonAsCostTest` |
| Selesai bila: aturan penutup gelombang untuk tujuh layar, termasuk keadaan kosong dengan data pemilik tanpa geometri | Tugas 12 | Step 2 sampai 10 |
| Keputusan nomor 1 (W1-F1) | Tugas 4 | `TrailIndexSearchTest::test_nothing_published_is_reported_as_waiting_even_with_filters`, `DesignPrimitivesTest::test_jelajah_names_who_is_awaited_when_nothing_is_published` |
| Keputusan nomor 2 (W1-F2) | Tugas 10 | `ButtonBladeParityTest` |
| Keputusan nomor 3 (aksen pos) | Tugas 6 | `TrailDetailPageTest::test_posts_carry_the_accent_for_camp_and_destination` |
| Keputusan nomor 4 (enam komponen domain dipindah) | Tugas 1, 3, 6 | Berkas lama dihapus di daftar Files; `DESIGN.md` bagian Primitif (Tugas 11) |
| Keputusan nomor 5 (`KotakCentang`) | Tugas 7 | `SavedRoutesTest::test_going_past_the_comparison_limit_is_explained` |
| Keputusan nomor 6 (glyph resmi hanya OFFICIAL) | Tugas 1 | `TrustGrammarTest::test_the_official_seal_marks_only_official_sources` |
| Keputusan nomor 7 (`nilai`, `onUbah`, `label`) | Tugas 1, 4 | `LabelKecocokan({ nilai, label })`, `StatusAkses({ nilai, label })`, `KotakCari({ nilai, onUbah })` |
| Keputusan nomor 8 (UNKNOWN "Belum dinilai", tiga label, tanpa skor) | Tugas 1, 4 | `TrustGrammarTest::test_the_unrated_word_is_never_typed_in_the_client`, `TrailCardTest::test_card_props_carry_labels_and_reasons_but_never_a_score` |
| Keputusan nomor 9 (gerbang W3) | Tugas 12 | Step 5, 6, dan 9 |
| Keputusan nomor 10 (migrasi di SQLite dan PostgreSQL CI, tidak di Supabase) | Tugas 8 | `ComparisonShareTest`, `tests/Spatial/ComparisonShareSchemaTest.php`; daftar izin akhir |
| Keputusan nomor 11 (papan ketik lewat test sumber dan klik koordinator) | Tugas 4 sampai 9, Tugas 12 | `KeyboardAndScreenReaderTest`, `SavedUndoTest::test_the_save_button_uses_idempotent_routes_and_asks_before_losing_a_note`; Step 8 |
| Keputusan nomor 12 (anggaran kueri setiap halaman baru) | Tugas 2, 3, 4, 6, 7, 8 | `MountainPageTest::test_the_page_does_not_grow_with_the_number_of_trails`, `FitAssessmentPageTest::test_the_page_does_not_grow_with_saved_trails_or_preparation_items`, `ResultListBudgetTest::test_jelajah_queries_do_not_grow_with_the_number_of_trails_for_a_rated_reader`, `TrailDetailPageTest::test_the_page_does_not_grow_with_posts_or_saved_trails_for_a_rated_reader`, `SavedRoutesTest::test_the_page_does_not_grow_with_the_number_of_saved_trails`, `ComparisonShareTest::test_the_public_page_does_not_grow_with_the_number_of_trails`; Bandingkan dijaga `PageQueryBudgetTest` |
| Konvensi W2-5i, W2-5e, lapisan W2-2a, W2-2b, W2-5a, nama props bersama, predikat simpanan | Tugas 4, 6, 7, 8 | `SavedUndoTest::test_the_undo_toast_belongs_to_the_page_not_the_card`, `SavedRoutesTest::test_the_page_owns_the_undo_toast_for_removals`, `ComparisonShareTest` (`onNetworkError`), `ReminderSheetTest` tetap hijau |
| W2-F1 (baki ringkas di layar sempit) | Tugas 4, 7 | `InertiaLayoutTest::test_every_bottom_layer_the_spacing_counts_measures_itself`, `SavedRoutesTest::test_the_compare_tray_is_a_one_row_summary_without_names` |
| W2-F2 (pemilik tab Banding) | Tugas 9 | `NavigationConsistencyTest::test_a_page_and_its_parent_light_the_same_tab` |
| W2-F3 (peta lengket di layar lebar) | Tugas 5 | `TrailIndexMapTest::test_the_sticky_map_and_filter_panel_leave_room_for_the_fixed_layers` |
| W2-F5 (anggaran tinggi bar aksi) | Tugas 6 | `TrailDetailPageTest::test_the_phone_action_bar_stays_one_row` |
| Antarmuka perbaikan akhir W2 (`--baki-banding`, `versionChange`, `--kepala-atas`, `jumlahSimpanan` di `trips.hike`) | Tugas 4 (`--baki-banding`); lainnya tidak disentuh | Pra-cek butir 4, 5, 7, 10 |

### Pemindaian placeholder

Tidak ada "TBD", "TODO", "implement later", atau "similar to Task N". Setiap langkah kode memuat kodenya: berkas baru
ditulis utuh, tulis ulang memuat isi utuh, dan perubahan kecil memakai blok "Ganti ... dengan ...". Blok-blok itu
dirakit dari rantai simulasi dan diperiksa dua hal. Pertama, teks lamanya muncul tepat sekali pada keadaan sebelum
tugas. Kedua, menerapkan semua blok berurutan menghasilkan berkas akhir tugas persis. Setiap berkas yang berubah di
simulasi disebut di tugas yang sama. Sesudah dirakit, teks rencana ini diputar ulang dari awal pada dasar W2 selesai
seperti implementer membacanya, dan setiap berkas yang disentuh sama dengan keadaan akhir tugasnya.

### Konsistensi nama dan tipe

Nama yang dipakai lintas tugas, dengan tugas yang membuatnya:

- **Tata bahasa (Tugas 1):** `StatusJalurRingkas::untuk()` dan `bentuk()`; `DataFreshnessService::keadaan()`;
  `LabelKecocokan({ nilai, label })` dengan `LabelFit`; `StatusAkses({ nilai, label, cakupan, ukuran })` dengan
  `StatusResmi`; `Kesegaran({ waktu, awalan, keadaan })`; `LabelSumber({ jenis })` dengan `JenisSumber`; prop
  `kataBelumDinilai`.
- **Gunung (Tugas 2):** `HalamanGunung::props()`; `MountainHeaderResource`; `SkalaProfil::titik()`, `naikBersama()`,
  `mutlakBersama()`; `TrailFigures::ringkas()`; `BarisStatus({ status })` dengan `StatusRingkas`.
- **Kecocokan (Tugas 3):** `PenilaianKecocokan::props()`; rute `trails.fit`; `FaktorKecocokan({ faktor })` dengan
  `FaktorBaris` dan `KategoriFaktor`.
- **Jelajah (Tugas 4):** `HalamanJelajah::props()`; `TrailSearch::tafsiran()`, `kandidat()`, `gagalPada()`;
  `KartuJalurResource`; `ConsiderationService::simpan()`; rute `trails.saved.store`; flash `simpananTerhapus`;
  `TombolSimpan({ jalur, tampilkanToast, bentuk })` dengan `JalurSimpan`; `KartuJalur`, `KotakCari({ nilai, onUbah })`,
  `SaringanJelajah`, `TafsiranKalimat`, `saringan.ts`; `GarisProfil({ titik, label })`.
- **Peta (Tugas 5):** `PetaJelajah({ fitur, baris, sorot, onLihatDiDaftar, kataBelumDinilai })`.
- **Rincian (Tugas 6):** `TrailDetailPage::props()`; `PosSepanjangJalur::km()`; `ProfilElevasiJalur::untuk()`;
  `CheckpointJourney::daftar()` dan `aksen()`; `TripPlan::tujuanPenilaian()` dan `ringkasan()`;
  `PreparationService::khususJalur()`; `ProfilElevasi({ profil })`, `DaftarPos({ pos })`; `TombolSimpan` bentuk
  `bar`.
- **Simpanan (Tugas 7):** `KartuSimpanan`, `AksiPilihan({ dipilih, maks, tersedia, pesan })`; `TombolSimpan` bentuk
  `hapus`.
- **Tautan bagikan (Tugas 8):** `ComparisonShare`, `TautanBagikan::buat()`, `aktif()`, `buka()`, `cabut()`; rute
  `shares.show`, `shares.store`, `shares.destroy`; flash `tautanBagikan`; `TautanAktif`; `LembarBagikan`.
- **Bandingkan (Tugas 9):** `RouteComparisonTable::susun()` dengan `KolomJalur`; `TabelBanding({ jalur, baris, beda,
  profil, sorotBeda, kataBelumDinilai })`.

tsc lulus sesudah setiap tugas pada simulasi, dengan rute bertipe dibangkitkan lebih dulu di tugas yang mengubah rute.

### Review Focus

Kelima baris Review Focus punya test di tugas pemilik kodenya:

- data lapangan kosong: Tugas 2, 4, 5, dan 6;
- UNKNOWN yang terbaca rendah atau Buka: Tugas 1, 3, 4, dan 6;
- simpan ganda, halaman basi, dan Urungkan: Tugas 4 dan 7;
- tautan bagikan dan data milik orang lain: Tugas 3, 4, dan 8;
- lapisan bawah dan panel lengket: Tugas 5, 6, 7, dan 9, lalu diukur Tugas 12.

Semua test itu lulus pada simulasi.

### Pemeriksaan teks rencana

- Rencana ini tanpa em dash. Tanda "§" hanya muncul saat mengutip ID PRD; dokumen lain dirujuk "bagian N".
- Teks antarmuka baru berbahasa Indonesia dengan sapaan "Anda".
- Tidak ada klaim "aman" atau "layak berangkat"; kata itu hanya muncul saat mengutip aturan yang melarangnya.
- Label kecocokan publik tidak berubah.
