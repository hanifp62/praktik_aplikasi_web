# Serah-terima Konteks — Aplikasi Web Gunung

Dokumen ini ditulis supaya percakapan dapat dilanjutkan di terminal baru **tanpa mengulang
dari nol**. Isinya tujuan, kesepakatan, keadaan kode, dan semua keputusan yang sudah
diambil beserta alasannya.

Terakhir diperbarui: 26 September 2026 (bagian 0). Bagian 1–10 berasal dari 21 September 2026
dan akan ditulis ulang penuh di Tugas 13 rencana konsolidasi.

---

## 0. Titik lanjut terkini (tonggak M1, 26 September 2026)

**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0 ini, lalu `docs/AGENTS.md` (kesepakatan kerja tim) dan rencana
> induk `docs/superpowers/plans/2026-09-26-memount-v3-induk.md`. Periksa keadaan nyata dulu: `git status`,
> `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`. Jangan commit, push, mengakses
> Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

**Arahan pemilik terakhir** (26 September 2026, verbatim): "lanjut, commit pastikan semua terintegrasi jadi saya bisa
cek di local host". Arahan sebelumnya (verbatim di spec v3 bagian 17.1): restrukturisasi repo untuk
persiapan push dengan rapi dan profesional; folder `docs/` berisi DESIGN, PRODUCT, PRD, dan AGENTS sebagai aturan
tim yang detail dan lengkap; skill UI/UX dipasang dan selalu diterapkan dengan dibandingkan dan digabung. Keputusan
eksekusi: W0 lalu WR, subagen dan peninjau per tugas, tanpa commit sampai tonggak M1.

**Keadaan tonggak M1 (W0 dan WR):**

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite Unit dan Feature, salinan kerja | 1.338 test, 1.337 lulus, 0 gagal, 0 error, 1 dilewati, 7.610 asersi | WR Tugas 10 Step 1 |
| Suite Unit dan Feature, ekspor bersih | 1.338 test, 1.337 lulus, 0 gagal, 1 dilewati, 6.950 asersi, dijalankan pada 946 berkas yang akan di-commit (tanpa berkas yang diabaikan git, tanpa folder Q19, tanpa berkas yang sudah terhapus) | Ruling W15c |
| TypeScript, build, Pint | `npx tsc --noEmit` 0 galat; `npm run build` sukses dalam 1,74 s (bundel terbesar `maplibre-gl` 768 KB, gzip 209,97 kB; `app` 345 KB); `vendor/bin/pint --test` lulus untuk seluruh repo | WR Tugas 10 Step 2 sampai 4 |
| Pemindai rahasia | Lulus atas berkas dilacak dan berkas baru yang tidak diabaikan git; ketujuh polanya disebut di `SECURITY.md` | `SecretsNotInRepoTest` |
| Audit dependensi | Composer bersih; npm merah: 1 critical GHSA-jrc7-96c5-q579 pada `maplibre-gl` 4.7.1 (npm menawarkan 6.11.2, perubahan breaking; Q20) | WR Tugas 10 Step 6 |
| Suite Spatial | PERLU KLARIFIKASI: belum pernah hijau di PostGIS. Cacat `SpatialTestCase` (galat "Target class [config]" sebelum terhubung) sudah diperbaiki, dan `SpatialHarnessTest` membuktikan suite kini mencapai koneksi yang ditunjuk DSN (port 1 ditolak dengan `SQLSTATE[08006]`). Hasil hijau pertama menunggu CI setelah push yang diizinkan; Docker tidak terpasang di mesin pemilik | WR Tugas 10 Step 9 |
| CI publik | FAKTA (API GitHub, 26 September 2026): ada dua run Actions dan keduanya gagal. Run 35658731779 (commit `6d9e090`) lulus build, Pint, Unit, dan Feature, lalu gagal di langkah Spatial; run 35654079411 (commit `855132a`) gagal di Unit dan Feature | `docs/PRD.md` §128 dikoreksi |
| Pelaporan kerentanan privat | FAKTA: private vulnerability reporting repositori ini `enabled: false`; `SECURITY.md` dan formulir isu sudah menyiapkan jalurnya beserta cadangannya | Keputusan 8 |
| Status migrasi Supabase | PERLU KLARIFIKASI sampai izin baca Supabase (Q22) | WR Tugas 10 Step 10 |
| Dokumen tim | `docs/AGENTS.md`, `docs/README.md`, `docs/PRD.md`, penunjuk `docs/DESIGN.md` dan `docs/PRODUCT.md`, `SECURITY.md`, `database/README.md`, `docs/lisensi-pihak-ketiga.md` | `TeamDocsIndexTest`, `RepoPolicyFilesTest`, `ThirdPartyLicenseTest` |
| Struktur repo | Root terinventaris; CI dipecah menjadi `test.yml`, `security.yml`, dan `deploy.yml` manual; `docker-compose.yml` PostGIS; templat PR dan isu; pemeriksaan "berkas ada" memakai isi repo, bukan disk | `RepoHygieneTest`, `CiWorkflowsTest`, `LocalPostgisComposeTest`, `PrdReferenceIntegrityTest` |
| Blok alat | SHA-1 blok Laravel Boost di `CLAUDE.md` dan `AGENTS.md` tetap `3e233a75a9edbcc7cadb54f40eb55cc2f8314386` | WR Tugas 10 Step 7 |
| `tests/e2e` | TERBLOKIR sampai Q17 | WR Tugas 9 |
| Commit dan push | Commit dibuat atas izin pemilik 26 September 2026 dalam tiga commit (riset dan rencana; prototype; kode, W0, dan WR); push belum | R-018 |

**Ringkasan diff untuk seri commit.** Setiap berkas masuk kelompok terakhir yang menyentuhnya; rincian per berkas ada
di `.superpowers/sdd/2026-09-26-memount-v3-wr-struktur-repo/kelompok-diff.json` (diabaikan git).

| Kelompok | Berkas | Baru | Diubah | Dihapus | Dipindah |
|---|---|---|---|---|---|
| Sesi sebelumnya (migrasi React, pensiun Livewire, konsolidasi dokumen) | 555 | 315 | 116 | 121 | 3 |
| W0 stabilisasi | 32 | 23 | 9 | 0 | 0 |
| WR struktur repo dan dokumen tim | 76 | 56 | 17 | 0 | 3 |
| Folder skill Q19 (tidak di-stage) | 209 | 209 | 0 | 0 | 0 |

Dieksekusi 26 September 2026 sebagai tiga commit yang masing-masing utuh: riset, spec, dan rencana (`docs/riset`,
`docs/superpowers`, `docs/audit`); prototype (`docs/desain/prototype`); lalu kode dan struktur repo (migrasi React dan
pensiun Livewire, W0, WR). Pembagian per sesi tidak dipakai karena banyak berkas membawa perubahan dari beberapa sesi
(misalnya `Icon.tsx` dibuat saat migrasi React lalu disunting WR), sehingga commit per sesi tidak dapat berjalan
sendiri. Folder Q19 dan berkas yang diabaikan git tidak ikut.

**Keputusan pemilik yang ditunggu:**

1. Diputuskan 26 September 2026 ("lanjut, commit"): commit dibuat. Yang masih ditunggu: ruang lingkup aturan commit (semua anggota atau hanya agen) di `docs/AGENTS.md` bagian "Kesepakatan kerja dan izin".
2. Persetujuan isi PRD v3.0; berkasnya di-commit sebagai draf konsolidasi atas izin commit (§143).
3. Q17 Playwright untuk `tests/e2e` (WR Tugas 9 terblokir).
4. Q18 `LICENSE` dan `.github/CODEOWNERS`; `composer.json` masih memuat `"name": "laravel/laravel"` dan `"license": "MIT"` warisan kerangka.
5. Q19 folder skill: 14 folder (209 berkas) di `.agents/skills/` belum dilacak dan tidak di-stage.
6. Q20 `maplibre-gl`: upgrade ke 6.x dengan uji peta (butuh izin), atau menerima risiko secara tertulis dengan tanggal tinjau ulang; `security.yml` merah sampai diputuskan.
7. `jelajah-gunung.zip`: usul arsipkan PRD NgodingPakeAI dan delapan berkas features ke `docs/arsip/`, lalu hapus zip-nya.
8. Aktifkan GitHub private vulnerability reporting sebelum push (FAKTA: mati); pertimbangkan juga secret scanning dengan push protection dan proteksi branch.
9. Push ke `origin/hardening` setelah keputusan di atas (R-018). Jadwal mingguan `security.yml` baru berjalan setelah workflow itu ada di cabang bawaan `main`.
10. Izin baca Supabase (Q22): `php artisan migrate:status`, advisor keamanan, RLS pada 12 tabel yang dibuat sesudah migrasi `2026_09_20_000100` (ditambah `trail_considerations` bila migrasi `2026_09_25_002314` belum berjalan), dan satu kueri baca akun `@example.com`. `DatabaseSeeder` membuat admin berkata sandi "password" yang tertulis di README publik; apakah seeder itu pernah dijalankan ke Supabase TIDAK DIKETAHUI.
11. Blok NgodingPakeAI di `AGENTS.md` memuat Workspace ID di repositori publik (bukan kredensial).
12. `docs/db/schema-snapshot-sebelum-hardening.md` memuat ref proyek Supabase yang sudah ada di `origin/hardening`.
13. Q21 prototype: foto dan huruf yang ditanam di bundel (dicatat di `docs/lisensi-pihak-ketiga.md`). Bila pemilik memilih menyimpannya lokal saja, `RepoHygieneTest`, `.gitignore`, `docs/desain/prototype/README.md`, `docs/README.md`, `docs/AGENTS.md`, dan daftar lisensi diubah bersama.
14. Catatan "Transitional duplication" yang basi di `.claude/rules/mountain-core.md` baris 8.
15. `composer run setup` menjalankan `php artisan migrate --force`; dengan `.env` yang menunjuk Supabase, skrip itu memigrasi produksi. Usul: keluarkan langkah migrate dari `setup` atau beri penjaga.
16. `DatabaseSeeder` belum punya penjaga produksi. Usul: tolak berjalan bila lingkungannya produksi atau host basis datanya tampak seperti Supabase.
17. Pemberitahuan lisensi di bundel produksi (Phosphor MIT, MapLibre BSD-3) dibuang minifier; diputuskan bersama penulisan ulang registri ikon di W1 (DK-39).

**Langkah berikutnya, berurutan:** pemilik mengecek aplikasi di localhost (basis datanya perlu diputuskan: Supabase
lewat `.env`, yang mungkin belum menjalankan migrasi terbaru, atau basis data lokal); pemilik memutuskan daftar di
atas; private vulnerability reporting diaktifkan; push atas izin, lalu hasil `test.yml` dan `security.yml` dibaca
(termasuk hasil pertama suite Spatial di PostGIS); rencana rinci W1 ditulis dengan `writing-plans`.

---

## 0-26. Catatan 26 September 2026 sebelum WR (riwayat)

**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0 ini, lalu rencana induk
> `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` dan spec
> `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (prototype pemilik sebagai patokan desain;
> keputusan DK-30 sampai DK-58; §15 daftar izin Q1 sampai Q22). Rencana rinci yang siap dieksekusi, berurutan:
> `docs/superpowers/plans/2026-09-26-memount-v3-w0-stabilisasi.md` lalu
> `docs/superpowers/plans/2026-09-26-memount-v3-wr-struktur-repo.md`. Periksa keadaan nyata dulu: `git status`,
> `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`. Jangan commit, push, mengakses
> Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

**Arahan pemilik 25 September 2026 (verbatim):** "dan disini saya telah membuat sebuah prototype untuk app memount
nya jadikan patokan untuk desaign.md dan ui/ux nya riset detail dan mendalam analisis imk nya kembangkan desain
tersebut sesuai kan pula dengan logic, workflow, konsep, dan realita nya, dll. kembangkan desain dan architecture
nya." Berkasnya `Memount Prototype.html` di root (5,3 MB, belum dilacak; 16 layar). Jawaban pemilik atas enam
pertanyaan keputusan dicatat verbatim di spec v3 §1: pendekatan A irisan vertikal; data contoh lokal saja; paket
luring disetujui dengan riset rujukan dan fitur input data resmi; foto terkurasi berlisensi; huruf dan ikon
didelegasikan ke riset.

**Keadaan 26 September 2026:**

| Butir | Keadaan |
|---|---|
| Analisis prototype | Selesai: 16 layar dibongkar dan dibaca, kontras 50 pasangan dihitung (43 lolos), cacat diukur di peramban (kartu Beranda terpotong 305 dari 468 px). Temuan di spec v3 §3 |
| Suite nyata | 1.252 test, 1.231 lulus, 5 gagal, 15 error, 1 dilewati. Penyebab di spec v3 §3.5 (FR-22 setengah jadi, komponen Simpanan hilang, `docs/fitur/` tidak ada, label navigasi lama di test, props mati, tugas pengingat tidak dipantau). Angka "1.159 dan 1.158" di catatan 25 September di bawah sudah basi |
| Riset luring dan data resmi | Selesai: lima catatan dan laporan (lihat prompt di atas) |
| Riset huruf dan ikon | Selesai: tiga catatan dan laporan. Keputusan atas delegasi pemilik: Plus Jakarta Sans 400 sampai 800 dengan angka tabular (Geist, Geist Mono, Newsreader dilepas); Phosphor dengan bobot per ukuran ditambah 8 sampai 12 glyph domain dari tanda "Jalur Berpos" |
| Spec v3 | Disetujui pemilik 26 September 2026 ("setuju"); addendum §17 (DK-51 sampai DK-58); koreksi 26 September di §3.2 (skema FR-25 ternyata rintisan) dan §3.5 (12 error, bukan 13; dua cacat runtime); izin dan keputusan terbuka di §15 (Q1 sampai Q22) |
| Rencana | Induk W0 sampai W9 di tingkat gelombang (tugas bernomor dengan berkas, test, keputusan yang ditunggu, dan syarat selesai). Rinci W0: 8 tugas, 77 langkah, diuji kering subagen di salinan proyek (1.268 test, 0 gagal). Rinci WR: prasyarat P1 sampai P4 dan 10 tugas, 127 langkah. Rencana tingkat langkah W1 sampai W9 ditulis sebelum gelombangnya dimulai. Ketiganya ditinjau koordinator: klaim utama dicocokkan ke kode, label bukti diluruskan |
| Kode aplikasi | Belum diubah |
| Memori agen | `prototype-patokan-desain.md` diperbarui (huruf Plus Jakarta Sans dan ikon Phosphor, bukan Geist dan Lucide); `skill-ui-wajib-gabung.md` ditambahkan |

**26 September 2026, lanjutan.** Pemilik menyetujui spec v3 ("setuju") dan menambah arahan: tulis rencana W0
sampai W9; restrukturisasi repo untuk persiapan push dengan membandingkan dan menggabungkan dua panduan struktur
(monorepo dan empat repo); folder `docs/` berisi DESIGN, PRODUCT, PRD, AGENTS sebagai aturan tim yang detail dan
lengkap (visi, misi, ekspektasi, kesepakatan, riset lewat brainstorming, cara audit); pasang dan selalu terapkan
ui-ux-pro-max, Claude Code, design-taste-frontend, digabung dengan impeccable dan skill lain. Verbatim di spec v3
§17.1. Yang sudah dikerjakan: skill terpasang dan ditinjau (spec §17.2); addendum spec §17 (DK-51 sampai DK-58:
tetap monolit modular Laravel, `docs/PRD.md` dan `docs/AGENTS.md` kanonis, `DESIGN.md` dan `PRODUCT.md` tetap di
root karena dibaca impeccable dan antislop, CI dipecah, `docker-compose.yml` PostGIS, perapian root, urutan wewenang
skill, tonggak M1 siap push = W0 + WR); gelombang WR ditambahkan ke spec §12; izin baru Q17 sampai Q19.

**Temuan saat menyusun rencana (26 September 2026, diverifikasi koordinator):**

- FAKTA: dua cacat runtime lolos suite. "Hapus" di Simpanan mengirim DELETE ke `/trails/compare/{id}`, rute yang
  tidak ada (`php artisan route:list`), karena mengimpor keluaran Wayfinder basi yang diabaikan git; `npx tsc` lulus
  hanya karena keluaran basi itu. "Hapus dari perbandingan" memanggil `router.delete(undefined)`, karena server
  mengirim untaian URL (`RouteComparisonController.php:151`) sedangkan komponen membaca `{metode, url}`
  (`TabelBanding.tsx:17`). Keduanya ditutup W0 dengan test regresi.
- FAKTA: migrasi FR-25 `personal_targets`, `user_owned_items`, dan `add_source_and_recorded_values_to_hiking_history`
  masih rintisan kosong (belum dilacak git); empat test rintisan (`PersonalTargetTest`, `OwnedEquipmentTest`,
  `ManualHikeRecordTest`, `PersonalDataWriteGuardTest`) lulus tanpa menguji apa pun. Diisi di W7.
- FAKTA: `.github/workflows/ci.yml` memakai `actions/checkout@v4` dan `actions/setup-node@v4` (Node 20), padahal
  GitHub melepas Node 20 dari runner pada 23 September 2026. Push sebelum WR Tugas 3 kemungkinan besar membuat CI
  merah.
- FAKTA: repo GitHub publik; Workspace ID NgodingPakeAI dan ref proyek Supabase sudah terbaca di `origin/hardening`
  (Q21). `maplibre-gl` 4.7.1 terkena CVE-2026-85061 (Q20). 13 tabel yang dibuat sesudah migrasi penutupan PostgREST
  tidak menyalakan RLS di migrasinya (Q22).
- INFERENCE: enam migrasi bertanggal 25 September belum dijalankan di Supabase (bagian 6 mencatat migrasi terakhir yang
  dijalankan `2026_09_21_000100`); bila benar: halaman Simpanan tetap terbuka dan menawarkan editor catatan (mode ketat
  Eloquent mati, kolom yang hilang terbaca null), tetapi menyimpan catatan gagal dengan galat server; halaman trip
  membaca kolom `end_date`, `participants_count`, `reminders_enabled`, dan tabel `trip_reminders` saat merender, jadi
  kemungkinan galatnya terjadi saat halaman dibuka, bukan hanya saat menyimpan (INFERENCE, belum diverifikasi
  menjalankan aplikasi). Verifikasi butuh izin baca (Q22).

**Keputusan pemilik 26 September 2026, setelah rencana W0 dan WR ditinjau penuh oleh koordinator:** mulai eksekusi
W0 lalu WR, berurutan, dengan subagen per tugas dan peninjau per tugas; penutup celah MapLibre (`attributionControl:
false` dan test regresi) masuk W0 sebagai Tugas 8; belum ada commit sampai tonggak M1, dan diff ditinjau pemilik di
sana. Keputusan terbuka: Q21, Q22, Q17 sampai Q19, upgrade MapLibre 6.x sebelum W3.

**W0 Stabilisasi selesai (26 September 2026)** menurut rencana
`docs/superpowers/plans/2026-09-26-memount-v3-w0-stabilisasi.md`:

| Butir | Keadaan |
|---|---|
| Suite Unit dan Feature | 1.277 test, 1.276 lulus, 0 gagal, 0 error, 1 dilewati (PostGIS), 7.240 assertion; diukur ulang koordinator sesudah gelombang perbaikan tinjauan akhir (sebelumnya 1.269 dan 1.268) |
| Tinjauan | setiap tugas ditinjau spesifikasi dan mutunya; tinjauan akhir W0 menemukan 0 Critical dan 3 Important, semuanya diperbaiki dalam satu gelombang lalu ditinjau ulang: fokus keyboard editor catatan (WCAG 2.4.3), konfirmasi sebelum menghapus simpanan yang berisi catatan, dan penjelasan HANDOVER tentang kegagalan bila kolom `note` belum ada |
| Sisa yang diketahui (backlog W3) | bila kurang dari dua simpanan yang terbit, alasan "tidak terbit" tidak tampil di kartu dan kalimat "Simpan satu jalur lagi" kurang tepat (kriteria 5 `docs/fitur/03-pertimbangkan.md` perlu disesuaikan); label "Bandingkan N jalur" di baki Jelajah dan detail jalur tidak dipotong batas banding; `NoDeadViewDataTest` menerima sebutan di komentar; logika batas pilihan ada di dua tempat; asersi literal sumber TSX rapuh terhadap perombakan W3; batasan angka `[0-9]{1,18}` untuk rute `{trail}` |
| Celah MapLibre (Q20) | kontrol atribusi bawaan dimatikan di `usePetaDasar.ts`, atribusi tetap teks lewat `WadahPeta`; `npm audit` tetap melaporkan CVE-2026-85061 sampai upgrade 6.x yang diputuskan sebelum W3 |
| `npx tsc --noEmit` | 0 galat terhadap keluaran Wayfinder yang dibangkitkan ulang |
| `npm run build` | sukses (diulang koordinator sesudah perbaikan dengan awalan SQLite: built in 1,75 s) |
| Pint | bersih; impor tak terpakai di empat test rintisan FR-25 ikut dibuang |
| FR-22 catatan simpanan | rute `trails.saved.note.update` dan `.destroy`, Form Request, layanan, props, dan editor di kartu Simpanan |
| Props yang dilaporkan mati | `batasBanding`, `jumlahTersimpan`, `pesan`, `pengingat` kini dipakai halaman, begitu juga `pilihan` yang sebelumnya lolos test hanya lewat komentar; tidak ada yang dicabut karena semuanya diikat test lain |
| Penjadwal | `trips:kirim-pengingat` dipantau `SchedulerHealthService::TUGAS` dengan toleransi 3 jam |
| `docs/fitur/` | delapan spesifikasi dengan sembilan heading templat |
| Cacat tersembunyi yang ditutup | impor Wayfinder basi di Simpanan (Hapus mengirim DELETE ke `/trails/compare/{id}` yang tidak ada, dan `tsc` serta build gagal begitu Wayfinder dibangkitkan); "Hapus dari perbandingan" memanggil `router.delete` dengan alamat kosong; halaman Banding membaca `modeTautan` dan `tautanBagikan` yang tidak pernah dikirim |
| Belum diverifikasi | klik-lewat dan tangkapan 390 px serta 1440 px di peramban (menunggu Q1 atau Q2); status task NgodingPakeAI di server |
| Commit | belum, sesuai keputusan pemilik 26 September 2026: diff ditinjau di tonggak M1 (R-018, DK-58) |

**Butuh keputusan pemilik:**
1. Seri commit di M1: seluruh migrasi React (374 entri `git status`) dan perubahan W0 serta WR belum di-commit; ringkasan diff per kelompok disusun di akhir WR untuk diputuskan pemilik.
2. Migrasi yang kemungkinan belum dijalankan di Supabase (INFERENCE: `HANDOVER.md` bagian 6 mencatat migrasi terakhir yang dijalankan `2026_09_21_000100`; verifikasi dengan `php artisan migrate:status` atas izin, R-019) tetapi dipakai fitur yang sudah tampil: `add_note_to_trail_considerations_table` (catatan simpanan), `add_plan_details_to_trip_plans_table`, dan `create_trip_reminders_table` (pengingat). Bila benar belum: halaman Simpanan tetap terbuka dan menawarkan editor catatan, tetapi menyimpan catatan gagal dengan
galat server; halaman trip kemungkinan galat saat dibuka karena kolom dan tabel itu dibaca saat merender (INFERENCE,
Q2, R-024). Bila dijalankan sebelum W7, jalankan per berkas dengan `--path` supaya tiga migrasi rintisan FR-25 tidak ikut.
3. Migrasi rintisan FR-25 (`create_personal_targets_table` dan `create_user_owned_items_table` hanya `id` dan `timestamps`; `add_source_and_recorded_values_to_hiking_history_table` kosong) jangan dijalankan di Supabase sebelum W7 mengisinya. Spec v3 §3.2 keliru menyebut skema itu sudah menampung data prototype.
4. Empat test rintisan (`PersonalTargetTest`, `OwnedEquipmentTest`, `ManualHikeRecordTest`, `PersonalDataWriteGuardTest`) hanya membuka `/` dan memberi lulus palsu. Usul: ditandai dilewati dengan alasan sampai W7, atau langsung diganti di W7.
5. Penjadwal produksi harus menjalankan `trips:kirim-pengingat` tiap jam dengan driver surel nyata (K11, K24); sampai itu Kesehatan Sistem menyebutnya "Belum pernah berjalan".
6. Toleransi 3 jam untuk tugas pengingat adalah ambang operasional usulan agen.
7. Rencana induk menaruh UI catatan di W3; W0 memasang editor dasar karena rute tanpa pemakai di antarmuka mengulang kelas cacat "pipeline tanpa konsumen" yang dijaga `NoDeadViewDataTest`. W3 merestilnya dengan primitif dari W1.

**Langkah berikutnya:** WR Struktur repo dan dokumen tim
(`docs/superpowers/plans/2026-09-26-memount-v3-wr-struktur-repo.md`), lalu W1.

---

## 0-25. Catatan 25 September 2026 (riwayat)

**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0 ini, lalu spec
> `docs/superpowers/specs/2026-09-25-memount-v2-design.md` (arahan pemilik verbatim,
> temuan, IA v2, aliran kerja A-G, izin yang ditunggu). Periksa keadaan nyata dulu:
> `git status`, `php artisan test --compact --testsuite=Unit,Feature`, `npx tsc --noEmit`.
> Lanjutkan aliran kerja yang belum selesai menurut tabel di bawah. Jangan commit, push,
> mengakses Supabase, menjalankan migrasi, atau menambah dependensi tanpa izin saya.

**Arahan pemilik 25 September 2026 (inti, verbatim lengkap di spec §1):** aplikasi masih
terasa "CRUD sederhana" dan tidak semua fitur berfungsi baik; riset tren UI/UX dan IMK
terbaru (termasuk bahasa desain OS brand besar) lalu terapkan; pakai dan gabungkan semua
skill; IA baru lima tab **Home, Explore, Trips, Track, Progress** plus **Profile**, dengan
**Community sebagai lapisan lintas-halaman**; rombak dan rapikan repo agar mudah untuk tim;
satukan dokumen yang bertumpuk menjadi satu visi, misi, ekspektasi, dan kesepakatan
bersama; siapkan konteks tahan-sesi. Pernyataan merek: "MEMOUNT adalah platform
perjalanan gunung yang dipersonalisasi, yang menghubungkan discovery, Route Fit,
preparation, readiness, field experience, trust, community, dan progression dalam satu
perjalanan utuh. MEMOUNT membantu saya menemukan perjalanan gunung yang cocok untuk saya."

**Aliran kerja** (rincian di spec §7):

| Aliran | Keadaan 25 Sep 2026 |
|---|---|
| A. Stabilisasi pasca-migrasi React | **Selesai.** Livewire dipensiunkan (layout, navigasi Volt, 33 komponen Blade mati dihapus; `App\Actions\Logout`); pintu Vite `app.js` dicabut; suite 1.159 test, 1.158 lulus, 1 dilewati (PostGIS), `tsc` 0, build sukses |
| B. Riset UI/UX dan IMK | Empat subagen menulis `docs/riset/2026-09-25-uiux/01-04`; sintesis `00-sintesis.md` menyusul |
| C. Dokumen dan repo | README proyek dan `CONTRIBUTING.md` (root) ditulis ulang; `docs/MATRIKS-SKILL.md`; `anti-slop/` pindah ke `docs/audit/anti-slop/`; `.gitignore` merapikan berkas lokal. Konsolidasi PRD (rencana 23 Sep) dikerjakan subagen; `PRD.md` kerja belum diganti sampai ia selesai |
| D. Fondasi desain | Menunggu B. Navigasi IA v2 (lima tab Beranda, Jelajah, Trip, Mendaki, Progres) dan tab Mendaki (`/mendaki`, `TrackController`) sudah terpasang dengan gaya lama |
| E. Halaman per tab | Menunggu D |
| F. Fitur DK-07 tanpa dependensi | Dua subagen backend: (1) simpanan vs banding maks 3, catatan pribadi, tautan bagikan, Kelola Peran; (2) detail rencana, pengingat, target pribadi, catat pendakian manual, perlengkapan. Migrasi baru hanya diuji lokal; **belum boleh dijalankan di Supabase tanpa izin** |
| G. Verifikasi dan serah terima | Berkelanjutan. Halaman Admin, Kesehatan Sistem kini menampilkan migrasi yang belum dijalankan di basis data yang dipakai (`MigrationStatus`) |

**Sudah dikerjakan 25 Sep 2026 (terverifikasi):** tabel banding tidak lagi melebarkan
halaman di 360 px (wadah gulir kini `relative`, dijaga `InertiaLayoutTest`); target
sentuh kecil diperbesar; log audit kini mencatat nilai lama yang benar (`getPrevious()`,
`AuditLogChangeTest`); penghapusan aturan izin mencatat isi baris; registri tautan React
kini daftar pengecualian `bukanHalamanReact.json` yang dicocokkan dengan rute nyata
(`InertiaLinkRegistryTest`) dan tautan ke situs lain selalu muat penuh; tema baru langsung
berlaku tanpa muat ulang (`ThemePreferenceTest`); lima komponen peta memakai satu dasar
`resources/js/components/peta/usePetaDasar.ts` dengan teks MapLibre berbahasa Indonesia
dan pengakuan gagal gaya peta; pratinjau garis di impor geometri admin; tabel admin
disatukan; CI kini menjalankan `npm run types`; hasil Wayfinder diabaikan git; test acak
`CheckpointJourneyTest` distabilkan (nama khas checkpoint pabrik).

**Keputusan terbuka untuk pemilik:** spec §8 (P1-P12).

---

## 0a. Catatan 24 September 2026 (riwayat)

**Sesi 24 September 2026, ringkas.** Pemilik menambah lima prinsip rekayasa (§3 butir 9)
dan mengusulkan nama **EMOUNT**. Nama itu dinilai dengan rubrik yang sama seperti nama
lain dan **gugur gerbang G3 dan G5** (merek "E-MOUNT" Sony kelas 9 dan "EPLAN EMOUNT"
kelas 9/35/42 aktif di USPTO; awalan "e" + "mount" deskriptif; pencarian tenggelam oleh
lensa Sony), meski PDKI bersih dan `emount.id` tersedia. Bukti dan tiga jalan ke depan:
`docs/riset/2026-09-23-konsolidasi/riset-nama-emount.md`. **Keputusan nama tetap milik
pemilik** (R-021); pekerjaan sesi ini dibangun agar nama apa pun cukup diganti di satu
tempat (`APP_NAME`).

Yang dibangun dan diverifikasi (test hijau, runtime dipotret, Supabase tidak disentuh):

- **Sistem identitas merek:** `config/brand.php` (tagline, deskripsi, warna tema),
  komponen `x-brand.mark` (tanda "Jalur Berpos", konsep A), `x-brand.lockup` (tanda +
  nama dari `config('app.name')`), `x-brand.head` (kepala dokumen bersama untuk enam
  layout). Logo bawaan Laravel dihapus (F5 selesai sebagai sketsa; gerbang DK-16 untuk
  aset final belum). Ikon PWA 192/512/maskable/180, `favicon.svg` dan `favicon.ico`
  digambar dari tanda merek.
- **Tema gelap (DK-17, DK-19):** token gelap di `app.css` (dua blok identik, dijaga
  test), `data-theme` dirender server dari cookie `tema` yang diwhitelist di
  `App\Support\ThemePreference`, `POST /tema` dengan `throttle:60,1`, pemilih tiga
  radio di Profil, mode pendakian, dan kaki halaman depan; `min-h-dvh`; peta diredupkan
  di tema gelap; token `brand-ink` untuk hijau sebagai latar depan; 33 `bg-white` dan
  semua `text-black`/`dark:` dihapus dari view.
- **Temuan ditutup:** F5 (sketsa), F8 (`lang/id/recommendation.php`, fallback `id`),
  F9 (meta "apa yang masih kurang sebelum berangkat"), F11 (nama manifest dari
  `config('app.name')`, ikon maskable terpisah), F12 (orientasi `any`), F13
  (`ColourContrastTest` membaca token per blok). DK-24: `@tailwindcss/vite` dicabut.
- **Test baru:** `ThemePreferenceTest` (8), `BrandIdentityTest` (8), `ColourContrastTest`
  diperluas (pasangan × dua tema, paritas blok gelap, non-teks gelap, larangan warna
  mentah). Bukti runtime: `curl` ke server lokal SQLite menunjukkan `Set-Cookie:
  tema=dark`, `<html data-theme="dark">`, dan penghapusan cookie saat "Ikuti sistem";
  24 tangkapan layar terang/gelap/sistem × ponsel/desktop di scratchpad sesi.

**Pembaruan malam 24 September 2026.** Pemilik mengusulkan MENDAKI dan MEMOUNT, dan
menambah arahan: *"frontend combine dengan react. jadi base nya tetap laravel namun, untuk
menunjang ui/ux nya combine dengan react."*
- **MENDAKI gugur** (kata kerja generik untuk jasanya sendiri, G2/G5; merek MENDAKI kelas 41
  sudah terdaftar di PDKI; `mendaki.id` dan `.com` terdaftar; Yayasan MENDAKI di Singapura).
- **MEMOUNT lolos semua gerbang meja** (v1 9 dari 12, v3 18 dari 26): PDKI bersih, USPTO tanpa
  merek identik, **`memount.com` dan `memount.id` tersedia**, tidak ada aplikasi bernama sama.
  Syarat: uji dengar-lalu-eja K1b dan amankan domain segera. Bukti:
  `docs/riset/2026-09-23-konsolidasi/riset-nama-mendaki-memount.md`.
- **React:** riset dan usulan keputusan di `docs/riset/2026-09-23-konsolidasi/frontend-react.md`.
  Rekomendasi: pulau React di halaman Blade/Livewire untuk peta, banding jalur, dan profil
  elevasi (kontrak: Livewire memegang data dan otorisasi, React memegang interaksi); Inertia 3
  diputuskan setelah tiga pulau pertama hidup. **Belum ada dependensi dipasang**: menunggu
  persetujuan pemilik atas `react`, `react-dom`, `@vitejs/plugin-react` (mengubah DK-24).

**KEPUTUSAN PEMILIK 24 September 2026 (verbatim): "tetapkan Memount sebagai nama brand app
web ini."** Diterapkan: `APP_NAME="Memount"` di `.env` dan `.env.example`, bawaan
`config/app.php` menjadi `Memount`, `BrandIdentityTest::test_the_brand_name_is_memount`
menjaganya; DK-13 di spec konsolidasi terisi; DESIGN.md "Arah" memuat kata-kata pemilik.
Nama ditulis **Memount** (kapital awal) di teks dan aria; wordmark menampilkannya kapital
penuh lewat CSS. Tindakan yang hanya bisa dilakukan pemilik, dan sebaiknya segera karena
ketersediaan bisa hilang: daftarkan `memount.com` dan `memount.id`, ambil `@memount` di
Instagram dan `@memount.id` di TikTok/X/YouTube, ajukan merek DJKI kelas 9 dan 42. Uji
dengar-lalu-eja K1b tetap dijalankan sebelum aset logo final (gerbang DK-16).

**KEPUTUSAN PEMILIK 24 September 2026: jalur C, migrasi penuh ke React + Inertia**
(jawaban "c" atas tiga opsi di `frontend-react.md`). Risiko sudah dinyatakan sebelum
keputusan; sejak itu dikerjakan sebagai migrasi bertahap dengan Livewire dan React
berdampingan sampai halaman Livewire terakhir pindah.

Fondasi yang sudah terpasang dan hijau (979 test, 978 lulus, 1 dilewati; `tsc` bersih;
Vite build sukses):
- Paket: `inertiajs/inertia-laravel` 3.3.4, `laravel/wayfinder` 0.1.21; npm `react` 19.3,
  `react-dom` 19.3, `@inertiajs/react` 3.7, `@inertiajs/vite` 3.7, `@vitejs/plugin-react`
  6.1, `typescript` 5.9, `@laravel/vite-plugin-wayfinder`. Tailwind tetap 3.4.19.
- `HandleInertiaRequests` (props bersama: auth secukupnya, merek, tema, peta, flash),
  templat akar `resources/views/inertia.blade.php` (judul dan meta description dirender
  server, tautan lewati, `x-brand.head`), `vite.config.js` dua pintu (`app.js` Livewire,
  `app.tsx` Inertia, berbagi `dasar.js`), `tsconfig.json`, rute bertipe Wayfinder
  (`php artisan wayfinder:generate` setiap rute berubah). SSR **mati** sampai diputuskan.
- Komponen React: `layouts/AppLayout`, `GuestLayout`, `NavLinks`; `components/brand`
  (Mark, Lockup); `components/ui` (Button, PageHeader, Card, EmptyState, Alert, Field,
  FitBadge, Pagination, MapView, ThemePicker); `components/trails` (TrailRow, FitLine,
  FilterForm, Menunggu, ConsiderationTray, types).
- Kontrak arsitektur tercatat di `docs/ARCHITECTURE.md` (server memegang data dan
  otorisasi, React memegang interaksi; skor internal tidak pernah ke props).

**Ledger migrasi halaman** (diperbarui setiap halaman pindah):

| Halaman | Status | Catatan |
|---|---|---|
| `/` halaman depan | **React** (`pages/Welcome.tsx`) | `welcome.blade.php` dihapus |
| `/trails` Jelajah | **React** (`pages/Trails/Index.tsx`, `TrailIndexController`, `Services\Trails\TrailSearch`, `TrailRowResource`, `POST trails/{trail}/timbang`) | Livewire `TrailIndex` dihapus; 12 berkas test diarahkan ke `assertInertia` |
| `/trails/{slug}`, `/trails/compare`, `/trails/saved` | Livewire | berikutnya (berbagi `TrailRowResource`) |
| Dasbor, Rencana, Rekomendasi, Trip, Persiapan, Readiness, Mode pendakian, Laporan, Kabar, Progres, Riwayat, Kontribusi | Livewire | urutan mengikuti loop produk |
| Auth (Breeze Volt), Profil | Livewire | `POST /logout` sudah ada untuk layout React |
| Admin, Moderasi | Livewire | terakhir |
| `/pendakian/{slug}`, sitemap | Blade | tetap Blade sampai SSR diputuskan (SEO) |

Bukti runtime 24 September 2026 malam: tangkapan layar `react-welcome` dan
`react-trails` terang/gelap, ponsel/desktop di scratchpad sesi; lockup Memount,
navigasi lima permukaan, baki simpanan, dan tema gelap terlihat benar. Peta sebaran
(MapLibre di React, `components/ui/MapView.tsx`) terbukti menggambar basemap OpenTopoMap
dan penanda gunung pada tangkapan `react-trails-map.png` (Chrome headless dengan WebGL
perangkat lunak); tanpa WebGL komponen menampilkan kalimat gagal yang jujur dan mencatat
galatnya ke konsol.

Yang perlu pemilik lakukan: set `APP_FALLBACK_LOCALE=id` di `.env` lokal (contohnya sudah
diubah); meninjau dua halaman React pertama di peramban; meninjau sketsa tanda merek di
header sebelum gerbang DK-16 dijalankan; memutuskan SSR (butuh Node di server produksi).

Catatan sesi sebelumnya (23 September 2026) di bawah tetap berlaku kecuali yang ditandai
selesai di tabel temuan.


**Tempelkan ini di terminal baru:**

> Baca `CLAUDE.md`, lalu `HANDOVER.md` bagian 0, lalu spec
> `docs/superpowers/specs/2026-09-23-konsolidasi-dokumen-design.md` dan rencana
> `docs/superpowers/plans/2026-09-23-konsolidasi-dokumen.md`. Catatan riset ada di
> `docs/riset/2026-09-23-konsolidasi/`. Lanjutkan dari "Langkah berikutnya" di bawah.
> Jangan commit, push, mengakses Supabase, atau menambah dependensi tanpa izin saya.

**Sudah selesai**

- Task NgodingPakeAI #10 "Bangun halaman daftar rute tersimpan" berstatus done di server.
  `/trails/saved` membaca `trail_considerations` lewat `ConsiderationService::forUser()`;
  satu tombol "Simpan rute" / "✓ Tersimpan"; pengumuman `role="status"`; tautan dari baki
  Jelajah dan halaman banding. Suite penuh 933 test: 916 lulus, 0 gagal, 17 dilewati.
  Diverifikasi di peramban terhadap SQLite scratch (Supabase tidak disentuh).
- Dihapus: 8 berkas sampah keluaran redirect shell, `debug_trail_search.php`,
  `resources/views/layouts/navigation.blade.php` (kode mati).
- Spec konsolidasi dokumen disetujui pemilik (DK-01 sampai DK-12) dan rencana 14 tugas
  ditulis. Belum ada berkas kanonis yang ditimpa.
- `npx ngodingpakeai sync --if-changed --plan` sudah dijalankan (metadata saja).

**Belum ada yang di-commit.** Working tree mencampur kerja agen sebelumnya (task #1–#9
Jelajah, belum diverifikasi) dengan kerja sesi ini di berkas yang sama.

**Keputusan dan arahan pemilik 23 September 2026**

- Cakupan: dokumen dan kerapian repo; struktur `app/` tidak dirombak.
- PRD digabung dengan pendekatan "satu pemilik per jenis isi"; nomor § adalah ID permanen.
- Diputuskan atas delegasi pemilik kepada riset: simpanan longgar dengan banding
  maksimal 3; peta digerbang data (hanya jalur bergeometri); amandemen R-009 menyebut
  ACCREDITED EXPERT "Ahli bersertifikat".
- Masuk MVP: catatan pribadi + tautan bagikan; pengingat + detail rencana; masuk dengan
  Google dan passkey; target pribadi + catat pendakian manual; verifikasi kode lewat
  email, WhatsApp, SMS.
- Deploy produksi di Vercel.
- Eksekusi rencana memakai subagen per tugas (izin eksplisit).
- Merek baru, kata-kata pemilik: "VELORA adalah platform perjalanan gunung yang
  dipersonalisasi, yang menghubungkan discovery, Route Fit, preparation, readiness,
  field experience, trust, community, dan progression dalam satu perjalanan utuh."
- Tambahan: mode gelap, bahasa tambahan, kompatibel semua perangkat, data gunung seluruh
  Indonesia yang dapat diinput di aplikasi.
- Landasan kerja: selalu riset mendalam dengan informasi terkini yang kompatibel dengan
  stack dan pengguna.

**Riset arahan baru**: lima catatan di `docs/riset/2026-09-23-konsolidasi/`
(strategi merek, identitas visual dan logo, mode gelap dan perangkat, multibahasa,
data gunung Indonesia). Salinan kerja di scratchpad sesi 23 September 2026.

**Temuan terbuka**

| # | Temuan | Status |
|---|---|---|
| F1 | Fokus keyboard hilang ke `<body>` setelah aksi Livewire (`x-ui.button` menonaktifkan diri) | Terukur di peramban |
| F2 | Peta Jelajah memakai koordinat gunung sebagai titik jalur | Diputuskan: dihapus (peta digerbang data) |
| F3 | Pencarian kalimat memakai 5 alias kota dan membuang teks pencarian | Diperbaiki lewat FR-27 |
| F5 | Logo header bawaan Laravel | Selesai 24 Sep 2026 sebagai sketsa tanda "Jalur Berpos" (`x-brand.lockup`); aset final menunggu gerbang DK-16 |
| F6 | Di ponsel daftar jalur mulai jauh di bawah layar pertama | Redesain Jelajah |
| F7 | Baki simpanan `sticky` berpotensi menutupi fokus (WCAG 2.4.11) | Hipotesis, belum diuji |
| F8 | `lang/en/recommendation.php` berisi teks Indonesia; `fallback_locale` = `en` | Selesai 24 Sep 2026: dipindah ke `lang/id`, fallback `id` (`.env` lokal pemilik masih perlu diubah) |
| F9 | Meta description publik "apakah sudah layak berangkat" (`welcome.blade.php:8`) bertentangan R-014 | Selesai 24 Sep 2026: `config('brand.description')`, dijaga `BrandIdentityTest` |
| F10 | Nama VELORA: dipakai protokol DeFi (ex-ParaSwap, April 2025) dan merek lokal; domain `velora.*` sudah terdaftar; PDKI belum diperiksa | Selesai: pemilik menolak nama itu (D1) |
| F11 | Manifest PWA: `app-512.png` dipakai untuk `any` dan `maskable`; nama "Rencana Pendakian"/"Pendakian" vs `APP_NAME` "Jalur Naik" (`ProgressiveWebAppController.php:17-18, 32-33`) | Selesai 24 Sep 2026: nama dari `config('app.name')`, `app-512-maskable.png` terpisah |
| F12 | Manifest `'orientation' => 'portrait'` (`ProgressiveWebAppController.php:22`) berisiko gagal WCAG 1.3.4 | Selesai 24 Sep 2026: `any` |
| F13 | `ColourContrastTest::token()` memakai `preg_match` (baris 259), jadi hanya token pertama yang dibaca; token gelap tidak akan diperiksa | Selesai 24 Sep 2026: token dibaca per blok, dua tema dihitung |

**Langkah berikutnya** (diperbarui 23 September 2026, malam)

1. Selesai: lima riset, laporan sintesis (`laporan-sintesis.md`), riset nama
   (`riset-nama-merek.md`, daftar pendek PAMARGI 12/12, PARWATA, LAMPAH, JALURA; PANDOM gugur).
2. Selesai, menunggu tinjauan pemilik: amandemen spec §2.1 DK-13–DK-24 (keputusan D1–D12),
   §156–§159 baru, K29–K34, D-C, F8–F13, GR-03 (§10.1), V9, sub-proyek 6.
3. Selesai: penilaian dua nama usulan pemilik di
   `docs/riset/2026-09-23-konsolidasi/riset-nama-usulan-pemilik.md`. VERTIKA 5 dari 12, gugur
   (aplikasi "Vertika" identik di App Store Indonesia); GOURVA 7 dari 12, cadangan bersyarat.
   Bukti penentu dicek ulang 14.47 UTC. Pemilik menyebut "ketiga nama" tetapi baru dua yang
   diterima; nama ketiga dinilai dengan rubrik yang sama bila ada.
4. Pemilik tidak memilih dari daftar itu. Jawabannya: *"riset lagi saya ingin go
   internasional"*; gourva.com bukan miliknya. Riset nama internasional berjalan: catatan di
   `docs/riset/2026-09-23-konsolidasi/nama-internasional/`, laporan akhir
   `docs/riset/2026-09-23-konsolidasi/riset-nama-internasional.md`. Bila laporan akhir belum
   ada, lanjutkan dari catatan yang sudah ada, jangan mulai dari nol. Setelah pemilik memilih,
   isi slot [NAMA] di DK-13 dan perbarui memori `velora-merek-dan-arah.md`.
   Keadaan riset per 23 September 2026 malam:
   - selesai: `lanskap-dan-strategi.md` (stem jenuh, Madrid dan DJKI, rubrik v3 berbobot;
     pesaing konsep terdekat muncak.id, dicek ulang) dan `kandidat-dan-saringan.md`
     (62 kandidat baru, 30 gugur; 12 lolos: STEPLARAS, BENDPIN, LIKUPOS, KELOKPOS, PINTANDA,
     LINTAMO, PORANTI, TALAMPI, TANLIMO, TOLAPI, TUMPALO, PAMARGA; PAMARGI turun ke 10 pada
     rubrik v2 internasional karena "gi" dibaca lunak dan "marg" berarti mati dalam bahasa Persia);
   - selesai (24 September 2026 dini hari): verifikasi mendalam tiga kelompok
     (`verifikasi-kelompok-1.md`, `-2.md`, `-3.md`), rubrik v2 dan v3 berdampingan. Skor v3
     (maks 26): TALAMPI 21, LINTAMO 20, TANLIMO 19, TUMPALO 17, PINTANDA 16 (bersyarat: merek
     TANDA di kelas 9/42), PAMARGA 16, PORANTI 16 (tidak disarankan), PAMARGI 14 (tertahan),
     STEPLARAS 12 (tertahan); gugur: TOLAPI (Tohapi, UE), BENDPIN, LIKUPOS, KELOKPOS;
   - selesai: laporan akhir `docs/riset/2026-09-23-konsolidasi/riset-nama-internasional.md`
     (TALAMPI memimpin tipis; usulan uji dengar-lalu-eja TALAMPI, LINTAMO, TANLIMO; TUMPALO
     cadangan). Bukti penentu dicek ulang 23 September 2026 20.00 UTC. Menunggu keputusan
     pemilik: jalankan uji pengguna, pilih langsung, atau kembali ke PAMARGI (Indonesia saja);
     serta apakah "go internasional" juga berarti cakupan produk di luar Indonesia (R-021). Uji dengar-lalu-eja dengan pengguna
     nyata (K1b) tidak bisa dijalankan agen; naskahnya disiapkan untuk pemilik.
5. Perbarui rencana: rentang indeks §1–§159, bagian §156–§159, tugas `DESIGN.md`, GR-03, V9.
6. Eksekusi rencana per tugas dengan subagen; loop NgodingPakeAI dilanjutkan setelah PRD
   gabungan disetujui (sisa halaman Jelajah: task #11–#13).

**Keputusan pemilik atas laporan sintesis (23 September 2026).** Laporan lengkap:
`docs/riset/2026-09-23-konsolidasi/laporan-sintesis.md`, bagian "Keputusan yang dibutuhkan
dari pemilik" (D1–D12).

- **D1: nama VELORA TIDAK dipakai; ganti ke nama lain.** Pernyataan merek dan delapan pilar
  tetap berlaku, hanya namanya berganti. Langkah berikut: riset nama baru (domain .id
  tersedia, konflik PDKI rendah, mudah diucapkan, tanpa konotasi kecepatan atau janji
  keselamatan), pemilik memilih dari daftar pendek. Hasil riset nama ditulis ke
  `docs/riset/2026-09-23-konsolidasi/riset-nama-merek.md`; bila berkas itu belum ada,
  riset nama belum selesai dan harus dijalankan ulang.
- **D4: logo konsep A "Jalur Berpos"** (rute zig-zag berujung cincin pos), dipadukan dengan
  wordmark nama baru; aset final baru setelah verifikasi nama, uji 16 px, audit kemiripan,
  uji pemahaman 5–8 pendaki.
- **D10: katalog gunung Tier A terkurasi** (ratusan entitas bersumber per field), status bahaya
  PVMBG dan status akses Kemenhut disimpan terpisah, OSM hanya peta dasar (tanpa ODbL),
  empat fase: katalog, status, golden trail, kontribusi termoderasi.
- **Disetujui semua rekomendasi lain:** D2 kontrak merek (tagline "Pilih jalur yang sesuai.
  Pahami alasannya.", tetap "Anda", perbaikan "layak berangkat"), D3 Route Fit sebagai fitur
  bermerek, D5 mode gelap token CSS + tema dirender server dari cookie (default "Ikuti
  sistem", tetap Tailwind 3.4.19), D6 peta topo diredupkan + cadangan OpenTopoMap, D7
  orientasi `any` + CSS `@container`, D8 Inggris lebih dulu + fallback `id` setelah F8,
  D9 `/en/mountains/...` + migrasi `/pendakian` bersamaan, D11 skema bertahap dengan izin
  per migrasi, D12 nol dependensi runtime baru + hapus `@tailwindcss/vite` yang mati.

**Selalu perlu izin pemilik:** commit, push, akses Supabase, menambah dependensi,
menjalankan migrasi.

---

## 1. Cara melanjutkan di terminal baru

Buka terminal baru di `E:\praktik_aplikasi_web`, jalankan `claude`, lalu tempelkan ini:

> Baca `HANDOVER.md` di root proyek ini dari awal sampai akhir sebelum melakukan apa pun.
> Itu konteks lengkap pekerjaan kita. Sesudah membacanya, konfirmasi ke saya apa yang kamu
> pahami tentang tujuan, kesepakatan, dan keadaan sekarang, lalu tunggu instruksi saya.
> Jangan mulai menulis kode sebelum saya setujui.

Dokumen ini plus `docs/PRD.md` plus `docs/superpowers/specs/` cukup untuk memulihkan seluruh
konteks.

---

## 2. Tujuan dan ekspektasi pemilik produk

Disalin dari kata-katanya sendiri, bukan ditafsirkan:

> "aplikasi web gunung yang memadukan antara aplikasi web atau aplikasi yang popular dan
> sudah banyak user nya dengan strava dan traveloka, bagaimana user riset di masing-masing
> aplikasi nya dengan itu saya ingin mengcompare ke 3 itu di padukan dan menerapkan pada
> aplikasi gunung."

> "saya mengutamakan untuk pertama imk dan ui/ux"

> "impact dari interaksi manusia komputer, ui, dan ux di semua refrensi yang saya cantumkan
> dari hasil riset, tujuan, dan kesepakatan diatas itu sama dengan aplikasi web yang akan
> kita bangun."

Artinya: **bukan menyerupai rupa ketiga rujukan, melainkan mencapai dampak IMK/UI/UX yang
setara dengan mereka.**

Aspek sistem yang dituntut, di luar UI/UX: arsitektur dan skalabilitas, keamanan dan
kepatuhan, keandalan dan manajemen kegagalan, operasional dan observability, pengelolaan
data dan konsistensi, serta marketing.

Peran yang diminta: lead engineer 30 tahun, sekaligus UI/UX designer, product designer,
software engineer, dan manajer audit.

---

## 3. Kesepakatan yang mengikat

Ini aturan kerja yang diminta pemilik produk dan **tidak boleh dilanggar**:

1. **Git adalah penyerahan kepada klien.** Push dan merge hanya dilakukan ketika aplikasi
   sudah benar-benar sesuai tujuan. Commit lokal boleh; push tidak, sampai diizinkan.
2. **Selalu konfirmasi sebelum mengakses Supabase**, termasuk kuncinya. Kunci boleh
   dipakai, tetapi **tidak pernah ditaruh di skrip mana pun**.
3. **Riset mendalam dan brainstorming terlebih dahulu sebelum memutuskan.** Bukan menebak.
4. **Selalu testing dan crosscheck.** Buktikan dengan pengukuran sebelum melapor temuan.
5. **Terapkan semua skill yang terpasang**, bukan hanya yang relevan tetapi juga yang
   mendukung.
6. **Selalu evaluasi dan susun rencana problem-solving berikutnya.**
7. **Setiap recap wajib memuat persentase progres bermetode**, plus problem, solusi, yang
   masih kurang, dan rencana berikutnya.
8. **Belajar di setiap task.**
9. **Lima prinsip rekayasa berlaku pada setiap perubahan** (arahan pemilik 24 September
   2026: "selalu menerapkan dan menambahkan prinsip proper architecture, scalable system,
   secure by design, stable, and reliable"). Sebelum melapor, setiap perubahan dijawab
   dengan lima pertanyaan ini, dan jawabannya masuk rekap:
   - **Proper architecture:** satu sumber kebenaran per konsep; tidak ada nilai yang
     ditulis di dua tempat (contoh: nama produk hanya di `config('app.name')`, palet
     hanya di token `app.css`, kepala dokumen hanya di `x-brand.head`).
   - **Scalable system:** jumlah query dan ukuran respons tidak tumbuh mengikuti jumlah
     data; state tidak menempel pada satu proses (preferensi tema di cookie, bukan sesi).
   - **Secure by design:** input tak dipercaya, daftar putih bukan daftar hitam, otorisasi
     di server, rate limit pada endpoint tanpa autentikasi, tidak ada secret di kode.
   - **Stable:** kegagalan terkendali dan tidak menjatuhkan halaman (peta gagal, cuaca
     tidak tersedia, JavaScript belum jalan); halaman pertama benar tanpa skrip.
   - **Reliable:** dibuktikan test yang menghitung, runtime yang dipotret, dan paritas
     PostgreSQL bila skema tersentuh (R-023, R-024). Tanpa bukti, tidak ada PASS.

---

## 4. Dasar produk: PRD

`docs/PRD.md` (21 September 2026: 3365 baris, 128 bagian). Yang paling menentukan:

- **§7 Core Product Loop**: PROFILE → ROUTE FIT → PLAN → PREPARE → CHECK → HIKE → REPORT
  → IMPROVE. Aturannya tegas: *"Semua fitur utama harus memiliki hubungan dengan loop
  tersebut."*
- **§8 Diferensiator**: bukan banyak gunung, bukan GPS, bukan review, bukan checklist,
  melainkan **menghubungkan karakteristik pendaki dengan karakteristik jalur, lalu
  menjelaskan alasannya**.
- **§18 / BR-02**: MDPL hanya atribut deskriptif, **tidak pernah** penentu kesulitan.
  Gunung 2.000 mdpl bertanjakan 1.400 m lebih berat daripada 3.000 mdpl bertanjakan 700 m.
- **§89 Progressive disclosure**: Apa ini → Cocokkah untuk saya → Mengapa → Apa
  tantangannya → Apa yang disiapkan → Bagaimana kondisinya.
- **§90 Explainability**: pengguna paham alasannya tanpa membuka dokumentasi teknis.
- **§91 No false precision**: dilarang persentase dan skor; **boleh** menyebut pengukuran
  nyata (8 jam, 700 m). Label hanya: Cocok / Perlu persiapan / Kurang cocok.
- **§92**: keterangan resmi dan masukan komunitas harus terbedakan secara visual.
- **§96**: anggaran query tidak boleh tumbuh mengikuti jumlah data.
- **§87 / §88**: WCAG 2.2 AA dihitung bukan ditaksir; mobile-first, tidak meluber di 400px.
- **BR-09**: skor internal tidak pernah sampai ke antarmuka dalam bentuk apa pun.
- **§60**: model kepercayaan data adalah nilai inti produk.

---

## 5. Hasil riset tiga rujukan, dan sintesisnya

Riset dilakukan dengan pencarian web, bukan dari ingatan. Sumbernya tercantum di
`docs/superpowers/specs/2026-09-21-mesin-kecocokan-design.md`.

**AllTrails** — tujuan pengguna sebenarnya adalah **memperkirakan ongkos**: berapa waktu,
tenaga, dan sumber daya. Titik sakit terbesar: bolak-balik antar halaman jalur karena tidak
ada cara melihat pilihan berdampingan.

**Traveloka** — corongnya **lihat → simpan → bandingkan → pesan**, dan riset menemukan
masalah di ketiga tahap awal, dengan kalimat yang nyaris identik dengan AllTrails. Keunggulan
Traveloka atas Tiket.com ada pada **kekayaan filternya**, bukan kecantikannya.

**Strava** — segment adalah **perbandingan yang dipersempit** ke kelompok acuan yang cukup
kecil sehingga menang terasa mungkin. 70% pengguna menyebut membandingkan diri sebagai
pendorong utama.

**Sintesis: ketiganya bukan katalog, ketiganya mesin pembanding.** Dan §8 PRD — menghubungkan
pendaki dengan jalur lalu menjelaskannya — **juga sebuah perbandingan**. Tujuan produk ini
dan mekanik ketiga rujukan ternyata satu hal yang sama.

**ATM yang sudah diterapkan:**
- Dari AllTrails: perbandingan dibingkai sebagai ongkos versus kemampuan pembaca.
- Dari Traveloka: tahap "Pertimbangkan" (simpan), yang sebelumnya tidak ada sama sekali.
- Dari Strava: tangga kemajuan — **acuannya diri sendiri, bukan pendaki lain**. Papan
  peringkat kontribusi **ditolak** karena memberi hadiah pada jumlah merusak §60.

---

## 6. SELESAI — sembilan migrasi sudah dijalankan di Supabase

**Status: beres pada 21 September 2026, atas izin eksplisit pemilik produk.**

Kesembilan migrasi dijalankan dengan `php artisan migrate --force` terhadap Supabase.
Hasilnya diverifikasi: `migrate:status` menunjukkan **nol Pending**, seluruhnya `Ran`
pada batch [7]. Tabel `trail_considerations`, `mountain_follows`, dan `hike_track_points`
ada beserta indeks, kunci unik, dan foreign key-nya. Aplikasi dijalankan terhadap skema
baru dan halaman publiknya menjawab 200 tanpa satu pun galat di log.

Sebelum ini, bagian berikut adalah masalah terbesar proyek, dan dicatat sebagai riwayat
karena penyebabnya adalah kelas kesalahan yang mudah berulang:

**Pemilik produk tidak pernah melihat satu pun hasil kerja sesi itu.** Basis datanya
tertinggal sembilan migrasi, sementara seluruh 874 test berjalan di SQLite in-memory yang
migrasinya otomatis lengkap. Jadi test hijau dan aplikasi error pada saat yang sama, di dua
basis data yang berbeda.

Lebih buruk lagi, halaman Jelajahi **sebelumnya berfungsi** dan menjadi error setelah
Tugas 5 menambahkan fitur timbang ke sana, karena fitur itu menanyakan tabel yang belum ada
di produksi. Pekerjaan yang dimaksudkan memperbaiki justru merusak, dan suite tidak dapat
melihatnya.

**Pelajaran untuk sesi berikutnya:** suite hijau tidak membuktikan aplikasi berjalan di
lingkungan pemilik produk. Setiap kali sebuah tugas menambahkan tabel atau kolom, periksa
`migrate:status` terhadap basis data yang sebenarnya dipakai, bukan hanya menjalankan test.

Halaman dan tabel yang dulu terdampak:

| Halaman | Butuh tabel | Status |
|---|---|---|
| Jelajahi | `trail_considerations` | migrasi belum jalan |
| Pertimbangkan | `trail_considerations` | migrasi belum jalan |
| Kabar | `mountain_follows` | migrasi belum jalan |
| Progres | `hike_track_points` | migrasi belum jalan |

Halaman Jelajahi **sebelumnya berfungsi** dan menjadi error setelah Tugas 5 menambahkan
fitur timbang ke sana. Jadi pekerjaan terakhir memperburuk keadaan yang dilihat pemilik
produk, meskipun seluruh 874 test hijau — karena test berjalan di SQLite yang migrasinya
otomatis lengkap.

**Perbaikannya satu perintah, dan harus dijalankan pemilik produk sendiri** karena lapisan
izin Claude Code memblokir akses Supabase dari agen:

```
! php artisan migrate --force
```

Sembilan migrasi yang tertunda, semuanya **aditif** (tabel dan kolom baru, tidak ada drop):

```
2026_09_20_000450_create_usability_sessions_table
2026_09_20_000500_create_scheduled_task_runs_table
2026_09_20_000600_add_trail_snapshot_to_recommendation_results_table
2026_09_20_000700_add_elevation_profile_to_trails_table
2026_09_20_000800_create_hike_track_points_table
2026_09_20_000810_add_track_recording_preference
2026_09_20_000900_create_report_thanks_table
2026_09_20_001000_create_mountain_follows_table
2026_09_21_000100_create_trail_considerations_table
```

**Alternatif tanpa menyentuh produksi**, untuk sekadar melihat hasilnya:

```
! php artisan migrate --force --database=sqlite --env=local
```

atau jalankan dengan basis data SQLite lokal (`database/local.sqlite`, sudah di-gitignore).

---

## 7. Keadaan kode

- Cabang: `hardening`. **Belum pernah di-push.** `origin/main` masih berisi PRD saja.
- Commit di depan `main`: sekitar 149.
- Suite: **874 test, 863 lulus, 11 dilewati, 0 gagal**. Pint bersih. Build sukses.
- 11 yang dilewati butuh `SPATIAL_TEST_DSN` (uji PostGIS).

**Yang sudah dibangun sesi terakhir** (rencana `docs/superpowers/plans/2026-09-21-mesin-kecocokan.md`):

1. `TrailFitService` — menilai kecocokan sekumpulan jalur tanpa query tambahan
2. Halaman jelajah menyebut kecocokan tiap jalur beserta alasannya
3. Penjelasan kecocokan menyebar ke detail jalur dan halaman trip
4. "Pertimbangkan" — timbangan maks 5, lintas perangkat, penolakan dinyatakan
5. Bandingkan dicapai dari jelajah, dibingkai ongkos versus kemampuan
6. Tangga kemajuan di atas tanjakan, acuannya diri sendiri
7. Menu 9 kata benda jadi 5 permukaan: Jelajah, Pertimbangkan, Perjalanan, Progres, Kabar

**Enam cacat produksi yang ditemukan dan ditutup**, tidak satu pun berasal dari rencana:

1. Izin pendakian salah hitung tujuh jam setiap hari (bisa bilang izin masih terbuka
   padahal sudah tutup)
2. Dasbor berkata "Berangkat besok" untuk keberangkatan hari itu juga
3. Kesegaran laporan kondisi terlihat lebih baru daripada kenyataan
4. Kunci mesin `official_status_closed` bocor ke layar pengguna
5. Halaman perbandingan mati total (`DivisionByZeroError`) untuk jalur berjarak nol
6. Timbangan tidak dapat dicapai dari menunya sendiri

---

## 8. Keputusan yang sudah diambil dan tidak perlu dibahas ulang

- **Papan peringkat kontribusi ditolak** — memberi hadiah pada jumlah merusak §60.
- **Tangga kemajuan memakai tanjakan, bukan MDPL** — BR-02/§18.
- **Foto jalur tidak dijadikan wajah kartu** — foto berasal dari laporan komunitas yang
  membawa nama pelapornya; §82 dan §83 melarang menerbitkannya begitu saja.
- **Peta-dulu seperti AllTrails ditunda** — geometri jalur baru masuk lewat impor admin,
  jadi peta-dulu akan memamerkan kekosongan.
- **Mode gelap belum diputuskan** pemilik produk, tercatat terbuka di `DESIGN.md`.
- **Tautan perbandingan yang dibagikan bersifat read-only** — tidak pernah menulis ke
  timbangan siapa pun.
- **Halaman detail menyatakan jenis penilaiannya** ("Kecocokan dasar" / "untuk rencana
  ini") agar tidak ada label yang terbaca berbeda di dua halaman tanpa penjelasan.

Catatan lengkap seluruh 24 ruling beserta biaya bila keliru ada di
`.superpowers/sdd/2026-09-21-mesin-kecocokan/progress.md`.

---

## 9. Yang masih terbuka

**Menghalangi pemilik produk melihat hasil:**
- Sembilan migrasi belum jalan di Supabase (lihat bagian 6)

**Keterbatasan yang diketahui, dinyatakan bukan disembunyikan:**
- Kunci balapan timbangan tidak terbukti suite: `lockForUpdate()` nyata di PostgreSQL,
  no-op di SQLite
- Penjaga zona waktu masih dapat dielakkan lewat variabel perantara
- Ambang A4 spec (kecocokan hadir di ≥10 dari 12 layar) baru tercapai 6 dari 12

**Butuh manusia, alatnya sudah ada di sistem:**
- Uji pakai 5–8 pemula untuk enam UX validation task §113
- SUS ≥ 80 — `UsabilityStudyService` menuntut minimal 12 responden
- Penelusuran pembaca layar
- Scheduler produksi belum berjalan

---

## 10. Umpan balik terakhir pemilik produk, belum terjawab

> "hasilnya tetap sama. error di bagian jelajahi, pertimbangan, kabar ... saya tidak
> merasakan experience berbeda masih jauh dari tujuan dan ekspektasi saya. yang saya lihat
> masih crud sederhana."

Penyebab error sudah terdiagnosis (bagian 6). **Penilaian "masih CRUD sederhana" belum
dapat diuji secara jujur**, karena pemilik produk belum pernah melihat halaman yang
berfungsi.

Langkah berikutnya yang benar, berurutan:

1. Jalankan sembilan migrasi
2. Pemilik produk membuka aplikasinya dan menilai ulang
3. **Baru** setelah itu evaluasi UI/UX besar-besaran, dengan penilaian yang berdasar pada
   apa yang benar-benar terlihat, bukan pada halaman error

Melakukan riset UI/UX besar-besaran sebelum langkah 1 dan 2 berarti merancang untuk
sesuatu yang belum pernah dilihat siapa pun.
