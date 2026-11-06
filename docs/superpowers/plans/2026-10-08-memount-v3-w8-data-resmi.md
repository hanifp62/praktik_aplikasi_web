# Memount v3 W8 Data Resmi Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Status resmi dicatat orang yang berwenang, dengan bukti dan aturan dua orang, lalu dibaca pendaki tanpa
dilonggarkan. Meja kurator (spec v3 bagian 10.7): peran `curator` yang lahir dari keanggotaan badan resmi
(`authority_members`) dan hanya berlaku di kawasan badan itu; formulir catatan dengan sasaran, penerbit, nilai, kategori
alasan, cara berakhir, jenis bukti, dan arsip bukti (PDF, atau foto yang ditulis ulang tanpa EXIF, dengan SHA-256, di
disk privat); aturan terbit asimetris (pembatasan berbukti primer terbit seketika, pembukaan dan pembatasan berbukti
berita menunggu pemeriksa kedua, berita saja tidak pernah membuka); rantai penggantian; pemeriksaan konsistensi (blokir
dan peringatan, dengan pemeriksaan kawasan hanya sebagai peringatan, Q12); jadwal tinjau per jenis status (Q9); antrean
tinjau; dan "Laporkan kesalahan data" dari pendaki yang masuk antrean cek ulang kurator kawasan itu. Di sisi pembaca,
status yang berakhir menjadi Belum diketahui dan tidak pernah otomatis Buka, draf tidak pernah terlihat, dan kalimat
OPEN bukan jaminan tetap ada di setiap layar (R-012). Ditambah status gunung api PVMBG yang dicatat kurator dan tampil
di halaman gunung, rincian jalur, Kabar, dan Cek kesiapan (DK-43, W4-R29); foto gunung berlisensi dengan atribusi di
pojok (DK-37); redesain `/kabar` (W5-C9); dan titipan W3 dan W4: kolom tanpa `geometry` pada kandidat, penjaga km pos,
urutan `COALESCE` status resmi, cache snapshot, R-006 di skema jalur, kurasi `elevation_loss_m`, kunci stabil butir izin
SIMAKSI, kalimat izin ruang kerja trip dari satu penentu, dan kesegaran prakiraan menurut baris tanggal trip.

**Architecture:** Aturan data resmi tinggal di `App\Services\DataResmi`: `Yurisdiksi` (satu-satunya aturan kawasan
kurator), `ArsipBukti`, `TerbitStatus` (satu-satunya jalan tulis status resmi baru, yang memeriksa
`OfficialStatusPolicy` di setiap langkah), `ArahPerubahan` (membuka atau membatasi), `PemeriksaanKonsistensi`, kelas
halaman `MejaKurator` dan `HalamanCatatanStatus`, dan `StatusGunungApi`; foto gunung di `App\Services\Mountains\FotoGunung`.
Controller di `App\Http\Controllers\Curator` tipis: Form Request memvalidasi, layanan memutus. Pembacaan status tetap
lewat `OfficialStatusService` dan `StatusJalurRingkas`, dengan scope `OfficialStatus::terlihatPendaki()` untuk
keterlihatan dan `terbaruDulu()` untuk urutan (`COALESCE(effective_at, created_at)`). Formulir status admin
dipensiunkan; halaman admin status menjadi ringkasan dan antrean baca saja. Skema ditambah sembilan migrasi aditif
`2026_10_09_000100` sampai `000810`, dua di antaranya migrasi data (`000700`, `000810`).

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3.0,
TypeScript 5.9.3, Vite 8.3, Tailwind CSS 3.4.19, Wayfinder 0.1.21, PHPUnit 12.5.35, Pint 1.32, GD dan exif (sudah ada
di PHP), SQLite dalam memori untuk test, PostGIS di CI untuk suite Spatial. Tidak ada dependensi baru.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (bagian 10 seluruhnya, terutama 10.7; 8.7
untuk foto; bagian 4 DK-37 dan DK-43; bagian 7, 9, 12, 13, dan 15 untuk Q9, Q11, Q12, dan Q13), rencana induk
`docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (bagian "W8 Data resmi" butir 1 sampai 9 dan "Selesai bila"),
`HANDOVER.md` bagian 0 subbagian W3 dan W4 (titipan "Ke W8"), dan aturan eksekusi di Lampiran A. Keadaan awalnya keadaan
akhir simulasi rencana W7 (`2026-10-08-memount-v3-w7-progres-profil.md`); W5, W6, dan W7 selesai di repo nyata sebelum
Tugas 1 rencana ini dimulai (Pra-cek).

## Global Constraints

- Versi dikunci: PHP 8.5, Laravel 13.31, Inertia Laravel 3.3.4, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3.4, Vite 8, TypeScript 5.9. Tidak ada perubahan versi kerangka (R-020).
- Tidak ada dependensi baru, runtime maupun dev, dan tidak ada `npx` apa pun. Pemeriksaan tipe memakai `node_modules/.bin/tsc --noEmit` (TypeScript lokal proyek). Pengolahan gambar memakai GD dan exif yang sudah ada (`App\Support\ImageSanitizer`).
- Tidak ada commit, push, akses Supabase, atau migrasi di basis data mana pun. Setiap langkah "Commit" berbunyi: "Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah."
- Skema diisi sembilan migrasi aditif baru, `2026_10_09_000100` sampai `000810` (W8-R2), yang hanya berjalan di dalam PHPUnit (SQLite dalam memori) dan suite Spatial di PostGIS CI. Migrasi lama tidak disunting. `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk `tinker --execute`), dan `memount:demo` tidak dipakai.
- Setiap perintah Artisan di luar PHPUnit, `npm run build`, dan `php -S` diberi awalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` (Git Bash). Di PowerShell: `$env:DB_CONNECTION="sqlite"; $env:DB_DATABASE=":memory:"; $env:DB_URL="(empty)"; <perintah>`, lalu `Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL`. `php artisan test ...` tidak butuh awalan karena `phpunit.xml` memaksa koneksi uji. Jangan membaca atau mencetak `.env`.
- Port `127.0.0.1:8100` dicadangkan untuk harness pratinjau koordinator (Tugas 18). Tugas 1 sampai 17 tidak menyalakan server apa pun. Server `php artisan serve` pemilik di `127.0.0.1:8000` tidak pernah dipakai atau dihentikan. Port 8170 milik penulis rencana.
- Bahasa Indonesia untuk teks antarmuka dan dokumen, sapaan "Anda", tanpa em dash (U+2014). Tidak ada kata "aman", "sehat", "dijamin", "terjamin", atau "pasti" sebagai klaim (R-012, R-014). "§N" hanya untuk ID PRD; dokumen lain dirujuk "bagian N".
- Hierarki otoritas OFFICIAL > ADMIN VERIFIED > COMMUNITY > AI (R-009). Kurator tidak pernah menjadikan laporan komunitas atau keluaran AI sebagai status resmi (R-010, R-011); laporan kesalahan data dari pendaki tidak mengubah status yang tampil. Berita media saja tidak pernah membuka status, dan catatan berbukti berita dikutip sebagai terverifikasi admin, bukan Resmi.
- Status yang berakhir menjadi Belum diketahui, tidak pernah otomatis Buka, dan tidak digantikan catatan yang lebih lama (R-006, R-007, W8-R14). Draf tidak pernah terlihat pendaki; catatan menunggu atau dipersoalkan hanya terlihat bila layanan terbit menandainya boleh terbit lebih dulu (W8-R4). Kalimat "Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan." tetap di setiap layar pembaca status (R-012, `OfficialStatusSafetySentenceTest`).
- Level gunung api PVMBG tidak pernah dibaca status akses, kecocokan, atau rekomendasi, tidak pernah dipetakan ke Buka atau Tutup, selalu beratribusi "PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)" dengan tautan laporannya, dan hanya tampil bila tercatat. Tanpa scraping dan tanpa pengambilan URL dari server (DK-43, W8-R18).
- Peran dan yurisdiksi tidak pernah diambil dari isian: `verified_by`, `authority_id`, `verification_state`, `visible_before_verification`, `superseded_by_id`, kolom arsip, `next_review_at`, `uploaded_by`, `checked_by`, `granted_by`, dan `user_id` selalu dari sesi dan Policy, dan tidak masuk `$fillable` (`OfficialStatusVisibilityTest::test_provenance_controls_are_not_mass_assignable`). `recorded_by`, yang sudah dapat diisi massal sejak sebelum W8, selalu ditulis `TerbitStatus` dari sesi dan tidak pernah ada di aturan Form Request (`CuratorDeskTest::test_a_draft_is_saved_with_who_and_on_whose_behalf_from_the_session`). Setiap aksi meja kurator diperiksa `OfficialStatusPolicy`, `DataErrorReportPolicy`, atau `VolcanoStatusPolicy` di server. Tidak ada akun kurator yang dibuat otomatis, termasuk di `DemoSeeder` (Q11).
- Setiap tulisan meja kurator, keanggotaan, laporan kesalahan, level gunung api, dan foto gunung tercatat di jejak audit dengan nilai sebelum dan sesudah (`AuditLogService`, PRD §61).
- Rute tulis baru untuk kurator, pendaki, dan foto gunung dibatasi laju dengan kunci sendiri: `meja-kurator` (`personal_writes_per_minute` per menit), `laporan-kesalahan` (`data_error_reports_per_hour` per jam), dan `foto-gunung` (`personal_writes_per_minute` per menit). Rute keanggotaan badan (`admin.authorities.members.*`) mengikuti rute admin lain: hanya admin, tanpa kunci laju sendiri. Validasi di Form Request. Unggahan bukti dan foto dibatasi tipe isi, ukuran, dan dimensi; gambar ditulis ulang tanpa EXIF sebelum disimpan; berkas hanya disajikan lewat rute yang memeriksa Policy dan jalur berkasnya (`ArsipBukti::sah()`, `FotoGunung::sah()`).
- Token warna hanya di `resources/css/app.css`; tanpa warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen. Kendali paling sedikit 44 px. Halaman tidak menggulir menyamping di 320 px. Status tidak pernah lewat warna saja. Meja kurator dipakai di desktop tetapi tetap utuh di 320 px dan dapat dipakai lewat papan ketik (WCAG 2.2 AA).
- Nama props bersama (`auth`, `jumlahSimpanan`, `pendakianAktif`, `daftarPengingat`, `demo`, `perangkat`, `flash`, dan lainnya di `HandleInertiaRequests`) tidak dipakai ulang sebagai kunci prop halaman (`ReminderSheetTest`). Prop bersama baru hanya `auth.user.isKurator`.
- Test yang menyandikan keputusan lama diperbarui menurut niat baru dengan alasan tertulis di docblock atau komentarnya; tidak dihapus dan tidak dilonggarkan diam-diam (spec bagian 14).
- Tugas dikerjakan berurutan, satu implementer satu waktu, dan hanya menyunting berkas di daftar Files tugasnya. Berkas bersama (`routes/web.php`, `config/hiking.php`, `config/filesystems.php`, `.env.example`, `app/Models/OfficialStatus.php`, `app/Policies/OfficialStatusPolicy.php`, `app/Services/OfficialStatusService.php`, `app/Services/DataFreshnessService.php`, `app/Services/DataResmi/MejaKurator.php`, `database/README.md`, `database/seeders/DemoSeeder.php`, `resources/js/components/kurator/types.ts`, `resources/js/pages/Curator/Desk.tsx`, `tests/Pratinjau/HalamanPratinjauTest.php`) hanya disunting tugas yang menyebutnya. `HANDOVER.md`, `CLAUDE.md`, dan `docs/PRD.md` tidak disunting tugas implementasi; koordinator memperbarui `HANDOVER.md` di Tugas 18.
- Berkas baru dibuat seperti W2 sampai W7: test dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction <Nama>` (test Spatial dan `tests/Concerns` dibuat langsung), migrasi dengan `make:migration`, model dengan `make:model`, factory dengan `make:factory`, policy dengan `make:policy`, Form Request dengan `make:request`, controller dengan `make:controller`, enum dengan `make:enum`, kelas PHP dengan `make:class` (semuanya `--no-interaction` dan awalan DB), lalu isinya diganti persis dengan kode rencana. Nama berkas migrasi memakai nama di rencana (ganti nama berkas hasil `make:migration`). Berkas TypeScript dibuat langsung. Setelah menyunting PHP jalankan `vendor/bin/pint --format agent <berkas>`; setelah menyunting TypeScript atau rute jalankan `npm run build` (membangkitkan ulang keluaran Wayfinder) lalu `node_modules/.bin/tsc --noEmit`.
- Blok "Ganti ... dengan ..." diterapkan berurutan dengan alat Edit. Teks lamanya unik di berkasnya pada keadaan dasar ditambah tugas-tugas sebelumnya, sudah diperiksa pada simulasi dan putaran ulang, dan yang jangkarnya berasal dari dasar sudah dicocokkan ke repo nyata dengan Grep (Pra-cek butir 1). Bila teks lama tidak ditemukan persis, berhenti dan laporkan NEEDS_CONTEXT; jangan mengarang padanannya. "Tulis ulang ... utuh" berarti isi berkas diganti seluruhnya dengan blok kodenya, dan hanya boleh bila blob berkas di repo nyata sama dengan blob dasar di Pra-cek butir 1a.

## Review Focus

- **Status yang terbaca lebih longgar daripada kenyataan.** Pembukaan tanpa pemeriksa kedua, berita yang membuka,
  draf atau catatan menunggu yang terbaca pendaki, penutupan yang berakhir lalu jalur terbaca Buka karena catatan Buka
  yang lebih lama, dan snapshot bercache yang menyebut status lama sesudah status baru mulai berlaku. Dijaga Tugas 2
  `OfficialStatusVisibilityTest`, Tugas 5 `AsymmetricPublishRuleTest` dan `OfficialStatusSupersedeTest`, Tugas 7
  `DataFreshnessReviewTest::test_an_ended_status_reads_unknown_never_reopens_and_waits_in_the_queue`, dan Tugas 1
  `StatusCacheInvalidationTest::test_a_scheduled_closure_is_visible_the_moment_it_starts`.
- **Kurator di luar kawasannya, atau peran dari klien.** Isian yang menyelundupkan `recorded_by`, `verified_by`,
  `authority_id`, atau `verification_state`; kurator balai A yang mencatat atau memverifikasi untuk gunung balai B;
  pemeriksa kedua yang sama dengan pencatat; admin atau moderator yang dianggap kurator; keanggotaan yang dicabut tetapi
  masih berlaku. Dijaga Tugas 3 `AuthorityMemberPolicyTest`, Tugas 5 `AsymmetricPublishRuleTest`, Tugas 8
  `CuratorDeskTest`, Tugas 9 `DataErrorReportTest`, dan Tugas 10 `VolcanoStatusTest`.
- **Berkas bukti dan foto yang bocor atau berbahaya.** EXIF yang lolos, berkas yang dapat diunduh orang tanpa wewenang,
  jalur berkas yang tidak ditulis layanan, skrip berekstensi `.pdf` atau `.jpg`, arsip yang berubah sesudah dicatat, dan
  URL yang diambil server. Dijaga Tugas 4 `EvidenceArchiveTest` dan Tugas 11 `MountainPhotoLicenseTest`.
- **Data yang hilang terbaca sebagai nilai.** Tuntutan teknis dan kerumitan navigasi tanpa nilai menjadi Sedang,
  "Berkemah: Tidak" tanpa data, turunan yang menyalin tanjakan, level PVMBG yang dipetakan ke Buka atau Tutup, dan
  prakiraan lama yang terbaca Terbaru. Dijaga Tugas 13 `UnknownTrailCharacteristicsTest` dan `TrailDetailPageTest`,
  Tugas 10 `VolcanoStatusTest`, dan Tugas 15 `ReadinessForecastFreshnessTest`.
- **Skema dan kueri di PostgreSQL.** Urutan NULL, CHECK, indeks unik sebagian, kunci asing, `->change()` pada kolom
  berbawaan, dan kueri kandidat yang ikut membaca `geometry`. SQLite tidak membuktikan satu pun; dijaga 32 test Spatial
  baru (Tugas 1, 2, 3, 7, 9, 10, 11, 12, 13, 14) yang berjalan di PostGIS CI (Tugas 18 Step 2).

---

## Bukti simulasi rencana

Kode di rencana ini dijalankan tugas demi tugas di salinan di scratchpad sesi perencanaan (`w8sim`). Salinan itu dibuat
dengan menyalin utuh `w7sim` (keadaan akhir simulasi rencana W7), termasuk `vendor/` dan `node_modules/` (salinan, bukan
tautan), dengan `git init` tanpa add dan tanpa commit, dan konstanta `ASAL` harness pratinjau diarahkan ke port 8170.
Sesudah Tugas 8, `PermitService.php` dan satu baris `PermitRequirement::summary()` diselaraskan dengan repo nyata (W8-R1);
Tugas 15 berjangkar pada versi itu. Repo nyata tidak pernah ditulis dan tidak pernah menjadi direktori kerja perintah;
ia dibaca dengan Read, Grep, dan Glob, ditambah dua pembacaan lewat Bash yang tercatat sebagai pelanggaran pagar di
catatan simulasi penulis. Setiap tugas diuji merah dengan test tugas itu terhadap kode tugas sebelumnya, lalu hijau, lalu suite Unit dan Feature penuh, build, `tsc --noEmit`,
Pint, dan suite Pratinjau. Sesudah dirakit, operasi rencana ini diputar ulang dari salinan dasar yang bersih
(`w8putar2`, dibuat dengan cara yang sama dan sudah memuat penyelarasan W8-R1 sebelum Tugas 1) memakai pengurai yang
hanya membaca blok "Buat", "Tulis ulang", "Ganti", dan "Hapus berkas"; angka di tabel adalah hasil putaran ulang itu.

| Tugas | Merah (filter tugas, sebelum implementasi) | Hijau | Suite Unit dan Feature | Pratinjau | Pohon sesudah tugas |
|---|---|---|---|---|---|
| Dasar (keadaan akhir W7) | | | 2.130 test, 2.128 lulus, 2 dilewati | 36 lulus | dasar putaran ulang |
| 1 Pembacaan status resmi | 14 test: 5 lulus, 5 gagal, 4 dilewati | 10 lulus, 4 dilewati | 2.134 test, 2.132 lulus, 2 dilewati | 36 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan W8-R1 |
| 2 Provenans dan keterlihatan | 35 test: 22 lulus, 7 error, 6 dilewati | 29 lulus, 6 dilewati | 2.141 test, 2.139 lulus, 2 dilewati | 36 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan W8-R1 |
| 3 Kurator dan anggota badan | 46 test: 30 lulus, 11 error, 5 dilewati | 41 lulus, 5 dilewati | 2.152 test, 2.150 lulus, 2 dilewati | 36 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan W8-R1 |
| 4 Arsip bukti | 23 test: 15 lulus, 7 error, 1 dilewati | 22 lulus, 1 dilewati | 2.159 test, 2.157 lulus, 2 dilewati | 36 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan W8-R1 |
| 5 Layanan terbit asimetris | 25 test: 7 lulus, 18 error | 25 lulus | 2.177 test, 2.175 lulus, 2 dilewati | 36 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan W8-R1 |
| 6 Pemeriksaan konsistensi | 24 test: 16 lulus, 8 error | 24 lulus | 2.185 test, 2.183 lulus, 2 dilewati | 36 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan W8-R1 |
| 7 Jadwal tinjau dan status berakhir | 62 test: 53 lulus, 3 gagal, 4 error, 2 dilewati | 60 lulus, 2 dilewati | 2.191 test, 2.189 lulus, 2 dilewati | 36 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan W8-R1 |
| 8 Meja kurator | 28 test: 12 lulus, 3 gagal, 13 error | 28 lulus | 2.200 test, 2.198 lulus, 2 dilewati | 40 lulus | identik dengan simulasi, kecuali dua berkas penyelarasan W8-R1 |
| 9 Laporkan kesalahan data | 30 test: 15 lulus, 12 error, 3 dilewati | 27 lulus, 3 dilewati | 2.212 test, 2.210 lulus, 2 dilewati | 40 lulus | identik dengan simulasi |
| 10 Status gunung api | 28 test: 15 lulus, 1 gagal, 9 error, 3 dilewati | 25 lulus, 3 dilewati | 2.221 test, 2.219 lulus, 2 dilewati | 41 lulus | identik dengan simulasi |
| 11 Foto gunung | 36 test: 26 lulus, 2 gagal, 8 error | 36 lulus | 2.231 test, 2.229 lulus, 2 dilewati | 42 lulus | identik dengan simulasi |
| 12 Kolom kandidat dan km pos | 35 test: 19 lulus, 3 gagal, 6 error, 7 dilewati | 28 lulus, 7 dilewati | 2.240 test, 2.238 lulus, 2 dilewati | 42 lulus | identik dengan simulasi |
| 13 R-006 di skema | 85 test: 76 lulus, 2 gagal, 4 error, 3 dilewati | 82 lulus, 3 dilewati | 2.246 test, 2.244 lulus, 2 dilewati | 42 lulus | identik dengan simulasi |
| 14 Kunci butir izin | 143 test: 133 lulus, 1 gagal, 9 error | 143 lulus | 2.250 test, 2.248 lulus, 2 dilewati | 42 lulus | identik dengan simulasi |
| 15 Izin ruang kerja dan prakiraan | 60 test: 52 lulus, 7 gagal, 1 error | 60 lulus | 2.254 test, 2.252 lulus, 2 dilewati | 42 lulus | identik dengan simulasi |
| 16 Kabar | 38 test: 34 lulus, 4 gagal | 38 lulus | 2.257 test, 2.255 lulus, 2 dilewati | 43 lulus | identik dengan simulasi |
| 17 Dokumen | 37 test: 37 lulus | 37 lulus | 2.257 test, 2.255 lulus, 2 dilewati | tidak dijalankan (dokumen) | identik dengan simulasi |
| Akhir | | | 2.257 test, 2.255 lulus, 2 dilewati; bawaan 2.334 test, 2.255 lulus, 79 dilewati; Spatial 77 test (dilewati tanpa `SPATIAL_TEST_DSN`; dasar 45) | 43 lulus | |

**Putaran ulang.** Putaran penuh di salinan dasar `w8putar2`, 8 Oktober 09.12 sampai 09.59 WIB (dibuat dengan menyalin `w7sim` utuh tanpa tautan, `ASAL` port 8170, dan penyelarasan W8-R1; 2 berkas beda dengan potret dasar, keduanya berkas penyelarasan itu) dengan pengurai yang hanya membaca blok rencana. Pengurai membaca 475 operasi (92 "Buat", 7 "Tulis ulang ... utuh", 375 blok "Ganti", 1 "Hapus berkas"), dan setiap teks lama blok "Ganti" ditemukan tepat sekali saat diterapkan. Pohon sumber sesudah setiap tugas (tanpa `vendor`, `node_modules`, keluaran build, keluaran Wayfinder, dan `public/_pratinjau`) sama berkas demi berkas dengan potret simulasi tugas itu: untuk Tugas 1 sampai 8 hanya dua berkas penyelarasan W8-R1 yang berbeda (potret simulasi Tugas 1 sampai 8 diambil sebelum penyelarasan), dan untuk Tugas 9 sampai 17 tidak ada beda. Putaran pertama (`w8putar`, 08.31 sampai 09.10) juga lulus di setiap tugas dengan angka yang sama; sesudahnya label level gunung api diubah dari titik tengah ke kurung (DESIGN.md bagian Tipografi), potongan dirakit ulang, dan putaran diulang dari salinan baru. Sesudah putaran akhir hanya teks di luar blok kode yang disunting; sidik SHA-256 daftar operasi rencana final sama dengan yang diputar (`ee58a8f9496d0360...`).

**Uji kering terhadap repo nyata.** Hanya membaca, dengan alat Grep, 8 Oktober 2026 pagi (09.10 sampai 09.45 WIB), saat W5 sedang dieksekusi di repo nyata. Rencana ini memuat 375 blok "Ganti". 286 blok teks lamanya berasal utuh dari dasar: 228 di antaranya (107 di berkas yang tidak disentuh rencana W5 sampai W7, dan 121 di berkas yang disentuh tetapi teks lamanya sudah ada sebelum W5) dicocokkan satu per satu dengan Grep multiline yang mengizinkan `\r?\n` di antara baris; 224 ditemukan tepat sekali dan 4 tidak ditemukan, semuanya Tugas 14, karena perbaikan akhir W4 sudah memasang `PreparationService::butirIzin()` di repo nyata (Pra-cek butir 1b; tiga teks lama varian repo nyatanya ditemukan tepat sekali). 58 blok lain berjangkar teks rencana W5 sampai W7 yang belum ada di repo nyata; koordinator mencocokkannya sesudah W7 (daftar per berkas di Pra-cek butir 1). 89 blok berjangkar teks tugas W8 sebelumnya atau penyelarasan W8-R1: 8 blok Tugas 15 yang berjangkar `PermitService` versi repo nyata ditemukan tepat sekali dengan Grep, dan sisanya dibuktikan putaran ulang. Ketujuh berkas yang ditulis ulang utuh dan satu berkas yang dihapus cocok jumlah barisnya dengan dasar (Pra-cek butir 1a). Skrip yang membaca repo nyata tidak dijalankan, karena pagar penulis hanya mengizinkan Read, Grep, dan Glob terhadap repo nyata.

**Pengukuran di peramban** (Chrome lewat MCP chrome-devtools, pratinjau statis salinan di port 8170; tangkapan 390 px
dengan emulasi seluler, sentuh, dan rasio piksel 2, tangkapan 1440 px rasio 1; reflow dan target diukur di iframe
selebar 320, 390, dan 768 px di dalam emulasi seluler 390 px rasio 1, dan kontras di kedua tema):

| Ukuran | Hasil |
|---|---|
| Reflow 320, 390, dan 768 px | `scrollWidth` sama dengan `clientWidth` di `meja-kurator`, `catatan-kurator`, `catatan-kurator-draf`, `formulir-kurator`, `formulir-gunung-api`, `kabar`, `gunung-berfoto`, `gunung`, `detail-jalur`, `cek-kesiapan`, dan `ruang-kerja`: 33 dari 33 |
| Target sentuh 390 px | Semua kendali di `main` kesebelas halaman paling sedikit 44 x 44 px, kecuali tautan remah "Gunung Gede" di rincian jalur (94 x 18 px, W3; tidak disentuh W8) |
| Kontras teks | Terang: terendah 5,08:1 di halaman W8 (meja kurator, formulir, gunung), 4,69:1 untuk chip "Terbaru" W3 di rincian jalur. Gelap: terendah 4,92:1 ("Hapus draf" dan "Hapus rencana", tombol bahaya), halaman W8 lain 6,11:1 ke atas. Atribusi foto di atas foto 15,99:1 (terang) dan 14,13:1 (gelap), dihitung terhadap alas putih dan hitam lalu diambil yang terendah. Tidak ada teks di bawah 4,5:1 |
| Kontras non-teks | Batas putus-putus komunitas dan batas isian formulir terhadap latarnya: 3,30:1 (terang), 4,58:1 sampai 5,29:1 (gelap) |
| Papan ketik | "Tandai sudah dicek" (meja kurator) dan "Laporkan kesalahan data" (rincian jalur): Enter membuka dialog dengan fokus di isian pertama, Escape menutup dan fokus kembali ke pemicunya |

**Cacat yang ditemukan simulasi dan sudah diperbaiki di kode rencana ini** (dicatat supaya peninjau tahu mengapa
bentuknya begini):

- `TrailCandidateColumnsTest` menangkap satu `select * from trails` yang lolos: relasi `results.trail` yang dimuat
  `RecommendationController`. Kini muatan relasi itu juga memakai `tanpaGeometri()`.
- `FotoGunung` semula memakai `firstOrNew(['mountain_id' => ...])`, yang membuang `mountain_id` karena kolom itu tidak
  dapat diisi massal; kini `forceFill`. Berkas baru yang sudah tersimpan juga dihapus bila penyimpanan basis datanya
  gagal (`MountainPhotoLicenseTest::test_a_failed_save_leaves_no_orphan_file`).
- `TrailFactory` menyalin tanjakan ke `elevation_loss_m`, sehingga test rincian jalur memeriksa tiga angka padahal yang
  ketiga karangan. Kini factory mengosongkan turunan, dan `TrailDetailPageTest` diperbarui dengan alasan tertulis
  (W8-R23).
- Tombol "Catat status gunung api" di meja kurator membungkus di 1440 px; kini `shrink-0`.
- Meja kurator (Tugas 8) di 390 px: peran keanggotaan semula dikecilkan sampai sulit dibaca, "dicatat" berhuruf kecil di
  awal baris, "Dicocokkan ke sumber" dan "Pemeriksa kedua" bertumpuk dalam satu baris, tautan sumber 24 px, dan isian
  sasaran serta badan berdampingan sampai terpotong. Kelimanya diperbaiki sebelum tangkapan.
- Migrasi `000600` memakai `->nullable()->default(null)->change()`. Di PostgreSQL bentuk itu menjadi `DROP DEFAULT`
  (diperiksa di `PostgresGrammar`), jadi `TrailCharacteristicsSchemaTest` memeriksa `column_default` kosong, bukan
  hanya `is_nullable`.
- `RepoPolicyFilesTest` menuntut `database/README.md` menyebut setiap tabel baru; baris `data_error_reports`,
  `volcano_statuses`, dan `mountain_photos` ditambahkan di tugasnya.
- Jejak audit memakai kolom `actor_id`, bukan `user_id`; asersi `VolcanoStatusTest` disesuaikan.
- Test jalur berkas foto yang tidak ditulis layanan semula memakai jalur bertitik dua, yang ditolak lapisan Storage
  sebelum kode yang diuji berjalan; kini memakai jalur sah di map lain (`report-photos/laporan-lain.jpg`), sehingga
  yang diuji benar-benar `FotoGunung::sah()`.

**Temuan di luar cakupan W8 (OPEN ISSUE):**

- "Sumber lingkungan" (baris "Lingkungan" di "Apa tantangannya?", W3-R30) tidak dibangun: belum ada data yang dapat
  dikutip (Konflik 9).
- PDF bukti disimpan apa adanya; metadata PDF (penulis, aplikasi) tidak dibuang. Dokumen bukti adalah surat atau laman
  resmi yang memang publik, dan berkasnya hanya diunduh kurator dan admin.
- Pemetaan surat edaran ke Ditutup atau Dibatasi tetap OPEN ISSUE PRD §159; kurator memilih nilainya per surat, dengan
  kutipan resmi dan ringkasan yang ditinjau pemeriksa kedua.
- Tidak ada entitas kawasan terpisah dari gunung (Konflik 3).
- Tautan remah "Gunung Gede" di rincian jalur 94 x 18 px (W3), di bawah target proyek 44 px; W8 tidak menyentuhnya.
- Pratinjau statis tidak dapat mengirim formulir; kirim, verifikasi, persoalkan, tandai sudah dicek, laporkan, dan
  unggah dibuktikan test HTTP (`CuratorDeskTest`, `AsymmetricPublishRuleTest`, `DataErrorReportTest`,
  `VolcanoStatusTest`, `MountainPhotoLicenseTest`) dan diperiksa manual sesudah izin Q1.

## Keadaan awal yang terukur (FAKTA, 8 Oktober 2026)

| Butir | Keadaan | Bukti |
|---|---|---|
| Suite dasar | Unit dan Feature 2.130 test (2.128 lulus, 2 dilewati), sama dengan angka akhir rencana W7; Pratinjau 36 test lulus; Spatial 45 test (dilewati tanpa `SPATIAL_TEST_DSN`); tsc tanpa galat; Pint lulus | `php artisan test --compact --testsuite=Unit,Feature`, `--testsuite=Pratinjau`, dan `--testsuite=Spatial` di `w8sim` dan `w8putar` sebelum Tugas 1 |
| Pencatatan status resmi | Admin mencatat sendiri lewat `Admin/OfficialStatusController` dan `StoreOfficialStatusRequest`: satu orang, tanpa penerbit badan, tanpa arsip bukti, tanpa keadaan verifikasi; kolom spec v3 bagian 10.7 tidak ada | `app/Http/Controllers/Admin/OfficialStatusController.php`, `app/Http/Requests/Admin/StoreOfficialStatusRequest.php`, `database/migrations/*official_statuses*` |
| Peran | `UserRole` hanya `hiker`, `moderator`, `admin`; tidak ada `authority_members` | `app/Enums/UserRole.php` |
| Urutan dan berakhirnya status | Catatan bertanggal berlaku selalu didahulukan (`OfficialStatus::scopeTerbaruDulu()`, `orderByRaw('effective_at is null')`); penutupan yang berakhir mengembalikan catatan Buka yang lebih lama | `app/Models/OfficialStatus.php`, `app/Services/OfficialStatusService.php` |
| Kesegaran dan cache | Satu ambang tinjau `freshness.status_review_days` 90; snapshot jalur `Cache::remember` 300 detik tanpa batas waktu catatan berikutnya; jenis `DataSource` tidak membuang snapshot | `config/hiking.php`, `OfficialStatusService::cachedSnapshotForTrail()` |
| PVMBG dan foto gunung | Tidak ada tabel; dokumen fitur 05 menyebut "Status gunung api PVMBG: Belum ada (DK-43, W8)"; halaman gunung selalu visual data | `docs/fitur/05-kabar-kondisi.md`, `resources/js/pages/Mountains/Show.tsx` |
| Kandidat dan pos | Kandidat Jelajah, rekomendasi, dan simpanan membaca `trails.*` termasuk `geometry`; km pos tanpa penjaga jarak dan urutan | `app/Services/ConsiderationService.php`, `app/Http/Controllers/Recommendations/RecommendationController.php`, `app/Services/Trails/PosSepanjangJalur.php` |
| Karakteristik jalur | `technical_demand` dan `navigation_complexity` berbawaan MODERATE, `camping_available` berbawaan false; seeder dan factory menyalin tanjakan ke `elevation_loss_m` | `database/migrations/2026_09_19_000220_create_trails_table.php` baris 25, 27, 29; `database/seeders/MvpDatasetSeeder.php`; `database/factories/TrailFactory.php` |
| Butir izin dan ruang kerja trip | Butir izin dikenali dari label `PreparationService::PERMIT_LABEL` "Booking izin pendakian (SIMAKSI)"; prop `izin` ruang kerja membawa `tautanPemesanan` mentah | `app/Services/PreparationService.php` baris 24; `app/Http/Controllers/Trips/TripController.php` baris 172 |
| Prakiraan di Cek kesiapan | Waktu ambil dan kesegaran dari konteks 72 jam `WeatherService`, bukan dari baris tanggal trip | `app/Services/Trips/HalamanKesiapan.php` baris 280 sampai 286 |
| Data awal status | `OfficialStatusSeeder`: lima catatan berakhir 31 Oktober 2026, satu 30 September 2026, satu 31 Desember 2026 | `database/seeders/OfficialStatusSeeder.php` |

## Ruling rencana (W8-R1 sampai W8-R28)

Setiap ruling ditulis dengan format keputusan, alasan, dan akibat bila salah.

| ID | Keputusan | Alasan | Akibat bila salah |
|---|---|---|---|
| W8-R1 | Dasar rencana adalah keadaan akhir simulasi W7 (`w7sim`), diselaraskan dengan repo nyata pada satu hal: `PermitService` versi perbaikan akhir W4 M1 (penentu `pemesanan()` dan `pemesananDariJendela()`) dan baris "(belum terverifikasi)" di `PermitRequirement::summary()`, yang sudah ada di repo nyata tetapi tidak ada di dasar. Delapan belas tugas: pembacaan status resmi (1), provenans dan keterlihatan (2), kurator dan anggota badan (3), arsip bukti (4), layanan terbit asimetris (5), pemeriksaan konsistensi (6), jadwal tinjau dan status berakhir (7), meja kurator (8), laporan kesalahan data (9), status gunung api (10), foto gunung (11), titipan geometri dan pos (12), R-006 di skema dan kurasi turunan (13), kunci butir izin (14), kalimat izin ruang kerja dan kesegaran prakiraan (15), Kabar (16), dokumen (17), verifikasi koordinator (18) | Rencana induk W8 butir 1 sampai 9 dan titipan "Ke W8"; satu tugas satu unit tinjauan; urutan mengikuti ketergantungan (layanan terbit butuh skema, Policy, dan arsip; meja butuh layanan; laporan, gunung api, dan foto butuh meja atau yurisdiksi) | Jangkar yang tertabrak ditemukan Pra-cek; implementer berhenti NEEDS_CONTEXT |
| W8-R2 | Sembilan migrasi aditif, berurutan: `2026_10_09_000100_add_provenance_to_official_statuses_table` (kolom spec v3 bagian 10.7, tiga CHECK PostgreSQL termasuk `verified_by <> recorded_by`), `000200_create_authority_members_table` (indeks unik sebagian keanggotaan aktif, dua CHECK, RLS), `000300_create_data_error_reports_table` (dua CHECK, RLS), `000400_create_volcano_statuses_table` (CHECK level 1 sampai 4, RLS), `000500_create_mountain_photos_table` (satu foto per gunung, CHECK lisensi dan SHA-256, RLS), `000600_make_trail_characteristics_nullable`, `000700_clear_copied_elevation_loss_on_trails` (migrasi data), `000800_add_kind_to_trip_preparation_items_table`, `000810_mark_permit_preparation_items` (migrasi data). Migrasi lama tidak disunting | Induk W8 butir 1; R-024 (setiap CHECK, indeks, kunci asing, dan RLS punya test Spatial); migrasi data dipisah dari migrasi skema supaya dapat diperiksa sendiri | Migrasi data mengubah baris produksi; kueri pemeriksaannya di Konflik 1 |
| W8-R3 | Urutan "catatan terbaru" memakai `COALESCE(effective_at, created_at)` menurun, seri diputus `published_at` lalu `id`, dengan urutan NULL eksplisit, di satu scope `OfficialStatus::scopeTerbaruDulu()` | Parkir W3-1i dan I3 tinjauan akhir W3 menyerahkan semantiknya ke W8; catatan tanpa tanggal berlaku yang lebih baru harus dapat menggantikan catatan bertanggal yang lebih lama, dan SQLite serta PostgreSQL harus memilih catatan yang sama | Catatan lama tanpa tanggal berlaku dapat berganti pemenang di data produksi (Konflik 11) |
| W8-R4 | Keterlihatan pendaki: catatan lama tanpa `verification_state` dan catatan `VERIFIED` terlihat; `PENDING` dan `DISPUTED` terlihat hanya bila `visible_before_verification`; `DRAFT` tidak pernah. Satu scope `terlihatPendaki()` dipakai setiap pembaca status pendaki. Enum baru `StatusVerificationState` (DRAFT, PENDING, VERIFIED, DISPUTED), karena `VerificationStatus` yang sudah ada milik sumber data dan tidak punya draf atau menunggu | Spec v3 bagian 10.7; induk W8 butir 1 (enum lama dipakai ulang hanya bila artinya sama) | Draf terlihat pendaki bila satu pembaca lupa memakai scope (dijaga `OfficialStatusVisibilityTest` dan `CuratorDeskTest`) |
| W8-R5 | Gerbang publikasi menghitung catatan yang terlihat (`Trail::statusTerlihat()`), bukan semua catatan, dan menolak penghapusan catatan terlihat terakhir jalur terbit | R-022: draf meja kurator bukan "status resmi pernah dicatat" | Jalur terbit tanpa status yang dibaca pendaki |
| W8-R6 | Jenis bukti: surat resmi, laman resmi, dan akun resmi pengelola adalah bukti primer; berita media sekunder. Berita tidak pernah membuka; pembatasan berbukti berita menunggu pemeriksa kedua dan dikutip sebagai sumber `ADMIN_VERIFIED` ("Pemberitaan atas pengumuman ..."), bukti primer sebagai `OFFICIAL` | Spec v3 bagian 10.7 (aturan terbit asimetris); R-009 | Kutipan sumber berita terbaca Resmi |
| W8-R7 | Peran `curator` di `UserRole`, terpisah dari moderator dan admin, lahir dari keanggotaan aktif di `authority_members` (peran `KURATOR` atau `PENERBIT_MITRA`, surat penunjukan, masa berlaku, pencabutan). Admin menambah dan mencabut keanggotaan di halaman Otoritas; keanggotaan aktif pertama menjadikan pendaki kurator dan pencabutan terakhir mengembalikannya. Yurisdiksi satu aturan (`Yurisdiksi`): gunung di kawasan badan tempat ia anggota aktif, hanya badan yang berwenang menyatakan status | Q11 (bangun sesuai spec v3 bagian 10.7, Konflik 2); induk W8 butir 2 | Pemilik memilih bentuk lain; perubahannya terkurung di `Yurisdiksi`, `AuthorityMemberController`, dan `UserRole` |
| W8-R8 | Matriks `OfficialStatusPolicy`: halaman admin status baca saja untuk admin; meja, catat, ubah draf, kirim, verifikasi, persoalkan, dan tinjau ulang hanya untuk kurator aktif di kawasan sasarannya; unduh bukti untuk kurator itu dan admin; pemeriksa kedua bukan pencatatnya; admin dan moderator bukan kurator | Spec v3 bagian 10.7 (otorisasi); security-and-hardening (tanpa eskalasi peran) | Akun admin atau moderator tidak dapat diberi keanggotaan (`StoreAuthorityMemberRequest::after()`); admin yang ingin mencatat memakai akun pendaki terpisah yang diberi keanggotaan (Konflik 6) |
| W8-R9 | Arsip bukti: PDF (tanda awal `%PDF-`) atau gambar yang terbaca GD, paling besar 10 MB; gambar ditulis ulang tanpa EXIF dengan sisi terpanjang 2.400 px; SHA-256 dari bita yang tersimpan; nama acak di `filesystems.evidence_disk` (`EVIDENCE_DISK`, bawaan disk privat lokal); diunduh hanya lewat `curator.evidence.show` dengan `Cache-Control: private, no-store` dan `nosniff`, sesudah sidiknya cocok. URL yang ditempel disimpan sebagai rujukan; server tidak mengambilnya | Induk W8 butir 6; skill security-and-hardening (SSRF, unggahan); spec meminta "arsip dan hash otomatis" dari URL (Konflik 7) | Kurator harus mengunggah salinan sendiri |
| W8-R10 | Aturan terbit asimetris di satu tempat (`ArahPerubahan`): sebuah catatan membuka bila nilainya OPEN atau lebih longgar daripada status yang terlihat pada tanggal mulainya (urutan OPEN 0, UNKNOWN 1, RESTRICTED 2, CLOSED 3). Kirim: pembatasan berbukti primer terbit seketika bertanda menunggu verifikasi kedua; pembukaan dan pembatasan berbukti berita menunggu pemeriksa kedua; berita yang membuka ditolak | Spec v3 bagian 10.7; induk W8 butir 3 | Pembukaan bertahap (Dibatasi ke Buka) diperlakukan sebagai pembukaan; itu memang maksud spec |
| W8-R11 | Rantai penggantian: saat catatan mulai terlihat, catatan terlihat lain untuk sasaran yang sama yang sudah mulai sebelumnya ditandai `superseded_by_id` dan berakhir pada tanggal mulai catatan baru; audit mencatat keduanya | Spec v3 bagian 10.7 langkah 5 | Riwayat status tetap utuh; tidak ada baris yang dihapus |
| W8-R12 | Pemeriksaan konsistensi: blokir untuk tanggal terbalik, cara berakhir tanpa tanggal, tanggal tanpa cara berakhir, di luar yurisdiksi, tanpa arsip, arsip berubah, dan berita yang membuka; peringatan untuk bukti hanya berita, dua catatan bertentangan, dan jalur Buka di gunung yang ditutup atau dibatasi di tingkat gunung. Blokir ditegakkan `TerbitStatus` saat kirim dan verifikasi; peringatan hanya tampil di meja | Induk W8 butir 4; Q12 (kawasan hanya peringatan, Konflik 3) | Rekomendasi pendaki tidak berubah sampai pemilik memutus Q12 |
| W8-R13 | Jadwal tinjau per jenis status: Buka 24 jam (`field_pack.open_status_stale_after_hours`, sama dengan ambang turun W6), Dibatasi, Ditutup, dan Belum diketahui 168 jam (`freshness.status_review_hours`, sama dengan masa terkini laporan komunitas), level gunung api 168 jam (`freshness.volcano_review_hours`); `next_review_at` tidak pernah melewati tanggal berakhir yang disebut pengelola. Menggantikan `status_review_days` 90 | Q9 (Konflik 4); induk W8 butir 5 | Status Buka terbaca "Perlu ditinjau" sesudah sehari bila tidak dicek ulang |
| W8-R14 | Status yang berakhir: catatan penentu terbaru yang masa berlakunya lewat membuat entitasnya Belum diketahui; catatan yang lebih lama tidak menggantikannya. Berlaku sama di `OfficialStatusService`, `StatusJalurRingkas`, dan snapshot | Spec v3 bagian 10.7 langkah 6; R-006, R-007 | Sebelum W8 penutupan yang berakhir mengembalikan catatan Buka lama (Konflik 11) |
| W8-R15 | Meja kurator tiga halaman: `Curator/Desk` ("Keanggotaan Anda", lalu "Menunggu verifikasi Anda", "Tinjau berkala", "Dipersoalkan", "Draf Anda", "Laporan kesalahan data", dan "Status gunung api (PVMBG)"), `Curator/Status` (rincian catatan, temuan konsistensi, langkah, aksi), dan `Curator/StatusForm` (sasaran dan badan hanya dari daftar kawasan kurator). Kelas halaman `MejaKurator` dan `HalamanCatatanStatus` | Induk W8 butir 9; spec v3 bagian 9.2 (satu kelas halaman per layar) | Tampilan menyimpang dari prototype admin W3, yang memang tidak memuat meja kurator |
| W8-R16 | Formulir status admin dipensiunkan: `StoreOfficialStatusRequest` dihapus, rute tulis admin status dicabut, dan `Admin/OfficialStatuses` menjadi ringkasan dan antrean baca saja yang menunjuk meja kurator | Satu jalan tulis (`TerbitStatus`) supaya aturan dua orang tidak dapat dilewati | Admin tidak lagi dapat mencatat status langsung (Konflik 6) |
| W8-R17 | "Laporkan kesalahan data" di kartu status jalur dan gunung: bagian (status, izin, rute, lainnya) dan catatan 10 sampai 1.000 karakter, paling banyak 5 per jam per pendaki (`laporan-kesalahan`), masuk antrean kurator kawasan itu; "Tandai sudah dicek" dengan catatan hasil; status yang tampil tidak berubah | Spec v3 bagian 10.7 langkah 7; R-010 | Kategori data pribadi baru (Konflik 8) |
| W8-R18 | Status gunung api PVMBG: tabel `volcano_statuses` (level I sampai IV, rekomendasi, tautan laporan, waktu terbit dan verifikasi, pencatat), dicatat kurator untuk gunung di kawasannya, tampil di halaman gunung, rincian jalur, Kabar, dan Cek kesiapan hanya bila ada, terpisah dari status akses | DK-43; spec v3 bagian 10.7; titipan W4-R29 | Tanpa catatan kurator, kartu tidak tampil |
| W8-R19 | Foto gunung berlisensi: satu per gunung, unggahan admin (JPG, PNG, atau WebP, paling besar 8 MB), ditulis ulang tanpa EXIF dengan sisi terpanjang 2.000 px, kolom wajib pembuat, lisensi (CC BY, CC BY-SA, domain publik, milik sendiri), atribusi, dan teks alternatif; atribusi di pojok foto; tanpa foto tampil visual data | DK-37; spec v3 bagian 8.7; R-017 | Disk produksi menunggu keputusan deploy (Konflik 7) |
| W8-R20 | Kandidat Jelajah, rekomendasi, simpanan, dan "Jalur lain untuk Anda" memilih kolom eksplisit tanpa `geometry` (`Trail::KOLOM_TANPA_GEOMETRI`, `scopeTanpaGeometri()`); `Trail::hasGeometry()` mengembalikan `null` (tidak diketahui) bila kolomnya tidak dimuat | Titipan W3 I7, T4-M5, dan artefak hasGeometry | Kolom baru di `trails` harus ditambahkan ke daftar itu (dijaga `TrailCandidateColumnsTest`) |
| W8-R21 | Km pos sepanjang jalur: pos yang lebih jauh dari 150 m dari garisnya (`map.batas_jarak_pos_m`) tidak diberi km; bila km pos berikutnya mundur lebih dari 0,05 km (`PosSepanjangJalur::TOLERANSI_KM`), tidak satu pos pun diberi km, karena urutan pos atau arah garisnya keliru | Titipan W3 T6-M8 dan m11; GPS ponsel di bawah tajuk meleset sekitar 30 m | Pos yang koordinatnya keliru tampil tanpa km, bukan dengan km karangan |
| W8-R22 | R-006 di skema: `technical_demand`, `navigation_complexity`, dan `camping_available` boleh kosong tanpa bawaan; kosong ditulis "Belum tersedia" dan Route Fit membacanya tidak diketahui, bukan Sedang atau tidak ada. Nilai yang sudah ada tidak diubah | Titipan W1 dan W3; butir izin akhir 6 | Kurasi nilai lama keputusan pemilik (Konflik 9) |
| W8-R23 | `elevation_loss_m` yang sama persis dengan tanjakan adalah salinan dan dikosongkan (migrasi data `000700`); seeder dan factory tidak lagi menyalinnya; rincian menampilkan turunan hanya bila tercatat | Titipan W3-R29; R-007 | Turunan dari GPX dan isian kurator yang berbeda tidak tersentuh |
| W8-R24 | Butir izin persiapan dikenali kolom `kind = 'PERMIT'`, bukan labelnya; migrasi data `000810` menandai butir lama berlabel "Booking izin pendakian (SIMAKSI)" dan menggantinya "Pesan izin pendakian (SIMAKSI)" | Titipan W4-C7b | Barang perlengkapan pribadi (W7) yang kebetulan bernama persis label lama ikut ditandai, karena butirnya juga tanpa templat; kueri pemeriksaan di Konflik 1 menghitungnya lebih dulu |
| W8-R25 | Kalimat izin ruang kerja trip dari `PermitService::pemesananDariJendela()` (penentu satu sumber W4 M1) dengan `judul` per keadaan jendela; ajakan memesan hanya saat jendela terbuka, tanpa alamat mentah | Titipan W4 M1 | Teks ruang kerja berubah mengikuti Persiapan dan Cek kesiapan |
| W8-R26 | Kesegaran prakiraan di Cek kesiapan dihitung dari waktu ambil baris tanggal trip yang tampil, bukan konteks 72 jam `WeatherService` | Titipan W4 (tinjauan akhir W4 butir 8); R-013 | Prakiraan lama tidak lagi terbaca Terbaru |
| W8-R27 | Redesain `/kabar`: pintu ke setiap gunung yang diikuti, kelompok menurut asal (status resmi, gunung api, prakiraan, komunitas) dengan garis yang sama seperti Beranda dan halaman gunung, kalimat R-012 di kelompok status | Titipan W5-C9; R-009, R-010 | Susunan berbeda dari W5; dokumen fitur 05 diperbarui (Tugas 16 dan 17) |
| W8-R28 | Snapshot status bercache tetap ber-TTL publik 300 detik, tetapi umurnya tidak pernah melewati saat catatan jalur atau gunungnya berikutnya mulai atau berhenti berlaku; mengubah jenis sebuah `DataSource` membuang snapshot setiap jalur yang statusnya mengutip sumber itu | Titipan W4-6h OOS6 (cache 300 detik lawan pembacaan langsung `BarisStatus`) dan W3-1j; status yang berlaku karena waktunya tiba tidak memicu event model | Satu kueri tambahan saat snapshot dibangun, tidak saat dibaca dari cache |

### Konflik untuk koordinator

1. **Sembilan migrasi W8 (butir izin akhir).** `2026_10_09_000100` sampai `000810` (W8-R2), diuji SQLite dan suite
   Spatial PostgreSQL, tidak dijalankan di Supabase. Urutan pasang: sesudah enam migrasi W7, satu batch, urut nama
   berkas (`000800` butuh kolom `user_owned_item_id` dari migrasi W7 `2026_10_08_000400`); kembalikan dengan urutan
   terbalik. `000700` tidak dapat dibalik (salinan tanjakan tidak dipulihkan); `down()` `000600` mengisi kolom kosong
   dengan Sedang dan "tanpa area kemah" lagi. Kueri pemeriksaan sebelum pasang di Supabase, dicatat hasilnya:
   `SELECT count(*) FROM trails WHERE elevation_loss_m IS NOT NULL AND elevation_loss_m = elevation_gain_m` (baris yang
   akan dikosongkan `000700`), `SELECT count(*) FROM trip_preparation_items WHERE preparation_item_id IS NULL AND label =
   'Booking izin pendakian (SIMAKSI)'` (baris yang akan ditandai `000810`), dan bagian kedua yang sama dengan `AND
   user_owned_item_id IS NOT NULL` (harus 0; bila tidak, barang perlengkapan pribadi pendaki bernama persis label itu
   ikut ditandai, W8-R24). Rekomendasi: masukkan ke daftar permintaan izin akhir bersama migrasi W5 sampai W7.
2. **Q11: peran kurator dan aturan terbit asimetris (keputusan pemilik).** Dibangun persis spec v3 bagian 10.7
   (W8-R7, W8-R8, W8-R10): peran `curator` terpisah dari moderator dan admin, lahir dari keanggotaan aktif badan resmi,
   hanya di kawasan badan itu; peran dan badan tidak pernah dari isian; Policy yurisdiksi di server; tidak ada akun
   kurator yang dibuat otomatis, termasuk di data contoh. Bawaan: tidak ada kurator sampai admin menambah keanggotaan
   pertama, dan pemberian itu menunggu pemilik (butir izin akhir operasional). Jalan mundur: bila pemilik memilih
   bentuk lain (misalnya admin sebagai kurator, atau satu kurator nasional tanpa kawasan), perubahannya terkurung di
   `Yurisdiksi`, `UserRole`, dan `StoreAuthorityMemberRequest::after()`; test `AuthorityMemberPolicyTest` menyandikan
   bentuk sekarang. Rekomendasi: terima, lalu pemilik menunjuk kurator pertama untuk balai gunung data awal.
3. **Q12: kebijakan tingkat kawasan terhadap rekomendasi (keputusan pemilik).** Pemeriksaan "jalur Buka di gunung yang
   ditutup atau dibatasi di tingkat gunung" hanya peringatan di meja kurator (W8-R12); status jalur, label kecocokan,
   dan rekomendasi pendaki tidak diubah. Kaskade PRD §42 yang sudah ada (pembatasan gunung turun ke jalur) tidak
   tersentuh. Tidak ada entitas "kawasan" terpisah dari gunung; kebijakan pusat yang menutup banyak kawasan sekaligus
   dicatat per gunung. Bila pemilik memutus kawasan menurunkan rekomendasi: satu aturan di `PenilaianKecocokan` dan
   `RouteFitService::peringatan()` membaca temuan `kawasan_tertutup`, ditambah test. Rekomendasi: biarkan sebagai
   peringatan sampai pemilik memutus.
4. **Q9: jadwal tinjau per jenis status (keputusan pemilik).** Buka 24 jam (sama dengan ambang turun status Buka W6,
   `field_pack.open_status_stale_after_hours`), Dibatasi, Ditutup, dan Belum diketahui 168 jam, level gunung api 168
   jam (W8-R13); menggantikan `status_review_days` 90. Dasarnya: Buka paling berisiko terbaca sebagai jaminan (R-012),
   jadi ambangnya sama di layar daring dan paket luring; tujuh hari sama dengan masa "terkini" laporan komunitas; PVMBG
   menerbitkan laporan aktivitas harian. Akibatnya: status Buka yang tidak dicek ulang sehari terbaca "Perlu ditinjau"
   (nilainya tetap Buka) dan masuk antrean "Tinjau berkala"; beban kurator naik. Jalan mundur: nilai di
   `config/hiking.php`, tanpa migrasi. Rekomendasi: terima; bila beban terlalu berat, naikkan Buka ke 72 jam bersama
   ambang W6.
5. **Q13: tindakan keluar pemilik.** Tidak dibangun dan tidak menahan W8.
6. **Pencatatan hanya lewat meja kurator, dan kurasi status mendesak sebelum 31 Oktober 2026 (butir izin akhir 14).**
   Formulir status admin dipensiunkan (W8-R16), jadi sesudah W8 tidak ada yang dapat mencatat status baru sebelum
   pemilik memberi keanggotaan kurator pertama. `OfficialStatusSeeder` mencatat status September 2026 yang berakhir 31
   Oktober 2026 (satu sudah berakhir 30 September); sesudah tanggal itu jalur-jalurnya terbaca Belum diketahui dengan
   peringatan "Status resmi jalur ini belum diketahui." (W8-R14, benar menurut R-006). Rencana tidak mengarang status
   baru. Rekomendasi: sebelum 31 Oktober pemilik menunjuk kurator untuk balai gunung data awal, dan kurator mencatat
   status terkini dari surat atau laman resmi lewat meja, dengan pemeriksa kedua. Bila tidak sempat, biarkan Belum
   diketahui; jangan memperpanjang tanggal di seeder tanpa sumber.
7. **Arsip bukti tanpa pengambilan URL, dan disk produksi bukti serta foto.** Spec v3 bagian 10.7 menyebut "tempel URL
   atau unggah dokumen, arsip dan hash otomatis". Rencana tidak mengambil URL dari server (SSRF, skill
   security-and-hardening): URL disimpan sebagai rujukan dan kurator mengunggah salinannya (W8-R9). Kedua penangan
   unggahan baru (bukti dan foto gunung) termasuk "Ask First" skill itu: dasarnya ada di spec dan induk W8 butir 6 dan 8,
   tetapi pemilik belum melihat kodenya. Disk bawaannya disk privat lokal (`EVIDENCE_DISK`, `MOUNTAIN_PHOTOS_DISK`); di
   Vercel disk lokal tidak bertahan, jadi produksi butuh disk persisten (S3 atau Supabase Storage). Rekomendasi: pemilik
   mengonfirmasi bersama permintaan izin akhir; disk produksi keputusan deploy.
8. **Dokumen canonical yang tidak disunting rencana.** Dua kategori data pribadi baru: laporan kesalahan data (id
   pendaki dan catatan bebas, terhapus bersama akun) dan keanggotaan kurator (surat penunjukan, masa berlaku).
   `docs/KEBIJAKAN-DATA-PRIBADI.md` adalah pemilik kebijakan itu (`CLAUDE.md` bagian 4), jadi rencana tidak menyuntingnya.
   Baris status program di `docs/PRD.md` juga basi sesudah W8, dan PRD butuh governance record. Rekomendasi: koordinator
   memperbarui keduanya bersama GR berikutnya, dengan persetujuan pemilik untuk kebijakan data pribadi.
9. **Kurasi data jalur (butir izin akhir 6).** Kolom `technical_demand`, `navigation_complexity`, dan
   `camping_available` kini boleh kosong (W8-R22), tetapi nilai lama tidak diubah: migrasi tidak dapat membedakan
   kurasi dari bawaan, jadi jalur lama tetap terbaca Sedang, Sedang, dan "tanpa area kemah" sampai dikurasi.
   `elevation_loss_m` salinan dikosongkan (W8-R23). "Sumber lingkungan" (baris "Lingkungan" di "Apa tantangannya?",
   W3-R30) tidak dibangun: belum ada sumber data suhu atau angin per jalur yang dapat dikutip (R-007). Rekomendasi:
   pemilik atau kurator data jalur meninjau nilai lama per jalur dengan sumbernya.
10. **Kunci butir izin dan label baru (W8-R24).** Migrasi data `000810` mengganti label butir izin di trip yang sudah
    ada menjadi "Pesan izin pendakian (SIMAKSI)". Rekomendasi: terima bersama migrasi.
11. **Semantik pembacaan untuk data lama (W8-R3, W8-R14).** Catatan lama tanpa tanggal berlaku kini dihitung dari
    waktu dicatatnya, dan penutupan yang berakhir tidak lagi mengembalikan catatan Buka yang lebih lama. Keduanya dapat
    mengubah status yang tampil untuk data produksi yang sudah ada. Rekomendasi: terima; kueri
    `SELECT count(*) FROM official_statuses WHERE effective_at IS NULL` sebelum deploy menunjukkan berapa catatan yang
    terdampak aturan pertama.

### Pra-cek untuk koordinator

Rencana ditulis di atas keadaan akhir simulasi W7. Periksa ulang di repo nyata sesudah W7 selesai, sebelum Tugas 1.
Penulis rencana membaca repo nyata dengan Read, Grep, dan Glob; dua pembacaan lewat Bash (`ls` folder ledger dan `wc -l`
berkas aturan skill) tercatat jujur di catatan simulasi, bagian Pagar. Repo nyata tidak pernah ditulis.

| Asumsi | Tempat rencana bergantung | Keadaan |
|---|---|---|
| 1. Jangkar setiap blok "Ganti" ada tepat sekali | Semua tugas | Sesuai di dasar dan pada putaran ulang. Di repo nyata (Grep multiline, 8 Oktober pagi, saat W5 dieksekusi): 224 dari 228 blok berjangkar dasar yang dapat diperiksa sekarang ditemukan tepat sekali; 4 blok Tugas 14 tidak (butir 1b); 8 blok Tugas 15 yang berjangkar `PermitService` repo nyata ditemukan tepat sekali. 58 blok berjangkar teks rencana W5 sampai W7 dicocokkan koordinator dengan Grep sesudah W7: `tests/Feature/OfficialStatusOrderTest.php` (8, Tugas 1), `tests/Spatial/OfficialStatusNullOrderTest.php` (5, Tugas 1), `app/Models/OfficialStatus.php` (3, Tugas 1 dan 5), `app/Services/OfficialStatusService.php` (2, Tugas 2), `.env.example`, `config/hiking.php`, dan `resources/js/lib/bukanHalamanReact.json` (masing-masing 1, Tugas 4), `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/components/akun/PintuAkun.tsx`, `resources/js/layouts/MenuAkun.tsx`, dan `tests/Feature/OfficialStatusSafetySentenceTest.php` (masing-masing 1, Tugas 8), `tests/Pratinjau/HalamanPratinjauTest.php` (6, Tugas 8, 9, 11), `resources/js/components/trails/gunung/KartuStatusGunung.tsx`, `resources/js/pages/Mountains/Show.tsx`, dan `routes/web.php` (masing-masing 1, Tugas 9), `app/Services/Trails/TrailDetailPage.php` (3, Tugas 7 dan 10), `app/Services/Trips/HalamanKesiapan.php` (5, Tugas 10, 14, 15), `resources/js/pages/Trips/Readiness.tsx` (1, Tugas 10), `app/Services/Trails/PenilaianKecocokan.php` (1, Tugas 12), `app/Services/Trails/RouteComparisonTable.php` (1, Tugas 13), `app/Services/PreparationService.php` (2), `tests/Feature/DashboardDeadlineTest.php` (2), `tests/Feature/DashboardPhaseTest.php` (1), dan `tests/Feature/PermitWindowReminderTest.php` (2) untuk Tugas 14, `tests/Feature/TrustGrammarTest.php` (1, Tugas 7), `docs/fitur/05-kabar-kondisi.md` (2, Tugas 16), dan `DESIGN.md`, `docs/ARCHITECTURE.md`, serta `docs/fitur/02-detail-rute.md` (masing-masing 1, Tugas 17). Setiap jangkar adalah konteks terpendek yang unik pada keadaan sebelum blok itu. |
| 1a. Berkas yang ditulis ulang utuh atau dihapus belum berubah sejak dasar | Tugas 7 (`DataFreshnessService.php`, `OfficialStatusService.php`), Tugas 8 (`Admin/OfficialStatusController.php`, `DataFreshnessService.php` lagi, `Admin/OfficialStatuses.tsx`, `OfficialStatusTargetTest.php`, `StoreOfficialStatusRequest.php` dihapus), Tugas 16 (`Community/TrailNews.tsx`) | Blob dasar (12 karakter pertama `git hash-object`): `app/Services/DataFreshnessService.php` 0f953335db1b (140 baris), `app/Services/OfficialStatusService.php` 842000c23f7f (367), `app/Http/Controllers/Admin/OfficialStatusController.php` 81296afa9ad7 (119), `resources/js/pages/Admin/OfficialStatuses.tsx` 93a7c4caa520 (247), `app/Http/Requests/Admin/StoreOfficialStatusRequest.php` 08136a236730 (67), `tests/Feature/OfficialStatusTargetTest.php` bb4b30c5bc4d (71), `resources/js/pages/Community/TrailNews.tsx` 39b6d761b789 (210). Jumlah baris ketujuh berkas di repo nyata sama dengan dasar (Grep, 8 Oktober). `DataFreshnessService.php`, `OfficialStatusService.php`, dan `TrailNews.tsx` disentuh rencana W5 sampai W7, jadi blobnya dibandingkan lagi sesudah W7. Bila berbeda, perubahan itu digabung manual; jangan menimpa. Tugas 1 dan 2 menyunting `OfficialStatusService.php` sebelum Tugas 7 menulisnya ulang; blok Tugas 1 dan 2 sudah termuat di tulisan ulang itu |
| 1b. Perbaikan akhir W4 di `PreparationService`, `HalamanKesiapan`, dan `HalamanPersiapan` | Tugas 14 | Repo nyata sudah memuat `PreparationService::butirIzin()` berbasis label (baris 112) dan memakainya di `HalamanKesiapan.php` (baris 189 dan 195) dan `HalamanPersiapan.php` (baris 93 dan 119), sedangkan dasar simulasi masih memakai pengecekan label langsung; `addPermitItem()` repo nyata juga memuat komentar dan `$description = $requirement->summary();` sebelum `$existing`. Karena itu empat blok Tugas 14 tidak ditemukan di repo nyata (blok 4 `PreparationService.php`, blok 1 `HalamanKesiapan.php`, blok 1 dan 2 `HalamanPersiapan.php`; blok 2 `HalamanKesiapan.php` termasuk 58 blok W5 sampai W7). Tugas 14 Step 3 memuat "Varian repo nyata" (tiga perubahan `PreparationService.php`, masing-masing teks lamanya ditemukan tepat sekali dengan Grep) dan daftar tiga test perbaikan akhir W4 yang membuat butir izin tanpa `kind` (`PreparationUndoTest`, `TripStepGuidanceTest`, `ReadinessDashboardTest`) |
| 2. W6 dan W7 selesai di repo nyata | Tugas 7 (`field_pack.open_status_stale_after_hours`, W6), Tugas 14 (kolom `user_owned_item_id` dan `IsiAwalPerlengkapan`, W7), Tugas 13 dan 15 (berkas yang disentuh W5 sampai W7) | Di repo nyata 8 Oktober kunci `open_status_stale_after_hours` belum ada (`config/hiking.php` masih `status_review_days` 90 di baris 132), sesuai keadaan sebelum W6. Periksa ulang sesudah W7 |
| 3. `PermitService` versi perbaikan akhir W4 M1 | Tugas 15 | Repo nyata: `pemesanan()` baris 237, `pemesananDariJendela()` baris 251, `BOOKING_BUKA_BELUM_TERCATAT` baris 110 (Grep, 8 Oktober). Simulasi memakai salinan persis berkas itu (W8-R1) |
| 4. `SchedulerHealthService::TUGAS` memuat `data:freshness-check`; `routes/console.php` menjadwalkannya harian | Tugas 7 (induk W8 butir 5) | Ada di dasar dan di repo nyata (Grep). Status berakhir dan jadwal tinjau dihitung saat dibaca, jadi tidak ada tugas terjadwal baru |
| 5. `AuthorityType::mayDeclareTrailStatus()` dan `ImageSanitizer::sanitize(string, int)` | Tugas 3, 4, 11 | Repo nyata: `app/Enums/AuthorityType.php` baris 34, `app/Support/ImageSanitizer.php` baris 24 (Grep) |
| 6. `OfficialStatusSeeder` berakhir paling lambat 31 Oktober 2026 | Konflik 6 | Repo nyata: lima catatan `expires_at` 2026-10-31, satu 2026-09-30, satu 2026-12-31 (Grep) |
| 7. `self::ASAL` di harness pratinjau | Tugas 8 sampai 11 dan 16 | Rencana memakai `self::ASAL` apa adanya |
| 8. GD dan exif terpasang di PHP | Tugas 4 dan 11 | `php -m` di simulasi: `gd`, `exif`, `fileinfo` ada |
| 9. Titipan "Ke W8" | Seluruh rencana | Peta di bawah. Tidak ada butir yang sudah ditangani rencana W5 sampai W7 |

Peta titipan "Ke W8" (`HANDOVER.md` bagian 0 subbagian W3 dan W4, brief koordinator):

| Titipan | Tugas | Ruling | Test |
|---|---|---|---|
| Kolom eksplisit tanpa `geometry` pada kandidat Jelajah, rekomendasi, simpanan, dan "Jalur lain untuk Anda" | 12 | W8-R20 | `TrailCandidateColumnsTest` (Feature dan Spatial), `RecommendationGeometryExclusionTest` |
| Penjaga kemonotonan dan jarak pos dari garis | 12 | W8-R21 | `PosSepanjangJalurGuardTest`, `PosSepanjangJalurTest` (Spatial) |
| Semantik COALESCE urutan status resmi dan urutan NULL yang sama di SQLite dan PostgreSQL | 1 | W8-R3 | `OfficialStatusOrderTest`, `OfficialStatusNullOrderTest` (Spatial) |
| Pembuangan snapshot bercache saat jenis `DataSource` berubah | 1 | W8-R28 | `StatusCacheInvalidationTest::test_changing_a_source_type_is_visible_before_the_cache_expires` |
| Cache status 300 detik lawan pembacaan langsung `BarisStatus` | 1 | W8-R28 | `StatusCacheInvalidationTest::test_a_scheduled_closure_is_visible_the_moment_it_starts`, `test_an_expiring_record_leaves_the_cache_when_it_ends` |
| Kurasi `elevation_loss_m` dan sumber lingkungan | 13 | W8-R23 | `TrailDetailPageTest`, `TrailCharacteristicsSchemaTest`; sumber lingkungan tidak dibangun (Konflik 9) |
| Skema R-006 dan "Berkemah: Tidak" di `/b/` (izin akhir 6) | 13 | W8-R22 | `UnknownTrailCharacteristicsTest`, `TrailCharacteristicsSchemaTest` |
| Artefak `Trail::hasGeometry()` di SQLite | 12 | W8-R20 | `TrailCandidateColumnsTest::test_candidates_carry_the_flag_but_not_the_line` (Spatial) |
| Kunci stabil butir izin, migrasi label lama, label "Pesan izin pendakian (SIMAKSI)" (W4-C7b) | 14 | W8-R24 | `PermitItemKeyTest`, `TripPreparationKindSchemaTest` |
| Kalimat izin ruang kerja trip dari penentu satu sumber `PermitService` (W4 M1) | 15 | W8-R25 | `TripPermitWindowTest` |
| Kesegaran prakiraan menurut baris tanggal trip, bukan konteks 72 jam | 15 | W8-R26 | `ReadinessForecastFreshnessTest` |
| Kartu gunung api di Cek kesiapan (W4-R29, tabel DK-43 butir 7) | 10 | W8-R18 | `VolcanoStatusTest` |
| Redesain `/kabar` dan perbaikan `docs/fitur/05-kabar-kondisi.md` (W5-C9) | 16, 17 | W8-R27 | `KabarPageTest`, `TrailNewsTest`, `PrdReferenceIntegrityTest` |
| Kurasi status resmi mendesak sebelum 31 Oktober 2026 (izin akhir 14) | 18 (laporan) | W8-R14 | Konflik 6 |

## Perbandingan skill (DK-57)

Urutan wewenang sama dengan W4 sampai W7: arahan pemilik dan aturan kanonis, lalu `DESIGN.md` (arah, dial ENERGY 2 /
RHYTHM 2 / MOTION 2 untuk layar aplikasi) dan prototype (patokan; prototype tidak menggambar meja kurator, jadi meja
memakai kosakata komponen konsol W3), lalu antislop, lalu saran skill. antislop berlaku mode 1 (selama pekerjaan) atas
arahan pemilik yang sudah berdiri; tidak ada pertanyaan baru kepada pemilik. Launcher impeccable tidak dijalankan: ia
butuh direktori kerja repo nyata dan dapat mengunduh biner, keduanya di luar pagar; konteksnya dibaca langsung dari
`DESIGN.md`, `PRODUCT.md`, dan `reference/operate.md`. Skrip ui-ux-pro-max dijalankan dari salinan skill di dalam
salinan simulasi (`.agents/skills/ui-ux-pro-max`, direktori kerja `w8sim`).

| Skill | Diambil | Ditolak dan alasannya |
|---|---|---|
| antislop (inti) | R-26: setiap kendali bekerja dan dibuktikan test HTTP (simpan draf, "Ubah draf", "Hapus draf", "Kirim untuk terbit", "Verifikasi dan terbitkan", "Persoalkan", "Sudah dicek ulang ke sumber", "Catat pengganti", unduh bukti, catat status gunung api, laporkan kesalahan data, tandai laporan sudah dicek, pasang, ganti, dan hapus foto gunung, tambah dan cabut anggota badan). R-27: keadaan kosong yang menyebut langkahnya ("Belum ada draf.", kelompok Kabar yang kosong), memproses (`processing` di setiap tombol kirim), dan galat di bawah isian yang bersangkutan, termasuk temuan konsistensi. R-17 dan R-38: data contoh berpenanda DATA CONTOH, tanpa level PVMBG, status, atau atribusi karangan; akun kurator hanya ada di harness pratinjau. R-25, R-32, R-34: kontras, papan ketik, dan kedua tema diperiksa di peramban | Tidak ada |
| ui-ux-pro-max | Lima pencarian `--domain ux`. "file upload evidence attachment form": label di atas isian dan umpan balik kirim (memproses lalu berhasil atau galat). "admin dashboard data table list": tabel melebar di ponsel, jadi antrean meja ditulis sebagai daftar berkelompok, bukan tabel. "notifications feed status updates": kabar tulisan lewat satu wilayah `role="status"` yang bermakna. "image attribution credit caption": foto dikecilkan sebelum disajikan dan berskala dengan wadahnya. "review queue approval two person verification": tidak ada kecocokan di basis data skill, jadi aturan dua orang mengikuti spec v3 bagian 10.7 saja | Aksi massal (centang banyak lalu verifikasi): aturan dua orang menuntut setiap catatan diperiksa sendiri. Toast yang hilang sendiri dalam 3 sampai 5 detik: konvensi repo adalah wilayah status yang menetap, dan pesan yang hilang sendiri merugikan pembaca lambat (WCAG 2.2.1). Putar otomatis media: tidak ada media |
| design-taste-frontend | Skill ini menyatakan dirinya bukan untuk panel admin, tabel data, atau formulir bertahap (bagian 13), jadi hanya bagian umumnya dipakai: pembacaan desain satu baris (0.B), keadaan interaktif lengkap (4.5), label di atas isian dan galat di bawahnya (4.6), tanpa label kecil berhuruf kapital renggang di atas judul, tanpa titik status hiasan, dan tanpa titik tengah di UI W8 (9.F, diperiksa dengan pencarian), tanpa em dash (9.G) | Dial bawaan 8/6/4, pustaka Motion, gambar picsum, aturan hero, dan "daftar panjang butuh komponen lain": antrean meja memang daftar kerja, dipisah per kelompok dengan judul dan jumlahnya |
| impeccable (Operate; `reference/operate.md`) | Kosakata komponen yang sudah ada dipakai ulang (`PageHeader`, `Button`, `Alert`, `Lencana`, `Tautan`, `EmptyState`, `Dialog`, `SelectField`, `TextareaField`, `StatusAkses`); keadaan semantik ditulis dengan kata, bukan warna saja (menunggu verifikasi, terverifikasi, dipersoalkan, draf); tanpa gerak baru; kepadatan meja diizinkan karena pemakainya bekerja di desktop. "Modal sebagai pikiran pertama" diperiksa: formulir catatan status adalah halaman penuh, dan dialog hanya untuk keputusan singkat berisi satu catatan (persoalkan, tandai sudah dicek, hapus draf, laporkan kesalahan data), seperti `FormulirCatatan` W7 | Launcher dan detektor hook tidak dijalankan (di luar pagar) |
| security-and-hardening | Model ancaman per batas, dengan STRIDE. Pemalsuan peran: peran dan badan selalu dibaca dari basis data untuk pengguna sesi (`Yurisdiksi`), kolom pelaku tidak dapat diisi massal, pendaki tidak dapat mengangkat dirinya. Perubahan berkas: SHA-256 arsip diperiksa sebelum diunduh dan sebelum terbit. Penyangkalan: audit sebelum-sesudah untuk setiap langkah, pemberian, dan pencabutan. Kebocoran: bukti hanya untuk kurator kawasan dan admin, `Cache-Control: private, no-store`, `nosniff`; identitas pelapor tidak tampil di meja. Beban: batas ukuran, dimensi, dan laju per kunci. Eskalasi: admin dan moderator tidak dapat diberi keanggotaan. SSRF: server tidak pernah mengambil URL bukti. Operasi destruktif pada jalur turunan: hanya jalur yang lolos `sah()` di map milik layanan yang disajikan atau dihapus | "Ask First" untuk penangan unggahan baru dan pemberian peran tidak dapat ditanyakan sebelum W9 (arahan pemilik); dibawa sebagai Konflik 2 dan 7 |
| laravel-best-practices | Form Request untuk setiap tulisan; Policy di setiap aksi; kolom pelaku lewat `forceFill`; migrasi aditif dengan `down()` yang jujur (`000700` tidak dapat dibalik dan menyebutnya); indeks mengikuti kueri antrean (`official_statuses_antrean`, `data_error_reports_antrean`, `volcano_statuses_terbaru`); tulisan dan auditnya dalam satu transaksi; anggaran kueri meja (`CuratorDeskTest::test_the_desk_does_not_grow_with_the_number_of_records`). Cache snapshot memakai `Cache::get` dengan pemeriksaan `is_array` lalu `Cache::put`, bukan `Cache::remember`, karena umur cache bergantung pada data dan dihitung hanya saat cache kosong; snapshot selalu larik, jadi nilai palsu tidak salah terbaca kosong | Antarmuka repository untuk data resmi: satu implementasi, lapisan dangkal |
| testing-best-practices | Test perilaku lewat HTTP untuk setiap rute baru: tamu, peran yang salah, kawasan lain, isian yang menyelundupkan kolom pelaku, kalimat galat, dan batas laju; matriks wewenang di `AuthorityMemberPolicyTest`; waktu dibekukan bila tanggal menentukan hasil; `Storage::fake` untuk unggahan; test lama yang menyandikan keputusan lama diperbarui dengan alasan di docblock | 404 untuk kawasan lain: konvensi proyek 403 didahulukan sampai Q5 diputus |
| superpowers:writing-plans | Kepala rencana, Global Constraints, Review Focus lima mode gagal, blok Files dan Interfaces per tugas, langkah test lebih dulu, tanpa placeholder, tinjauan diri | Langkah commit diganti "Lewati" (keputusan pemilik) |

**Pembacaan desain (antislop bagian 3):** meja kurator adalah alat kerja Operate untuk kurator data resmi di desktop,
yang tetap utuh di ponsel; Kabar, kartu gunung api, foto gunung, dan "Laporkan kesalahan data" adalah layar pendaki di
ponsel. Dial ENERGY 2 / RHYTHM 2 / MOTION 2 (`DESIGN.md`), tanpa gerak baru. Meja menjawab "catatan mana yang menunggu
saya, dan apa buktinya"; Kabar menjawab "apa kata pengelola, PVMBG, prakiraan, dan pendaki lain tentang gunung yang saya
ikuti, dan dari siapa". Motif identitas tetap tata bahasa kepercayaan: resmi bergaris penuh, komunitas bergaris
putus-putus, dan level gunung api sebagai keluarga status sendiri yang tidak pernah berbunyi Buka atau Tutup.

**Inventaris skill untuk gelombang ini (CLAUDE.md bagian 7):**

- INTI (CORE): superpowers:writing-plans, testing-best-practices, laravel-best-practices, security-and-hardening,
  antislop, ui-ux-pro-max, design-taste-frontend (terbatas), impeccable.
- PENDUKUNG (SUPPORTING): antislop-copywriting, antislop-human, antislop-layoutmobile, frontend-ui-engineering,
  vercel-react-best-practices, vercel-composition-patterns, web-design-guidelines: tidak dimuat di sesi ini; butirnya
  diwakili antislop inti, pola W7 yang dipakai ulang, dan pengukuran di peramban.
- LINTAS (CROSS-CUTTING): superpowers:verification-before-completion dan Delivery Gate antislop; chrome-devtools MCP untuk
  pengukuran dan tangkapan.
- N/A:
  - dataviz: W8 tidak menambah grafik;
  - shadcn: rujukan pola saja di W4 sampai W7; tidak ada komponen baru yang butuh pola di luar kosakata repo;
  - tailwindcss-development: tidak ada token, varian, atau konfigurasi Tailwind baru;
  - archify, slides, banner-design, brand, design, design-system, ui-styling: bukan artefak halaman, dan aset logo
    menunggu gerbang DK-16;
  - animate dan skill gerak lain: W8 tidak menambah gerak;
  - livewire-development dan volt-development: Livewire sudah dipensiunkan;
  - deploying-to-cloud dan deploy-to-vercel: tanpa rilis;
  - ngodingpakeai, caveman, dan skill alur commit: tanpa commit (keputusan pemilik).

## File Structure

**Dibuat:**

- Tugas 2: `app/Enums/EvidenceType.php`, `app/Enums/StatusEndCondition.php`, `app/Enums/StatusReasonCategory.php`, `app/Enums/StatusVerificationState.php`, `database/migrations/2026_10_09_000100_add_provenance_to_official_statuses_table.php`, `tests/Feature/OfficialStatusVisibilityTest.php`, `tests/Spatial/OfficialStatusProvenanceSchemaTest.php`
- Tugas 3: `app/Enums/AuthorityMemberRole.php`, `app/Http/Controllers/Admin/AuthorityMemberController.php`, `app/Http/Requests/Admin/StoreAuthorityMemberRequest.php`, `app/Models/AuthorityMember.php`, `app/Services/DataResmi/Yurisdiksi.php`, `database/factories/AuthorityFactory.php`, `database/factories/AuthorityMemberFactory.php`, `database/migrations/2026_10_09_000200_create_authority_members_table.php`, `tests/Feature/AuthorityMemberPolicyTest.php`, `tests/Spatial/AuthorityMemberSchemaTest.php`
- Tugas 4: `app/Http/Controllers/Curator/EvidenceController.php`, `app/Services/DataResmi/ArsipBukti.php`, `tests/Feature/EvidenceArchiveTest.php`
- Tugas 5: `app/Services/DataResmi/TerbitStatus.php`, `tests/Concerns/MejaKurator.php`, `tests/Feature/AsymmetricPublishRuleTest.php`, `tests/Feature/OfficialStatusSupersedeTest.php`
- Tugas 6: `app/Services/DataResmi/ArahPerubahan.php`, `app/Services/DataResmi/PemeriksaanKonsistensi.php`, `tests/Feature/OfficialStatusConsistencyTest.php`
- Tugas 7: `tests/Spatial/OfficialStatusReviewScheduleTest.php`
- Tugas 8: `app/Http/Controllers/Curator/CuratorDeskController.php`, `app/Http/Controllers/Curator/CuratorStatusController.php`, `app/Http/Requests/Curator/PersoalkanStatusRequest.php`, `app/Http/Requests/Curator/SimpanCatatanStatusRequest.php`, `app/Services/DataResmi/HalamanCatatanStatus.php`, `app/Services/DataResmi/MejaKurator.php`, `resources/js/components/kurator/DaftarAntrean.tsx`, `resources/js/components/kurator/types.ts`, `resources/js/pages/Curator/Desk.tsx`, `resources/js/pages/Curator/Status.tsx`, `resources/js/pages/Curator/StatusForm.tsx`, `tests/Feature/CuratorDeskTest.php`
- Tugas 9: `app/Enums/DataErrorCategory.php`, `app/Enums/DataErrorState.php`, `app/Http/Controllers/Community/DataErrorReportController.php`, `app/Http/Controllers/Curator/DataErrorCheckController.php`, `app/Http/Requests/Community/StoreDataErrorReportRequest.php`, `app/Http/Requests/Curator/CekLaporanKesalahanRequest.php`, `app/Models/DataErrorReport.php`, `app/Policies/DataErrorReportPolicy.php`, `database/factories/DataErrorReportFactory.php`, `database/migrations/2026_10_09_000300_create_data_error_reports_table.php`, `resources/js/components/kurator/DaftarLaporan.tsx`, `resources/js/components/trails/LaporkanKesalahan.tsx`, `tests/Feature/DataErrorReportTest.php`, `tests/Spatial/DataErrorReportSchemaTest.php`
- Tugas 10: `app/Enums/VolcanoLevel.php`, `app/Http/Controllers/Curator/VolcanoStatusController.php`, `app/Http/Requests/Curator/SimpanStatusGunungApiRequest.php`, `app/Models/VolcanoStatus.php`, `app/Policies/VolcanoStatusPolicy.php`, `app/Services/DataResmi/StatusGunungApi.php`, `database/factories/VolcanoStatusFactory.php`, `database/migrations/2026_10_09_000400_create_volcano_statuses_table.php`, `resources/js/components/kurator/DaftarGunungApi.tsx`, `resources/js/components/trails/KartuGunungApi.tsx`, `resources/js/pages/Curator/VolcanoForm.tsx`, `tests/Feature/VolcanoStatusTest.php`, `tests/Spatial/VolcanoStatusSchemaTest.php`
- Tugas 11: `app/Enums/PhotoLicense.php`, `app/Http/Controllers/Admin/MountainPhotoManagerController.php`, `app/Http/Controllers/Trails/MountainPhotoController.php`, `app/Http/Requests/Admin/SimpanFotoGunungRequest.php`, `app/Models/MountainPhoto.php`, `app/Services/Mountains/FotoGunung.php`, `database/factories/MountainPhotoFactory.php`, `database/migrations/2026_10_09_000500_create_mountain_photos_table.php`, `resources/js/components/trails/gunung/FotoGunung.tsx`, `resources/js/pages/Admin/MountainPhoto.tsx`, `tests/Feature/MountainPhotoLicenseTest.php`, `tests/Spatial/MountainPhotoSchemaTest.php`
- Tugas 12: `tests/Feature/TrailCandidateColumnsTest.php`, `tests/Spatial/TrailCandidateColumnsTest.php`, `tests/Unit/PosSepanjangJalurGuardTest.php`
- Tugas 13: `database/migrations/2026_10_09_000600_make_trail_characteristics_nullable.php`, `database/migrations/2026_10_09_000700_clear_copied_elevation_loss_on_trails.php`, `tests/Feature/UnknownTrailCharacteristicsTest.php`, `tests/Spatial/TrailCharacteristicsSchemaTest.php`
- Tugas 14: `database/migrations/2026_10_09_000800_add_kind_to_trip_preparation_items_table.php`, `database/migrations/2026_10_09_000810_mark_permit_preparation_items.php`, `tests/Feature/PermitItemKeyTest.php`, `tests/Spatial/TripPreparationKindSchemaTest.php`
- Tugas 15: `tests/Feature/ReadinessForecastFreshnessTest.php`
- Tugas 16: `tests/Feature/KabarPageTest.php`

**Dihapus:** `app/Http/Requests/Admin/StoreOfficialStatusRequest.php` (Tugas 8).

**Diubah:**

- Tugas 1: `app/Models/DataSource.php`, `app/Models/OfficialStatus.php`, `app/Services/OfficialStatusService.php`, `tests/Feature/OfficialStatusOrderTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Spatial/OfficialStatusNullOrderTest.php`
- Tugas 2: `app/Models/OfficialStatus.php`, `app/Models/Trail.php`, `app/Services/Admin/TrailDataHealth.php`, `app/Services/DataFreshnessService.php`, `app/Services/OfficialStatusService.php`, `app/Services/TrailNewsService.php`, `app/Services/Trails/StatusJalurRingkas.php`
- Tugas 3: `app/Enums/UserRole.php`, `app/Http/Controllers/Admin/AuthorityController.php`, `app/Models/Authority.php`, `app/Models/User.php`, `app/Policies/OfficialStatusPolicy.php`, `database/README.md`, `resources/js/pages/Admin/Authorities.tsx`, `routes/web.php`
- Tugas 4: `.env.example`, `app/Policies/OfficialStatusPolicy.php`, `app/Support/PenjagaDataContoh.php`, `config/filesystems.php`, `config/hiking.php`, `resources/js/lib/bukanHalamanReact.json`, `routes/web.php`
- Tugas 5: `app/Models/OfficialStatus.php`, `app/Policies/OfficialStatusPolicy.php`, `app/Services/DataFreshnessService.php`, `tests/Feature/AuditLogChangeTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`
- Tugas 6: `app/Services/DataResmi/TerbitStatus.php`
- Tugas 7: `app/Models/OfficialStatus.php`, `app/Policies/OfficialStatusPolicy.php`, `app/Services/DataFreshnessService.php` (ditulis ulang utuh), `app/Services/DataResmi/TerbitStatus.php`, `app/Services/OfficialStatusService.php` (ditulis ulang utuh), `app/Services/Trails/StatusJalurRingkas.php`, `app/Services/Trails/TrailDetailPage.php`, `config/hiking.php`, `tests/Feature/DataFreshnessReviewTest.php`, `tests/Feature/OfficialStatusSeederTest.php`, `tests/Feature/TrustGrammarTest.php`, `tests/Unit/DataFreshnessServiceTest.php`
- Tugas 8: `app/Http/Controllers/Admin/OfficialStatusController.php` (ditulis ulang utuh), `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Resources/Admin/OfficialStatusRowResource.php`, `app/Policies/OfficialStatusPolicy.php`, `app/Services/DataFreshnessService.php` (ditulis ulang utuh), `resources/js/components/akun/PintuAkun.tsx`, `resources/js/layouts/MenuAkun.tsx`, `resources/js/pages/Admin/OfficialStatuses.tsx` (ditulis ulang utuh), `resources/js/types/index.d.ts`, `routes/web.php`, `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/OfficialStatusPageTest.php`, `tests/Feature/OfficialStatusSafetySentenceTest.php`, `tests/Feature/OfficialStatusTargetTest.php` (ditulis ulang utuh), `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`
- Tugas 9: `app/Services/DataResmi/MejaKurator.php`, `app/Services/Mountains/HalamanGunung.php`, `app/Services/Trails/TrailDetailPage.php`, `config/hiking.php`, `database/README.md`, `resources/js/components/kurator/types.ts`, `resources/js/components/trails/gunung/KartuStatusGunung.tsx`, `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/pages/Curator/Desk.tsx`, `resources/js/pages/Mountains/Show.tsx`, `resources/js/pages/Trails/Show.tsx`, `routes/web.php`, `tests/Pratinjau/HalamanPratinjauTest.php`
- Tugas 10: `app/Services/DataResmi/MejaKurator.php`, `app/Services/Mountains/HalamanGunung.php`, `app/Services/Trails/TrailDetailPage.php`, `app/Services/Trips/HalamanKesiapan.php`, `config/hiking.php`, `database/README.md`, `database/seeders/DemoSeeder.php`, `resources/js/components/kurator/types.ts`, `resources/js/components/trails/types.ts`, `resources/js/pages/Curator/Desk.tsx`, `resources/js/pages/Mountains/Show.tsx`, `resources/js/pages/Trails/Show.tsx`, `resources/js/pages/Trips/Readiness.tsx`, `routes/web.php`, `tests/Feature/DemoSeederTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`
- Tugas 11: `.env.example`, `app/Http/Resources/Admin/MountainRowResource.php`, `app/Models/Mountain.php`, `app/Services/Mountains/HalamanGunung.php`, `app/Support/PenjagaDataContoh.php`, `config/filesystems.php`, `config/hiking.php`, `database/README.md`, `resources/js/components/trails/gunung/types.ts`, `resources/js/lib/bukanHalamanReact.json`, `resources/js/pages/Admin/Mountains.tsx`, `resources/js/pages/Mountains/Show.tsx`, `routes/web.php`, `tests/Pratinjau/HalamanPratinjauTest.php`
- Tugas 12: `app/Http/Controllers/Recommendations/RecommendationController.php`, `app/Models/Trail.php`, `app/Services/Admin/DataHealthSummary.php`, `app/Services/ConsiderationService.php`, `app/Services/RouteFitService.php`, `app/Services/Trails/PenilaianKecocokan.php`, `app/Services/Trails/PosSepanjangJalur.php`, `config/hiking.php`, `tests/Feature/FitAssessmentPageTest.php`, `tests/Spatial/PosSepanjangJalurTest.php`, `tests/Spatial/RecommendationGeometryExclusionTest.php`
- Tugas 13: `app/Http/Controllers/Admin/TrailController.php`, `app/Http/Requests/Admin/TrailRequest.php`, `app/Http/Resources/Admin/TrailAdminRowResource.php`, `app/Models/PreparationItem.php`, `app/Models/RecommendationResult.php`, `app/Services/RouteFit/CompatibilityScorer.php`, `app/Services/Trails/PenilaianKecocokan.php`, `app/Services/Trails/RouteComparisonTable.php`, `app/Services/Trails/TrailDetailPage.php`, `database/factories/TrailFactory.php`, `database/seeders/DemoSeeder.php`, `database/seeders/MvpDatasetSeeder.php`, `resources/js/components/admin/FormulirJalur.tsx`, `resources/views/public/trail.blade.php`, `tests/Feature/TrailDetailPageTest.php`
- Tugas 14: `app/Models/TripPreparationItem.php`, `app/Services/PreparationService.php`, `app/Services/Trips/HalamanKesiapan.php`, `app/Services/Trips/HalamanPersiapan.php`, `tests/Feature/DashboardDeadlineTest.php`, `tests/Feature/DashboardPhaseTest.php`, `tests/Feature/PermitVisibilityTest.php`, `tests/Feature/PermitWindowReminderTest.php`
- Tugas 15: `app/Http/Controllers/Trips/TripController.php`, `app/Services/PermitService.php`, `app/Services/Trips/HalamanKesiapan.php`, `resources/js/pages/Trips/Show.tsx`, `tests/Feature/TripPermitWindowTest.php`
- Tugas 16: `app/Services/Community/KabarJalur.php`, `docs/fitur/05-kabar-kondisi.md`, `resources/js/components/trails/KartuGunungApi.tsx`, `resources/js/pages/Community/TrailNews.tsx` (ditulis ulang utuh), `tests/Feature/TrailNewsTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`
- Tugas 17: `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur/02-detail-rute.md`, `docs/fitur/05-kabar-kondisi.md`, `docs/fitur/08-konsol-pengelola.md`

Tugas 18 (koordinator) membuat `docs/desain/tangkapan/w8/*.webp` dan `docs/desain/tangkapan/w8/README.md`, dan
memperbarui `HANDOVER.md` bagian 0 serta `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

---

### Task 1: Pembacaan status resmi: urutan, umur cache, dan jenis sumber

Titipan W3 (parkir W3-1i dan I3 tinjauan akhir W3; W3-1j) dan W4 (W4-6h OOS6); ruling W8-R3 dan W8-R28; R-024. Tiga
perbaikan pembaca sebelum ada penulis baru. Urutan "catatan terbaru" di satu scope `OfficialStatus::scopeTerbaruDulu()`
kini `COALESCE(effective_at, created_at)` menurun dengan urutan NULL eksplisit, sehingga catatan tanpa tanggal berlaku
yang lebih baru dapat menggantikan catatan bertanggal yang lebih lama, dan SQLite serta PostgreSQL memilih catatan yang
sama. Snapshot status jalur bercache tetap ber-TTL publik, tetapi umurnya tidak melewati saat catatan jalur atau
gunungnya berikutnya mulai atau berhenti berlaku. Mengubah jenis sebuah `DataSource` membuang snapshot setiap jalur yang
statusnya mengutip sumber itu.

**Files:**
- Modify: `app/Models/DataSource.php`, `app/Models/OfficialStatus.php`, `app/Services/OfficialStatusService.php`
- Test: `tests/Feature/OfficialStatusOrderTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Spatial/OfficialStatusNullOrderTest.php`

**Interfaces:**
- Consumes: `OfficialStatus::scopeTerbaruDulu()` dan event model `OfficialStatus` (W3); `OfficialStatusService::snapshotCacheKey(int)`, `forgetCachedSnapshot(int)`, `snapshotForTrail(Trail)` (W3); `config('hiking.cache.public_ttl_seconds')` (300).
- Produces:
  - `OfficialStatus::scopeTerbaruDulu(Builder): Builder` dengan semantik `COALESCE(effective_at, created_at)` menurun, lalu `published_at`, lalu `id`;
  - `OfficialStatus::forgetAffectedTrailCaches(): void` (kini publik; kaskade jalur, gunung, dan segmen);
  - `OfficialStatusService::forgetSnapshotsForSource(int $dataSourceId): void`, dipanggil hook `DataSource::booted()` saat `source_type` berubah;
  - `OfficialStatusService::cachedSnapshotForTrail(Trail): array` dengan umur cache `min(TTL, detik sampai batas berikutnya + 1)`.

- [ ] **Step 1: Tulis test yang gagal.** `OfficialStatusOrderTest` dan kembarannya di PostgreSQL diperbarui menurut
niat baru (docblock menyebut alasannya), dan `StatusCacheInvalidationTest` mendapat tiga test baru: penutupan
terjadwal terlihat begitu mulai, catatan yang berakhir keluar dari cache saat berakhir, dan perubahan jenis sumber
terlihat sebelum cache habis.

`tests/Feature/OfficialStatusOrderTest.php`:

1. Ganti:

```php
/**
 * Urutan "catatan status resmi terbaru" (I3 dan m6 tinjauan akhir W3; parkir W3-1i, R-024).
 *
 * `effective_at` boleh kosong (StoreOfficialStatusRequest), dan `ORDER BY effective_at DESC`
 * menaruh NULL terakhir di SQLite tetapi pertama di PostgreSQL. Tanpa urutan eksplisit,
 * catatan Buka tanpa tanggal berlaku mengalahkan penutupan bertanggal di Supabase sementara
 * suite ini tetap memilih penutupan. Semua pembaca status memakai satu scope,
 * `OfficialStatus::scopeTerbaruDulu()`, yang menulis urutan NULL-nya sendiri; semantik akhir
 * (COALESCE dengan created_at) tetap keputusan W8 dan cukup diubah di scope itu.
 *
 * Test ini menjaga perilaku di SQLite dan bentuk SQL-nya; kembarannya di PostgreSQL ada di
 * tests/Spatial/OfficialStatusNullOrderTest.
 */
```

dengan:

```php
/**
 * Urutan "catatan status resmi terbaru" (I3 dan m6 tinjauan akhir W3; parkir W3-1i, diputus W8,
 * ruling W8-R3; R-024).
 *
 * Sampai W8 catatan bertanggal berlaku selalu didahulukan, sehingga catatan tanpa tanggal
 * berlaku tidak pernah dapat menggantikan catatan bertanggal, berapa pun barunya. W8 memutus
 * semantiknya: waktu sebuah catatan adalah tanggal berlakunya, atau waktu dicatatnya bila
 * tanggal berlaku tidak disebut (`COALESCE(effective_at, created_at)`), dan yang terbaru
 * didahulukan. Niat lama tetap dijaga: catatan Buka tanpa tanggal yang lebih lama tidak
 * pernah menutupi penutupan bertanggal (dua test pertama), dan urutan NULL ditulis eksplisit
 * sehingga SQLite dan PostgreSQL memilih catatan yang sama. Meja kurator W8 mewajibkan tanggal
 * berlaku, jadi catatan tanpa tanggal hanya datang dari data lama.
 *
 * Kembarannya di PostgreSQL ada di tests/Spatial/OfficialStatusNullOrderTest.
 */
```

2. Ganti:

```php
    }

    public function test_an_undated_open_record_never_outranks_a_dated_closure(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subHour()]);
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['effective_at' => null, 'published_at' => null]);

        $layanan = app(OfficialStatusService::class);
```

dengan:

```php
    }

    public function test_an_older_undated_open_record_never_outranks_a_dated_closure(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['effective_at' => null, 'published_at' => null, 'created_at' => now()->subDays(3)]);
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subHour()]);

        $layanan = app(OfficialStatusService::class);
```

3. Ganti:

```php
    /**
     * Seri pada effective_at diputus published_at, juga dengan NULL ditaruh terakhir.
     */
```

dengan:

```php
    /**
     * Catatan tanpa tanggal berlaku dihitung dari waktu dicatatnya: yang dicatat sesudah
     * penutupan mulai berlaku adalah keterangan yang lebih baru.
     */
    public function test_an_undated_record_counts_from_when_it_was_recorded(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subDays(2)]);
        $this->catatan($jalur, OfficialStatusValue::RESTRICTED, ['effective_at' => null, 'published_at' => null, 'created_at' => now()->subHour()]);

        $layanan = app(OfficialStatusService::class);

        $this->assertSame(OfficialStatusValue::RESTRICTED, $layanan->currentFor($jalur)?->status);
        $this->assertSame('RESTRICTED', app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->whereKey($jalur->id)->get())[$jalur->id]['status']);
    }

    /**
     * Seri pada waktu berlaku diputus published_at, juga dengan NULL ditaruh terakhir.
     */
```

4. Ganti:

```php
    }

    public function test_a_dated_segment_restriction_is_not_hidden_by_an_undated_open_record(): void
    {
```

dengan:

```php
    }

    public function test_a_dated_segment_restriction_is_not_hidden_by_an_older_undated_open_record(): void
    {
```

5. Ganti:

```php
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        $this->catatan($segmen, OfficialStatusValue::RESTRICTED, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => now()->subHour()]);
        $this->catatan($segmen, OfficialStatusValue::OPEN, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => null, 'published_at' => null]);

        $layanan = app(OfficialStatusService::class);
```

dengan:

```php
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        $this->catatan($segmen, OfficialStatusValue::OPEN, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => null, 'published_at' => null, 'created_at' => now()->subDays(3)]);
        $this->catatan($segmen, OfficialStatusValue::RESTRICTED, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => now()->subHour()]);

        $layanan = app(OfficialStatusService::class);
```

6. Ganti:

```php
    /**
     * Urutan NULL ditulis eksplisit di SQL, satu tempat. Pembaca status yang mengurut
     * effective_at sendiri akan berselisih lagi dengan yang lain di PostgreSQL.
     */
```

dengan:

```php
    /**
     * Urutan ditulis eksplisit di SQL, satu tempat, termasuk urutan NULL-nya. Pembaca status
     * yang mengurut waktu berlaku sendiri akan berselisih lagi dengan yang lain di PostgreSQL.
     */
```

7. Ganti:

```php
        $sql = strtolower(OfficialStatus::query()->terbaruDulu()->toSql());

        $this->assertStringContainsString('effective_at is null', $sql);
        $this->assertStringContainsString('published_at is null', $sql);
        $this->assertLessThan(strpos($sql, 'published_at is null'), strpos($sql, 'effective_at is null'));

        $pelanggar = [];
```

dengan:

```php
        $sql = strtolower(OfficialStatus::query()->terbaruDulu()->toSql());

        $this->assertStringContainsString('coalesce(effective_at, created_at) is null', $sql);
        $this->assertStringContainsString('coalesce(effective_at, created_at) desc', $sql);
        $this->assertStringContainsString('published_at is null', $sql);
        $this->assertLessThan(strpos($sql, 'published_at is null'), strpos($sql, 'coalesce(effective_at, created_at) desc'));

        $pelanggar = [];
```

8. Ganti:

```php
            }

            if (preg_match("/orderBy(?:Desc)?\\('effective_at'|latest\\('effective_at'/", File::get($berkas->getRealPath()))) {
                $pelanggar[] = $berkas->getRelativePathname();
```

dengan:

```php
            }

            if (preg_match("/orderBy(?:Desc)?\\('effective_at'|latest\\('effective_at'|coalesce\\(effective_at/i", File::get($berkas->getRealPath()))) {
                $pelanggar[] = $berkas->getRelativePathname();
```

`tests/Feature/StatusCacheInvalidationTest.php`:

1. Ganti:

```php
use App\Enums\OfficialStatusValue;
use App\Enums\UserRole;
use App\Models\Mountain;
use App\Models\Trail;
```

dengan:

```php
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\UserRole;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
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
     * Status yang mulai berlaku karena waktunya tiba tidak memicu event model apa pun, jadi
     * observer di atas tidak menolongnya (titipan W4-6h OOS6). Snapshot tidak boleh disimpan
     * melewati batas waktu catatan berikutnya: "Sejak cek terakhir" di Cek kesiapan membaca
     * snapshot ini, sedangkan kartu status membaca langsung, dan keduanya harus sepakat.
     */
    public function test_a_scheduled_closure_is_visible_the_moment_it_starts(): void
    {
        $trail = Trail::factory()->easy()->create();
        OfficialStatus::factory()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'status' => OfficialStatusValue::OPEN->value,
            'effective_at' => now()->subDay(),
        ]);
        OfficialStatus::factory()->closed()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'effective_at' => now()->addMinutes(2),
        ]);

        $this->assertSame(OfficialStatusValue::OPEN->value, $this->statusTercache($trail));

        $this->travel(3)->minutes();

        $this->assertSame(OfficialStatusValue::CLOSED->value, $this->statusTercache($trail->fresh()));
        $this->assertSame(OfficialStatusValue::CLOSED, app(OfficialStatusService::class)->effectiveStatusForTrail($trail->fresh()));
    }

    public function test_an_expiring_record_leaves_the_cache_when_it_ends(): void
    {
        $trail = Trail::factory()->easy()->create();
        OfficialStatus::factory()->closed()->create([
            'statusable_type' => $trail->getMorphClass(),
            'statusable_id' => $trail->id,
            'effective_at' => now()->subDay(),
            'expires_at' => now()->addMinute(),
        ]);

        $this->assertSame(OfficialStatusValue::CLOSED->value, $this->statusTercache($trail));

        $this->travel(2)->minutes();

        $this->assertSame(OfficialStatusValue::UNKNOWN->value, $this->statusTercache($trail->fresh()), 'Penutupan yang berakhir menjadi Belum diketahui, tidak pernah Buka.');
    }

    /**
     * Snapshot membawa jenis sumber (Resmi, Komunitas, ...), dan jenis itu milik baris
     * data_sources, bukan baris status (titipan W3-1j). Mengubah jenis sumber harus terlihat
     * seketika di setiap jalur yang statusnya mengutip sumber itu.
     */
    public function test_changing_a_source_type_is_visible_before_the_cache_expires(): void
    {
        $sumber = DataSource::factory()->create(['source_type' => SourceType::OFFICIAL->value]);
        $mountain = Mountain::factory()->create();
        $trail = Trail::factory()->easy()->for($mountain)->create();
        OfficialStatus::factory()->create([
            'statusable_type' => $mountain->getMorphClass(),
            'statusable_id' => $mountain->id,
            'scope' => 'MOUNTAIN',
            'status' => OfficialStatusValue::CLOSED->value,
            'data_source_id' => $sumber->id,
        ]);

        $this->assertSame(SourceType::OFFICIAL->value, app(OfficialStatusService::class)->cachedSnapshotForTrail($trail)['source_type']);

        $this->actingAs($this->admin())
            ->put(route('admin.sources.update', $sumber), [
                'source_name' => $sumber->source_name,
                'source_type' => SourceType::COMMUNITY->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(SourceType::COMMUNITY->value, app(OfficialStatusService::class)->cachedSnapshotForTrail($trail->fresh())['source_type']);
    }
}
```

`tests/Spatial/OfficialStatusNullOrderTest.php`:

1. Ganti:

```php
/**
 * I3 tinjauan akhir W3 (R-024; parkir W3-1i keliru sebagian): paritas PostgreSQL untuk urutan
 * NULL pada `OfficialStatus::scopeTerbaruDulu()`. PostgreSQL menaruh NULL pertama pada
 * `ORDER BY effective_at DESC`, jadi tanpa urutan eksplisit catatan Buka tanpa tanggal
 * berlaku mengalahkan penutupan bertanggal, sementara suite SQLite memilih penutupan
 * (tests/Feature/OfficialStatusOrderTest). Skenario di sini sama dengan kembarannya di SQLite.
 *
```

dengan:

```php
/**
 * Paritas PostgreSQL untuk urutan `OfficialStatus::scopeTerbaruDulu()` (I3 tinjauan akhir W3;
 * semantik diputus W8, ruling W8-R3; R-024). Waktu sebuah catatan adalah
 * `COALESCE(effective_at, created_at)`, yang terbaru didahulukan, dan urutan NULL ditulis
 * eksplisit: PostgreSQL menaruh NULL pertama pada `ORDER BY ... DESC`, SQLite terakhir.
 * Skenario di sini sama dengan kembarannya di SQLite (tests/Feature/OfficialStatusOrderTest).
 *
```

2. Ganti:

```php
    }

    public function test_an_undated_open_record_never_outranks_a_dated_closure(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subHour()]);
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['effective_at' => null, 'published_at' => null]);

        $layanan = app(OfficialStatusService::class);
```

dengan:

```php
    }

    public function test_an_older_undated_open_record_never_outranks_a_dated_closure(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['effective_at' => null, 'published_at' => null, 'created_at' => now()->subDays(3)]);
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subHour()]);

        $layanan = app(OfficialStatusService::class);
```

3. Ganti:

```php
        $this->assertSame('CLOSED', app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
    }
```

dengan:

```php
        $this->assertSame('CLOSED', app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
    }

    public function test_an_undated_record_counts_from_when_it_was_recorded(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subDays(2)]);
        $this->catatan($jalur, OfficialStatusValue::RESTRICTED, ['effective_at' => null, 'published_at' => null, 'created_at' => now()->subHour()]);

        $this->assertSame(OfficialStatusValue::RESTRICTED, app(OfficialStatusService::class)->currentFor($jalur)?->status);
    }
```

4. Ganti:

```php
    }

    public function test_a_dated_segment_restriction_is_not_hidden_by_an_undated_open_record(): void
    {
```

dengan:

```php
    }

    public function test_a_dated_segment_restriction_is_not_hidden_by_an_older_undated_open_record(): void
    {
```

5. Ganti:

```php
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        $this->catatan($segmen, OfficialStatusValue::RESTRICTED, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => now()->subHour()]);
        $this->catatan($segmen, OfficialStatusValue::OPEN, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => null, 'published_at' => null]);

        $layanan = app(OfficialStatusService::class);
```

dengan:

```php
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        $this->catatan($segmen, OfficialStatusValue::OPEN, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => null, 'published_at' => null, 'created_at' => now()->subDays(3)]);
        $this->catatan($segmen, OfficialStatusValue::RESTRICTED, ['scope' => StatusScope::SEGMENT->value, 'effective_at' => now()->subHour()]);

        $layanan = app(OfficialStatusService::class);
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='OfficialStatusOrder|OfficialStatusNullOrder|StatusCacheInvalidation'`
Expected: FAIL. Pada simulasi rencana: 14 test, 5 lulus, 5 gagal, 4 dilewati. Yang merah: `OfficialStatusOrderTest::test_an_undated_record_counts_from_when_it_was_recorded`
dan `test_the_order_is_written_out_once_in_a_scope` (urutan lama mendahulukan catatan bertanggal), serta ketiga test
cache baru (snapshot lama bertahan sampai 300 detik). 4 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.**

`app/Models/DataSource.php`:

1. Ganti:

```php
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
```

dengan:

```php
use App\Enums\VerificationStatus;
use App\Services\OfficialStatusService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
     * Jenis sumber ikut tersimpan di snapshot status bercache (R-009, "Resmi" atau bukan), jadi
     * perubahannya membuang snapshot jalur yang terdampak seketika (titipan W3-1j). Dipasang
     * pada model supaya setiap jalur tulis tercakup, sama seperti OfficialStatus.
     */
    protected static function booted(): void
    {
        static::saved(function (self $sumber): void {
            if ($sumber->wasChanged('source_type')) {
                OfficialStatusService::forgetSnapshotsForSource($sumber->id);
            }
        });
    }
}
```

`app/Models/OfficialStatus.php`:

1. Ganti:

```php
     * Catatan terbaru lebih dulu, dengan urutan NULL yang sama di SQLite dan PostgreSQL
     * (I3 dan m6 tinjauan akhir W3, R-024).
     *
     * `effective_at` boleh kosong (StoreOfficialStatusRequest), dan `ORDER BY ... DESC`
     * menaruh NULL terakhir di SQLite tetapi pertama di PostgreSQL. Tanpa urutan eksplisit,
     * catatan Buka tanpa tanggal berlaku mengalahkan penutupan bertanggal di Supabase
     * sementara suite SQLite memilih penutupan. Di sini catatan bertanggal selalu
     * didahulukan, seri diputus published_at (juga bertanggal dulu), lalu id.
     *
     * Satu-satunya tempat urutan ini ditulis: setiap pembaca "catatan terbaru per entitas"
     * (OfficialStatusService, StatusJalurRingkas) memakainya, jadi keputusan semantik W8
     * (COALESCE dengan created_at, parkir W3-1i) cukup mengubah scope ini.
     */
```

dengan:

```php
     * Catatan terbaru lebih dulu, dengan urutan NULL yang sama di SQLite dan PostgreSQL
     * (I3 dan m6 tinjauan akhir W3, R-024; semantik diputus W8, ruling W8-R3).
     *
     * Waktu sebuah catatan adalah tanggal berlakunya, atau waktu dicatatnya bila tanggal
     * berlaku tidak disebut: `COALESCE(effective_at, created_at)`. Yang terbaru didahulukan,
     * seri diputus published_at (bertanggal dulu), lalu id. `ORDER BY ... DESC` menaruh NULL
     * terakhir di SQLite tetapi pertama di PostgreSQL, jadi urutan NULL ditulis eksplisit.
     * Sebelum W8 catatan bertanggal selalu didahulukan, sehingga catatan tanpa tanggal yang
     * lebih baru tidak pernah dapat menggantikannya; meja kurator kini mewajibkan tanggal
     * berlaku, jadi catatan tanpa tanggal hanya datang dari data lama.
     *
     * Satu-satunya tempat urutan ini ditulis: setiap pembaca "catatan terbaru per entitas"
     * (OfficialStatusService, StatusJalurRingkas) memakainya (OfficialStatusOrderTest).
     */
```

2. Ganti:

```php
        return $query
            ->orderByRaw('effective_at is null')
            ->orderByDesc('effective_at')
            ->orderByRaw('published_at is null')
```

dengan:

```php
        return $query
            ->orderByRaw('coalesce(effective_at, created_at) is null')
            ->orderByRaw('coalesce(effective_at, created_at) desc')
            ->orderByRaw('published_at is null')
```

3. Ganti:

```php
    }

    private function forgetAffectedTrailCaches(): void
    {
```

dengan:

```php
    }

    /**
     * Membuang snapshot bercache setiap jalur yang terdampak catatan ini (kaskade §42): jalur
     * itu sendiri, semua jalur gunungnya, atau jalur pemilik segmennya. Publik supaya
     * perubahan sumber datanya (DataSource) dapat memakai aturan yang sama.
     */
    public function forgetAffectedTrailCaches(): void
    {
```

`app/Services/OfficialStatusService.php`:

1. Ganti:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
```

dengan:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
```

2. Ganti:

```php
    {
        return Cache::remember(
            self::snapshotCacheKey($trail->id),
            (int) config('hiking.cache.public_ttl_seconds'),
            fn () => $this->snapshotForTrail($trail)
        );
    }
```

dengan:

```php
    {
        $kunci = self::snapshotCacheKey($trail->id);
        $tersimpan = Cache::get($kunci);

        if (is_array($tersimpan)) {
            return $tersimpan;
        }

        $snapshot = $this->snapshotForTrail($trail);
        Cache::put($kunci, $snapshot, $this->umurSnapshot($trail));

        return $snapshot;
    }

    /**
     * Umur snapshot bercache: TTL publik, tetapi tidak pernah melewati saat catatan jalur atau
     * gunungnya berikutnya mulai atau berhenti berlaku (titipan W4-6h OOS6).
     *
     * Status yang berlaku karena waktunya tiba tidak memicu event model, jadi tanpa batas ini
     * snapshot dapat menyebut status lama sampai lima menit, sementara pembaca langsung
     * (kartu status, StatusJalurRingkas) sudah memakai yang baru. Satu kueri, hanya saat
     * snapshot dibangun.
     */
    private function umurSnapshot(Trail $trail): int
    {
        $ttl = (int) config('hiking.cache.public_ttl_seconds');
        $kini = Carbon::now();
        $sasaran = [[(new Trail)->getMorphClass(), $trail->id]];

        if ($trail->mountain_id !== null) {
            $sasaran[] = [(new Mountain)->getMorphClass(), $trail->mountain_id];
        }

        $baris = OfficialStatus::query()
            ->where(function ($q) use ($sasaran) {
                foreach ($sasaran as [$jenis, $id]) {
                    $q->orWhere(fn ($w) => $w->where('statusable_type', $jenis)->where('statusable_id', $id));
                }
            })
            ->where(fn ($q) => $q->where('effective_at', '>', $kini)->orWhere('expires_at', '>', $kini))
            ->get(['effective_at', 'expires_at']);

        $batas = $baris
            ->flatMap(fn (OfficialStatus $s) => [$s->effective_at, $s->expires_at])
            ->filter(fn ($waktu) => $waktu !== null && $waktu->greaterThan($kini))
            ->min();

        if ($batas === null) {
            return $ttl;
        }

        // Satu detik sesudah batasnya: expires_at masih berlaku pada detik itu sendiri
        // (scopeEffectiveOn memakai >=).
        return max(1, min($ttl, (int) $kini->diffInSeconds($batas) + 1));
    }
```

3. Ganti:

```php
        Cache::forget(self::snapshotCacheKey($trailId));
    }
```

dengan:

```php
        Cache::forget(self::snapshotCacheKey($trailId));
    }

    /**
     * Snapshot membawa jenis sumber dari data_sources (R-009), jadi mengubah jenis sebuah
     * sumber membuang snapshot setiap jalur yang statusnya mengutip sumber itu (titipan
     * W3-1j), dengan kaskade yang sama seperti perubahan status.
     */
    public static function forgetSnapshotsForSource(int $dataSourceId): void
    {
        OfficialStatus::query()
            ->where('data_source_id', $dataSourceId)
            ->get(['id', 'statusable_type', 'statusable_id'])
            ->each(fn (OfficialStatus $status) => $status->forgetAffectedTrailCaches());
    }
```

- [ ] **Step 4: Build dan tipe.** Tidak ada TypeScript, rute, atau daftar rute bukan halaman React yang berubah; build
tidak perlu.

Run: `node_modules/.bin/tsc --noEmit`
Expected: tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Models/DataSource.php app/Models/OfficialStatus.php app/Services/OfficialStatusService.php tests/Feature/OfficialStatusOrderTest.php tests/Feature/StatusCacheInvalidationTest.php tests/Spatial/OfficialStatusNullOrderTest.php`, lalu `php artisan test --compact --filter='OfficialStatusOrder|OfficialStatusNullOrder|StatusCacheInvalidation'`
Expected: PASS. Pada simulasi rencana: 14 test, 10 lulus, 4 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.134 test, 2.132 lulus, 2 dilewati (sebelum tugas: 2.130).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 2: Provenans status resmi dan keterlihatan pendaki

Spec v3 bagian 10.7 (tabel tambahan), induk W8 butir 1; ruling W8-R2, W8-R4, W8-R5. Migrasi aditif
`2026_10_09_000100` menambah kolom provenans ke `official_statuses`: penerbit (`authority_id`), nomor dan tanggal surat,
jenis dan arsip bukti dengan SHA-256, cara berakhir, batas pembukaan parsial, kategori alasan, dasar hukum, kutipan resmi,
ringkasan, pemeriksa kedua, keadaan verifikasi, tanda boleh terbit lebih dulu, rantai penggantian, dan jadwal tinjau,
dengan tiga CHECK PostgreSQL. Empat enum baru (`StatusVerificationState`, `EvidenceType`, `StatusEndCondition`,
`StatusReasonCategory`); `VerificationStatus` yang sudah ada milik sumber data dan artinya berbeda. Setiap pembaca status
pendaki (`OfficialStatusService`, `StatusJalurRingkas`, `TrailNewsService`, `DataFreshnessService`, `TrailDataHealth`)
memakai scope `terlihatPendaki()`, dan gerbang publikasi menghitung catatan yang terlihat (`Trail::statusTerlihat()`).
Kolom kendali tidak dapat diisi massal.

**Files:**
- Create: `app/Enums/EvidenceType.php`, `app/Enums/StatusEndCondition.php`, `app/Enums/StatusReasonCategory.php`, `app/Enums/StatusVerificationState.php`, `database/migrations/2026_10_09_000100_add_provenance_to_official_statuses_table.php`
- Modify: `app/Models/OfficialStatus.php`, `app/Models/Trail.php`, `app/Services/Admin/TrailDataHealth.php`, `app/Services/DataFreshnessService.php`, `app/Services/OfficialStatusService.php`, `app/Services/TrailNewsService.php`, `app/Services/Trails/StatusJalurRingkas.php`
- Test: `tests/Feature/OfficialStatusVisibilityTest.php` (baru), `tests/Spatial/OfficialStatusProvenanceSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `OfficialStatus` dan pembacanya (W3, Tugas 1); `PublicationGateViolation` dan event `deleting` gerbang publikasi (W3); tabel `authorities` (W3).
- Produces:
  - enum `StatusVerificationState` (`DRAFT`, `PENDING`, `VERIFIED`, `DISPUTED`; `label()`), `EvidenceType` (`OFFICIAL_LETTER`, `OFFICIAL_PAGE`, `OFFICIAL_ACCOUNT`, `NEWS`; `label()`, `isPrimary()`), `StatusEndCondition` (`ON_DATE`, `UNTIL_NOTICE`, `NOT_STATED`; `label()`), `StatusReasonCategory` (`FIRE`, `ERUPTION`, `WEATHER`, `MAINTENANCE`, `EVENT`, `ACCIDENT`, `OTHER`; `label()`);
  - kolom baru `official_statuses` (lihat migrasi) dan cast-nya; isian massal hanya kolom isi (`document_number`, `document_date`, `evidence_type`, `captured_at`, `end_condition`, `limit_description`, `reason_category`, `legal_basis`, `official_excerpt`, `plain_summary`);
  - relasi `OfficialStatus::verifiedBy()`, `authority()`, `supersededBy()`, `replaces()`; `scopeTerlihatPendaki(Builder): Builder`, `terlihatPendaki(): bool`, `menungguVerifikasiKedua(): bool`;
  - `Trail::statusTerlihat(): MorphMany`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction OfficialStatusVisibilityTest`, lalu isinya diganti persis. Berkas lain, `tests/Spatial/OfficialStatusProvenanceSchemaTest.php`, dibuat langsung.

Buat `tests/Feature/OfficialStatusVisibilityTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\StatusScope;
use App\Enums\StatusVerificationState;
use App\Exceptions\PublicationGateViolation;
use App\Models\Authority;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Services\DataFreshnessService;
use App\Services\OfficialStatusService;
use App\Services\TrailNewsService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Provenans dan keterlihatan status resmi (spec v3 bagian 10.7; rencana induk W8 butir 1;
 * ruling W8-R4 dan W8-R5).
 *
 * Catatan dari meja kurator punya keadaan verifikasi. Pendaki hanya membaca catatan yang
 * terverifikasi orang kedua, catatan pembatasan yang boleh terbit lebih dulu (bertanda menunggu
 * verifikasi kedua), dan catatan lama dari sebelum meja kurator (keadaan kosong). Draf dan
 * pembukaan yang belum diverifikasi tidak pernah sampai ke pembaca mana pun, termasuk gerbang
 * publikasi (R-022): draf bukan "status resmi pernah dicatat".
 */
class OfficialStatusVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Keadaan meja kurator ditulis layanan lewat forceFill, bukan lewat isian; test memakai
     * jalan yang sama.
     *
     * @param  array<string, mixed>  $keadaan
     */
    private function catatan(Model $statusable, OfficialStatusValue $nilai, array $keadaan = []): OfficialStatus
    {
        $status = OfficialStatus::factory()->create([
            'statusable_type' => $statusable->getMorphClass(),
            'statusable_id' => $statusable->getKey(),
            'scope' => $statusable instanceof TrailSegment ? StatusScope::SEGMENT->value : StatusScope::TRAIL->value,
            'status' => $nilai->value,
            'effective_at' => now()->subHour(),
        ]);
        $status->forceFill($keadaan)->save();

        return $status->fresh();
    }

    public function test_drafts_and_unverified_openings_never_reach_a_hiker_reader(): void
    {
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->for($gunung)->create();
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        $this->catatan($jalur, OfficialStatusValue::CLOSED, ['effective_at' => now()->subDays(3)]);
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['verification_state' => StatusVerificationState::DRAFT]);
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['verification_state' => StatusVerificationState::PENDING, 'visible_before_verification' => false]);
        $this->catatan($segmen, OfficialStatusValue::CLOSED, ['verification_state' => StatusVerificationState::DRAFT]);

        $layanan = app(OfficialStatusService::class);
        $daftar = Trail::with('mountain')->whereKey($jalur->id)->get();

        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->currentFor($jalur)?->status);
        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->statusOnDate($jalur, now()));
        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->effectiveStatusesForTrails($daftar)[$jalur->id]);
        $this->assertSame('CLOSED', $layanan->cachedSnapshotForTrail($jalur)['status']);
        $this->assertSame('CLOSED', app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
        $this->assertSame([], $layanan->segmentRestrictionsForTrail($jalur));
        $this->assertSame([], $layanan->segmentRestrictionsForTrails($daftar)[$jalur->id]);
    }

    /**
     * Pembatasan oleh satu kurator dengan bukti primer terbit lebih dulu dan ditandai menunggu
     * verifikasi kedua (aturan terbit asimetris); pendaki membacanya seperti catatan lain.
     */
    public function test_a_restriction_published_before_verification_is_read_and_marked(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['effective_at' => now()->subDays(3)]);
        $tutup = $this->catatan($jalur, OfficialStatusValue::CLOSED, [
            'verification_state' => StatusVerificationState::PENDING,
            'visible_before_verification' => true,
        ]);

        $this->assertTrue($tutup->terlihatPendaki());
        $this->assertTrue($tutup->menungguVerifikasiKedua());
        $this->assertSame(OfficialStatusValue::CLOSED, app(OfficialStatusService::class)->currentFor($jalur)?->status);
    }

    public function test_the_news_feed_and_the_review_queue_skip_drafts(): void
    {
        $pendaki = User::factory()->create();
        $gunung = Mountain::factory()->create();
        MountainFollow::create(['user_id' => $pendaki->id, 'mountain_id' => $gunung->id]);
        $jalur = Trail::factory()->easy()->published()->for($gunung)->create();
        $jalur->officialStatuses()->delete();
        $this->catatan($jalur, OfficialStatusValue::CLOSED, [
            'verification_state' => StatusVerificationState::DRAFT,
            'expires_at' => now()->subDay(),
        ]);

        $kabar = app(TrailNewsService::class)->forUser($pendaki);
        $tinjau = app(DataFreshnessService::class)->review();

        $this->assertCount(0, $kabar);
        $this->assertCount(0, $tinjau['kedaluwarsa']);
        $this->assertSame(0, app(DataFreshnessService::class)->reviewCount());
    }

    /**
     * R-022: gerbang publikasi menghitung status yang dapat dibaca pendaki. Jalur yang hanya
     * punya draf belum pernah punya status resmi di mata pendaki.
     */
    public function test_a_draft_does_not_satisfy_the_publication_gate(): void
    {
        $jalur = $this->jalurLengkapTanpaStatus();
        $this->catatan($jalur, OfficialStatusValue::OPEN, ['verification_state' => StatusVerificationState::DRAFT]);

        $this->assertContains('Status resmi belum pernah dicatat.', $jalur->fresh()->publishabilityReport());
        $this->assertContains('Keterangan status resmi', $jalur->fresh()->awaitingData());
        $this->assertFalse(Trail::query()->whereKey($jalur->id)->dataComplete()->exists());

        $this->expectException(PublicationGateViolation::class);
        $jalur->fresh()->update(['is_published' => true]);
    }

    public function test_a_published_trail_cannot_lose_its_last_readable_status_but_may_lose_a_draft(): void
    {
        $jalur = $this->jalurLengkapTanpaStatus();
        $terlihat = $this->catatan($jalur, OfficialStatusValue::OPEN);
        $draf = $this->catatan($jalur, OfficialStatusValue::CLOSED, ['verification_state' => StatusVerificationState::DRAFT]);
        $jalur->fresh()->update(['is_published' => true]);

        $draf->delete();
        $this->assertModelMissing($draf);

        $this->expectException(PublicationGateViolation::class);
        $terlihat->fresh()->delete();
    }

    /**
     * Kolom yang menentukan siapa, atas nama siapa, dan apakah sebuah catatan terlihat tidak
     * pernah dapat diisi lewat isian massal; layanan meja kurator menulisnya dari sesi.
     */
    public function test_provenance_controls_are_not_mass_assignable(): void
    {
        $jalur = Trail::factory()->create();
        $penyusup = User::factory()->create();
        $otoritas = Authority::create(['name' => 'Balai uji', 'slug' => 'balai-uji', 'type' => 'NATIONAL_PARK']);

        $status = OfficialStatus::create([
            'statusable_type' => $jalur->getMorphClass(),
            'statusable_id' => $jalur->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'document_number' => 'SE.4/KSDAE/2026',
            'evidence_type' => EvidenceType::OFFICIAL_LETTER->value,
            'end_condition' => StatusEndCondition::UNTIL_NOTICE->value,
            'reason_category' => StatusReasonCategory::FIRE->value,
            'authority_id' => $otoritas->id,
            'verified_by' => $penyusup->id,
            'verification_state' => StatusVerificationState::VERIFIED->value,
            'visible_before_verification' => true,
            'superseded_by_id' => 1,
            'evidence_archive_path' => '../../.env',
            'evidence_sha256' => str_repeat('a', 64),
            'next_review_at' => now()->addYear(),
        ])->fresh();

        $this->assertSame('SE.4/KSDAE/2026', $status->document_number);
        $this->assertSame(EvidenceType::OFFICIAL_LETTER, $status->evidence_type);
        $this->assertSame(StatusEndCondition::UNTIL_NOTICE, $status->end_condition);
        $this->assertSame(StatusReasonCategory::FIRE, $status->reason_category);
        $this->assertNull($status->authority_id);
        $this->assertNull($status->verified_by);
        $this->assertNull($status->verification_state);
        $this->assertFalse($status->visible_before_verification);
        $this->assertNull($status->superseded_by_id);
        $this->assertNull($status->evidence_archive_path);
        $this->assertNull($status->evidence_sha256);
        $this->assertNull($status->next_review_at);
    }

    public function test_the_labels_say_who_is_awaited(): void
    {
        $this->assertSame('Menunggu verifikasi kedua', StatusVerificationState::PENDING->label());
        $this->assertSame('Sampai pemberitahuan lebih lanjut', StatusEndCondition::UNTIL_NOTICE->label());
        $this->assertTrue(EvidenceType::OFFICIAL_LETTER->isPrimary());
        $this->assertFalse(EvidenceType::NEWS->isPrimary());
    }

    private function jalurLengkapTanpaStatus(): Trail
    {
        $jalur = Trail::factory()->create(['data_source_id' => DataSource::factory()->create()->id]);
        Checkpoint::factory()->for($jalur)->create(['sequence' => 1]);

        return $jalur->fresh();
    }
}
```

Buat `tests/Spatial/OfficialStatusProvenanceSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusVerificationState;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Provenans status resmi di PostgreSQL (R-024; ruling W8-R4 dan W8-R5): kolom tambahan spec v3
 * bagian 10.7, tiga CHECK yang hanya ada di PostgreSQL (keadaan verifikasi, dua orang berbeda,
 * bentuk SHA-256), kunci asing yang menjadi kosong bila rujukannya hilang, indeks antrean, dan
 * scope keterlihatan yang sama dengan SQLite. Migrasinya tidak dijalankan di Supabase sebelum
 * izin pemilik (spec v3 Q2).
 */
class OfficialStatusProvenanceSchemaTest extends SpatialTestCase
{
    public function test_the_columns_checks_and_indexes_have_their_production_shape(): void
    {
        $kolom = collect(DB::select(
            "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = 'official_statuses'"
        ))->keyBy('column_name');

        $this->assertSame(['date', 'YES'], [$kolom['document_date']->data_type, $kolom['document_date']->is_nullable]);
        $this->assertSame(['character', 64], [$kolom['evidence_sha256']->data_type, (int) $kolom['evidence_sha256']->character_maximum_length]);
        $this->assertSame(['boolean', 'NO'], [$kolom['visible_before_verification']->data_type, $kolom['visible_before_verification']->is_nullable]);
        $this->assertStringContainsString('false', (string) $kolom['visible_before_verification']->column_default);
        $this->assertSame('YES', $kolom['verification_state']->is_nullable, 'Catatan lama tanpa keadaan tetap dibaca pendaki.');

        foreach (['authority_id', 'document_number', 'evidence_type', 'evidence_archive_path', 'captured_at', 'end_condition', 'limit_description', 'reason_category', 'legal_basis', 'official_excerpt', 'plain_summary', 'verified_by', 'superseded_by_id', 'next_review_at'] as $nama) {
            $this->assertArrayHasKey($nama, $kolom->all(), $nama);
        }

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'official_statuses'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('official_statuses_keadaan_sah', $cek);
        $this->assertContains('official_statuses_dua_orang', $cek);
        $this->assertContains('official_statuses_sha256_sah', $cek);

        $indeks = collect(DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'official_statuses'"))->pluck('indexname')->all();
        foreach (['official_statuses_antrean', 'official_statuses_authority_id_index', 'official_statuses_verified_by_index', 'official_statuses_superseded_by_id_index'] as $nama) {
            $this->assertContains($nama, $indeks);
        }

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'official_statuses'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('n', $hapus['authority_id']);
        $this->assertSame('n', $hapus['verified_by']);
        $this->assertSame('n', $hapus['superseded_by_id']);
    }

    public function test_an_unknown_verification_state_is_refused(): void
    {
        $status = OfficialStatus::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('official_statuses')->where('id', $status->id)->update(['verification_state' => 'PUBLISHED']);
    }

    public function test_the_verifier_can_never_be_the_recorder(): void
    {
        $kurator = User::factory()->create();
        $status = OfficialStatus::factory()->create(['recorded_by' => $kurator->id]);

        $this->expectException(QueryException::class);
        DB::table('official_statuses')->where('id', $status->id)->update(['verified_by' => $kurator->id]);
    }

    public function test_the_evidence_hash_must_be_lowercase_hex(): void
    {
        $status = OfficialStatus::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('official_statuses')->where('id', $status->id)->update(['evidence_sha256' => str_repeat('Z', 64)]);
    }

    public function test_deleting_the_authority_keeps_the_record(): void
    {
        $otoritas = Authority::create(['name' => 'Balai uji', 'slug' => 'balai-uji', 'type' => 'NATIONAL_PARK']);
        $status = OfficialStatus::factory()->create();
        $status->forceFill(['authority_id' => $otoritas->id])->save();

        $otoritas->delete();

        $this->assertNull($status->fresh()->authority_id);
    }

    public function test_drafts_stay_out_of_every_reader_on_postgresql(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $segmen = TrailSegment::factory()->for($jalur)->create(['name' => 'Puncak']);
        OfficialStatus::factory()->closed()->create(['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->id, 'effective_at' => now()->subDays(3)]);
        $draf = OfficialStatus::factory()->create(['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->id, 'effective_at' => now()->subHour()]);
        $draf->forceFill(['verification_state' => StatusVerificationState::DRAFT])->save();
        $tertunda = OfficialStatus::factory()->closed()->create(['statusable_type' => $segmen->getMorphClass(), 'statusable_id' => $segmen->id, 'scope' => 'SEGMENT', 'effective_at' => now()->subHour()]);
        $tertunda->forceFill(['verification_state' => StatusVerificationState::PENDING, 'visible_before_verification' => true])->save();

        $layanan = app(OfficialStatusService::class);
        $daftar = Trail::with('mountain')->whereKey($jalur->id)->get();

        $this->assertSame(OfficialStatusValue::CLOSED, $layanan->currentFor($jalur)?->status);
        $this->assertSame('CLOSED', app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
        $this->assertSame('Puncak', $layanan->segmentRestrictionsForTrails($daftar)[$jalur->id][0]['segment'] ?? null);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='OfficialStatusVisibility|OfficialStatusProvenanceSchema|OfficialStatusPage|PublicationInvariant'`
Expected: FAIL. Pada simulasi rencana: 35 test, 22 lulus, 7 error, 6 dilewati. Yang merah: ketujuh test `OfficialStatusVisibilityTest` (enum `StatusVerificationState`
dan `EvidenceType` belum ada). 6 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction EvidenceType`, `... make:enum --no-interaction StatusEndCondition`, `... make:enum --no-interaction StatusReasonCategory`, `... make:enum --no-interaction StatusVerificationState`, dan `... make:migration --no-interaction add_provenance_to_official_statuses_table` (ganti nama berkas migrasi hasilnya menjadi `2026_10_09_000100_add_provenance_to_official_statuses_table.php`), lalu isinya diganti persis.

Buat `app/Enums/EvidenceType.php`:

```php
<?php

namespace App\Enums;

/**
 * Jenis bukti di balik sebuah catatan status resmi (spec v3 bagian 10.7).
 *
 * Bukti primer datang dari pengelola sendiri. Berita media adalah bukti sekunder: ia tidak
 * pernah dapat membuka jalur, dan pembatasan yang hanya berbukti berita menunggu verifikasi
 * orang kedua sebelum terbit (ruling W8-R6).
 */
enum EvidenceType: string
{
    case OFFICIAL_LETTER = 'OFFICIAL_LETTER';
    case OFFICIAL_PAGE = 'OFFICIAL_PAGE';
    case OFFICIAL_ACCOUNT = 'OFFICIAL_ACCOUNT';
    case NEWS = 'NEWS';

    public function label(): string
    {
        return match ($this) {
            self::OFFICIAL_LETTER => 'Surat atau dokumen resmi',
            self::OFFICIAL_PAGE => 'Laman resmi pengelola',
            self::OFFICIAL_ACCOUNT => 'Akun media sosial resmi pengelola',
            self::NEWS => 'Berita media',
        };
    }

    public function isPrimary(): bool
    {
        return $this !== self::NEWS;
    }
}
```

Buat `app/Enums/StatusEndCondition.php`:

```php
<?php

namespace App\Enums;

/**
 * Cara sebuah status resmi berakhir (spec v3 bagian 10.7). Memisahkan dua arti `expires_at`
 * kosong: pengelola menyebut "sampai pemberitahuan lebih lanjut", atau pengelola tidak menyebut
 * apa pun (R-007).
 */
enum StatusEndCondition: string
{
    case ON_DATE = 'ON_DATE';
    case UNTIL_NOTICE = 'UNTIL_NOTICE';
    case NOT_STATED = 'NOT_STATED';

    public function label(): string
    {
        return match ($this) {
            self::ON_DATE => 'Sampai tanggal tertentu',
            self::UNTIL_NOTICE => 'Sampai pemberitahuan lebih lanjut',
            self::NOT_STATED => 'Tidak disebut pengelola',
        };
    }
}
```

Buat `app/Enums/StatusReasonCategory.php`:

```php
<?php

namespace App\Enums;

/**
 * Golongan alasan sebuah status resmi (spec v3 bagian 10.7). Kalimat alasan dari pengelola tetap
 * di kolom `reason`; golongan ini membuat alasan dapat dibaca dan disaring dengan kata yang sama.
 */
enum StatusReasonCategory: string
{
    case FIRE = 'FIRE';
    case ERUPTION = 'ERUPTION';
    case WEATHER = 'WEATHER';
    case MAINTENANCE = 'MAINTENANCE';
    case EVENT = 'EVENT';
    case ACCIDENT = 'ACCIDENT';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::FIRE => 'Kebakaran',
            self::ERUPTION => 'Erupsi',
            self::WEATHER => 'Cuaca',
            self::MAINTENANCE => 'Pemeliharaan',
            self::EVENT => 'Acara',
            self::ACCIDENT => 'Kecelakaan',
            self::OTHER => 'Lainnya',
        };
    }
}
```

Buat `app/Enums/StatusVerificationState.php`:

```php
<?php

namespace App\Enums;

/**
 * Keadaan verifikasi catatan status resmi dari meja kurator (spec v3 bagian 10.7; ruling W8-R4).
 *
 * Catatan lama dari sebelum meja kurator tidak punya keadaan (kolom kosong) dan tetap dibaca
 * pendaki. Draf tidak pernah terlihat. Menunggu verifikasi kedua dan dipersoalkan hanya terlihat
 * bila layanan terbit menandainya boleh terbit lebih dulu (pembatasan dengan bukti primer).
 */
enum StatusVerificationState: string
{
    case DRAFT = 'DRAFT';
    case PENDING = 'PENDING';
    case VERIFIED = 'VERIFIED';
    case DISPUTED = 'DISPUTED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::PENDING => 'Menunggu verifikasi kedua',
            self::VERIFIED => 'Terverifikasi',
            self::DISPUTED => 'Dipersoalkan',
        };
    }
}
```

Buat `database/migrations/2026_10_09_000100_add_provenance_to_official_statuses_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Provenans status resmi untuk meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 1;
 * ruling W8-R4).
 *
 * Penerbit (`authority_id`), nomor dan tanggal surat, jenis dan arsip bukti beserta SHA-256 dan
 * waktu tangkapnya, cara berakhir, batas pembukaan parsial, golongan alasan, dasar hukum,
 * kutipan resmi, ringkasan bahasa sederhana, pemeriksa kedua, keadaan verifikasi, rantai
 * penggantian, dan jadwal tinjau. Semua kolom boleh kosong: catatan lama dari sebelum meja kurator
 * tidak punya keadaan verifikasi dan tetap dibaca pendaki seperti sekarang, jadi migrasi ini tidak
 * mengubah satu pun status yang tampil.
 *
 * Di PostgreSQL tiga CHECK menjaga bentuk baris di lapisan basis data: keadaan verifikasi dari
 * daftar tertutup, pemeriksa kedua tidak pernah sama dengan pencatat (aturan dua orang), dan
 * SHA-256 berupa 64 karakter heksadesimal kecil. Kunci asing ke otoritas, pemeriksa, dan catatan
 * pengganti menjadi kosong bila rujukannya hilang, sehingga menghapus akun kurator tidak menghapus
 * status resmi (kebijakan data pribadi bagian 2: data tata kelola dipertahankan seminimal
 * mungkin). official_statuses sudah ber-RLS sejak 2026_09_20_000100.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_statuses', function (Blueprint $table) {
            $table->foreignId('authority_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_number', 120)->nullable();
            $table->date('document_date')->nullable();
            $table->string('evidence_type', 20)->nullable();
            $table->string('evidence_archive_path')->nullable();
            $table->char('evidence_sha256', 64)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->string('end_condition', 20)->nullable();
            $table->string('limit_description', 200)->nullable();
            $table->string('reason_category', 20)->nullable();
            $table->string('legal_basis')->nullable();
            $table->text('official_excerpt')->nullable();
            $table->text('plain_summary')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_state', 20)->nullable();
            $table->boolean('visible_before_verification')->default(false);
            $table->foreignId('superseded_by_id')->nullable()->constrained('official_statuses')->nullOnDelete();
            $table->timestamp('next_review_at')->nullable();

            $table->index('authority_id');
            $table->index('verified_by');
            $table->index('superseded_by_id');
            // Antrean meja kurator membaca keadaan dan jadwal tinjau.
            $table->index(['verification_state', 'next_review_at'], 'official_statuses_antrean');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE official_statuses ADD CONSTRAINT official_statuses_keadaan_sah CHECK (verification_state IS NULL OR verification_state IN ('DRAFT', 'PENDING', 'VERIFIED', 'DISPUTED'))");
            DB::statement('ALTER TABLE official_statuses ADD CONSTRAINT official_statuses_dua_orang CHECK (verified_by IS NULL OR recorded_by IS NULL OR verified_by <> recorded_by)');
            DB::statement("ALTER TABLE official_statuses ADD CONSTRAINT official_statuses_sha256_sah CHECK (evidence_sha256 IS NULL OR evidence_sha256 ~ '^[0-9a-f]{64}$')");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE official_statuses DROP CONSTRAINT IF EXISTS official_statuses_keadaan_sah');
            DB::statement('ALTER TABLE official_statuses DROP CONSTRAINT IF EXISTS official_statuses_dua_orang');
            DB::statement('ALTER TABLE official_statuses DROP CONSTRAINT IF EXISTS official_statuses_sha256_sah');
        }

        Schema::table('official_statuses', function (Blueprint $table) {
            $table->dropIndex('official_statuses_antrean');
            $table->dropIndex(['authority_id']);
            $table->dropIndex(['verified_by']);
            $table->dropIndex(['superseded_by_id']);
            $table->dropConstrainedForeignId('authority_id');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropConstrainedForeignId('superseded_by_id');
            $table->dropColumn([
                'document_number', 'document_date', 'evidence_type', 'evidence_archive_path', 'evidence_sha256',
                'captured_at', 'end_condition', 'limit_description', 'reason_category', 'legal_basis',
                'official_excerpt', 'plain_summary', 'verification_state', 'visible_before_verification',
                'next_review_at',
            ]);
        });
    }
};
```

`app/Models/OfficialStatus.php`:

1. Ganti:

```php
namespace App\Models;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Exceptions\PublicationGateViolation;
```

dengan:

```php
namespace App\Models;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\StatusScope;
use App\Enums\StatusVerificationState;
use App\Exceptions\PublicationGateViolation;
```

2. Ganti:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
```

dengan:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
```

3. Ganti:

```php
use Illuminate\Support\Carbon;

#[Fillable([
```

dengan:

```php
use Illuminate\Support\Carbon;

/**
 * Catatan status resmi satu gunung, jalur, atau segmen (PRD §41, §42).
 *
 * Provenans meja kurator (W8, spec v3 bagian 10.7) yang menentukan siapa, atas nama siapa, dan
 * apakah catatan terlihat pendaki (`authority_id`, `verified_by`, `verification_state`,
 * `visible_before_verification`, `superseded_by_id`, arsip bukti, `next_review_at`) sengaja tidak
 * dapat diisi massal: layanan terbit menulisnya dari sesi dan Policy lewat forceFill (ruling
 * W8-R4).
 */
#[Fillable([
```

4. Ganti:

```php
    'published_at', 'fetched_at', 'verified_at', 'effective_at', 'expires_at', 'reason', 'notes', 'recorded_by',
])]
```

dengan:

```php
    'published_at', 'fetched_at', 'verified_at', 'effective_at', 'expires_at', 'reason', 'notes', 'recorded_by',
    'document_number', 'document_date', 'evidence_type', 'captured_at', 'end_condition', 'limit_description',
    'reason_category', 'legal_basis', 'official_excerpt', 'plain_summary',
])]
```

5. Ganti:

```php
            'expires_at' => 'datetime',
        ];
```

dengan:

```php
            'expires_at' => 'datetime',
            'document_date' => 'date',
            'evidence_type' => EvidenceType::class,
            'captured_at' => 'datetime',
            'end_condition' => StatusEndCondition::class,
            'reason_category' => StatusReasonCategory::class,
            'verification_state' => StatusVerificationState::class,
            'visible_before_verification' => 'boolean',
            'next_review_at' => 'datetime',
        ];
```

6. Ganti:

```php
        return $this->belongsTo(User::class, 'recorded_by');
    }
```

dengan:

```php
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class);
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    /**
     * Catatan lama yang digantikan catatan ini (rantai penggantian, spec v3 bagian 10.7).
     */
    public function replaces(): HasOne
    {
        return $this->hasOne(self::class, 'superseded_by_id');
    }

    /**
     * Catatan yang boleh dibaca pendaki (ruling W8-R4): catatan lama tanpa keadaan verifikasi,
     * catatan terverifikasi orang kedua, dan catatan menunggu verifikasi atau dipersoalkan yang
     * oleh layanan terbit ditandai boleh terbit lebih dulu (pembatasan dengan bukti primer).
     * Draf dan pembukaan yang belum diverifikasi tidak pernah terlihat. Setiap pembaca status
     * untuk pendaki memakai scope ini; terlihatPendaki() adalah padanannya di PHP.
     */
    public function scopeTerlihatPendaki(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('verification_state')
            ->orWhere('verification_state', StatusVerificationState::VERIFIED->value)
            ->orWhere(fn (Builder $tertunda) => $tertunda
                ->whereIn('verification_state', [StatusVerificationState::PENDING->value, StatusVerificationState::DISPUTED->value])
                ->where('visible_before_verification', true)));
    }

    public function terlihatPendaki(): bool
    {
        return match ($this->verification_state) {
            null, StatusVerificationState::VERIFIED => true,
            StatusVerificationState::PENDING, StatusVerificationState::DISPUTED => (bool) $this->visible_before_verification,
            StatusVerificationState::DRAFT => false,
        };
    }

    /**
     * Terbit lebih dulu oleh satu kurator dan belum diverifikasi orang kedua: layar pendaki
     * menyebutnya, supaya pembatasan yang belum diperiksa dua orang tidak terbaca sama
     * pastinya dengan yang sudah.
     */
    public function menungguVerifikasiKedua(): bool
    {
        return $this->verification_state === StatusVerificationState::PENDING && (bool) $this->visible_before_verification;
    }
```

7. Ganti:

```php
            }

            if ($trail->officialStatuses()->count() > 1) {
                return;
```

dengan:

```php
            }

            // Draf dan catatan yang belum terlihat boleh dibuang; yang dijaga adalah catatan
            // terakhir yang dibaca pendaki (ruling W8-R5).
            if (! $status->terlihatPendaki() || $trail->statusTerlihat()->count() > 1) {
                return;
```

`app/Models/Trail.php`:

1. Ganti:

```php
    }

    public function conditionReports(): HasMany
```

dengan:

```php
    }

    /**
     * Catatan status yang dibaca pendaki (OfficialStatus::scopeTerlihatPendaki()). Gerbang
     * publikasi menghitung relasi ini, bukan semua catatan: draf meja kurator bukan "status
     * resmi pernah dicatat" (R-022, ruling W8-R5).
     */
    public function statusTerlihat(): MorphMany
    {
        return $this->morphMany(OfficialStatus::class, 'statusable')->terlihatPendaki();
    }

    public function conditionReports(): HasMany
```

2. Ganti:

```php
    {
        $query->withCount(['checkpoints', 'officialStatuses']);

        if (static::spatialSupported()) {
```

dengan:

```php
    {
        $query->withCount(['checkpoints', 'statusTerlihat']);

        if (static::spatialSupported()) {
```

3. Ganti:

```php
            ->whereHas('checkpoints')
            ->whereHas('officialStatuses');

        if (static::spatialSupported()) {
```

dengan:

```php
            ->whereHas('checkpoints')
            ->whereHas('statusTerlihat');

        if (static::spatialSupported()) {
```

4. Ganti:

```php
            }

            foreach (['checkpoints_count', 'official_statuses_count', 'has_geometry'] as $atribut) {
                if (array_key_exists($atribut, $sumber->getAttributes())) {
```

dengan:

```php
            }

            foreach (['checkpoints_count', 'status_terlihat_count', 'has_geometry'] as $atribut) {
                if (array_key_exists($atribut, $sumber->getAttributes())) {
```

5. Ganti:

```php
        // withCount() menamai atributnya dari nama relasi apa adanya, tanpa
        // di-singular-kan: officialStatuses menjadi official_statuses_count.
        $attribute = Str::snake($relation).'_count';
```

dengan:

```php
        // withCount() menamai atributnya dari nama relasi apa adanya, tanpa
        // di-singular-kan: statusTerlihat menjadi status_terlihat_count.
        $attribute = Str::snake($relation).'_count';
```

6. Ganti:

```php
        }

        if ($this->countFor('officialStatuses') === 0) {
            $menunggu[] = 'Keterangan status resmi';
```

dengan:

```php
        }

        if ($this->countFor('statusTerlihat') === 0) {
            $menunggu[] = 'Keterangan status resmi';
```

7. Ganti:

```php
        }

        if ($this->countFor('officialStatuses') === 0) {
            $missing[] = 'Status resmi belum pernah dicatat.';
```

dengan:

```php
        }

        if ($this->countFor('statusTerlihat') === 0) {
            $missing[] = 'Status resmi belum pernah dicatat.';
```

`app/Services/Admin/TrailDataHealth.php`:

1. Ganti:

```php
                'label' => 'Status resmi belum pernah dicatat',
                'lingkup' => fn (Builder $query) => $query->whereDoesntHave('officialStatuses'),
            ],
```

dengan:

```php
                'label' => 'Status resmi belum pernah dicatat',
                'lingkup' => fn (Builder $query) => $query->whereDoesntHave('statusTerlihat'),
            ],
```

`app/Services/DataFreshnessService.php`:

1. Ganti:

```php
    {
        return OfficialStatus::query()->with('statusable');
    }
```

dengan:

```php
    {
        // Antrean tinjau berkala hanya memuat catatan yang dibaca pendaki; draf dan pembukaan
        // yang menunggu verifikasi punya antreannya sendiri di meja kurator (W8).
        return OfficialStatus::query()->terlihatPendaki()->with('statusable');
    }
```

`app/Services/OfficialStatusService.php`:

1. Ganti:

```php
            ->where('statusable_id', $statusable->getKey())
            ->currentlyEffective()
```

dengan:

```php
            ->where('statusable_id', $statusable->getKey())
            ->terlihatPendaki()
            ->currentlyEffective()
```

2. Ganti:

```php
            ->where('statusable_id', $statusable->getKey())
            ->effectiveOn($date)
```

dengan:

```php
            ->where('statusable_id', $statusable->getKey())
            ->terlihatPendaki()
            ->effectiveOn($date)
```

3. Ganti:

```php
        }

        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->currentlyEffective()
            ->terbaruDulu()
            ->get();

        foreach ($segments as $segment) {
```

dengan:

```php
        }

        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->terlihatPendaki()
            ->currentlyEffective()
            ->terbaruDulu()
            ->get();

        foreach ($segments as $segment) {
```

4. Ganti:

```php
            ->whereIn('statusable_id', $ids)
            ->currentlyEffective()
```

dengan:

```php
            ->whereIn('statusable_id', $ids)
            ->terlihatPendaki()
            ->currentlyEffective()
```

5. Ganti:

```php
        }

        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->currentlyEffective()
            ->terbaruDulu()
            ->get();

        $restrictions = [];
```

dengan:

```php
        }

        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->terlihatPendaki()
            ->currentlyEffective()
            ->terbaruDulu()
            ->get();

        $restrictions = [];
```

6. Ganti:

```php
            })
            ->where(fn ($q) => $q->where('effective_at', '>', $kini)->orWhere('expires_at', '>', $kini))
```

dengan:

```php
            })
            ->terlihatPendaki()
            ->where(fn ($q) => $q->where('effective_at', '>', $kini)->orWhere('expires_at', '>', $kini))
```

`app/Services/TrailNewsService.php`:

1. Ganti:

```php
            ->with(['statusable', 'dataSource:id,source_type'])
            ->where(fn ($q) => $q
```

dengan:

```php
            ->with(['statusable', 'dataSource:id,source_type'])
            // Draf dan pembukaan yang belum diverifikasi tidak pernah menjadi kabar (W8).
            ->terlihatPendaki()
            ->where(fn ($q) => $q
```

`app/Services/Trails/StatusJalurRingkas.php`:

1. Ganti:

```php
            ->whereIn('statusable_id', $ids)
            ->currentlyEffective()
```

dengan:

```php
            ->whereIn('statusable_id', $ids)
            ->terlihatPendaki()
            ->currentlyEffective()
```

- [ ] **Step 4: Build dan tipe.** Tidak ada TypeScript, rute, atau daftar rute bukan halaman React yang berubah; build
tidak perlu.

Run: `node_modules/.bin/tsc --noEmit`
Expected: tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/EvidenceType.php app/Enums/StatusEndCondition.php app/Enums/StatusReasonCategory.php app/Enums/StatusVerificationState.php app/Models/OfficialStatus.php app/Models/Trail.php app/Services/Admin/TrailDataHealth.php app/Services/DataFreshnessService.php app/Services/OfficialStatusService.php app/Services/TrailNewsService.php app/Services/Trails/StatusJalurRingkas.php database/migrations/2026_10_09_000100_add_provenance_to_official_statuses_table.php tests/Feature/OfficialStatusVisibilityTest.php tests/Spatial/OfficialStatusProvenanceSchemaTest.php`, lalu `php artisan test --compact --filter='OfficialStatusVisibility|OfficialStatusProvenanceSchema|OfficialStatusPage|PublicationInvariant'`
Expected: PASS. Pada simulasi rencana: 35 test, 29 lulus, 6 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.141 test, 2.139 lulus, 2 dilewati (sebelum tugas: 2.134).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 3: Peran kurator dan keanggotaan badan resmi

Spec v3 bagian 10.7 (peran `curator`, tabel `authority_members`, otorisasi), induk W8 butir 2, Q11 (Konflik 2); ruling
W8-R7 dan W8-R8. Migrasi `2026_10_09_000200` membuat `authority_members` (pengguna, badan, peran, surat penunjukan, masa
berlaku, pemberi, pencabutan) dengan indeks unik sebagian untuk keanggotaan aktif, dua CHECK, dan RLS di PostgreSQL.
`Yurisdiksi` menjadi satu-satunya aturan kawasan: kurator aktif berwenang atas gunung di kawasan badan tempat ia anggota,
dan hanya badan yang berwenang menyatakan status jalur. `OfficialStatusPolicy` memakai aturan itu untuk meja, catat,
lihat, ubah draf, dan verifikasi; pemeriksa kedua tidak pernah pencatatnya. Admin menambah dan mencabut keanggotaan di
halaman Otoritas; keanggotaan aktif pertama menjadikan pendaki kurator, pencabutan terakhir mengembalikannya, dan akun
admin atau moderator tidak dapat diberi keanggotaan. Tidak ada akun kurator yang dibuat otomatis.

**Files:**
- Create: `app/Enums/AuthorityMemberRole.php`, `app/Http/Controllers/Admin/AuthorityMemberController.php`, `app/Http/Requests/Admin/StoreAuthorityMemberRequest.php`, `app/Models/AuthorityMember.php`, `app/Services/DataResmi/Yurisdiksi.php`, `database/factories/AuthorityFactory.php`, `database/factories/AuthorityMemberFactory.php`, `database/migrations/2026_10_09_000200_create_authority_members_table.php`
- Modify: `app/Enums/UserRole.php`, `app/Http/Controllers/Admin/AuthorityController.php`, `app/Models/Authority.php`, `app/Models/User.php`, `app/Policies/OfficialStatusPolicy.php`, `database/README.md`, `resources/js/pages/Admin/Authorities.tsx`, `routes/web.php`
- Test: `tests/Feature/AuthorityMemberPolicyTest.php` (baru), `tests/Spatial/AuthorityMemberSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `Authority`, `AuthorityType::mayDeclareTrailStatus()`, kawasan gunung badan (`Authority::mountains()`, W3); `AuditLogService::record()` dan `recordChange()`; halaman `Admin/Authorities` dan `AuthorityController::index()` (W3); `OfficialStatus` (Tugas 2).
- Produces:
  - `UserRole::CURATOR` (`'curator'`), `User::isCurator(): bool`, `User::authorityMemberships(): HasMany`, `Authority::members(): HasMany`;
  - enum `AuthorityMemberRole` (`KURATOR`, `PENERBIT_MITRA`; `label()`); model `AuthorityMember` (`scopeAktif()`), pabrik `AuthorityFactory` dan `AuthorityMemberFactory`;
  - `App\Services\DataResmi\Yurisdiksi`: `keanggotaan(User): Collection`, `kuratorAktif(User): bool`, `gunung(User): Collection`, `gunungSasaran(Model): ?int` (statis), `otoritasUntuk(User, Model): Collection`, `berwenang(User, Model, ?Authority): bool`;
  - `OfficialStatusPolicy` dengan konstruktor `Yurisdiksi` dan aksi `viewDesk(User)`, `record(User, Model, Authority)`, `view(User, OfficialStatus)`, `updateDraft(User, OfficialStatus)`, `verify(User, OfficialStatus)`;
  - rute `admin.authorities.members.store` (POST `admin/authorities/{authority}/anggota`) dan `admin.authorities.members.destroy` (DELETE, `scopeBindings`); prop `anggota` per badan di `Admin/Authorities`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction AuthorityMemberPolicyTest`, lalu isinya diganti persis. Berkas lain, `tests/Spatial/AuthorityMemberSchemaTest.php`, dibuat langsung.

Buat `tests/Feature/AuthorityMemberPolicyTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\AuthorityMemberRole;
use App\Enums\AuthorityType;
use App\Enums\StatusVerificationState;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Peran kurator dan anggota otoritas (spec v3 bagian 10.7; rencana induk W8 butir 2; Q11 dibangun
 * menurut spec, ruling W8-R7 dan W8-R8).
 *
 * Kurator terpisah dari moderator dan admin. Yurisdiksinya adalah kawasan badan resmi tempat ia
 * menjadi anggota aktif, dan hanya badan yang berwenang menyatakan status jalur yang dapat
 * menaunginya. Peran tidak pernah diambil dari klien: hanya admin yang menambah dan mencabut
 * keanggotaan, setiap perubahan peran tercatat di jejak audit (PRD §61), dan tidak ada akun
 * kurator yang dibuat otomatis.
 */
class AuthorityMemberPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Mountain $gede;

    private Mountain $semeru;

    private Authority $balaiGede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gede = Mountain::factory()->create(['name' => 'Gunung Gede']);
        $this->semeru = Mountain::factory()->create(['name' => 'Gunung Semeru']);
        $this->balaiGede = Authority::factory()->create(['name' => 'Balai Besar TN Gede Pangrango']);
        $this->balaiGede->mountains()->attach($this->gede);
    }

    private function kurator(Authority $otoritas, array $atribut = []): User
    {
        $user = User::factory()->create(['role' => UserRole::CURATOR->value]);
        AuthorityMember::factory()->for($user)->for($otoritas)->create($atribut);

        return $user;
    }

    public function test_a_curator_records_only_inside_the_jurisdiction_of_their_authority(): void
    {
        $kurator = $this->kurator($this->balaiGede);
        $jalurGede = Trail::factory()->for($this->gede)->create();
        $segmenGede = TrailSegment::factory()->for($jalurGede)->create();
        $jalurSemeru = Trail::factory()->for($this->semeru)->create();
        $balaiLain = Authority::factory()->create();
        $balaiLain->mountains()->attach($this->gede);

        $this->assertTrue(Gate::forUser($kurator)->allows('viewDesk', OfficialStatus::class));
        $this->assertTrue(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $this->gede, $this->balaiGede]));
        $this->assertTrue(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $jalurGede, $this->balaiGede]));
        $this->assertTrue(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $segmenGede, $this->balaiGede]));
        $this->assertFalse(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $jalurSemeru, $this->balaiGede]), 'Di luar kawasan badannya.');
        $this->assertFalse(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $jalurGede, $balaiLain]), 'Atas nama badan tempat ia bukan anggota.');
    }

    public function test_an_expired_or_revoked_membership_grants_nothing(): void
    {
        $kedaluwarsa = $this->kurator($this->balaiGede, ['valid_from' => now()->subYear()->toDateString(), 'valid_until' => now()->subDays(2)->toDateString()]);
        $dicabut = $this->kurator($this->balaiGede);
        $dicabut->authorityMemberships()->update(['revoked_at' => now()]);

        foreach ([$kedaluwarsa, $dicabut] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('viewDesk', OfficialStatus::class));
            $this->assertFalse(Gate::forUser($user)->allows('record', [OfficialStatus::class, $this->gede, $this->balaiGede]));
        }
    }

    /**
     * Badan yang mengesahkan orang (lembaga sertifikasi) tidak menyatakan keadaan jalur, jadi
     * keanggotaan di sana tidak membuka meja kurator untuk status (AuthorityType).
     */
    public function test_a_certification_body_cannot_shelter_a_curator(): void
    {
        $lsp = Authority::factory()->create(['type' => AuthorityType::CERTIFICATION_BODY->value]);
        $lsp->mountains()->attach($this->gede);
        $kurator = $this->kurator($lsp);

        $this->assertFalse(Gate::forUser($kurator)->allows('viewDesk', OfficialStatus::class));
        $this->assertFalse(Gate::forUser($kurator)->allows('record', [OfficialStatus::class, $this->gede, $lsp]));
    }

    public function test_admins_moderators_and_hikers_are_not_curators(): void
    {
        foreach ([UserRole::ADMIN, UserRole::MODERATOR, UserRole::HIKER] as $peran) {
            $user = User::factory()->create(['role' => $peran->value]);
            AuthorityMember::factory()->for($user)->for($this->balaiGede)->create();

            $this->assertFalse(Gate::forUser($user)->allows('viewDesk', OfficialStatus::class), $peran->value);
            $this->assertFalse(Gate::forUser($user)->allows('record', [OfficialStatus::class, $this->gede, $this->balaiGede]), $peran->value);
        }
    }

    public function test_the_recorder_never_verifies_their_own_record(): void
    {
        $pencatat = $this->kurator($this->balaiGede);
        $kedua = $this->kurator($this->balaiGede);
        $luar = $this->kurator(tap(Authority::factory()->create(), fn (Authority $a) => $a->mountains()->attach($this->semeru)));
        $status = OfficialStatus::factory()->create([
            'statusable_type' => $this->gede->getMorphClass(),
            'statusable_id' => $this->gede->id,
            'scope' => 'MOUNTAIN',
            'recorded_by' => $pencatat->id,
        ]);
        $status->forceFill(['verification_state' => StatusVerificationState::PENDING, 'authority_id' => $this->balaiGede->id])->save();

        $this->assertFalse(Gate::forUser($pencatat)->allows('verify', $status->fresh()));
        $this->assertTrue(Gate::forUser($kedua)->allows('verify', $status->fresh()));
        $this->assertFalse(Gate::forUser($luar)->allows('verify', $status->fresh()), 'Pemeriksa kedua juga harus berwenang atas kawasannya.');
    }

    public function test_an_admin_grants_a_membership_and_the_hiker_becomes_a_curator(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $pendaki = User::factory()->create(['email' => 'citra@contoh.id']);

        $this->actingAs($admin)
            ->post(route('admin.authorities.members.store', $this->balaiGede), [
                'email' => 'citra@contoh.id',
                'peran' => AuthorityMemberRole::KURATOR->value,
                'surat' => 'ST.12/MEMOUNT/2026',
                'berlaku_mulai' => now()->toDateString(),
                'berlaku_sampai' => now()->addYear()->toDateString(),
            ])
            ->assertRedirect(route('admin.authorities'))
            ->assertSessionHas('status', 'citra@contoh.id kini kurator Balai Besar TN Gede Pangrango.');

        $this->assertSame(UserRole::CURATOR, $pendaki->fresh()->role);
        $this->assertDatabaseHas('authority_members', ['user_id' => $pendaki->id, 'authority_id' => $this->balaiGede->id, 'granted_by' => $admin->id, 'revoked_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'authority_member.granted']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'user.role_changed', 'entity_id' => $pendaki->id]);
        $this->assertTrue(Gate::forUser($pendaki->fresh())->allows('viewDesk', OfficialStatus::class));
    }

    public function test_a_moderator_or_admin_cannot_be_made_a_curator_and_a_hiker_cannot_grant(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $moderator = User::factory()->create(['role' => UserRole::MODERATOR->value, 'email' => 'mod@contoh.id']);
        $isian = ['email' => 'mod@contoh.id', 'peran' => AuthorityMemberRole::KURATOR->value, 'surat' => 'ST.1', 'berlaku_mulai' => now()->toDateString()];

        $this->actingAs($admin)->post(route('admin.authorities.members.store', $this->balaiGede), $isian)
            ->assertSessionHasErrors(['email' => 'Kurator terpisah dari moderator dan admin. Pakai akun pendaki lain untuk peran ini.']);
        $this->assertSame(UserRole::MODERATOR, $moderator->fresh()->role);

        $pendaki = User::factory()->create();
        $this->actingAs($pendaki)->post(route('admin.authorities.members.store', $this->balaiGede), [...$isian, 'email' => $pendaki->email])->assertForbidden();
        $this->assertDatabaseCount('authority_members', 0);
    }

    /**
     * Peran tidak pernah diambil dari isian pengguna sendiri (CLAUDE.md bagian 4).
     */
    public function test_a_hiker_cannot_promote_themselves_through_the_profile(): void
    {
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->patch(route('profile.update'), ['name' => $pendaki->name, 'email' => $pendaki->email, 'role' => 'curator']);

        $this->assertSame(UserRole::HIKER, $pendaki->fresh()->role);
    }

    public function test_revoking_the_last_membership_returns_the_curator_to_hiker(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $kurator = $this->kurator($this->balaiGede);
        $keanggotaan = $kurator->authorityMemberships()->first();

        $this->actingAs($admin)
            ->delete(route('admin.authorities.members.destroy', [$this->balaiGede, $keanggotaan]))
            ->assertRedirect(route('admin.authorities'));

        $this->assertNotNull($keanggotaan->fresh()->revoked_at);
        $this->assertSame($admin->id, $keanggotaan->fresh()->revoked_by);
        $this->assertSame(UserRole::HIKER, $kurator->fresh()->role);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'authority_member.revoked']);
        $this->assertFalse(Gate::forUser($kurator->fresh())->allows('viewDesk', OfficialStatus::class));
    }

    public function test_a_membership_is_only_reachable_through_its_own_authority(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $kurator = $this->kurator($this->balaiGede);
        $lain = Authority::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.authorities.members.destroy', [$lain, $kurator->authorityMemberships()->first()]))
            ->assertNotFound();
    }

    public function test_the_authorities_page_lists_active_members(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $kurator = $this->kurator($this->balaiGede);

        $this->actingAs($admin)
            ->get(route('admin.authorities'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Authorities')
                ->has('anggota', 1)
                ->where('anggota.0.nama', $kurator->name)
                ->where('anggota.0.badan', 'Balai Besar TN Gede Pangrango')
                ->where('anggota.0.peran', 'Kurator Memount')
                ->has('badanBerwenang', 1)
                ->has('peranAnggota', 2));
    }
}
```

Buat `tests/Spatial/AuthorityMemberSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel anggota otoritas di PostgreSQL (R-024; ruling W8-R7): satu keanggotaan aktif per pengguna
 * per badan (indeks unik parsial), peran dari daftar tertutup, masa berlaku yang tidak terbalik,
 * kunci asing yang ikut terhapus bersama akun dan badannya, dan RLS yang menutup REST API. Suite
 * SQLite tidak membuktikan CHECK dan RLS. Migrasinya tidak dijalankan di Supabase sebelum izin
 * pemilik (spec v3 Q2).
 */
class AuthorityMemberSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'authority_members'")->relrowsecurity, 'RLS lapis kedua harus menyala.');

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'authority_members'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('authority_members_peran_sah', $cek);
        $this->assertContains('authority_members_masa_sah', $cek);

        $indeks = DB::selectOne("SELECT indexdef FROM pg_indexes WHERE tablename = 'authority_members' AND indexname = 'authority_members_aktif_unik'");
        $this->assertNotNull($indeks);
        $this->assertStringContainsString('revoked_at IS NULL', $indeks->indexdef);

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'authority_members'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('c', $hapus['user_id']);
        $this->assertSame('c', $hapus['authority_id']);
        $this->assertSame('n', $hapus['granted_by']);
        $this->assertSame('n', $hapus['revoked_by']);
    }

    public function test_one_active_membership_per_user_per_authority(): void
    {
        $user = User::factory()->create();
        $badan = Authority::factory()->create();
        AuthorityMember::factory()->for($user)->for($badan)->create();

        $this->expectException(UniqueConstraintViolationException::class);
        AuthorityMember::factory()->for($user)->for($badan)->create();
    }

    public function test_a_revoked_membership_does_not_block_a_new_one(): void
    {
        $user = User::factory()->create();
        $badan = Authority::factory()->create();
        AuthorityMember::factory()->for($user)->for($badan)->create(['revoked_at' => now()]);
        AuthorityMember::factory()->for($user)->for($badan)->create();

        $this->assertSame(2, AuthorityMember::where('user_id', $user->id)->count());
    }

    /**
     * Satu pelanggaran per test: di PostgreSQL galat pertama membatalkan transaksi test, jadi
     * pernyataan sesudahnya gagal karena transaksinya, bukan karena CHECK-nya.
     */
    public function test_an_unknown_role_is_refused(): void
    {
        $keanggotaan = AuthorityMember::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('authority_members')->where('id', $keanggotaan->id)->update(['role' => 'ADMIN']);
    }

    public function test_a_reversed_period_is_refused(): void
    {
        $keanggotaan = AuthorityMember::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('authority_members')->where('id', $keanggotaan->id)->update(['valid_from' => '2026-10-09', 'valid_until' => '2026-10-01']);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='AuthorityMemberPolicy|AuthorityMemberSchema|AuthorityManager|AdminAccess|RepoPolicyFiles'`
Expected: FAIL. Pada simulasi rencana: 46 test, 30 lulus, 11 error, 5 dilewati. Yang merah: kesebelas test `AuthorityMemberPolicyTest` (`AuthorityFactory` dan
`AuthorityMember` belum ada). 5 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction AuthorityMemberRole`, `... make:controller --no-interaction Admin/AuthorityMemberController`, `... make:request --no-interaction Admin/StoreAuthorityMemberRequest`, `... make:model --no-interaction AuthorityMember`, `... make:class --no-interaction Services/DataResmi/Yurisdiksi`, `... make:factory --no-interaction AuthorityFactory`, `... make:factory --no-interaction AuthorityMemberFactory`, dan `... make:migration --no-interaction create_authority_members_table` (ganti nama berkas migrasi hasilnya menjadi `2026_10_09_000200_create_authority_members_table.php`), lalu isinya diganti persis.

Buat `app/Enums/AuthorityMemberRole.php`:

```php
<?php

namespace App\Enums;

/**
 * Peran seseorang di sebuah badan resmi untuk meja kurator (spec v3 bagian 10.7, Q11; ruling
 * W8-R7). Tahap 1: kurator Memount menyalin dari sumber resmi badan itu. Tahap 2: penerbit mitra,
 * petugas badan itu sendiri, sesudah kemitraan. Keduanya tunduk pada aturan terbit asimetris
 * yang sama dan hanya di kawasan badannya.
 */
enum AuthorityMemberRole: string
{
    case KURATOR = 'KURATOR';
    case PENERBIT_MITRA = 'PENERBIT_MITRA';

    public function label(): string
    {
        return match ($this) {
            self::KURATOR => 'Kurator Memount',
            self::PENERBIT_MITRA => 'Penerbit mitra',
        };
    }
}
```

Buat `app/Http/Controllers/Admin/AuthorityMemberController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAuthorityMemberRequest;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pemberian dan pencabutan peran kurator lewat keanggotaan badan resmi (spec v3 bagian 10.7, Q11;
 * ruling W8-R7). Hanya admin. Menambah keanggotaan pertama menjadikan pendaki kurator; mencabut
 * keanggotaan aktif terakhir mengembalikannya menjadi pendaki. Setiap perubahan peran dan
 * keanggotaan tercatat di jejak audit (PRD §61). Tidak ada akun kurator yang dibuat otomatis.
 */
class AuthorityMemberController extends Controller
{
    public function store(StoreAuthorityMemberRequest $request, Authority $authority, AuditLogService $audit): RedirectResponse
    {
        $data = $request->validated();
        $pengguna = $request->pengguna();

        DB::transaction(function () use ($request, $authority, $audit, $data, $pengguna): void {
            $keanggotaan = (new AuthorityMember)->fill([
                'role' => $data['peran'],
                'appointment_reference' => $data['surat'],
                'valid_from' => $data['berlaku_mulai'],
                'valid_until' => $data['berlaku_sampai'] ?? null,
            ])->forceFill([
                'user_id' => $pengguna->id,
                'authority_id' => $authority->id,
                'granted_by' => $request->user()->id,
            ]);
            $keanggotaan->save();
            $audit->record($request->user(), 'authority_member.granted', $keanggotaan, null, [
                'user_id' => $pengguna->id,
                'authority_id' => $authority->id,
                'role' => $data['peran'],
                'appointment_reference' => $data['surat'],
                'valid_from' => $data['berlaku_mulai'],
                'valid_until' => $data['berlaku_sampai'] ?? null,
            ]);

            if ($pengguna->role === UserRole::HIKER) {
                $pengguna->forceFill(['role' => UserRole::CURATOR])->save();
                $audit->record($request->user(), 'user.role_changed', $pengguna, ['role' => UserRole::HIKER->value], ['role' => UserRole::CURATOR->value]);
            }
        });

        return redirect()->route('admin.authorities')->with('status', $pengguna->email.' kini kurator '.$authority->displayName().'.');
    }

    public function destroy(Request $request, Authority $authority, AuthorityMember $member, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('manageAuthorities', Authority::class);

        DB::transaction(function () use ($request, $member, $audit): void {
            $member->forceFill(['revoked_at' => now(), 'revoked_by' => $request->user()->id])->save();
            $audit->recordChange($request->user(), 'authority_member.revoked', $member);

            $pengguna = $member->user;
            $masihAnggota = $pengguna->authorityMemberships()->whereNull('revoked_at')->exists();

            if ($pengguna->role === UserRole::CURATOR && ! $masihAnggota) {
                $pengguna->forceFill(['role' => UserRole::HIKER])->save();
                $audit->record($request->user(), 'user.role_changed', $pengguna, ['role' => UserRole::CURATOR->value], ['role' => UserRole::HIKER->value]);
            }
        });

        return redirect()->route('admin.authorities')->with('status', 'Keanggotaan '.$member->user->email.' di '.$authority->displayName().' dicabut.');
    }
}
```

Buat `app/Http/Requests/Admin/StoreAuthorityMemberRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Enums\AuthorityMemberRole;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Menambah anggota meja kurator ke sebuah badan resmi (ruling W8-R7). Hanya admin; badan dari
 * rute, pengguna dicari dari surel, dan peran pengguna tidak pernah diambil dari isian.
 */
class StoreAuthorityMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAuthorities', Authority::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'peran' => ['required', new Enum(AuthorityMemberRole::class)],
            'surat' => ['required', 'string', 'max:160'],
            'berlaku_mulai' => ['required', 'date_format:Y-m-d'],
            'berlaku_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:berlaku_mulai'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.exists' => 'Belum ada akun dengan surel ini. Minta orangnya mendaftar lebih dulu.',
            'surat.required' => 'Tulis nomor atau keterangan surat penunjukannya.',
            'berlaku_sampai.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $badan = $this->route('authority');
            $pengguna = $this->pengguna();

            if (! $badan instanceof Authority || ! $badan->type->mayDeclareTrailStatus()) {
                $validator->errors()->add('email', 'Badan ini tidak menyatakan status jalur, jadi tidak menaungi kurator.');

                return;
            }

            if (! in_array($pengguna->role, [UserRole::HIKER, UserRole::CURATOR], true)) {
                $validator->errors()->add('email', 'Kurator terpisah dari moderator dan admin. Pakai akun pendaki lain untuk peran ini.');

                return;
            }

            if (AuthorityMember::query()->where('user_id', $pengguna->id)->where('authority_id', $badan->id)->whereNull('revoked_at')->exists()) {
                $validator->errors()->add('email', 'Orang ini sudah menjadi anggota badan ini.');
            }
        }];
    }

    public function pengguna(): User
    {
        return User::where('email', $this->validated('email'))->firstOrFail();
    }
}
```

Buat `app/Models/AuthorityMember.php`:

```php
<?php

namespace App\Models;

use App\Enums\AuthorityMemberRole;
use App\Support\Timezone;
use Database\Factories\AuthorityMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keanggotaan seorang pengguna di sebuah badan resmi untuk meja kurator (spec v3 bagian 10.7;
 * ruling W8-R7). Pengguna, badan, pemberi, dan pencabut ditulis controller admin lewat forceFill,
 * tidak pernah dari isian.
 */
#[Fillable(['role', 'appointment_reference', 'valid_from', 'valid_until'])]
class AuthorityMember extends Model
{
    /** @use HasFactory<AuthorityMemberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => AuthorityMemberRole::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class);
    }

    /**
     * Keanggotaan yang sedang berlaku: belum dicabut dan tanggal hari ini di dalam masa
     * berlakunya. "Hari ini" adalah tanggal WIT, zona Indonesia yang paling dulu berganti hari,
     * jadi masa berlaku tidak pernah terasa lebih panjang di zona mana pun.
     */
    public function scopeAktif(Builder $query): Builder
    {
        $hariIni = Timezone::latestDateInIndonesia();

        return $query
            ->whereNull('revoked_at')
            ->whereDate('valid_from', '<=', $hariIni)
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $hariIni));
    }
}
```

Buat `app/Services/DataResmi/Yurisdiksi.php`:

```php
<?php

namespace App\Services\DataResmi;

use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Yurisdiksi meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 2; ruling W8-R7): kawasan
 * badan resmi tempat seorang kurator menjadi anggota aktif, dan hanya badan yang berwenang
 * menyatakan status jalur (AuthorityType::mayDeclareTrailStatus()). Satu-satunya tempat aturan
 * ini ditulis; OfficialStatusPolicy, Form Request meja kurator, dan pemeriksaan konsistensi
 * memakainya. Peran dan badan selalu dibaca dari basis data untuk pengguna sesi, tidak pernah
 * dari isian.
 *
 * Keanggotaan aktif dimuat sekali per instans pengguna per permintaan.
 */
final class Yurisdiksi
{
    /** @var array<int, Collection<int, AuthorityMember>> */
    private array $muatan = [];

    /**
     * Keanggotaan aktif pengguna di badan yang berwenang menyatakan status, beserta kawasannya.
     * Kosong untuk pengguna yang bukan kurator.
     *
     * @return Collection<int, AuthorityMember>
     */
    public function keanggotaan(User $pengguna): Collection
    {
        if (! $pengguna->isCurator()) {
            return collect();
        }

        return $this->muatan[$pengguna->id] ??= AuthorityMember::query()
            ->aktif()
            ->where('user_id', $pengguna->id)
            ->with('authority.mountains:mountains.id')
            ->get()
            ->filter(fn (AuthorityMember $a) => $a->authority?->type?->mayDeclareTrailStatus() === true)
            ->values();
    }

    public function kuratorAktif(User $pengguna): bool
    {
        return $this->keanggotaan($pengguna)->isNotEmpty();
    }

    /**
     * Id gunung yang berada di bawah badan-badan pengguna.
     *
     * @return Collection<int, int>
     */
    public function gunung(User $pengguna): Collection
    {
        return $this->keanggotaan($pengguna)
            ->flatMap(fn (AuthorityMember $a) => $a->authority->mountains->pluck('id'))
            ->unique()
            ->values();
    }

    /**
     * Gunung tempat sebuah sasaran status berada: gunung itu sendiri, gunung jalur, atau gunung
     * jalur pemilik segmen.
     */
    public static function gunungSasaran(Model $sasaran): ?int
    {
        return match (true) {
            $sasaran instanceof Mountain => $sasaran->id,
            $sasaran instanceof Trail => $sasaran->mountain_id,
            $sasaran instanceof TrailSegment => Trail::whereKey($sasaran->trail_id)->value('mountain_id'),
            default => null,
        };
    }

    /**
     * Badan pengguna yang berwenang atas kawasan sasaran: daftar penerbit yang boleh dipilih di
     * formulir, dan satu-satunya daftar yang diterima server.
     *
     * @return Collection<int, Authority>
     */
    public function otoritasUntuk(User $pengguna, Model $sasaran): Collection
    {
        $gunung = self::gunungSasaran($sasaran);

        if ($gunung === null) {
            return collect();
        }

        return $this->keanggotaan($pengguna)
            ->filter(fn (AuthorityMember $a) => $a->authority->mountains->contains('id', $gunung))
            ->map(fn (AuthorityMember $a) => $a->authority)
            ->unique('id')
            ->values();
    }

    public function berwenang(User $pengguna, Model $sasaran, ?Authority $otoritas = null): bool
    {
        $badan = $this->otoritasUntuk($pengguna, $sasaran);

        return $otoritas === null ? $badan->isNotEmpty() : $badan->contains('id', $otoritas->id);
    }
}
```

Buat `database/factories/AuthorityFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\AuthorityType;
use App\Models\Authority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Authority>
 */
class AuthorityFactory extends Factory
{
    public function definition(): array
    {
        // Tanpa apostrof, alasan yang sama dengan TrailFactory.
        $nama = 'Balai '.str_replace("'", '', fake()->unique()->lastName());

        return [
            'name' => $nama,
            'slug' => Str::slug($nama).'-'.fake()->unique()->numberBetween(1, 99999),
            'type' => AuthorityType::NATIONAL_PARK->value,
            'website' => 'https://contoh.go.id',
            'verified_at' => now(),
        ];
    }
}
```

Buat `database/factories/AuthorityMemberFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\AuthorityMemberRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuthorityMember>
 */
class AuthorityMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'authority_id' => Authority::factory(),
            'role' => AuthorityMemberRole::KURATOR->value,
            'appointment_reference' => 'Surat tugas '.fake()->numerify('###/2026'),
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
        ];
    }
}
```

Buat `database/migrations/2026_10_09_000200_create_authority_members_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anggota badan resmi untuk meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 2; ruling
 * W8-R7): pengguna, badan, peran (kurator Memount atau penerbit mitra), surat penunjukan, masa
 * berlaku, dan pencabutan. Yurisdiksi seorang kurator adalah kawasan badan tempat ia menjadi
 * anggota aktif (authority_mountain).
 *
 * Keanggotaan dicabut, tidak dihapus, supaya catatan status yang pernah dibuatnya tetap dapat
 * ditelusuri; satu keanggotaan aktif per pengguna per badan dijaga indeks unik parsial (SQLite dan
 * PostgreSQL sama-sama mendukungnya). Hapus akun menghapus keanggotaannya (kebijakan data pribadi
 * bagian 2); pemberi dan pencabut menjadi kosong bila akunnya hilang. Di PostgreSQL dua CHECK
 * menjaga peran dan masa berlaku, dan RLS menutup REST API (lapis kedua 2026_09_20_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authority_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('authority_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('appointment_reference', 160);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('authority_id');
            $table->index(['user_id', 'revoked_at']);
            $table->index('granted_by');
            $table->index('revoked_by');
        });

        DB::statement('CREATE UNIQUE INDEX authority_members_aktif_unik ON authority_members (user_id, authority_id) WHERE revoked_at IS NULL');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE authority_members ADD CONSTRAINT authority_members_peran_sah CHECK (role IN ('KURATOR', 'PENERBIT_MITRA'))");
            DB::statement('ALTER TABLE authority_members ADD CONSTRAINT authority_members_masa_sah CHECK (valid_until IS NULL OR valid_until >= valid_from)');
            DB::statement('ALTER TABLE public."authority_members" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('authority_members');
    }
};
```

`app/Enums/UserRole.php`:

1. Ganti:

```php
    case ADMIN = 'admin';

    public function label(): string
```

dengan:

```php
    case ADMIN = 'admin';

    // Kurator data resmi (spec v3 bagian 10.7, ruling W8-R7): terpisah dari moderator dan admin.
    // Peran ini hanya diberikan admin lewat keanggotaan badan resmi (AuthorityMemberController),
    // dan meja kurator tetap menuntut keanggotaan aktif, jadi peran saja tidak membuka apa pun.
    case CURATOR = 'curator';

    public function label(): string
```

2. Ganti:

```php
            self::ADMIN => 'Admin',
        };
```

dengan:

```php
            self::ADMIN => 'Admin',
            self::CURATOR => 'Kurator',
        };
```

`app/Http/Controllers/Admin/AuthorityController.php`:

1. Ganti:

```php
namespace App\Http\Controllers\Admin;

use App\Enums\AuthorityType;
```

dengan:

```php
namespace App\Http\Controllers\Admin;

use App\Enums\AuthorityMemberRole;
use App\Enums\AuthorityType;
```

2. Ganti:

```php
use App\Models\Authority;
use App\Models\Mountain;
```

dengan:

```php
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
```

3. Ganti:

```php
                ->map(fn (Mountain $m) => ['value' => $m->id, 'label' => $m->name])->values()->all(),
        ]);
    }

    public function store(AuthorityRequest $request, AuditLogService $audit): RedirectResponse
```

dengan:

```php
                ->map(fn (Mountain $m) => ['value' => $m->id, 'label' => $m->name])->values()->all(),

            // Anggota meja kurator yang belum dicabut (W8, ruling W8-R7), satu kueri.
            'anggota' => AuthorityMember::query()
                ->whereNull('revoked_at')
                ->with(['user:id,name,email', 'authority:id,name,abbreviation'])
                ->orderBy('authority_id')
                ->orderBy('id')
                ->limit(self::BATAS_ANGGOTA)
                ->get()
                ->map(fn (AuthorityMember $a) => [
                    'id' => $a->id,
                    'badanId' => $a->authority_id,
                    'nama' => $a->user->name,
                    'email' => $a->user->email,
                    'badan' => $a->authority->displayName(),
                    'peran' => $a->role->label(),
                    'surat' => $a->appointment_reference,
                    'berlaku' => $a->valid_until === null
                        ? 'Sejak '.$a->valid_from->translatedFormat('j M Y')
                        : $a->valid_from->translatedFormat('j M Y').' sampai '.$a->valid_until->translatedFormat('j M Y'),
                ])->values()->all(),
            'badanBerwenang' => Authority::query()
                ->whereIn('type', array_map(fn (AuthorityType $t) => $t->value, array_filter(AuthorityType::cases(), fn (AuthorityType $t) => $t->mayDeclareTrailStatus())))
                ->orderBy('name')
                ->get()
                ->map(fn (Authority $b) => ['value' => $b->id, 'label' => $b->displayName()])->values()->all(),
            'peranAnggota' => array_map(fn (AuthorityMemberRole $p) => ['value' => $p->value, 'label' => $p->label()], AuthorityMemberRole::cases()),
        ]);
    }

    /**
     * Daftar anggota di halaman ini; pemilik memberi peran satu per satu, jadi seratus cukup jauh
     * di atas kebutuhan tahap 1.
     */
    private const BATAS_ANGGOTA = 100;

    public function store(AuthorityRequest $request, AuditLogService $audit): RedirectResponse
```

`app/Models/Authority.php`:

1. Ganti:

```php
use App\Enums\AuthorityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
```

dengan:

```php
use App\Enums\AuthorityType;
use Database\Factories\AuthorityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
```

2. Ganti:

```php
{
    use HasFactory;
```

dengan:

```php
{
    /** @use HasFactory<AuthorityFactory> */
    use HasFactory;
```

3. Ganti:

```php
    }

    public function displayName(): string
```

dengan:

```php
    }

    /**
     * Anggota meja kurator badan ini (ruling W8-R7), termasuk yang sudah dicabut.
     */
    public function members(): HasMany
    {
        return $this->hasMany(AuthorityMember::class);
    }

    public function displayName(): string
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

    public function authorityMemberships(): HasMany
    {
        return $this->hasMany(AuthorityMember::class);
    }

    /**
     * Berperan kurator (ruling W8-R7). Meja kurator menuntut juga keanggotaan aktif di badan yang
     * berwenang menyatakan status (OfficialStatusPolicy::viewDesk()), jadi ini saja tidak membuka
     * apa pun.
     */
    public function isCurator(): bool
    {
        return $this->role === UserRole::CURATOR;
    }

    public function isAdmin(): bool
```

`app/Policies/OfficialStatusPolicy.php`:

1. Ganti:

```php
namespace App\Policies;

use App\Models\OfficialStatus;
use App\Models\User;

class OfficialStatusPolicy
{
    public function viewAny(User $user): bool
```

dengan:

```php
namespace App\Policies;

use App\Enums\StatusVerificationState;
use App\Models\Authority;
use App\Models\OfficialStatus;
use App\Models\User;
use App\Services\DataResmi\Yurisdiksi;
use Illuminate\Database\Eloquent\Model;

/**
 * Status resmi (PRD §41; spec v3 bagian 10.7; ruling W8-R7 dan W8-R8).
 *
 * Halaman admin status resmi hanya untuk admin. Meja kurator hanya untuk kurator dengan
 * keanggotaan aktif di badan yang berwenang menyatakan status; mencatat dan memeriksa hanya di
 * kawasan badannya, atas nama badan tempat ia menjadi anggota, dan pemeriksa kedua tidak pernah
 * pencatatnya sendiri. Admin dan moderator bukan kurator.
 */
class OfficialStatusPolicy
{
    public function __construct(private readonly Yurisdiksi $yurisdiksi) {}

    public function viewAny(User $user): bool
```

2. Ganti:

```php
    }
}
```

dengan:

```php
    }

    public function viewDesk(User $user): bool
    {
        return $this->yurisdiksi->kuratorAktif($user);
    }

    /**
     * Mencatat status untuk sebuah gunung, jalur, atau segmen atas nama satu badan.
     */
    public function record(User $user, Model $sasaran, Authority $otoritas): bool
    {
        return $this->yurisdiksi->berwenang($user, $sasaran, $otoritas);
    }

    /**
     * Melihat catatan meja kurator: kurator yang berwenang atas kawasannya.
     */
    public function view(User $user, OfficialStatus $status): bool
    {
        return $status->statusable !== null && $this->yurisdiksi->berwenang($user, $status->statusable);
    }

    /**
     * Mengubah draf: hanya pencatatnya, selama masih draf dan masih berwenang.
     */
    public function updateDraft(User $user, OfficialStatus $status): bool
    {
        return $status->verification_state === StatusVerificationState::DRAFT
            && $status->recorded_by === $user->id
            && $this->view($user, $status);
    }

    /**
     * Verifikasi orang kedua: kurator lain yang berwenang atas kawasannya, untuk catatan yang
     * menunggu verifikasi.
     */
    public function verify(User $user, OfficialStatus $status): bool
    {
        return $status->verification_state === StatusVerificationState::PENDING
            && $status->recorded_by !== $user->id
            && $this->view($user, $status);
    }
}
```

`database/README.md`:

1. Ganti:

```markdown
| Gunung dan jalur | `mountains`, `trails`, `trail_segments`, `checkpoints`, `restricted_areas` | Mountain dan Trail entitas terpisah (R-001); jalur adalah unit keputusan (R-002) |
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain` | Hierarki otoritas (R-009) |
| Kondisi dan kepercayaan data | `official_statuses`, `weather_snapshots`, `trail_condition_reports`, `report_thanks`, `permit_requirements`, `mountain_follows` | Laporan komunitas tidak menimpa status resmi (R-010); cuaca bersumber dan berkesegaran (R-013) |
```

dengan:

```markdown
| Gunung dan jalur | `mountains`, `trails`, `trail_segments`, `checkpoints`, `restricted_areas` | Mountain dan Trail entitas terpisah (R-001); jalur adalah unit keputusan (R-002) |
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain`, `authority_members` | Hierarki otoritas (R-009); anggota meja kurator per badan resmi, dengan masa berlaku dan pencabutan (W8) |
| Kondisi dan kepercayaan data | `official_statuses`, `weather_snapshots`, `trail_condition_reports`, `report_thanks`, `permit_requirements`, `mountain_follows` | Laporan komunitas tidak menimpa status resmi (R-010); cuaca bersumber dan berkesegaran (R-013) |
```

`resources/js/pages/Admin/Authorities.tsx`:

1. Ganti:

```tsx
import { useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
```

dengan:

```tsx
import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
```

2. Ganti:

```tsx
import { store, update } from '@/routes/admin/authorities';
import type { PropsBersama } from '@/types';
```

dengan:

```tsx
import { store, update } from '@/routes/admin/authorities';
import { destroy as membersDestroy, store as membersStore } from '@/routes/admin/authorities/members';
import type { PropsBersama } from '@/types';
```

3. Ganti:

```tsx
}

interface Props extends PropsBersama {
```

dengan:

```tsx
}

interface Anggota {
    id: number;
    badanId: number;
    nama: string;
    email: string;
    badan: string;
    peran: string;
    surat: string;
    berlaku: string;
}

interface IsianAnggota {
    badan: string;
    email: string;
    peran: string;
    surat: string;
    berlaku_mulai: string;
    berlaku_sampai: string;
}

interface Props extends PropsBersama {
```

4. Ganti:

```tsx
    mountains: Array<{ value: number; label: string }>;
}
```

dengan:

```tsx
    mountains: Array<{ value: number; label: string }>;
    anggota: Anggota[];
    badanBerwenang: Array<{ value: number; label: string }>;
    peranAnggota: Opsi[];
}
```

5. Ganti:

```tsx
export default function Authorities() {
    const { flash, badan, types, mountains } = usePage<Props>().props;
    const [terbuka, setTerbuka] = useState(false);
```

dengan:

```tsx
export default function Authorities() {
    const { flash, badan, types, mountains, anggota, badanBerwenang, peranAnggota } = usePage<Props>().props;
    const [terbuka, setTerbuka] = useState(false);
```

6. Ganti:

```tsx
                </div>
            </div>
```

dengan:

```tsx
                </div>

                <AnggotaKurator anggota={anggota} badanBerwenang={badanBerwenang} peranAnggota={peranAnggota} />
            </div>
```

7. Ganti:

```tsx
}

Authorities.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

dengan:

```tsx
}

/**
 * Anggota meja kurator (W8, ruling W8-R7). Kurator terpisah dari moderator dan admin, dan hanya
 * mencatat status di kawasan badannya. Peran diberikan di sini oleh admin, tidak pernah dari isian
 * penggunanya sendiri, dan setiap pemberian serta pencabutan masuk jejak audit.
 */
function AnggotaKurator({ anggota, badanBerwenang, peranAnggota }: Pick<Props, 'anggota' | 'badanBerwenang' | 'peranAnggota'>) {
    const form = useForm<IsianAnggota>({ badan: '', email: '', peran: 'KURATOR', surat: '', berlaku_mulai: '', berlaku_sampai: '' });

    const tambah = (e: FormEvent) => {
        e.preventDefault();
        if (form.data.badan === '') {
            form.setError('badan', 'Pilih badan resmi.');
            return;
        }
        form.post(membersStore.url(Number(form.data.badan)), { preserveScroll: true, onSuccess: () => form.reset('email', 'surat', 'berlaku_mulai', 'berlaku_sampai') });
    };

    return (
        <section aria-labelledby="judul-anggota" className="mt-10">
            <h2 id="judul-anggota" className="text-bagian text-primary">Anggota meja kurator</h2>
            <p className="mt-1 max-w-prose text-kecil text-secondary">
                Kurator menyalin status dari sumber resmi badan ini ke meja kurator, hanya untuk kawasan badannya. Pembatasan boleh terbit oleh satu kurator dengan bukti primer; pembukaan wajib diperiksa kurator kedua. Peran kurator terpisah dari moderator dan admin.
            </p>

            <Card className="mt-4" title="Tambah anggota">
                <form onSubmit={tambah} className="space-y-4">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectField
                            name="badan"
                            label="Badan resmi"
                            required
                            placeholder="Pilih badan"
                            options={badanBerwenang.map((b) => ({ value: String(b.value), label: b.label }))}
                            value={form.data.badan}
                            onChange={(e) => form.setData('badan', e.target.value)}
                            error={form.errors.badan}
                        />
                        <FormField name="email" type="email" label="Surel akun pendaki" required value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} />
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectField name="peran" label="Peran" required options={peranAnggota} value={form.data.peran} onChange={(e) => form.setData('peran', e.target.value)} error={form.errors.peran} />
                        <FormField name="surat" label="Surat penunjukan" required hint="Nomor atau keterangan surat tugasnya." value={form.data.surat} onChange={(e) => form.setData('surat', e.target.value)} error={form.errors.surat} />
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField name="berlaku_mulai" type="date" label="Berlaku mulai" required value={form.data.berlaku_mulai} onChange={(e) => form.setData('berlaku_mulai', e.target.value)} error={form.errors.berlaku_mulai} />
                        <FormField name="berlaku_sampai" type="date" label="Berlaku sampai (opsional)" value={form.data.berlaku_sampai} onChange={(e) => form.setData('berlaku_sampai', e.target.value)} error={form.errors.berlaku_sampai} />
                    </div>
                    <Button type="submit" processing={form.processing}>Tambah anggota</Button>
                </form>
            </Card>

            <div className="mt-6">
                <BingkaiTabel keterangan="Anggota meja kurator yang aktif" kolom={['Nama', 'Badan', 'Peran', 'Berlaku', 'Aksi']}>
                    {anggota.length === 0 ? (
                        <BarisKosong
                            kolom={5}
                            judul="Belum ada kurator"
                            penjelasan="Tanpa kurator, tidak ada yang dapat mencatat status resmi baru. Status lama yang kedaluwarsa tampil sebagai belum diketahui."
                        />
                    ) : (
                        anggota.map((a) => (
                            <tr key={a.id} className="border-b border-subtle align-top">
                                <td className="p-4">
                                    <span className="block font-medium text-primary">{a.nama}</span>
                                    <span className="block break-all text-meta text-muted">{a.email}</span>
                                </td>
                                <td className="p-4 text-secondary">{a.badan}</td>
                                <td className="p-4 text-secondary">
                                    {a.peran}
                                    <span className="block text-meta text-muted">{a.surat}</span>
                                </td>
                                <td className="p-4 text-secondary">{a.berlaku}</td>
                                <td className="p-4">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        onClick={() => router.delete(membersDestroy.url({ authority: a.badanId, member: a.id }), { preserveScroll: true })}
                                        aria-label={`Cabut keanggotaan ${a.nama} di ${a.badan}`}
                                    >
                                        Cabut
                                    </Button>
                                </td>
                            </tr>
                        ))
                    )}
                </BingkaiTabel>
            </div>
        </section>
    );
}

Authorities.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Admin\AuthorityController;
use App\Http\Controllers\Admin\CheckpointController;
```

dengan:

```php
use App\Http\Controllers\Admin\AuthorityController;
use App\Http\Controllers\Admin\AuthorityMemberController;
use App\Http\Controllers\Admin\CheckpointController;
```

2. Ganti:

```php
    Route::put('authorities/{authority}', [AuthorityController::class, 'update'])->name('authorities.update');
    Route::get('credentials', [CredentialController::class, 'index'])->name('credentials');
```

dengan:

```php
    Route::put('authorities/{authority}', [AuthorityController::class, 'update'])->name('authorities.update');
    // Anggota meja kurator (W8, ruling W8-R7): badan dari rute; keanggotaan harus milik badan di URL.
    Route::post('authorities/{authority}/anggota', [AuthorityMemberController::class, 'store'])->name('authorities.members.store');
    Route::delete('authorities/{authority}/anggota/{member}', [AuthorityMemberController::class, 'destroy'])->scopeBindings()->name('authorities.members.destroy');
    Route::get('credentials', [CredentialController::class, 'index'])->name('credentials');
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `admin.authorities.members.store`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/AuthorityMemberRole.php app/Enums/UserRole.php app/Http/Controllers/Admin/AuthorityController.php app/Http/Controllers/Admin/AuthorityMemberController.php app/Http/Requests/Admin/StoreAuthorityMemberRequest.php app/Models/Authority.php app/Models/AuthorityMember.php app/Models/User.php app/Policies/OfficialStatusPolicy.php app/Services/DataResmi/Yurisdiksi.php database/factories/AuthorityFactory.php database/factories/AuthorityMemberFactory.php database/migrations/2026_10_09_000200_create_authority_members_table.php routes/web.php tests/Feature/AuthorityMemberPolicyTest.php tests/Spatial/AuthorityMemberSchemaTest.php`, lalu `php artisan test --compact --filter='AuthorityMemberPolicy|AuthorityMemberSchema|AuthorityManager|AdminAccess|RepoPolicyFiles'`
Expected: PASS. Pada simulasi rencana: 46 test, 41 lulus, 5 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.152 test, 2.150 lulus, 2 dilewati (sebelum tugas: 2.141).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 4: Arsip bukti status resmi

Spec v3 bagian 10.7 (masukan dan arsip), induk W8 butir 6; ruling W8-R9; Konflik 7. `ArsipBukti` menerima PDF (tanda
awal `%PDF-`) atau gambar yang terbaca GD sampai 10 MB, menulis ulang gambar tanpa EXIF dengan sisi terpanjang 2.400 px,
menghitung SHA-256 dari bita yang tersimpan, dan menyimpannya dengan nama acak di map `bukti-status` pada disk
`filesystems.evidence_disk` (`EVIDENCE_DISK`, bawaan disk privat lokal). `EvidenceController` menyajikan arsip hanya
kepada kurator kawasan sasarannya dan admin, sebagai unduhan dengan `Cache-Control: private, no-store` dan `nosniff`,
dan hanya bila jalurnya ditulis layanan (`ArsipBukti::sah()`) dan sidiknya cocok. Server tidak pernah mengambil URL
yang ditempel. Menyajikan data contoh memaksa disk bukti tetap lokal (`PenjagaDataContoh`).

**Files:**
- Create: `app/Http/Controllers/Curator/EvidenceController.php`, `app/Services/DataResmi/ArsipBukti.php`
- Modify: `.env.example`, `app/Policies/OfficialStatusPolicy.php`, `app/Support/PenjagaDataContoh.php`, `config/filesystems.php`, `config/hiking.php`, `resources/js/lib/bukanHalamanReact.json`, `routes/web.php`
- Test: `tests/Feature/EvidenceArchiveTest.php` (baru)

**Interfaces:**
- Consumes: `ImageSanitizer::sanitize(string, int)` dan `PenjagaDataContoh` (sudah ada); `OfficialStatusPolicy` dan `Yurisdiksi` (Tugas 3); kolom `evidence_archive_path` dan `evidence_sha256` (Tugas 2).
- Produces:
  - `App\Services\DataResmi\ArsipBukti`: `MAP = 'bukti-status'`, `aturan(): array` (statis, dipakai Form Request meja), `jenisDariIsi(UploadedFile): ?string` (statis), `simpan(UploadedFile): array{path: string, sha256: string}`, `utuh(OfficialStatus): bool`, `sah(?string): bool` (statis), `disk(): string` (statis);
  - `OfficialStatusPolicy::viewEvidence(User, OfficialStatus): bool`;
  - rute `curator.evidence.show` (GET `kurator/status/{status}/bukti`), terdaftar di `resources/js/lib/bukanHalamanReact.json`;
  - config `filesystems.evidence_disk`, `hiking.uploads.evidence_max_kb` (10240) dan `evidence_dimension` (2400); kunci `.env.example` `EVIDENCE_DISK=local`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction EvidenceArchiveTest`, lalu isinya diganti persis.

Buat `tests/Feature/EvidenceArchiveTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\User;
use App\Services\DataResmi\ArsipBukti;
use App\Support\PenjagaDataContoh;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Arsip bukti status resmi (spec v3 bagian 10.7; rencana induk W8 butir 6; ruling W8-R9).
 *
 * Bukti yang diunggah kurator disimpan di disk privat dengan nama acak. Gambar ditulis ulang tanpa
 * EXIF (tangkapan layar dari ponsel kurator membawa koordinat tempat ia memotret) dan dikecilkan;
 * PDF disimpan apa adanya. SHA-256 dihitung dari bita yang benar-benar tersimpan, dan arsip yang
 * tidak lagi cocok dengan sidiknya tidak pernah disajikan. Hanya kurator yang berwenang atas
 * kawasannya dan admin yang dapat membukanya.
 */
class EvidenceArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ArsipBukti::disk());
    }

    /**
     * JPEG 3000 x 2000 dengan segmen EXIF berisi tag Make (pola ProfilePhotoTest).
     */
    private function gambarDenganExif(string $nama = 'surat-balai-dari-hp-kurator.jpg'): UploadedFile
    {
        $gambar = imagecreatetruecolor(3000, 2000);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 240, 240, 230));
        ob_start();
        imagejpeg($gambar);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);

        $make = "Kurator\0";
        $ifd = pack('v', 1).pack('vvVV', 0x010F, 2, strlen($make), 8 + 2 + 12 + 4).pack('V', 0).$make;
        $muatan = "Exif\0\0"."II\x2a\x00".pack('V', 8).$ifd;
        $exif = "\xFF\xE1".pack('n', strlen($muatan) + 2).$muatan;

        return UploadedFile::fake()->createWithContent($nama, substr($jpeg, 0, 2).$exif.substr($jpeg, 2));
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('SE-4-KSDAE-2026.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
    }

    public function test_an_image_is_rewritten_without_metadata_and_fingerprinted(): void
    {
        $unggahan = $this->gambarDenganExif();
        $this->assertNotFalse(@exif_read_data($unggahan->getRealPath()), 'Berkas uji harus membawa EXIF sebelum diunggah.');

        $arsip = app(ArsipBukti::class)->simpan($unggahan);

        $this->assertTrue(ArsipBukti::sah($arsip['path']));
        $this->assertStringNotContainsStringIgnoringCase('kurator', $arsip['path'], 'Nama berkas acak, bukan dari nama berkas asal.');
        $berkas = Storage::disk(ArsipBukti::disk())->path($arsip['path']);
        [$lebar, $tinggi] = getimagesize($berkas);
        $this->assertSame([2400, 1600], [$lebar, $tinggi]);
        $exif = @exif_read_data($berkas);
        $this->assertTrue($exif === false || ! isset($exif['Make']), 'Metadata tidak boleh tersisa.');
        $this->assertSame(hash_file('sha256', $berkas), $arsip['sha256']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $arsip['sha256']);
    }

    public function test_a_pdf_is_kept_byte_for_byte_and_fingerprinted(): void
    {
        $unggahan = $this->pdf();
        $isi = file_get_contents($unggahan->getRealPath());

        $arsip = app(ArsipBukti::class)->simpan($unggahan);

        $this->assertStringEndsWith('.pdf', $arsip['path']);
        $this->assertSame($isi, Storage::disk(ArsipBukti::disk())->get($arsip['path']));
        $this->assertSame(hash('sha256', $isi), $arsip['sha256']);
    }

    /**
     * Tipe berkas diperiksa dari isinya, bukan dari nama atau tebakan tipe: skrip yang dinamai
     * .jpg, SVG, dan HTML yang dinamai .pdf ditolak sebelum disimpan.
     */
    public function test_only_real_images_and_pdfs_within_the_limit_are_accepted(): void
    {
        $tolak = [
            'skrip berkedok foto' => UploadedFile::fake()->createWithContent('surat.jpg', "<?php system(\$_GET['c']); ?>\n"),
            'svg' => UploadedFile::fake()->createWithContent('surat.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
            'html berkedok pdf' => UploadedFile::fake()->createWithContent('surat.pdf', '<html><script>alert(1)</script></html>'),
            'terlalu besar' => UploadedFile::fake()->create('surat.pdf', 10241, 'application/pdf'),
        ];

        foreach ($tolak as $alasan => $berkas) {
            $this->assertTrue(Validator::make(['bukti' => $berkas], ['bukti' => ArsipBukti::aturan()])->fails(), $alasan);
        }

        $this->assertFalse(Validator::make(['bukti' => $this->pdf()], ['bukti' => ArsipBukti::aturan()])->fails());
        $this->assertFalse(Validator::make(['bukti' => $this->gambarDenganExif()], ['bukti' => ArsipBukti::aturan()])->fails());
        $this->assertSame([], Storage::disk(ArsipBukti::disk())->allFiles());
    }

    public function test_curators_of_the_area_and_admins_download_the_archive_and_nobody_else(): void
    {
        [$status, $kurator] = $this->statusBerarsip();
        $luar = User::factory()->create(['role' => UserRole::CURATOR->value]);
        AuthorityMember::factory()->for($luar)->for(tap(Authority::factory()->create(), fn (Authority $a) => $a->mountains()->attach(Mountain::factory()->create())))->create();

        $this->actingAs($kurator)->get(route('curator.evidence.show', $status))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename=bukti-status-'.$status->id.'.pdf');
        $this->assertStringContainsString('no-store', (string) $this->actingAs($kurator)->get(route('curator.evidence.show', $status))->headers->get('Cache-Control'));
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))->get(route('curator.evidence.show', $status))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('curator.evidence.show', $status))->assertForbidden();
        $this->actingAs($luar)->get(route('curator.evidence.show', $status))->assertForbidden();
        $this->post(route('logout'));
        $this->get(route('curator.evidence.show', $status))->assertRedirect(route('login'));
    }

    public function test_a_tampered_archive_is_never_served(): void
    {
        [$status, $kurator] = $this->statusBerarsip();
        Storage::disk(ArsipBukti::disk())->put($status->evidence_archive_path, "%PDF-1.4\nisi yang diganti\n");

        $this->actingAs($kurator)->get(route('curator.evidence.show', $status))
            ->assertStatus(409)
            ->assertSee('Arsip bukti tidak lagi cocok dengan sidik SHA-256 yang dicatat.');
    }

    public function test_a_path_not_written_by_the_archive_is_never_served(): void
    {
        [$status, $kurator] = $this->statusBerarsip();
        Storage::disk(ArsipBukti::disk())->put('lain/catatan.txt', 'bukan arsip');
        $status->forceFill(['evidence_archive_path' => 'lain/catatan.txt', 'evidence_sha256' => hash('sha256', 'bukan arsip')])->save();

        $this->actingAs($kurator)->get(route('curator.evidence.show', $status))->assertNotFound();
    }

    /**
     * Saat data contoh disajikan, disk arsip bukti diputus dari .env seperti disk foto laporan
     * (tinjauan akhir W1, I-3). Disk foto profil W7 belum ikut diputus; ia ditutup di sini juga.
     */
    public function test_serving_sample_data_keeps_uploads_off_production_disks(): void
    {
        config(['filesystems.evidence_disk' => 'supabase', 'filesystems.avatars_disk' => 'supabase']);

        app(PenjagaDataContoh::class)->putuskanSaluranLuar();

        $this->assertSame('local', config('filesystems.evidence_disk'));
        $this->assertSame('local', config('filesystems.avatars_disk'));
    }

    /**
     * @return array{0: OfficialStatus, 1: User}
     */
    private function statusBerarsip(): array
    {
        $gunung = Mountain::factory()->create();
        $badan = Authority::factory()->create();
        $badan->mountains()->attach($gunung);
        $kurator = User::factory()->create(['role' => UserRole::CURATOR->value]);
        AuthorityMember::factory()->for($kurator)->for($badan)->create();

        $arsip = app(ArsipBukti::class)->simpan($this->pdf());
        $status = OfficialStatus::factory()->create(['statusable_type' => $gunung->getMorphClass(), 'statusable_id' => $gunung->id, 'scope' => 'MOUNTAIN']);
        $status->forceFill(['evidence_archive_path' => $arsip['path'], 'evidence_sha256' => $arsip['sha256'], 'authority_id' => $badan->id])->save();

        return [$status->fresh(), $kurator];
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='EvidenceArchive|InertiaLinkRegistry|DemoCommandGuard'`
Expected: FAIL. Pada simulasi rencana: 23 test, 15 lulus, 7 error, 1 dilewati. Yang merah: ketujuh test `EvidenceArchiveTest` (`ArsipBukti` belum ada).

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:controller --no-interaction Curator/EvidenceController` dan `... make:class --no-interaction Services/DataResmi/ArsipBukti`, lalu isinya diganti persis.

Buat `app/Http/Controllers/Curator/EvidenceController.php`:

```php
<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Models\OfficialStatus;
use App\Services\DataResmi\ArsipBukti;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Arsip bukti satu catatan status resmi (rencana induk W8 butir 6; ruling W8-R9). Hanya kurator
 * yang berwenang atas kawasannya dan admin; berkas selalu diunduh (bukan ditampilkan sebagai
 * halaman), tanpa tebakan tipe, tanpa cache, dan hanya bila sidiknya cocok.
 */
class EvidenceController extends Controller
{
    public function show(OfficialStatus $status, ArsipBukti $arsip): Response
    {
        $this->authorize('viewEvidence', $status);

        $path = $status->evidence_archive_path;
        abort_if(! ArsipBukti::sah($path) || ! Storage::disk(ArsipBukti::disk())->exists($path), 404);
        abort_unless($arsip->utuh($status), 409, 'Arsip bukti tidak lagi cocok dengan sidik SHA-256 yang dicatat.');

        return Storage::disk(ArsipBukti::disk())->download($path, 'bukti-status-'.$status->id.'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
```

Buat `app/Services/DataResmi/ArsipBukti.php`:

```php
<?php

namespace App\Services\DataResmi;

use App\Models\OfficialStatus;
use App\Support\ImageSanitizer;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Arsip bukti status resmi (spec v3 bagian 10.7; rencana induk W8 butir 6; ruling W8-R9).
 *
 * Kurator mengunggah salinan surat, laman, atau unggahan akun resmi pengelola. Tipe berkas
 * diperiksa dari isinya (tanda awal PDF, atau gambar yang terbaca GD), bukan dari nama atau
 * tebakan tipe. Gambar ditulis ulang lebih dulu tanpa EXIF dan dikecilkan (ImageSanitizer, PRD
 * §81 dan §82); PDF disimpan apa adanya. SHA-256 dihitung dari bita yang tersimpan, nama berkas
 * acak di disk privat yang dapat diganti (filesystems.evidence_disk), dan berkas hanya disajikan
 * lewat rute curator.evidence.show sesudah sidiknya cocok.
 *
 * Arsip otomatis dari URL yang ditempel tidak dibangun: mengambil alamat sembarang dari server
 * membuka SSRF, jadi URL disimpan sebagai rujukan dan salinannya diunggah kurator (Konflik
 * koordinator, ruling W8-R9).
 */
final class ArsipBukti
{
    public const MAP = 'bukti-status';

    private const GAMBAR = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Aturan validasi berkas bukti, dipakai Form Request meja kurator.
     *
     * @return array<int, mixed>
     */
    public static function aturan(): array
    {
        return [
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:'.(int) config('hiking.uploads.evidence_max_kb'),
            function (string $atribut, mixed $berkas, Closure $gagal): void {
                if ($berkas instanceof UploadedFile && self::jenisDariIsi($berkas) === null) {
                    $gagal('Bukti harus berupa PDF atau foto JPG, PNG, atau WebP.');
                }
            },
        ];
    }

    /**
     * 'pdf' atau 'gambar' menurut isi berkas, atau null bila bukan keduanya.
     */
    public static function jenisDariIsi(UploadedFile $berkas): ?string
    {
        $path = (string) $berkas->getRealPath();

        if ($path === '' || ! is_readable($path)) {
            return null;
        }

        $pegangan = @fopen($path, 'rb');
        $awal = $pegangan === false ? '' : (string) fread($pegangan, 5);

        if ($pegangan !== false) {
            fclose($pegangan);
        }

        $ekstensi = strtolower($berkas->getClientOriginalExtension());

        if ($awal === '%PDF-' && $ekstensi === 'pdf') {
            return 'pdf';
        }

        if (in_array($ekstensi, self::GAMBAR, true) && @getimagesize($path) !== false) {
            return 'gambar';
        }

        return null;
    }

    /**
     * @return array{path: string, sha256: string}
     */
    public function simpan(UploadedFile $berkas): array
    {
        $jenis = self::jenisDariIsi($berkas);

        if ($jenis === null) {
            throw ValidationException::withMessages(['bukti' => 'Bukti harus berupa PDF atau foto JPG, PNG, atau WebP.']);
        }

        if ($jenis === 'gambar') {
            try {
                ImageSanitizer::sanitize((string) $berkas->getRealPath(), (int) config('hiking.uploads.evidence_dimension'));
            } catch (RuntimeException) {
                throw ValidationException::withMessages(['bukti' => 'Foto bukti tidak dapat diproses. Unggah PDF atau foto lain.']);
            }
        }

        $sha256 = (string) hash_file('sha256', (string) $berkas->getRealPath());
        $path = $berkas->store(self::MAP, self::disk());

        if ($path === false) {
            throw ValidationException::withMessages(['bukti' => 'Bukti gagal disimpan. Coba lagi.']);
        }

        return ['path' => $path, 'sha256' => $sha256];
    }

    /**
     * Arsip catatan ini masih ada dan bita-bitanya masih cocok dengan sidik yang dicatat.
     */
    public function utuh(OfficialStatus $status): bool
    {
        $path = $status->evidence_archive_path;
        $disk = Storage::disk(self::disk());

        return self::sah($path)
            && $status->evidence_sha256 !== null
            && $disk->exists($path)
            && hash_equals($status->evidence_sha256, hash('sha256', (string) $disk->get($path)));
    }

    /**
     * Hanya berkas yang ditulis layanan ini: satu nama acak langsung di dalam map arsip.
     */
    public static function sah(?string $path): bool
    {
        return $path !== null && preg_match('#^'.self::MAP.'/[A-Za-z0-9]+\.(pdf|jpe?g|png|webp)$#', $path) === 1;
    }

    public static function disk(): string
    {
        return (string) config('filesystems.evidence_disk');
    }
}
```

`.env.example`:

1. Ganti:

```
AVATARS_DISK=local
SUPABASE_STORAGE_ENDPOINT=
```

dengan:

```
AVATARS_DISK=local
EVIDENCE_DISK=local
SUPABASE_STORAGE_ENDPOINT=
```

`app/Policies/OfficialStatusPolicy.php`:

1. Ganti:

```php
    /**
     * Mengubah draf: hanya pencatatnya, selama masih draf dan masih berwenang.
```

dengan:

```php
    /**
     * Arsip bukti: kurator yang berwenang atas kawasannya, dan admin untuk tata kelola.
     */
    public function viewEvidence(User $user, OfficialStatus $status): bool
    {
        return $user->isAdmin() || $this->view($user, $status);
    }

    /**
     * Mengubah draf: hanya pencatatnya, selama masih draf dan masih berwenang.
```

`app/Support/PenjagaDataContoh.php`:

1. Ganti:

```php
        config(['filesystems.report_photos_disk' => 'local']);

        // Tinjauan W1-9b #2: config/broadcasting.php tidak ada di config/ aplikasi, tapi
```

dengan:

```php
        config(['filesystems.report_photos_disk' => 'local']);

        // Disk foto profil (W7) dan arsip bukti status resmi (W8) punya kunci .env sendiri juga.
        config(['filesystems.avatars_disk' => 'local', 'filesystems.evidence_disk' => 'local']);

        // Tinjauan W1-9b #2: config/broadcasting.php tidak ada di config/ aplikasi, tapi
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
    | Evidence Archive Disk
    |--------------------------------------------------------------------------
    |
    | Disk arsip bukti status resmi (W8, spec v3 bagian 10.7). Bawaannya disk privat
    | lokal; berkasnya tidak pernah publik dan hanya diunduh lewat rute
    | curator.evidence.show sesudah sidik SHA-256-nya cocok.
    |
    */

    'evidence_disk' => env('EVIDENCE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
```

`config/hiking.php`:

1. Ganti:

```php
        'avatar_dimension' => 512,

        'gpx_max_kb' => 8192,
```

dengan:

```php
        'avatar_dimension' => 512,

        // Arsip bukti status resmi (W8): PDF surat atau foto unggahan pengelola. Foto ditulis
        // ulang tanpa EXIF dengan sisi terpanjang ini, cukup untuk membaca nomor surat.
        'evidence_max_kb' => 10240,
        'evidence_dimension' => 2400,

        'gpx_max_kb' => 8192,
```

`resources/js/lib/bukanHalamanReact.json`:

1. Ganti:

```json
        "^/reports/[^/]+/photo$",
        "^/profile/foto$",
```

dengan:

```json
        "^/reports/[^/]+/photo$",
        "^/kurator/status/[^/]+/bukti$",
        "^/profile/foto$",
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Community\TrailNewsController;
use App\Http\Controllers\DashboardController;
```

dengan:

```php
use App\Http\Controllers\Community\TrailNewsController;
use App\Http\Controllers\Curator\EvidenceController;
use App\Http\Controllers\DashboardController;
```

2. Ganti:

```php
    });
});
```

dengan:

```php
    });

    /*
     * Meja kurator data resmi (W8, spec v3 bagian 10.7). Tanpa middleware peran, karena admin
     * lolos middleware role apa pun padahal kurator terpisah dari admin; setiap aksi dijaga
     * OfficialStatusPolicy (keanggotaan aktif dan yurisdiksi).
     */
    Route::get('kurator/status/{status}/bukti', [EvidenceController::class, 'show'])->whereNumber('status')->name('curator.evidence.show');
});
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `curator.evidence.show`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Curator/EvidenceController.php app/Policies/OfficialStatusPolicy.php app/Services/DataResmi/ArsipBukti.php app/Support/PenjagaDataContoh.php config/filesystems.php config/hiking.php routes/web.php tests/Feature/EvidenceArchiveTest.php`, lalu `php artisan test --compact --filter='EvidenceArchive|InertiaLinkRegistry|DemoCommandGuard'`
Expected: PASS. Pada simulasi rencana: 23 test, 22 lulus, 1 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.159 test, 2.157 lulus, 2 dilewati (sebelum tugas: 2.152).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 5: Layanan terbit asimetris dan rantai penggantian

Spec v3 bagian 10.7 (aturan terbit asimetris, otorisasi, langkah 4 dan 5), induk W8 butir 3; ruling W8-R6, W8-R10,
W8-R11. `TerbitStatus` menjadi satu-satunya jalan tulis status resmi baru: simpan, ubah, dan hapus draf hanya oleh
pencatatnya; kirim menjadikan draf menunggu verifikasi kedua, dengan pembatasan berbukti primer terbit seketika dan
pembukaan atau bukti berita menunggu pemeriksa kedua; berita yang membuka ditolak; verifikasi oleh kurator lain di
kawasan yang sama; persoalkan menyembunyikan pembukaan yang belum diverifikasi tanpa menghidupkan catatan lama. Saat
sebuah catatan mulai terlihat, catatan terlihat lain untuk sasaran yang sama yang sudah mulai sebelumnya ditandai
digantikan dan berakhir pada tanggal mulai catatan baru. Sumber data diturunkan dari jenis bukti (`OFFICIAL` untuk bukti
primer, `ADMIN_VERIFIED` "Pemberitaan atas pengumuman ..." untuk berita). Pencatat, penerbit, pemeriksa, dan keadaan
selalu dari sesi dan Policy, dan setiap langkah diaudit dengan nilai sebelum dan sesudah. Test meja kurator memakai
satu trait penyiapan bersama, `tests/Concerns/MejaKurator`.

**Files:**
- Create: `app/Services/DataResmi/TerbitStatus.php`
- Modify: `app/Models/OfficialStatus.php`, `app/Policies/OfficialStatusPolicy.php`, `app/Services/DataFreshnessService.php`
- Test: `tests/Concerns/MejaKurator.php` (baru), `tests/Feature/AsymmetricPublishRuleTest.php` (baru), `tests/Feature/AuditLogChangeTest.php`, `tests/Feature/OfficialStatusSupersedeTest.php` (baru), `tests/Feature/StatusCacheInvalidationTest.php`

**Interfaces:**
- Consumes: `Yurisdiksi` dan `OfficialStatusPolicy` (Tugas 3); `ArsipBukti` (Tugas 4); keterlihatan dan kolom provenans (Tugas 2); `OfficialStatusService` (Tugas 1); `AuditLogService::record()` dan `recordChange()`; `SourceType::OFFICIAL` dan `ADMIN_VERIFIED`.
- Produces:
  - `App\Services\DataResmi\TerbitStatus` (konstruktor `AuditLogService`, `ArsipBukti`, `OfficialStatusService`): `buatDraf(User, Model $sasaran, Authority, array $isian, ?UploadedFile $bukti): OfficialStatus`, `ubahDraf(User, OfficialStatus, array, ?UploadedFile): OfficialStatus`, `hapusDraf(User, OfficialStatus): void`, `kirim(User, OfficialStatus): OfficialStatus`, `verifikasi(User, OfficialStatus): OfficialStatus`, `persoalkan(User, OfficialStatus, string $alasan): OfficialStatus`, `membuka(OfficialStatus): bool` dan `tingkat(OfficialStatusValue): int` (statis; keduanya pindah ke `ArahPerubahan` di Tugas 6), `cakupan(Model): string` (statis); galat `ValidationException` dengan kunci `bukti`, `evidence_type`, atau `konsistensi`;
  - `OfficialStatus::scopeDimulaiPalingLambat(Builder, CarbonInterface): Builder`; `OfficialStatusPolicy::dispute(User, OfficialStatus): bool`;
  - trait `Tests\Concerns\MejaKurator`: `siapkanKawasan()`, `kurator(?Authority): User`, `isianStatus(OfficialStatusValue, array): array`, `suratPdf(): UploadedFile`, `draf(User, OfficialStatusValue, array, ?Model): OfficialStatus`, `catatanLama(OfficialStatusValue, array, ?Model): OfficialStatus`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction AsymmetricPublishRuleTest` dan `... make:test --phpunit --no-interaction OfficialStatusSupersedeTest`, lalu isinya diganti persis. Berkas lain, `tests/Concerns/MejaKurator.php`, dibuat langsung. `AuditLogChangeTest` dan `StatusCacheInvalidationTest`
masing-masing mendapat satu test baru untuk penggantian dan verifikasi pembukaan.

Buat `tests/Concerns/MejaKurator.php`:

```php
<?php

namespace Tests\Concerns;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Penyiapan bersama test meja kurator W8: satu gunung di bawah satu balai, kurator anggota balai
 * itu, dan isian catatan status yang lengkap. Pengguna dan badan selalu dibuat lewat pabrik,
 * bukan diambil dari isian (ruling W8-R7).
 */
trait MejaKurator
{
    protected Mountain $gunung;

    protected Authority $balai;

    protected function siapkanKawasan(): void
    {
        $this->gunung = Mountain::factory()->create(['name' => 'Gunung Gede', 'timezone' => 'Asia/Jakarta']);
        $this->balai = Authority::factory()->create(['name' => 'Balai Besar TN Gede Pangrango', 'abbreviation' => 'BBTNGGP']);
        $this->balai->mountains()->attach($this->gunung);
    }

    protected function kurator(?Authority $badan = null): User
    {
        $user = User::factory()->create(['role' => UserRole::CURATOR->value]);
        AuthorityMember::factory()->for($user)->for($badan ?? $this->balai)->create();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    protected function isianStatus(OfficialStatusValue $nilai, array $ganti = []): array
    {
        return [
            'status' => $nilai->value,
            'document_number' => 'SE.4/KSDAE/PJLHK/2026',
            'document_date' => now()->subDays(2)->toDateString(),
            'evidence_type' => EvidenceType::OFFICIAL_LETTER->value,
            'source_url' => 'https://gedepangrango.org/pengumuman',
            'captured_at' => now()->subHour(),
            'effective_at' => now()->subHour(),
            'end_condition' => StatusEndCondition::UNTIL_NOTICE->value,
            'expires_at' => null,
            'reason_category' => StatusReasonCategory::FIRE->value,
            'reason' => 'Kebakaran hutan di sekitar jalur.',
            'limit_description' => null,
            'legal_basis' => null,
            'official_excerpt' => 'Pendakian ditutup sampai pemberitahuan lebih lanjut.',
            'plain_summary' => 'Pendakian ditutup karena kebakaran hutan.',
            ...$ganti,
        ];
    }

    protected function suratPdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat-balai.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n");
    }

    /**
     * Draf berbukti PDF di kawasan uji, dicatat kurator yang diberikan.
     *
     * @param  array<string, mixed>  $ganti
     */
    protected function draf(User $kurator, OfficialStatusValue $nilai, array $ganti = [], ?Model $sasaran = null): OfficialStatus
    {
        return app(TerbitStatus::class)->buatDraf($kurator, $sasaran ?? $this->gunung, $this->balai, $this->isianStatus($nilai, $ganti), $this->suratPdf());
    }

    /**
     * Catatan lama dari sebelum meja kurator (keadaan verifikasi kosong).
     *
     * @param  array<string, mixed>  $atribut
     */
    protected function catatanLama(OfficialStatusValue $nilai, array $atribut = [], ?Model $sasaran = null): OfficialStatus
    {
        $sasaran ??= $this->gunung;

        return OfficialStatus::factory()->create([
            'statusable_type' => $sasaran->getMorphClass(),
            'statusable_id' => $sasaran->getKey(),
            'scope' => TerbitStatus::cakupan($sasaran),
            'status' => $nilai->value,
            'effective_at' => now()->subDays(10),
            ...$atribut,
        ]);
    }
}
```

Buat `tests/Feature/AsymmetricPublishRuleTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusVerificationState;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Aturan terbit asimetris (spec v3 bagian 10.7; rencana induk W8 butir 3; Q11 dibangun menurut
 * spec, ruling W8-R6 dan W8-R10).
 *
 * Perubahan yang membatasi boleh terbit oleh satu kurator dengan bukti primer, bertanda menunggu
 * verifikasi kedua. Perubahan yang membuka (Buka, atau lebih longgar dari status yang berlaku pada
 * tanggal mulainya) wajib diverifikasi kurator kedua sebelum pendaki melihatnya. Berita media saja
 * tidak pernah dapat membuka jalur. Aturan ini ditegakkan di lapisan layanan (TerbitStatus), jadi
 * jalan tulis mana pun memakai aturan yang sama; pencatat, pemeriksa, dan penerbit selalu dari sesi
 * dan Policy.
 */
class AsymmetricPublishRuleTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    private function layanan(): TerbitStatus
    {
        return app(TerbitStatus::class);
    }

    private function nilaiUntukPendaki(): OfficialStatusValue
    {
        return app(OfficialStatusService::class)->currentValueFor($this->gunung);
    }

    public function test_a_restriction_with_primary_evidence_publishes_at_once_awaiting_a_second_check(): void
    {
        $this->catatanLama(OfficialStatusValue::OPEN);
        $kurator = $this->kurator();
        $draf = $this->draf($kurator, OfficialStatusValue::CLOSED);

        $this->assertSame(OfficialStatusValue::OPEN, $this->nilaiUntukPendaki(), 'Draf tidak pernah terlihat.');

        $terbit = $this->layanan()->kirim($kurator, $draf);

        $this->assertSame(StatusVerificationState::PENDING, $terbit->verification_state);
        $this->assertTrue($terbit->menungguVerifikasiKedua());
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilaiUntukPendaki());
        $this->assertSame(SourceType::OFFICIAL, $terbit->dataSource->source_type, 'Bukti dari pengelola dikutip sebagai Resmi (R-009).');
        $this->assertSame('Balai Besar TN Gede Pangrango (BBTNGGP)', $terbit->source);
    }

    public function test_an_opening_waits_for_a_second_curator(): void
    {
        $tutup = $this->catatanLama(OfficialStatusValue::CLOSED);
        $pencatat = $this->kurator();
        $pemeriksa = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::OPEN, ['reason_category' => null, 'reason' => null, 'official_excerpt' => 'Pendakian dibuka kembali.', 'plain_summary' => 'Pendakian dibuka kembali.']);

        $menunggu = $this->layanan()->kirim($pencatat, $draf);

        $this->assertSame(StatusVerificationState::PENDING, $menunggu->verification_state);
        $this->assertFalse($menunggu->terlihatPendaki());
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilaiUntukPendaki(), 'Pembukaan belum terlihat sebelum orang kedua memeriksa.');

        $terverifikasi = $this->layanan()->verifikasi($pemeriksa, $menunggu);

        $this->assertSame(StatusVerificationState::VERIFIED, $terverifikasi->verification_state);
        $this->assertSame($pemeriksa->id, $terverifikasi->verified_by);
        $this->assertSame(OfficialStatusValue::OPEN, $this->nilaiUntukPendaki());
        $this->assertSame($terverifikasi->id, $tutup->fresh()->superseded_by_id);
    }

    /**
     * Membuka sebagian penutupan juga membuka: Dibatasi sesudah Tutup tidak terbit sebelum
     * diperiksa orang kedua.
     */
    public function test_loosening_a_closure_to_a_restriction_also_waits(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $kurator = $this->kurator();

        $menunggu = $this->layanan()->kirim($kurator, $this->draf($kurator, OfficialStatusValue::RESTRICTED, ['limit_description' => 'Sampai Kandang Badak']));

        $this->assertFalse($menunggu->terlihatPendaki());
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilaiUntukPendaki());
    }

    public function test_news_alone_never_opens_a_trail(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $kurator = $this->kurator();
        $buka = $this->draf($kurator, OfficialStatusValue::OPEN, ['evidence_type' => EvidenceType::NEWS->value]);

        try {
            $this->layanan()->kirim($kurator, $buka);
            $this->fail('Pembukaan berbukti berita harus ditolak.');
        } catch (ValidationException $galat) {
            $this->assertSame(['Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.'], $galat->errors()['evidence_type']);
        }

        $this->assertSame(StatusVerificationState::DRAFT, $buka->fresh()->verification_state);
    }

    /**
     * Pembatasan yang hanya berbukti berita tidak terbit lebih dulu; ia menunggu orang kedua, dan
     * dikutip sebagai terverifikasi admin, bukan Resmi.
     */
    public function test_a_restriction_from_news_waits_and_is_not_cited_as_official(): void
    {
        $this->catatanLama(OfficialStatusValue::OPEN);
        $pencatat = $this->kurator();
        $menunggu = $this->layanan()->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::CLOSED, ['evidence_type' => EvidenceType::NEWS->value]));

        $this->assertFalse($menunggu->terlihatPendaki());
        $this->assertSame(SourceType::ADMIN_VERIFIED, $menunggu->dataSource->source_type);

        $this->layanan()->verifikasi($this->kurator(), $menunggu);

        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilaiUntukPendaki());
    }

    public function test_the_recorder_never_verifies_their_own_record(): void
    {
        $kurator = $this->kurator();
        $menunggu = $this->layanan()->kirim($kurator, $this->draf($kurator, OfficialStatusValue::OPEN));

        $this->expectException(AuthorizationException::class);
        $this->layanan()->verifikasi($kurator, $menunggu);
    }

    /**
     * Pencatat, penerbit, pemeriksa, dan keadaan tidak pernah diambil dari isian, walau isiannya
     * menyelundupkannya.
     */
    public function test_who_and_on_whose_behalf_come_from_the_session(): void
    {
        $kurator = $this->kurator();
        $lain = User::factory()->create();
        $badanLain = Authority::factory()->create();

        $draf = $this->layanan()->buatDraf($kurator, $this->gunung, $this->balai, $this->isianStatus(OfficialStatusValue::CLOSED, [
            'recorded_by' => $lain->id,
            'verified_by' => $lain->id,
            'authority_id' => $badanLain->id,
            'verification_state' => StatusVerificationState::VERIFIED->value,
            'visible_before_verification' => true,
            'superseded_by_id' => 99,
            'evidence_sha256' => str_repeat('a', 64),
        ]), $this->suratPdf());

        $this->assertSame($kurator->id, $draf->recorded_by);
        $this->assertNull($draf->verified_by);
        $this->assertSame($this->balai->id, $draf->authority_id);
        $this->assertSame(StatusVerificationState::DRAFT, $draf->verification_state);
        $this->assertFalse($draf->visible_before_verification);
        $this->assertNull($draf->superseded_by_id);
        $this->assertSame(hash('sha256', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n"), $draf->evidence_sha256);
    }

    public function test_a_curator_cannot_draft_outside_their_area_or_for_another_body(): void
    {
        $kurator = $this->kurator();

        try {
            $this->layanan()->buatDraf($kurator, Mountain::factory()->create(), $this->balai, $this->isianStatus(OfficialStatusValue::CLOSED), null);
            $this->fail('Di luar kawasan badannya harus ditolak.');
        } catch (AuthorizationException) {
            // Diharapkan.
        }

        $this->expectException(AuthorizationException::class);
        $this->layanan()->buatDraf($kurator, $this->gunung, tap(Authority::factory()->create(), fn (Authority $a) => $a->mountains()->attach($this->gunung)), $this->isianStatus(OfficialStatusValue::CLOSED), null);
    }

    public function test_submitting_needs_an_archived_copy(): void
    {
        $kurator = $this->kurator();
        $draf = $this->layanan()->buatDraf($kurator, $this->gunung, $this->balai, $this->isianStatus(OfficialStatusValue::CLOSED), null);

        try {
            $this->layanan()->kirim($kurator, $draf);
            $this->fail('Kiriman tanpa arsip bukti harus ditolak.');
        } catch (ValidationException $galat) {
            $this->assertArrayHasKey('bukti', $galat->errors());
        }

        $this->assertSame(StatusVerificationState::DRAFT, $draf->fresh()->verification_state);
    }

    public function test_every_transition_is_audited_with_before_and_after(): void
    {
        $pencatat = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::CLOSED);
        $menunggu = $this->layanan()->kirim($pencatat, $draf);
        $this->layanan()->verifikasi($pemeriksa = $this->kurator(), $menunggu);
        $this->layanan()->persoalkan($pemeriksa, $menunggu->fresh(), 'Nomor surat tidak cocok dengan laman balai.');

        $tindakan = AuditLog::where('entity_id', $draf->id)->orderBy('id')->pluck('action')->all();
        $this->assertSame(['official_status.drafted', 'official_status.submitted', 'official_status.verified', 'official_status.disputed'], $tindakan);

        $kirim = AuditLog::where('action', 'official_status.submitted')->sole();
        $this->assertSame('DRAFT', $kirim->before['verification_state']);
        $this->assertSame('PENDING', $kirim->after['verification_state']);
        $this->assertTrue($kirim->after['visible_before_verification']);

        $soal = AuditLog::where('action', 'official_status.disputed')->sole();
        $this->assertSame('VERIFIED', $soal->before['verification_state']);
        $this->assertSame('Nomor surat tidak cocok dengan laman balai.', $soal->after['alasan']);
        $this->assertSame($pemeriksa->id, $soal->actor_id);
    }

    /**
     * Pembukaan terverifikasi yang kemudian dipersoalkan tidak lagi dibaca pendaki, dan penutupan
     * yang digantikannya tidak hidup kembali: statusnya Belum diketahui, tidak pernah Buka.
     */
    public function test_disputing_a_verified_opening_hides_it_without_reviving_the_old_record(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $pencatat = $this->kurator();
        $menunggu = $this->layanan()->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::OPEN));
        $terverifikasi = $this->layanan()->verifikasi($this->kurator(), $menunggu);

        $this->layanan()->persoalkan($pencatat, $terverifikasi, 'Laman balai sudah menarik pengumumannya.');

        $this->assertSame(StatusVerificationState::DISPUTED, $terverifikasi->fresh()->verification_state);
        $this->assertSame(OfficialStatusValue::UNKNOWN, $this->nilaiUntukPendaki());
    }

    public function test_a_draft_can_be_corrected_or_withdrawn_by_its_recorder_only(): void
    {
        $pencatat = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::CLOSED);
        $pathLama = $draf->evidence_archive_path;

        $diubah = $this->layanan()->ubahDraf($pencatat, $draf, $this->isianStatus(OfficialStatusValue::RESTRICTED, ['limit_description' => 'Sampai Pos Kandang Badak']), $this->suratPdf());
        $this->assertSame(OfficialStatusValue::RESTRICTED, $diubah->status);
        $this->assertNotSame($pathLama, $diubah->evidence_archive_path);
        Storage::disk(config('filesystems.evidence_disk'))->assertMissing($pathLama);

        try {
            $this->layanan()->hapusDraf($this->kurator(), $diubah);
            $this->fail('Kurator lain tidak boleh membuang draf orang lain.');
        } catch (AuthorizationException) {
            // Diharapkan.
        }

        $this->layanan()->hapusDraf($pencatat, $diubah);
        $this->assertModelMissing($diubah);
        Storage::disk(config('filesystems.evidence_disk'))->assertMissing($diubah->evidence_archive_path);
        $this->assertSame(0, OfficialStatus::count());
    }
}
```

Buat `tests/Feature/OfficialStatusSupersedeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Services\DataFreshnessService;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Rantai penggantian status resmi (spec v3 bagian 10.7; rencana induk W8 butir 3; ruling W8-R11).
 *
 * Saat sebuah catatan mulai terlihat pendaki, catatan lain untuk sasaran yang sama yang sudah
 * mulai berlaku sebelum tanggal mulainya ditandai digantikan dan berakhir pada tanggal itu.
 * Catatan yang baru berlaku sesudahnya (pengumuman ke depan) tidak disentuh. Catatan yang
 * digantikan tidak pernah hidup kembali: sesudah catatan baru berakhir, statusnya Belum
 * diketahui sampai ada catatan berikutnya (R-006, R-007). Tanggal berakhir lama tetap terbaca di
 * jejak audit.
 */
class OfficialStatusSupersedeTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    private function nilai(): OfficialStatusValue
    {
        return app(OfficialStatusService::class)->currentValueFor($this->gunung);
    }

    public function test_publishing_supersedes_older_records_from_its_start(): void
    {
        $lama = $this->catatanLama(OfficialStatusValue::OPEN);
        $kurator = $this->kurator();

        $baru = app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::CLOSED));

        $this->assertSame($baru->id, $lama->fresh()->superseded_by_id);
        $this->assertTrue($lama->fresh()->expires_at->equalTo($baru->effective_at));
        $this->assertSame($lama->id, $baru->replaces->id);
        $this->assertSame($baru->id, $lama->fresh()->supersededBy->id);
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilai());
    }

    /**
     * Penutupan yang diumumkan untuk tanggal yang belum tiba tidak memotong status yang berlaku
     * hari ini lebih awal; sesudah penutupan itu berakhir, status lama tidak hidup kembali.
     */
    public function test_a_future_record_keeps_the_current_one_until_it_starts_and_never_revives_it(): void
    {
        $this->catatanLama(OfficialStatusValue::OPEN);
        $kurator = $this->kurator();
        app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::CLOSED, [
            'effective_at' => now()->addDays(3)->startOfDay(),
            'end_condition' => StatusEndCondition::ON_DATE->value,
            'expires_at' => now()->addDays(10)->endOfDay(),
        ]));

        $this->assertSame(OfficialStatusValue::OPEN, $this->nilai());

        $this->travel(4)->days();
        $this->assertSame(OfficialStatusValue::CLOSED, $this->nilai());

        $this->travel(10)->days();
        $this->assertSame(OfficialStatusValue::UNKNOWN, $this->nilai(), 'Status lama tidak hidup kembali sesudah penutupan berakhir.');
    }

    public function test_records_that_start_later_are_left_alone(): void
    {
        $nanti = $this->catatanLama(OfficialStatusValue::CLOSED, ['effective_at' => now()->addDays(20)]);
        $kurator = $this->kurator();

        app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::RESTRICTED));

        $this->assertNull($nanti->fresh()->superseded_by_id);
        $this->assertNull($nanti->fresh()->expires_at);
    }

    public function test_a_superseded_record_leaves_the_review_queue(): void
    {
        $this->catatanLama(OfficialStatusValue::OPEN, ['verified_at' => now()->subYear()]);
        $kurator = $this->kurator();

        app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::CLOSED));

        $tinjau = app(DataFreshnessService::class)->review();
        $this->assertCount(0, $tinjau['kedaluwarsa'], 'Catatan yang digantikan bukan catatan kedaluwarsa.');
        $this->assertCount(0, $tinjau['basi']->where('status', OfficialStatusValue::OPEN));
    }
}
```

`tests/Feature/AuditLogChangeTest.php`:

1. Ganti:

```php
namespace Tests\Feature;

use App\Enums\UserRole;
```

dengan:

```php
namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\UserRole;
```

2. Ganti:

```php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
```

dengan:

```php
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;
```

3. Ganti:

```php
{
    use RefreshDatabase;
```

dengan:

```php
{
    use MejaKurator;
    use RefreshDatabase;
```

4. Ganti:

```php
    }

    private function admin(): User
```

dengan:

```php
    }

    /**
     * Status resmi yang digantikan meja kurator (W8) berakhir pada tanggal mulai penggantinya;
     * tanggal berakhir yang disebut catatan lamanya hanya tersisa di jejak audit, jadi "before"
     * harus memuatnya dengan benar.
     */
    public function test_superseding_an_official_status_logs_the_end_it_had_before(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $lama = $this->catatanLama(OfficialStatusValue::OPEN, ['expires_at' => now()->addMonth()->startOfMinute()]);
        $kurator = $this->kurator();

        $baru = app(TerbitStatus::class)->kirim($kurator, $this->draf($kurator, OfficialStatusValue::CLOSED));

        $log = AuditLog::where('action', 'official_status.superseded')->sole();

        $this->assertSame($lama->id, $log->entity_id);
        $this->assertSame($kurator->id, $log->actor_id);
        $this->assertSame(now()->addMonth()->startOfMinute()->toIso8601String(), Carbon::parse($log->before['expires_at'])->toIso8601String());
        $this->assertNull($log->before['superseded_by_id']);
        $this->assertSame($baru->id, $log->after['superseded_by_id']);
    }

    private function admin(): User
```

`tests/Feature/StatusCacheInvalidationTest.php`:

1. Ganti:

```php
use App\Models\User;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
```

dengan:

```php
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;
```

2. Ganti:

```php
{
    use RefreshDatabase;
```

dengan:

```php
{
    use MejaKurator;
    use RefreshDatabase;
```

3. Ganti:

```php
    /**
     * Snapshot membawa jenis sumber (Resmi, Komunitas, ...), dan jenis itu milik baris
```

dengan:

```php
    /**
     * Pembukaan dari meja kurator baru terlihat sesudah verifikasi orang kedua (W8), dan saat itu
     * juga snapshot setiap jalur gunungnya dibuang: penggantian catatan lama dan verifikasi sama-sama
     * tulisan model.
     */
    public function test_a_verified_opening_is_visible_before_the_cache_expires(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $trail = Trail::factory()->easy()->for($this->gunung)->create();
        $this->catatanLama(OfficialStatusValue::CLOSED, [], $trail);
        $pencatat = $this->kurator();

        $menunggu = app(TerbitStatus::class)->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::OPEN, [], $trail));
        $this->assertSame(OfficialStatusValue::CLOSED->value, $this->statusTercache($trail));

        app(TerbitStatus::class)->verifikasi($this->kurator(), $menunggu);

        $this->assertSame(OfficialStatusValue::OPEN->value, $this->statusTercache($trail->fresh()));
    }

    /**
     * Snapshot membawa jenis sumber (Resmi, Komunitas, ...), dan jenis itu milik baris
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='AsymmetricPublishRule|OfficialStatusSupersede|StatusCacheInvalidation|AuditLogChange'`
Expected: FAIL. Pada simulasi rencana: 25 test, 7 lulus, 18 error. Yang merah: kedua belas test `AsymmetricPublishRuleTest`, keempat test
`OfficialStatusSupersedeTest`, dan masing-masing satu test baru di `AuditLogChangeTest` dan
`StatusCacheInvalidationTest` (`TerbitStatus` belum ada).

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/DataResmi/TerbitStatus`, lalu isinya diganti persis.

Buat `app/Services/DataResmi/TerbitStatus.php`:

```php
<?php

namespace App\Services\DataResmi;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
use App\Enums\StatusScope;
use App\Enums\StatusVerificationState;
use App\Models\Authority;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\OfficialStatusService;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Layanan terbit status resmi dari meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 3;
 * ruling W8-R6, W8-R10, dan W8-R11). Satu-satunya jalan tulis status resmi baru: aturan dua orang
 * dan aturan terbit asimetris ditegakkan di sini, bukan di controller, jadi setiap pemanggil
 * memakai aturan yang sama, dan setiap langkah memeriksa OfficialStatusPolicy sendiri.
 *
 * - Pencatat, penerbit (`authority_id`), pemeriksa, dan keadaan selalu dari sesi dan Policy; isian
 *   hanya memberi isi catatan (kolom yang dapat diisi massal di OfficialStatus).
 * - Kirim: draf menjadi menunggu verifikasi kedua. Catatan yang membatasi (tidak lebih longgar dari
 *   status yang terlihat pada tanggal mulainya) dengan bukti primer terbit seketika; selain itu
 *   tidak terlihat sampai orang kedua memverifikasinya. Berita media saja tidak pernah membuka.
 * - Saat sebuah catatan mulai terlihat, catatan terlihat lain untuk sasaran yang sama yang sudah
 *   mulai sebelumnya ditandai digantikan dan berakhir pada tanggal mulai catatan baru.
 * - Setiap langkah tercatat di jejak audit dengan nilai sebelum dan sesudah (PRD §61). Cache
 *   snapshot jalur dibuang event model OfficialStatus.
 */
final class TerbitStatus
{
    /**
     * Kolom isi catatan yang diterima dari isian tervalidasi; sisanya ditulis layanan ini.
     */
    private const ISI = [
        'status', 'document_number', 'document_date', 'evidence_type', 'source_url', 'captured_at',
        'effective_at', 'end_condition', 'expires_at', 'reason_category', 'reason', 'limit_description',
        'legal_basis', 'official_excerpt', 'plain_summary',
    ];

    /**
     * Kolom yang dicatat jejak audit untuk setiap langkah.
     */
    private const DIAUDIT = [
        'status', 'verification_state', 'visible_before_verification', 'verified_by', 'verified_at',
        'effective_at', 'expires_at', 'evidence_sha256', 'superseded_by_id',
    ];

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly ArsipBukti $arsip,
        private readonly OfficialStatusService $status,
    ) {}

    /**
     * @param  array<string, mixed>  $isian  kolom isi catatan, sudah divalidasi
     */
    public function buatDraf(User $kurator, Model $sasaran, Authority $otoritas, array $isian, ?UploadedFile $bukti): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('record', [OfficialStatus::class, $sasaran, $otoritas]);

        return DB::transaction(function () use ($kurator, $sasaran, $otoritas, $isian, $bukti): OfficialStatus {
            $draf = (new OfficialStatus)->fill(Arr::only($isian, self::ISI))->forceFill([
                'statusable_type' => $sasaran->getMorphClass(),
                'statusable_id' => $sasaran->getKey(),
                'scope' => self::cakupan($sasaran),
                'authority_id' => $otoritas->id,
                'recorded_by' => $kurator->id,
                'verification_state' => StatusVerificationState::DRAFT,
                'visible_before_verification' => false,
            ]);
            $this->turunkanSumber($draf, $otoritas);

            if ($bukti !== null) {
                $this->arsipkan($draf, $bukti);
            }

            $draf->save();
            $this->audit->record($kurator, 'official_status.drafted', $draf, null, $draf->only(self::DIAUDIT));

            return $draf;
        });
    }

    /**
     * @param  array<string, mixed>  $isian
     */
    public function ubahDraf(User $kurator, OfficialStatus $draf, array $isian, ?UploadedFile $bukti): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('updateDraft', $draf);

        return DB::transaction(function () use ($kurator, $draf, $isian, $bukti): OfficialStatus {
            $sebelum = $draf->only(self::DIAUDIT);
            $arsipLama = $draf->evidence_archive_path;
            $draf->fill(Arr::only($isian, self::ISI));
            $this->turunkanSumber($draf, $draf->authority);

            if ($bukti !== null) {
                $this->arsipkan($draf, $bukti);
            }

            $draf->save();

            if ($bukti !== null) {
                $this->buangArsip($arsipLama);
            }

            $this->audit->record($kurator, 'official_status.draft_updated', $draf, $sebelum, $draf->only(self::DIAUDIT));

            return $draf;
        });
    }

    public function hapusDraf(User $kurator, OfficialStatus $draf): void
    {
        Gate::forUser($kurator)->authorize('updateDraft', $draf);

        DB::transaction(function () use ($kurator, $draf): void {
            $this->audit->record($kurator, 'official_status.draft_deleted', $draf, $draf->only(self::DIAUDIT), null);
            $draf->delete();
        });

        $this->buangArsip($draf->evidence_archive_path);
    }

    /**
     * Kirim draf untuk terbit (aturan terbit asimetris).
     */
    public function kirim(User $kurator, OfficialStatus $draf): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('updateDraft', $draf);

        if (! $this->arsip->utuh($draf)) {
            throw ValidationException::withMessages(['bukti' => 'Unggah salinan bukti sebelum mengirim: surat, laman, atau unggahan akun resmi pengelola.']);
        }

        $membuka = $this->membuka($draf);

        if ($membuka && $draf->evidence_type === EvidenceType::NEWS) {
            throw ValidationException::withMessages(['evidence_type' => 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.']);
        }

        return DB::transaction(function () use ($kurator, $draf, $membuka): OfficialStatus {
            $sebelum = $draf->only(self::DIAUDIT);
            $terlihat = ! $membuka && $draf->evidence_type?->isPrimary() === true;

            $draf->forceFill([
                'verification_state' => StatusVerificationState::PENDING,
                'visible_before_verification' => $terlihat,
                // Kurator mencocokkan catatan dengan sumbernya saat mengirim (spec v3 bagian 10.3).
                'verified_at' => now(),
                'fetched_at' => $draf->captured_at ?? now(),
            ])->save();

            if ($terlihat) {
                $this->gantikan($kurator, $draf);
            }

            $this->audit->record($kurator, 'official_status.submitted', $draf, $sebelum, $draf->only(self::DIAUDIT));

            return $draf;
        });
    }

    /**
     * Verifikasi orang kedua: catatan terbit (bila belum) dan menggantikan catatan lama.
     */
    public function verifikasi(User $pemeriksa, OfficialStatus $catatan): OfficialStatus
    {
        Gate::forUser($pemeriksa)->authorize('verify', $catatan);

        if (! $this->arsip->utuh($catatan)) {
            throw ValidationException::withMessages(['bukti' => 'Arsip bukti tidak lagi cocok dengan sidiknya. Minta pencatat mengunggah ulang salinannya.']);
        }

        if ($catatan->evidence_type === EvidenceType::NEWS && $this->membuka($catatan)) {
            throw ValidationException::withMessages(['evidence_type' => 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.']);
        }

        return DB::transaction(function () use ($pemeriksa, $catatan): OfficialStatus {
            $sebelum = $catatan->only(self::DIAUDIT);
            $sudahTerlihat = $catatan->terlihatPendaki();

            $catatan->forceFill([
                'verification_state' => StatusVerificationState::VERIFIED,
                'verified_by' => $pemeriksa->id,
                'verified_at' => now(),
            ])->save();

            if (! $sudahTerlihat) {
                $this->gantikan($pemeriksa, $catatan);
            }

            $this->audit->record($pemeriksa, 'official_status.verified', $catatan, $sebelum, $catatan->only(self::DIAUDIT));

            return $catatan;
        });
    }

    /**
     * Mempersoalkan catatan yang menunggu atau sudah terverifikasi. Pembatasan yang sudah terbit
     * tetap terbaca; pembukaan berhenti terbaca, dan catatan yang digantikannya tidak hidup
     * kembali. Alasannya hanya tersimpan di jejak audit, tidak di catatan yang dibaca pendaki.
     */
    public function persoalkan(User $kurator, OfficialStatus $catatan, string $alasan): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('dispute', $catatan);

        return DB::transaction(function () use ($kurator, $catatan, $alasan): OfficialStatus {
            $sebelum = $catatan->only(self::DIAUDIT);
            $catatan->forceFill(['verification_state' => StatusVerificationState::DISPUTED])->save();
            $this->audit->record($kurator, 'official_status.disputed', $catatan, $sebelum, $catatan->only(self::DIAUDIT) + ['alasan' => $alasan]);

            return $catatan;
        });
    }

    /**
     * Catatan ini membuka: Buka, atau lebih longgar dari status yang terlihat untuk sasaran yang
     * sama pada tanggal mulainya. Urutan pembatasan: Buka, Belum diketahui, Dibatasi, Tutup.
     */
    public function membuka(OfficialStatus $catatan): bool
    {
        if ($catatan->status === OfficialStatusValue::OPEN) {
            return true;
        }

        $sasaran = $catatan->statusable;
        $sebelumnya = $sasaran === null
            ? OfficialStatusValue::UNKNOWN
            : $this->status->statusOnDate($sasaran, $catatan->effective_at ?? now());

        return self::tingkat($catatan->status) < self::tingkat($sebelumnya);
    }

    public static function tingkat(OfficialStatusValue $nilai): int
    {
        return match ($nilai) {
            OfficialStatusValue::OPEN => 0,
            OfficialStatusValue::UNKNOWN => 1,
            OfficialStatusValue::RESTRICTED => 2,
            OfficialStatusValue::CLOSED => 3,
        };
    }

    public static function cakupan(Model $sasaran): string
    {
        return match (true) {
            $sasaran instanceof Mountain => StatusScope::MOUNTAIN->value,
            $sasaran instanceof TrailSegment => StatusScope::SEGMENT->value,
            default => StatusScope::TRAIL->value,
        };
    }

    /**
     * Catatan terlihat lain untuk sasaran yang sama yang sudah mulai sebelum catatan baru mulai,
     * dan belum berakhir saat itu, ditandai digantikan dan berakhir pada tanggal mulai itu.
     */
    private function gantikan(User $pelaku, OfficialStatus $baru): void
    {
        $mulai = $baru->effective_at ?? $baru->created_at;

        $lama = OfficialStatus::query()
            ->where('statusable_type', $baru->statusable_type)
            ->where('statusable_id', $baru->statusable_id)
            ->whereKeyNot($baru->id)
            ->whereNull('superseded_by_id')
            ->terlihatPendaki()
            ->dimulaiPalingLambat($mulai)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $mulai))
            ->get();

        foreach ($lama as $catatan) {
            $sebelum = $catatan->only(['expires_at', 'superseded_by_id']);
            $catatan->forceFill(['superseded_by_id' => $baru->id, 'expires_at' => $mulai])->save();
            $this->audit->record($pelaku, 'official_status.superseded', $catatan, $sebelum, $catatan->only(['expires_at', 'superseded_by_id']));
        }
    }

    /**
     * Sumber data yang dikutip catatan (R-009): bukti dari pengelola sendiri dikutip sebagai Resmi
     * atas nama badannya; berita atas pengumuman pengelola sebagai terverifikasi admin, seperti
     * OfficialStatusSeeder. Baris sumbernya dibuat sekali per badan dan jenis.
     */
    private function turunkanSumber(OfficialStatus $catatan, Authority $otoritas): void
    {
        $primer = $catatan->evidence_type?->isPrimary() !== false;
        $nama = $primer ? $otoritas->displayName() : 'Pemberitaan atas pengumuman '.$otoritas->displayName();

        $sumber = DataSource::firstOrCreate(['source_name' => $nama], [
            'source_type' => ($primer ? SourceType::OFFICIAL : SourceType::ADMIN_VERIFIED)->value,
            'source_url' => $otoritas->website,
            'source_owner' => $otoritas->name,
            'freshness_policy' => 'Dicatat kurator dari meja kurator; jadwal tinjau mengikuti jenis status.',
        ]);

        $catatan->forceFill([
            'data_source_id' => $sumber->id,
            'source' => $otoritas->displayName(),
            'published_at' => $catatan->document_date?->copy()->shiftTimezone(self::zona($catatan))->startOfDay(),
        ]);
    }

    private function arsipkan(OfficialStatus $catatan, UploadedFile $bukti): void
    {
        $arsip = $this->arsip->simpan($bukti);

        $catatan->forceFill(['evidence_archive_path' => $arsip['path'], 'evidence_sha256' => $arsip['sha256']]);
    }

    private function buangArsip(?string $path): void
    {
        if (ArsipBukti::sah($path)) {
            Storage::disk(ArsipBukti::disk())->delete($path);
        }
    }

    /**
     * Zona gunung sasaran: tanggal surat dibaca menurut zona kantornya.
     */
    private static function zona(OfficialStatus $catatan): string
    {
        $sasaran = $catatan->statusable;
        $gunung = match (true) {
            $sasaran instanceof Mountain => $sasaran,
            $sasaran instanceof Trail => $sasaran->mountain,
            $sasaran instanceof TrailSegment => $sasaran->trail?->mountain,
            default => null,
        };

        return Timezone::isSupported($gunung?->timezone) ? $gunung->timezone : Timezone::DEFAULT;
    }
}
```

`app/Models/OfficialStatus.php`:

1. Ganti:

```php
    /**
     * Stempel waktu yang dikutip layar untuk sebuah catatan, beserta kata awalannya (W3-2c dan
```

dengan:

```php
    /**
     * Catatan yang sudah mulai pada atau sebelum $waktu, dengan waktu mulai yang sama dengan urutan
     * scopeTerbaruDulu(): tanggal berlaku, atau waktu dicatat bila tidak disebut. Dipakai rantai
     * penggantian meja kurator (TerbitStatus).
     */
    public function scopeDimulaiPalingLambat(Builder $query, CarbonInterface $waktu): Builder
    {
        return $query->whereRaw('coalesce(effective_at, created_at) <= ?', [$waktu]);
    }

    /**
     * Stempel waktu yang dikutip layar untuk sebuah catatan, beserta kata awalannya (W3-2c dan
```

`app/Policies/OfficialStatusPolicy.php`:

1. Ganti:

```php
    /**
     * Verifikasi orang kedua: kurator lain yang berwenang atas kawasannya, untuk catatan yang
```

dengan:

```php
    /**
     * Mempersoalkan catatan yang menunggu atau sudah terverifikasi: kurator mana pun yang berwenang
     * atas kawasannya, termasuk pencatatnya sendiri.
     */
    public function dispute(User $user, OfficialStatus $status): bool
    {
        return in_array($status->verification_state, [StatusVerificationState::PENDING, StatusVerificationState::VERIFIED], true)
            && $this->view($user, $status);
    }

    /**
     * Verifikasi orang kedua: kurator lain yang berwenang atas kawasannya, untuk catatan yang
```

`app/Services/DataFreshnessService.php`:

1. Ganti:

```php
        // Antrean tinjau berkala hanya memuat catatan yang dibaca pendaki; draf dan pembukaan
        // yang menunggu verifikasi punya antreannya sendiri di meja kurator (W8).
        return OfficialStatus::query()->terlihatPendaki()->with('statusable');
    }
```

dengan:

```php
        // Antrean tinjau berkala hanya memuat catatan yang dibaca pendaki; draf dan pembukaan
        // yang menunggu verifikasi punya antreannya sendiri di meja kurator (W8). Catatan yang
        // digantikan sudah berakhir karena penggantinya, bukan kedaluwarsa.
        return OfficialStatus::query()->terlihatPendaki()->whereNull('superseded_by_id')->with('statusable');
    }
```

- [ ] **Step 4: Build dan tipe.** Tidak ada TypeScript, rute, atau daftar rute bukan halaman React yang berubah; build
tidak perlu.

Run: `node_modules/.bin/tsc --noEmit`
Expected: tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Models/OfficialStatus.php app/Policies/OfficialStatusPolicy.php app/Services/DataFreshnessService.php app/Services/DataResmi/TerbitStatus.php tests/Concerns/MejaKurator.php tests/Feature/AsymmetricPublishRuleTest.php tests/Feature/AuditLogChangeTest.php tests/Feature/OfficialStatusSupersedeTest.php tests/Feature/StatusCacheInvalidationTest.php`, lalu `php artisan test --compact --filter='AsymmetricPublishRule|OfficialStatusSupersede|StatusCacheInvalidation|AuditLogChange'`
Expected: PASS. Pada simulasi rencana: 25 test, 25 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.177 test, 2.175 lulus, 2 dilewati (sebelum tugas: 2.159).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 6: Pemeriksaan konsistensi otomatis

Spec v3 bagian 10.7 langkah 3, induk W8 butir 4, Q12 (Konflik 3); ruling W8-R10 dan W8-R12. `PemeriksaanKonsistensi`
menilai satu catatan: blokir untuk tanggal terbalik, cara berakhir yang tidak cocok dengan tanggalnya, sasaran di luar
kawasan atau badan yang tidak berwenang, arsip yang hilang atau berubah, dan berita yang membuka; peringatan untuk bukti
hanya berita, catatan lain yang bertentangan untuk sasaran yang sama, dan jalur Buka di gunung yang ditutup atau
dibatasi di tingkat gunung. Arah perubahan (membuka atau membatasi) pindah ke `ArahPerubahan` supaya layanan terbit dan
pemeriksaan selalu sepakat. `TerbitStatus` menolak kirim dan verifikasi bila ada temuan blokir. Peringatan kawasan tidak
mengubah status jalur, label kecocokan, maupun rekomendasi.

**Files:**
- Create: `app/Services/DataResmi/ArahPerubahan.php`, `app/Services/DataResmi/PemeriksaanKonsistensi.php`
- Modify: `app/Services/DataResmi/TerbitStatus.php`
- Test: `tests/Feature/OfficialStatusConsistencyTest.php` (baru)

**Interfaces:**
- Consumes: `TerbitStatus` (Tugas 5), `Yurisdiksi` (Tugas 3), `ArsipBukti::utuh()` (Tugas 4), `OfficialStatusService::statusOnDate(Model, CarbonInterface)` (W3).
- Produces:
  - `App\Services\DataResmi\ArahPerubahan` (konstruktor `OfficialStatusService`): `membuka(OfficialStatus): bool`, `tingkat(OfficialStatusValue): int` (statis; OPEN 0, UNKNOWN 1, RESTRICTED 2, CLOSED 3);
  - `App\Services\DataResmi\PemeriksaanKonsistensi` (konstruktor `ArsipBukti`, `OfficialStatusService`, `ArahPerubahan`): `periksa(OfficialStatus): array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>`, `blokir(OfficialStatus): array<int, string>`; kode `tanggal_terbalik`, `akhir_tanpa_tanggal`, `tanggal_tanpa_akhir`, `di_luar_yurisdiksi`, `tanpa_arsip`, `arsip_berubah`, `berita_membuka`, `bukti_berita`, `bertentangan`, `kawasan_tertutup`;
  - konstruktor `TerbitStatus` kini `AuditLogService`, `ArsipBukti`, `ArahPerubahan`, `PemeriksaanKonsistensi`; `TerbitStatus::membuka()` dan `tingkat()` pindah ke `ArahPerubahan`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction OfficialStatusConsistencyTest`, lalu isinya diganti persis.

Buat `tests/Feature/OfficialStatusConsistencyTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Models\Trail;
use App\Services\DataResmi\PemeriksaanKonsistensi;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Pemeriksaan konsistensi otomatis sebelum terbit (spec v3 bagian 10.7; rencana induk W8 butir 4;
 * ruling W8-R12; Q12 sebagai peringatan saja).
 *
 * Temuan "blokir" menahan kiriman dan verifikasi di lapisan layanan: tanggal terbalik, cara
 * berakhir yang tidak cocok dengan tanggalnya, sasaran di luar kawasan badan penerbit, tanpa arsip
 * bukti, dan pembukaan yang hanya berbukti berita. Temuan "peringatan" tampil di meja kurator dan
 * tidak menahan apa pun: dua catatan berlaku yang bertentangan, bukti hanya berita, dan jalur Buka
 * di kawasan yang ditutup. Peringatan kawasan tidak mengubah status jalur maupun rekomendasi
 * pendaki sampai pemilik memutus Q12.
 */
class OfficialStatusConsistencyTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    /**
     * @return array<int, string>
     */
    private function kode(array $temuan, string $tingkat): array
    {
        return array_values(array_map(fn (array $t) => $t['kode'], array_filter($temuan, fn (array $t) => $t['tingkat'] === $tingkat)));
    }

    public function test_a_consistent_record_has_no_findings(): void
    {
        $draf = $this->draf($this->kurator(), OfficialStatusValue::CLOSED);

        $this->assertSame([], app(PemeriksaanKonsistensi::class)->periksa($draf));
    }

    public function test_reversed_dates_block_submission(): void
    {
        $kurator = $this->kurator();
        $draf = $this->draf($kurator, OfficialStatusValue::CLOSED, [
            'effective_at' => now()->addDays(5),
            'end_condition' => StatusEndCondition::ON_DATE->value,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertSame(['tanggal_terbalik'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($draf), 'blokir'));

        $this->expectException(ValidationException::class);
        app(TerbitStatus::class)->kirim($kurator, $draf);
    }

    public function test_an_end_condition_must_match_its_date(): void
    {
        $draf = $this->draf($this->kurator(), OfficialStatusValue::CLOSED, ['end_condition' => StatusEndCondition::ON_DATE->value, 'expires_at' => null]);
        $sampaiPemberitahuan = $this->draf($this->kurator(), OfficialStatusValue::CLOSED, ['end_condition' => StatusEndCondition::UNTIL_NOTICE->value, 'expires_at' => now()->addWeek()]);

        $this->assertSame(['akhir_tanpa_tanggal'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($draf), 'blokir'));
        $this->assertSame(['tanggal_tanpa_akhir'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($sampaiPemberitahuan), 'blokir'));
    }

    /**
     * Kawasan badan dapat berubah sesudah draf dibuat. Pemeriksaan membaca kawasan saat itu,
     * bukan saat draf ditulis.
     */
    public function test_a_target_outside_the_authority_area_blocks_submission(): void
    {
        $kurator = $this->kurator();
        $draf = $this->draf($kurator, OfficialStatusValue::CLOSED);
        $this->balai->mountains()->detach($this->gunung);

        $this->assertContains('di_luar_yurisdiksi', $this->kode(app(PemeriksaanKonsistensi::class)->periksa($draf->fresh()), 'blokir'));
    }

    public function test_a_missing_archive_blocks_submission(): void
    {
        $draf = app(TerbitStatus::class)->buatDraf($this->kurator(), $this->gunung, $this->balai, $this->isianStatus(OfficialStatusValue::CLOSED), null);

        $this->assertSame(['tanpa_arsip'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($draf), 'blokir'));
    }

    public function test_news_evidence_warns_and_never_opens(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $tutup = $this->draf($this->kurator(), OfficialStatusValue::CLOSED, ['evidence_type' => EvidenceType::NEWS->value]);
        $buka = $this->draf($this->kurator(), OfficialStatusValue::OPEN, ['evidence_type' => EvidenceType::NEWS->value]);

        $this->assertSame(['bukti_berita'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($tutup), 'peringatan'));
        $this->assertSame([], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($tutup), 'blokir'));
        $this->assertSame(['berita_membuka'], $this->kode(app(PemeriksaanKonsistensi::class)->periksa($buka), 'blokir'));
    }

    public function test_conflicting_records_for_the_same_target_warn(): void
    {
        $kuratorLain = $this->kurator();
        app(TerbitStatus::class)->kirim($kuratorLain, $this->draf($kuratorLain, OfficialStatusValue::OPEN));
        $nanti = $this->catatanLama(OfficialStatusValue::RESTRICTED, ['effective_at' => now()->addDays(5)]);
        $draf = $this->draf($this->kurator(), OfficialStatusValue::CLOSED);

        $temuan = app(PemeriksaanKonsistensi::class)->periksa($draf);

        $this->assertSame(['bertentangan', 'bertentangan'], $this->kode($temuan, 'peringatan'));
        $this->assertStringContainsString('menunggu verifikasi kedua', $temuan[0]['pesan'].$temuan[1]['pesan']);
        $this->assertStringContainsString('#'.$nanti->id, $temuan[0]['pesan'].$temuan[1]['pesan']);
    }

    /**
     * Q12 belum diputus: jalur Buka di gunung yang ditutup hanya diperingatkan. Status jalur tetap
     * mengikuti kaskade §42 yang sudah ada, dan kiriman tidak ditahan.
     */
    public function test_an_open_trail_inside_a_closed_area_only_warns(): void
    {
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $jalur = Trail::factory()->for($this->gunung)->create();
        $pencatat = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::OPEN, [], $jalur);

        $temuan = app(PemeriksaanKonsistensi::class)->periksa($draf);

        $this->assertSame(['kawasan_tertutup'], $this->kode($temuan, 'peringatan'));
        $this->assertSame([], $this->kode($temuan, 'blokir'));

        $menunggu = app(TerbitStatus::class)->kirim($pencatat, $draf);
        app(TerbitStatus::class)->verifikasi($this->kurator(), $menunggu);

        $this->assertSame(OfficialStatusValue::CLOSED, app(OfficialStatusService::class)->effectiveStatusForTrail($jalur->fresh()), 'Kaskade §42 tidak berubah.');
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='OfficialStatusConsistency|AsymmetricPublishRule|OfficialStatusSupersede'`
Expected: FAIL. Pada simulasi rencana: 24 test, 16 lulus, 8 error. Yang merah: kedelapan test `OfficialStatusConsistencyTest` (`PemeriksaanKonsistensi`
belum ada).

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:class --no-interaction Services/DataResmi/ArahPerubahan` dan `... make:class --no-interaction Services/DataResmi/PemeriksaanKonsistensi`, lalu isinya diganti persis.

Buat `app/Services/DataResmi/ArahPerubahan.php`:

```php
<?php

namespace App\Services\DataResmi;

use App\Enums\OfficialStatusValue;
use App\Models\OfficialStatus;
use App\Services\OfficialStatusService;

/**
 * Arah perubahan sebuah catatan status resmi terhadap status yang terlihat pendaki untuk sasaran
 * yang sama pada tanggal mulainya (aturan terbit asimetris, spec v3 bagian 10.7; ruling W8-R10).
 * Dipakai bersama TerbitStatus (siapa yang boleh menerbitkan) dan PemeriksaanKonsistensi (apa
 * yang diperingatkan), jadi keduanya selalu sepakat.
 */
final class ArahPerubahan
{
    public function __construct(private readonly OfficialStatusService $status) {}

    /**
     * Catatan ini membuka: Buka, atau lebih longgar dari status yang terlihat untuk sasaran yang
     * sama pada tanggal mulainya. Urutan pembatasan: Buka, Belum diketahui, Dibatasi, Tutup.
     */
    public function membuka(OfficialStatus $catatan): bool
    {
        if ($catatan->status === OfficialStatusValue::OPEN) {
            return true;
        }

        $sasaran = $catatan->statusable;
        $sebelumnya = $sasaran === null
            ? OfficialStatusValue::UNKNOWN
            : $this->status->statusOnDate($sasaran, $catatan->effective_at ?? now());

        return self::tingkat($catatan->status) < self::tingkat($sebelumnya);
    }

    public static function tingkat(OfficialStatusValue $nilai): int
    {
        return match ($nilai) {
            OfficialStatusValue::OPEN => 0,
            OfficialStatusValue::UNKNOWN => 1,
            OfficialStatusValue::RESTRICTED => 2,
            OfficialStatusValue::CLOSED => 3,
        };
    }
}
```

Buat `app/Services/DataResmi/PemeriksaanKonsistensi.php`:

```php
<?php

namespace App\Services\DataResmi;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusVerificationState;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\OfficialStatusService;

/**
 * Pemeriksaan konsistensi otomatis sebuah catatan meja kurator (spec v3 bagian 10.7; rencana
 * induk W8 butir 4; ruling W8-R12).
 *
 * Tingkat "blokir" ditegakkan TerbitStatus saat kirim dan verifikasi; tingkat "peringatan" hanya
 * tampil di meja kurator. Pemeriksaan kawasan (jalur Buka di kawasan yang ditutup) sengaja hanya
 * peringatan: kebijakan tingkat kawasan terhadap rekomendasi adalah keputusan pemilik yang belum
 * diambil (Q12), jadi status jalur dan rekomendasi pendaki tidak diubah di sini.
 */
final class PemeriksaanKonsistensi
{
    public function __construct(
        private readonly ArsipBukti $arsip,
        private readonly OfficialStatusService $status,
        private readonly ArahPerubahan $arah,
    ) {}

    /**
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    public function periksa(OfficialStatus $catatan): array
    {
        return [
            ...$this->tanggal($catatan),
            ...$this->wewenang($catatan),
            ...$this->bukti($catatan),
            ...$this->bertentangan($catatan),
            ...$this->kawasan($catatan),
        ];
    }

    /**
     * @return array<int, string> pesan temuan tingkat blokir
     */
    public function blokir(OfficialStatus $catatan): array
    {
        return array_values(array_map(
            fn (array $t) => $t['pesan'],
            array_filter($this->periksa($catatan), fn (array $t) => $t['tingkat'] === 'blokir'),
        ));
    }

    /**
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function tanggal(OfficialStatus $catatan): array
    {
        $temuan = [];

        if ($catatan->expires_at !== null && $catatan->effective_at !== null && $catatan->expires_at->lessThanOrEqualTo($catatan->effective_at)) {
            $temuan[] = self::temuanBlokir('tanggal_terbalik', 'Tanggal berakhir tidak boleh sebelum tanggal mulai berlaku.');
        }

        if ($catatan->end_condition === StatusEndCondition::ON_DATE && $catatan->expires_at === null) {
            $temuan[] = self::temuanBlokir('akhir_tanpa_tanggal', 'Cara berakhir "sampai tanggal tertentu" butuh tanggal berakhir.');
        }

        if ($catatan->end_condition !== null && $catatan->end_condition !== StatusEndCondition::ON_DATE && $catatan->expires_at !== null) {
            $temuan[] = self::temuanBlokir('tanggal_tanpa_akhir', 'Tanggal berakhir hanya diisi bila pengelola menyebut tanggalnya. Pilih "sampai tanggal tertentu" atau kosongkan tanggalnya.');
        }

        return $temuan;
    }

    /**
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function wewenang(OfficialStatus $catatan): array
    {
        $sasaran = $catatan->statusable;
        $badan = $catatan->authority;

        if ($sasaran === null || $badan === null || ! $badan->type->mayDeclareTrailStatus()) {
            return [self::temuanBlokir('di_luar_yurisdiksi', 'Badan penerbit tidak berwenang menyatakan status untuk sasaran ini.')];
        }

        $gunung = Yurisdiksi::gunungSasaran($sasaran);

        if ($gunung === null || ! $badan->mountains()->whereKey($gunung)->exists()) {
            return [self::temuanBlokir('di_luar_yurisdiksi', 'Sasaran ini di luar kawasan '.$badan->displayName().'.')];
        }

        return [];
    }

    /**
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function bukti(OfficialStatus $catatan): array
    {
        $temuan = [];

        if ($catatan->evidence_archive_path === null) {
            $temuan[] = self::temuanBlokir('tanpa_arsip', 'Unggah salinan bukti: surat, laman, atau unggahan akun resmi pengelola.');
        } elseif (! $this->arsip->utuh($catatan)) {
            $temuan[] = self::temuanBlokir('arsip_berubah', 'Arsip bukti tidak lagi cocok dengan sidik SHA-256 yang dicatat. Unggah ulang salinannya.');
        }

        if ($catatan->evidence_type === EvidenceType::NEWS) {
            $temuan[] = $this->arah->membuka($catatan)
                ? self::temuanBlokir('berita_membuka', 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.')
                : self::temuanPeringatan('bukti_berita', 'Bukti hanya berita: catatan ini tidak terbit sebelum diperiksa kurator kedua, dan dikutip sebagai terverifikasi admin, bukan Resmi.');
        }

        return $temuan;
    }

    /**
     * Catatan lain untuk sasaran yang sama yang tidak akan digantikan catatan ini: catatan terlihat
     * yang baru mulai sesudahnya, dan catatan kurator lain yang menunggu verifikasi atau
     * dipersoalkan, dengan nilai berbeda.
     *
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function bertentangan(OfficialStatus $catatan): array
    {
        $mulai = $catatan->effective_at ?? now();

        $lain = OfficialStatus::query()
            ->where('statusable_type', $catatan->statusable_type)
            ->where('statusable_id', $catatan->statusable_id)
            ->whereKeyNot($catatan->id)
            ->whereNull('superseded_by_id')
            ->where('status', '!=', $catatan->status->value)
            ->where(fn ($q) => $q
                ->whereIn('verification_state', [StatusVerificationState::PENDING->value, StatusVerificationState::DISPUTED->value])
                ->orWhere(fn ($terlihat) => $terlihat->terlihatPendaki()->where('effective_at', '>', $mulai)))
            ->orderBy('id')
            ->get();

        return $lain->map(function (OfficialStatus $s) use ($catatan) {
            $tertunda = in_array($s->verification_state, [StatusVerificationState::PENDING, StatusVerificationState::DISPUTED], true) && ! $s->terlihatPendaki();

            return self::temuanPeringatan('bertentangan', $tertunda
                ? 'Catatan #'.$s->id.' ('.$s->status->label().') untuk sasaran yang sama '.$s->verification_state->label().' dan belum terbaca pendaki; menunggu verifikasi kedua. Pastikan keduanya tidak bertentangan.'
                : 'Catatan #'.$s->id.' ('.$s->status->label().' mulai '.$s->effective_at?->translatedFormat('j M Y').') berlaku sesudah catatan ini dan akan menentukan status mulai tanggalnya. Catatan '.$catatan->status->label().' ini tidak menggantikannya.');
        })->values()->all();
    }

    /**
     * Q12: jalur atau segmen Buka di gunung yang sedang Tutup atau Dibatasi. Kaskade §42 tetap
     * menampilkan status gunung kepada pendaki; meja kurator hanya diberi tahu.
     *
     * @return array<int, array{kode: string, tingkat: 'blokir'|'peringatan', pesan: string}>
     */
    private function kawasan(OfficialStatus $catatan): array
    {
        $sasaran = $catatan->statusable;

        if ($catatan->status !== OfficialStatusValue::OPEN || $sasaran instanceof Mountain || $sasaran === null) {
            return [];
        }

        $gunung = match (true) {
            $sasaran instanceof Trail => $sasaran->mountain,
            $sasaran instanceof TrailSegment => $sasaran->trail?->mountain,
            default => null,
        };

        if (! $gunung instanceof Mountain) {
            return [];
        }

        $nilai = $this->status->statusOnDate($gunung, $catatan->effective_at ?? now());

        if (! in_array($nilai, [OfficialStatusValue::CLOSED, OfficialStatusValue::RESTRICTED], true)) {
            return [];
        }

        return [self::temuanPeringatan('kawasan_tertutup', $gunung->name.' sedang '.$nilai->label().' di tingkat gunung. Pendaki tetap melihat status gunung itu untuk jalur ini (pembatasan turun ke jalur). Kebijakan kawasan terhadap rekomendasi menunggu keputusan pemilik (Q12).')];
    }

    /**
     * @return array{kode: string, tingkat: 'blokir', pesan: string}
     */
    private static function temuanBlokir(string $kode, string $pesan): array
    {
        return ['kode' => $kode, 'tingkat' => 'blokir', 'pesan' => $pesan];
    }

    /**
     * @return array{kode: string, tingkat: 'peringatan', pesan: string}
     */
    private static function temuanPeringatan(string $kode, string $pesan): array
    {
        return ['kode' => $kode, 'tingkat' => 'peringatan', 'pesan' => $pesan];
    }
}
```

`app/Services/DataResmi/TerbitStatus.php`:

1. Ganti:

```php
use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\SourceType;
```

dengan:

```php
use App\Enums\EvidenceType;
use App\Enums\SourceType;
```

2. Ganti:

```php
use App\Services\AuditLogService;
use App\Services\OfficialStatusService;
use App\Support\Timezone;
```

dengan:

```php
use App\Services\AuditLogService;
use App\Support\Timezone;
```

3. Ganti:

```php
        private readonly ArsipBukti $arsip,
        private readonly OfficialStatusService $status,
    ) {}
```

dengan:

```php
        private readonly ArsipBukti $arsip,
        private readonly ArahPerubahan $arah,
        private readonly PemeriksaanKonsistensi $konsistensi,
    ) {}
```

4. Ganti:

```php
        }

        $membuka = $this->membuka($draf);

        if ($membuka && $draf->evidence_type === EvidenceType::NEWS) {
```

dengan:

```php
        }

        $membuka = $this->arah->membuka($draf);

        if ($membuka && $draf->evidence_type === EvidenceType::NEWS) {
```

5. Ganti:

```php
            throw ValidationException::withMessages(['evidence_type' => 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.']);
        }

        return DB::transaction(function () use ($kurator, $draf, $membuka): OfficialStatus {
```

dengan:

```php
            throw ValidationException::withMessages(['evidence_type' => 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.']);
        }

        $this->tolakBilaBlokir($draf);

        return DB::transaction(function () use ($kurator, $draf, $membuka): OfficialStatus {
```

6. Ganti:

```php
        }

        if ($catatan->evidence_type === EvidenceType::NEWS && $this->membuka($catatan)) {
            throw ValidationException::withMessages(['evidence_type' => 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.']);
        }

        return DB::transaction(function () use ($pemeriksa, $catatan): OfficialStatus {
```

dengan:

```php
        }

        if ($catatan->evidence_type === EvidenceType::NEWS && $this->arah->membuka($catatan)) {
            throw ValidationException::withMessages(['evidence_type' => 'Berita media saja tidak pernah dapat membuka jalur. Lampirkan bukti dari pengelola.']);
        }

        $this->tolakBilaBlokir($catatan);

        return DB::transaction(function () use ($pemeriksa, $catatan): OfficialStatus {
```

7. Ganti:

```php
    /**
     * Catatan ini membuka: Buka, atau lebih longgar dari status yang terlihat untuk sasaran yang
     * sama pada tanggal mulainya. Urutan pembatasan: Buka, Belum diketahui, Dibatasi, Tutup.
     */
    public function membuka(OfficialStatus $catatan): bool
    {
        if ($catatan->status === OfficialStatusValue::OPEN) {
            return true;
        }

        $sasaran = $catatan->statusable;
        $sebelumnya = $sasaran === null
            ? OfficialStatusValue::UNKNOWN
            : $this->status->statusOnDate($sasaran, $catatan->effective_at ?? now());

        return self::tingkat($catatan->status) < self::tingkat($sebelumnya);
    }

    public static function tingkat(OfficialStatusValue $nilai): int
    {
        return match ($nilai) {
            OfficialStatusValue::OPEN => 0,
            OfficialStatusValue::UNKNOWN => 1,
            OfficialStatusValue::RESTRICTED => 2,
            OfficialStatusValue::CLOSED => 3,
        };
    }
```

dengan:

```php
    /**
     * Temuan pemeriksaan konsistensi tingkat blokir menahan kiriman dan verifikasi (ruling
     * W8-R12), apa pun jalan tulisnya.
     */
    private function tolakBilaBlokir(OfficialStatus $catatan): void
    {
        $blokir = $this->konsistensi->blokir($catatan);

        if ($blokir !== []) {
            throw ValidationException::withMessages(['konsistensi' => $blokir]);
        }
    }
```

- [ ] **Step 4: Build dan tipe.** Tidak ada TypeScript, rute, atau daftar rute bukan halaman React yang berubah; build
tidak perlu.

Run: `node_modules/.bin/tsc --noEmit`
Expected: tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/DataResmi/ArahPerubahan.php app/Services/DataResmi/PemeriksaanKonsistensi.php app/Services/DataResmi/TerbitStatus.php tests/Feature/OfficialStatusConsistencyTest.php`, lalu `php artisan test --compact --filter='OfficialStatusConsistency|AsymmetricPublishRule|OfficialStatusSupersede'`
Expected: PASS. Pada simulasi rencana: 24 test, 24 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.185 test, 2.183 lulus, 2 dilewati (sebelum tugas: 2.177).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 7: Jadwal tinjau per jenis status dan status yang berakhir

Spec v3 bagian 10.7 langkah 6, induk W8 butir 5, Q9 (Konflik 4); ruling W8-R13 dan W8-R14. Ambang tunggal
`status_review_days` 90 diganti jadwal per jenis status: Buka memakai ambang turun paket lapangan
(`field_pack.open_status_stale_after_hours`, 24 jam), Dibatasi, Ditutup, dan Belum diketahui 168 jam
(`freshness.status_review_hours`). Catatan meja kurator menyimpan `next_review_at` yang tidak pernah melewati tanggal
berakhir yang disebut pengelola; "Sudah dicek ulang ke sumber" menyetel ulang jadwal itu (`TerbitStatus::tinjauUlang()`).
Status yang berakhir menjadi Belum diketahui: catatan penentu terbaru yang masa berlakunya lewat tidak digantikan catatan
yang lebih lama, sama di `OfficialStatusService`, `StatusJalurRingkas`, dan snapshot, dan catatan itu masuk antrean tinjau.
Tidak ada tugas terjadwal baru: `data:freshness-check` sudah berjalan harian, dan keadaan dihitung saat dibaca.

**Files:**
- Modify: `app/Models/OfficialStatus.php`, `app/Policies/OfficialStatusPolicy.php`, `app/Services/DataFreshnessService.php` (ditulis ulang utuh), `app/Services/DataResmi/TerbitStatus.php`, `app/Services/OfficialStatusService.php` (ditulis ulang utuh), `app/Services/Trails/StatusJalurRingkas.php`, `app/Services/Trails/TrailDetailPage.php`, `config/hiking.php`
- Test: `tests/Feature/DataFreshnessReviewTest.php`, `tests/Feature/OfficialStatusSeederTest.php`, `tests/Feature/TrustGrammarTest.php`, `tests/Spatial/OfficialStatusReviewScheduleTest.php` (baru, PostgreSQL), `tests/Unit/DataFreshnessServiceTest.php`

**Interfaces:**
- Consumes: `next_review_at` (Tugas 2), `TerbitStatus` dan `OfficialStatusPolicy` (Tugas 5), `config('hiking.field_pack.open_status_stale_after_hours')` (W6), `SchedulerHealthService::TUGAS` (`data:freshness-check`).
- Produces:
  - `DataFreshnessService::jamTinjau(OfficialStatusValue): int`, `jadwalUntuk(OfficialStatusValue, CarbonInterface $diverifikasi, ?CarbonInterface $berakhir): Carbon`, `keadaanDari(?CarbonInterface, ?CarbonInterface, ?OfficialStatusValue = null, ?CarbonInterface $jadwal = null): string` ("Terkini", "Perlu ditinjau", "Kedaluwarsa");
  - `OfficialStatusService::catatanPenentu(?OfficialStatus $jalur, ?OfficialStatus $gunung): ?OfficialStatus` (statis) dan pembacaan "berakhir berarti Belum diketahui"; `OfficialStatus::berlakuPada(CarbonInterface): bool`;
  - `TerbitStatus::tinjauUlang(User, OfficialStatus): OfficialStatus`, dengan `DataFreshnessService` sebagai dependensi konstruktor kelima; `OfficialStatusPolicy::review(User, OfficialStatus): bool`;
  - config `hiking.freshness.status_review_hours` (`RESTRICTED`, `CLOSED`, `UNKNOWN` = 168), menggantikan `status_review_days`.

- [ ] **Step 1: Tulis test yang gagal.** `DataFreshnessReviewTest` mendapat lima test baru untuk jadwal per jenis dan
status yang berakhir; `TrustGrammarTest`, `OfficialStatusSeederTest`, dan `DataFreshnessServiceTest` diperbarui menurut
jadwal baru dengan alasan di docblock. Berkas `tests/Spatial/OfficialStatusReviewScheduleTest.php` dibuat langsung.

Buat `tests/Spatial/OfficialStatusReviewScheduleTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Services\DataFreshnessService;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;

/**
 * Paritas PostgreSQL untuk kueri status W8 (R-024; ruling W8-R13 dan W8-R14): pemilihan catatan
 * terakhir yang sudah dimulai (`coalesce(effective_at, created_at) <= ?` dengan stempel terikat),
 * catatan yang berakhir menjadi Belum diketahui, dan antrean tinjau per jenis status
 * (perbandingan `verified_at` per nilai status dan `next_review_at`). Kembarannya di SQLite:
 * tests/Feature/DataFreshnessReviewTest.
 */
class OfficialStatusReviewScheduleTest extends SpatialTestCase
{
    public function test_an_ended_record_reads_unknown_and_never_revives_an_older_one(): void
    {
        $jalur = Trail::factory()->for(Mountain::factory())->create();
        $sasaran = ['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->id];
        OfficialStatus::factory()->create([...$sasaran, 'status' => 'OPEN', 'effective_at' => now()->subMonths(2)]);
        OfficialStatus::factory()->closed()->create([...$sasaran, 'effective_at' => now()->subMonth(), 'expires_at' => now()->subDay()]);
        OfficialStatus::factory()->restricted()->create([...$sasaran, 'effective_at' => now()->addWeek()]);

        $layanan = app(OfficialStatusService::class);
        $daftar = Trail::with('mountain')->whereKey($jalur->id)->get();

        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->currentValueFor($jalur));
        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->effectiveStatusesForTrails($daftar)[$jalur->id]);
        $this->assertNull(app(StatusJalurRingkas::class)->untuk($daftar)[$jalur->id]['status']);
        $this->assertSame(OfficialStatusValue::RESTRICTED, $layanan->statusOnDate($jalur, now()->addWeeks(2)));
        $this->assertSame(OfficialStatusValue::OPEN, $layanan->statusOnDate($jalur, now()->subWeeks(6)));
    }

    public function test_the_review_queue_follows_each_kind_of_status(): void
    {
        $buat = fn (string $nilai, $diverifikasi, $jadwal = null) => tap(OfficialStatus::factory()->create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => Trail::factory()->create()->id,
            'status' => $nilai,
            'effective_at' => now()->subMonth(),
            'verified_at' => $diverifikasi,
        ]), fn (OfficialStatus $s) => $jadwal === null ? null : $s->forceFill(['next_review_at' => $jadwal])->save());

        $bukaBasi = $buat('OPEN', now()->subHours(25));
        $tutupSegar = $buat('CLOSED', now()->subHours(25));
        $tutupBasi = $buat('CLOSED', now()->subDays(8));
        $terjadwalLewat = $buat('CLOSED', now(), now()->subMinute());

        $basi = app(DataFreshnessService::class)->stale()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$bukaBasi->id, $tutupBasi->id, $terjadwalLewat->id], $basi);
        $this->assertNotContains($tutupSegar->id, $basi);
        $this->assertSame(3, app(DataFreshnessService::class)->reviewCount());
    }
}
```

`tests/Feature/DataFreshnessReviewTest.php`:

1. Ganti:

```php
namespace Tests\Feature;

use App\Enums\UserRole;
```

dengan:

```php
namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\UserRole;
```

2. Ganti:

```php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
```

dengan:

```php
use App\Models\User;
use App\Services\DataFreshnessService;
use App\Services\DataResmi\TerbitStatus;
use App\Services\OfficialStatusService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;
```

3. Ganti:

```php
{
    use RefreshDatabase;
```

dengan:

```php
{
    use MejaKurator;
    use RefreshDatabase;
```

4. Ganti:

```php
    }

    private function hitungQuery(string $url): int
```

dengan:

```php
    }

    /**
     * Q9 (ruling W8-R13): jadwal tinjau per jenis status menggantikan ambang tunggal 90 hari.
     * Buka paling sering ditinjau, karena klaim itu yang paling berisiko terbaca aman (R-012).
     */
    public function test_each_kind_of_status_has_its_own_review_schedule(): void
    {
        $layanan = app(DataFreshnessService::class);
        $buka = $this->statusTerverifikasi(OfficialStatusValue::OPEN, now()->subHours(25));
        $tutupBaru = $this->statusTerverifikasi(OfficialStatusValue::CLOSED, now()->subHours(25));
        $tutupLama = $this->statusTerverifikasi(OfficialStatusValue::CLOSED, now()->subDays(8));

        $basi = $layanan->stale()->pluck('id')->all();

        $this->assertContains($buka->id, $basi);
        $this->assertNotContains($tutupBaru->id, $basi);
        $this->assertContains($tutupLama->id, $basi);
        $this->assertSame('Perlu ditinjau', $layanan->keadaan($buka));
        $this->assertSame('Terkini', $layanan->keadaan($tutupBaru));
        $this->assertSame(2, $layanan->reviewCount());
    }

    /**
     * Ambang Buka satu sumber dengan ambang turun status Buka di paket luring (W6-R8), jadi
     * pendaki yang daring dan luring membaca batas yang sama.
     */
    public function test_the_open_schedule_is_the_field_pack_threshold(): void
    {
        config(['hiking.field_pack.open_status_stale_after_hours' => 6]);
        $buka = $this->statusTerverifikasi(OfficialStatusValue::OPEN, now()->subHours(7));

        $this->assertSame(6, app(DataFreshnessService::class)->jamTinjau(OfficialStatusValue::OPEN));
        $this->assertContains($buka->id, app(DataFreshnessService::class)->stale()->pluck('id')->all());
    }

    public function test_a_curated_record_waits_for_its_review_date_and_a_recheck_resets_it(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $pencatat = $this->kurator();
        $tutup = app(TerbitStatus::class)->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::CLOSED));

        $this->assertTrue($tutup->next_review_at->equalTo($tutup->verified_at->copy()->addHours(168)));
        $this->assertSame('Terkini', app(DataFreshnessService::class)->keadaan($tutup));

        $this->travel(8)->days();
        $this->assertContains($tutup->id, app(DataFreshnessService::class)->stale()->pluck('id')->all());

        $diperiksa = app(TerbitStatus::class)->tinjauUlang($this->kurator(), $tutup->fresh());

        $this->assertTrue($diperiksa->next_review_at->greaterThan(now()->addDays(6)));
        $this->assertNotContains($tutup->id, app(DataFreshnessService::class)->stale()->pluck('id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'official_status.reviewed', 'entity_id' => $tutup->id]);
    }

    /**
     * Jadwal tinjau tidak pernah melewati tanggal berakhir yang disebut pengelola.
     */
    public function test_a_record_with_an_end_date_is_reviewed_by_that_date(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $pencatat = $this->kurator();

        $tutup = app(TerbitStatus::class)->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::CLOSED, [
            'end_condition' => StatusEndCondition::ON_DATE->value,
            'expires_at' => now()->addDays(2)->startOfMinute(),
        ]));

        $this->assertTrue($tutup->next_review_at->equalTo(now()->addDays(2)->startOfMinute()));
    }

    /**
     * Spec v3 bagian 10.7: kedaluwarsa menjadi Belum diketahui dan masuk antrean, tidak pernah
     * otomatis Buka. Catatan Buka yang lebih lama tidak hidup kembali ketika penutupan yang lebih
     * baru berakhir (ruling W8-R14).
     */
    public function test_an_ended_status_reads_unknown_never_reopens_and_waits_in_the_queue(): void
    {
        $jalur = Trail::factory()->create(['name' => 'Jalur Kandang Badak']);
        $sasaran = ['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->id];
        OfficialStatus::factory()->create([...$sasaran, 'status' => OfficialStatusValue::OPEN->value, 'effective_at' => now()->subMonths(2), 'verified_at' => now()->subMonths(2)]);
        $tutup = OfficialStatus::factory()->closed()->create([...$sasaran, 'effective_at' => now()->subMonth(), 'expires_at' => now()->subDay(), 'verified_at' => now()->subMonth()]);

        $layanan = app(OfficialStatusService::class);

        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->currentValueFor($jalur));
        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->effectiveStatusesForTrails(Trail::whereKey($jalur->id)->get())[$jalur->id]);
        $this->assertNull(app(StatusJalurRingkas::class)->untuk(Trail::with('mountain')->whereKey($jalur->id)->get())[$jalur->id]['status']);
        $this->assertSame(OfficialStatusValue::OPEN, $layanan->statusOnDate($jalur, now()->subMonths(1)->subWeek()), 'Masa lalu tetap terbaca menurut catatannya.');
        $this->assertTrue(app(DataFreshnessService::class)->expired()->contains('id', $tutup->id));
    }

    private function statusTerverifikasi(OfficialStatusValue $nilai, Carbon $diverifikasi): OfficialStatus
    {
        return OfficialStatus::factory()->create([
            'statusable_type' => Trail::class,
            'statusable_id' => Trail::factory()->create()->id,
            'status' => $nilai->value,
            'effective_at' => now()->subMonth(),
            'expires_at' => null,
            'verified_at' => $diverifikasi,
        ]);
    }

    private function hitungQuery(string $url): int
```

`tests/Feature/OfficialStatusSeederTest.php`:

1. Ganti:

```php
use App\Models\OfficialStatus;
use App\Services\OfficialStatusService;
```

dengan:

```php
use App\Models\OfficialStatus;
use App\Services\DataFreshnessService;
use App\Services\OfficialStatusService;
```

2. Ganti:

```php
    }

    public function test_running_the_seeder_twice_does_not_duplicate(): void
```

dengan:

```php
    }

    /**
     * Butir izin akhir 14: catatan seeder berakhir paling lambat 31 Oktober 2026. Sesudahnya
     * setiap gunung itu Belum diketahui, tidak pernah Buka, dan seluruhnya menunggu di antrean
     * tinjau (meja kurator dan halaman admin) sampai kurator mencatat keadaan terbaru dari
     * sumber resmi. Penutupan Desember Semeru belum berakhir, jadi tidak ikut antrean.
     */
    public function test_after_october_the_seeded_statuses_read_unknown_and_wait_for_a_curator(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 08:00:00'));

        foreach (['gunung-gede', 'gunung-pangrango', 'gunung-semeru', 'gunung-merbabu', 'gunung-rinjani'] as $slug) {
            $this->assertSame(OfficialStatusValue::UNKNOWN, $this->statusTerkini($slug), $slug);
        }

        $kedaluwarsa = app(DataFreshnessService::class)->review()['kedaluwarsa'];

        $this->assertCount(5, $kedaluwarsa);
        $this->assertSame(
            ['Gunung Gede', 'Gunung Merbabu', 'Gunung Pangrango', 'Gunung Rinjani', 'Gunung Semeru'],
            $kedaluwarsa->map(fn (OfficialStatus $s) => $s->statusable->name)->sort()->values()->all(),
        );
    }

    public function test_running_the_seeder_twice_does_not_duplicate(): void
```

`tests/Feature/TrustGrammarTest.php`:

1. Ganti:

```php
     * P-3: DataFreshnessService::keadaan() dengan waktu dibekukan, termasuk satu kasus
     * tepat di batas hari tinjau (perbandingannya "lt", bukan "lte", jadi tepat di batas
     * masih Terkini).
     */
```

dengan:

```php
     * P-3: DataFreshnessService::keadaan() dengan waktu dibekukan, termasuk satu kasus
     * tepat di batas jadwal tinjau (tepat di batas masih Terkini). Sejak W8 jadwalnya per jenis
     * status (Q9, ruling W8-R13): kasus di sini memakai Buka, yang ditinjau sesudah jam ambang
     * Buka paket luring, bukan sesudah 90 hari.
     */
```

2. Ganti:

```php
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $hariTinjau = (int) config('hiking.freshness.status_review_days', 90);
        $hariPeringatan = (int) config('hiking.freshness.status_expiry_warning_days', 14);
```

dengan:

```php
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $jamTinjau = app(DataFreshnessService::class)->jamTinjau(OfficialStatusValue::OPEN);
        $hariPeringatan = (int) config('hiking.freshness.status_expiry_warning_days', 14);
```

3. Ganti:

```php
            'belum pernah diverifikasi' => [null, null, 'Perlu ditinjau'],
            'terlalu lama tidak diverifikasi' => [now()->subDays($hariTinjau + 1), null, 'Perlu ditinjau'],
            'segera kedaluwarsa' => [now(), now()->addDays($hariPeringatan - 1), 'Perlu ditinjau'],
            'sudah kedaluwarsa' => [now(), now()->subDay(), 'Kedaluwarsa'],
            'tepat di batas hari tinjau, belum Perlu ditinjau' => [now()->subDays($hariTinjau), null, 'Terkini'],
        ];
```

dengan:

```php
            'belum pernah diverifikasi' => [null, null, 'Perlu ditinjau'],
            'terlalu lama tidak diverifikasi' => [now()->subHours($jamTinjau + 1), null, 'Perlu ditinjau'],
            'segera kedaluwarsa' => [now(), now()->addDays($hariPeringatan - 1), 'Perlu ditinjau'],
            'sudah kedaluwarsa' => [now(), now()->subDay(), 'Kedaluwarsa'],
            'tepat di batas jadwal tinjau, belum Perlu ditinjau' => [now()->subHours($jamTinjau), null, 'Terkini'],
        ];
```

4. Ganti:

```php
        foreach ($kasus as $nama => [$verified, $expires, $diharapkan]) {
            $status = new OfficialStatus(['verified_at' => $verified, 'expires_at' => $expires]);

            $this->assertSame($diharapkan, $layanan->keadaan($status), $nama);
```

dengan:

```php
        foreach ($kasus as $nama => [$verified, $expires, $diharapkan]) {
            $status = new OfficialStatus(['status' => OfficialStatusValue::OPEN->value, 'verified_at' => $verified, 'expires_at' => $expires]);

            $this->assertSame($diharapkan, $layanan->keadaan($status), $nama);
```

5. Ganti:

```php
    {
        $hariTinjau = (int) config('hiking.freshness.status_review_days', 90);
        $jalur = $this->jalurTerbitDenganCatatan(['verified_at' => now()->subDays($hariTinjau + 10)]);
        $pembaca = User::factory()->create();
```

dengan:

```php
    {
        // Tiga puluh hari melewati jadwal tinjau jenis status mana pun (ruling W8-R13).
        $jalur = $this->jalurTerbitDenganCatatan(['verified_at' => now()->subDays(30)]);
        $pembaca = User::factory()->create();
```

`tests/Unit/DataFreshnessServiceTest.php`:

1. Ganti:

```php
        $this->layanan = app(DataFreshnessService::class);
        config(['hiking.freshness.status_review_days' => 90]);
        config(['hiking.freshness.status_expiry_warning_days' => 14]);
```

dengan:

```php
        $this->layanan = app(DataFreshnessService::class);
        // Q9 (ruling W8-R13): jadwal tinjau per jenis status menggantikan ambang tunggal 90 hari;
        // pabrik status memakai Buka, yang ditinjau sesudah 24 jam.
        config(['hiking.field_pack.open_status_stale_after_hours' => 24]);
        config(['hiking.freshness.status_review_hours' => ['RESTRICTED' => 168, 'CLOSED' => 168, 'UNKNOWN' => 168]]);
        config(['hiking.freshness.status_expiry_warning_days' => 14]);
```

2. Ganti:

```php
    }

    public function test_a_recently_verified_status_is_not_stale(): void
    {
        $status = $this->buatStatus(['verified_at' => now()->subDays(10), 'expires_at' => null]);

        $this->assertFalse($this->layanan->stale()->contains('id', $status->id));
```

dengan:

```php
    }

    /**
     * Sebelum W8 "baru" berarti sepuluh hari untuk semua status; kini Buka ditinjau sesudah 24
     * jam (ruling W8-R13), jadi status Buka yang diverifikasi sepuluh jam lalu masih segar.
     */
    public function test_a_recently_verified_status_is_not_stale(): void
    {
        $status = $this->buatStatus(['verified_at' => now()->subHours(10), 'expires_at' => null]);

        $this->assertFalse($this->layanan->stale()->contains('id', $status->id));
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='DataFreshnessReview|DataFreshnessService|TrustGrammar|OfficialStatusSeeder|SchedulerHealth|OfficialStatusReviewSchedule'`
Expected: FAIL. Pada simulasi rencana: 62 test, 53 lulus, 3 gagal, 4 error, 2 dilewati. Yang merah: lima test baru `DataFreshnessReviewTest` (jadwal per jenis, `jamTinjau()`,
jadwal tersimpan, dan status yang berakhir yang semula mengembalikan Buka) dan dua test `TrustGrammarTest` (ambang 90
hari masih berlaku). 2 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** `DataFreshnessService.php` dan `OfficialStatusService.php` ditulis ulang utuh
(Pra-cek butir 1a); tulisan ulang `OfficialStatusService.php` sudah memuat perubahan Tugas 1 dan 2.

`app/Models/OfficialStatus.php`:

1. Ganti:

```php
    }

    public function isExpired(): bool
```

dengan:

```php
    }

    /**
     * Masih berlaku pada $waktu: belum berakhir (expires_at inklusif, sama dengan
     * scopeEffectiveOn). Pembaca status memilih catatan terakhir yang sudah dimulai lalu
     * memeriksa ini; catatan yang berakhir tidak digantikan catatan yang lebih lama (W8-R14).
     */
    public function berlakuPada(CarbonInterface $waktu): bool
    {
        return $this->expires_at === null || $this->expires_at->greaterThanOrEqualTo($waktu);
    }

    public function isExpired(): bool
```

`app/Policies/OfficialStatusPolicy.php`:

1. Ganti:

```php
    /**
     * Mempersoalkan catatan yang menunggu atau sudah terverifikasi: kurator mana pun yang berwenang
```

dengan:

```php
    /**
     * Tinjau berkala: catatan meja kurator yang sedang dibaca pendaki dan belum digantikan,
     * oleh kurator mana pun yang berwenang atas kawasannya. Catatan lama dari sebelum meja kurator
     * tidak ditinjau ulang; kurator mencatat penggantinya.
     */
    public function review(User $user, OfficialStatus $status): bool
    {
        return $status->verification_state !== null
            && $status->terlihatPendaki()
            && $status->superseded_by_id === null
            && $this->view($user, $status);
    }

    /**
     * Mempersoalkan catatan yang menunggu atau sudah terverifikasi: kurator mana pun yang berwenang
```

Tulis ulang `app/Services/DataFreshnessService.php` utuh:

```php
<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Models\OfficialStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Pemeriksaan data basi untuk status resmi (PRD §93, §98).
 *
 * Status yang kedaluwarsa diam-diam kembali menjadi UNKNOWN. Itu aman menurut §95,
 * tetapi senyap: jalur yang kemarin berstatus BUKA hari ini tidak punya status sama
 * sekali, dan tidak ada yang memberi tahu admin bahwa itu terjadi.
 *
 * Sejak W8 jadwal tinjaunya per jenis status (Q9; ruling W8-R13): Buka mengikuti ambang turun
 * status Buka paket luring, jenis lain `hiking.freshness.status_review_hours`. Catatan dari meja
 * kurator menyimpan jadwalnya di `next_review_at` (paling lambat tanggal berakhirnya); catatan
 * lama menghitungnya dari `verified_at`. Antrean admin, meja kurator, dan kata kesegaran untuk
 * pendaki membaca satu aturan yang sama.
 */
class DataFreshnessService
{
    /**
     * Sudah lewat masa berlakunya. Jalurnya sekarang berjalan tanpa status resmi.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function expired(): Collection
    {
        return $this->dasar()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }

    /**
     * Akan kedaluwarsa dalam waktu dekat, jadi masih sempat diperpanjang sebelum jalurnya
     * kehilangan status.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function expiringSoon(): Collection
    {
        return $this->dasar()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($this->hariPeringatan())])
            ->get();
    }

    /**
     * Sudah lewat jadwal tinjaunya, atau belum pernah diverifikasi sama sekali.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function stale(): Collection
    {
        return $this->dasar()
            ->where(fn (Builder $q) => $this->lewatJadwal($q))
            ->get();
    }

    /**
     * Jumlah status yang perlu ditinjau, dalam satu query karena angka ini tampil di
     * setiap halaman admin.
     *
     * Kedaluwarsa, akan kedaluwarsa, dan basi dihitung sebagai kategori yang saling
     * lepas supaya satu status tidak terhitung dua kali.
     */
    public function reviewCount(): int
    {
        return $this->dasar()
            ->where(function (Builder $q) {
                $q->where('expires_at', '<', now())
                    ->orWhereBetween('expires_at', [now(), now()->addDays($this->hariPeringatan())])
                    ->orWhere(fn (Builder $basi) => $this->lewatJadwal($basi));
            })
            ->count();
    }

    /**
     * @return array{kedaluwarsa: Collection<int, OfficialStatus>, segera: Collection<int, OfficialStatus>, basi: Collection<int, OfficialStatus>}
     */
    public function review(): array
    {
        $kedaluwarsa = $this->expired();

        return [
            'kedaluwarsa' => $kedaluwarsa,
            'segera' => $this->expiringSoon(),

            // Yang sudah kedaluwarsa tidak diulang di daftar basi; admin sudah melihatnya
            // di kategori yang lebih mendesak.
            'basi' => $this->stale()->reject(fn (OfficialStatus $s) => $kedaluwarsa->contains('id', $s->id)),
        ];
    }

    /**
     * Keadaan satu catatan status untuk pendaki (spec v3 bagian 7): "Terkini" bila
     * diverifikasi dalam jadwal tinjaunya dan tidak segera kedaluwarsa, "Perlu ditinjau" bila
     * belum pernah diverifikasi, lewat jadwal tinjaunya, atau segera kedaluwarsa, dan
     * "Kedaluwarsa" bila masa berlakunya sudah lewat. Aturannya sama dengan antrean tinjau
     * admin di atas, jadi pendaki dan admin tidak pernah membaca dua penilaian.
     */
    public function keadaan(OfficialStatus $status): string
    {
        return $this->keadaanDari($status->verified_at, $status->expires_at, $status->status, $status->next_review_at);
    }

    /**
     * Keadaan kesegaran dari stempelnya saja, fungsi murni yang dipakai keadaan() dan rincian
     * jalur (I1 tinjauan akhir W3). Rincian membaca snapshot status yang bercache, jadi
     * kesegarannya dihitung dari stempel pada saat halaman dirender, bukan ikut dibekukan di
     * cache, dan kartu Jelajah serta rincian menyebut kata yang sama untuk catatan yang sama.
     */
    public function keadaanDari(?CarbonInterface $diverifikasi, ?CarbonInterface $kedaluwarsa, ?OfficialStatusValue $nilai = null, ?CarbonInterface $jadwal = null): string
    {
        if ($kedaluwarsa !== null && $kedaluwarsa->isPast()) {
            return 'Kedaluwarsa';
        }

        $batas = $jadwal ?? $diverifikasi?->copy()->addHours($this->jamTinjau($nilai ?? OfficialStatusValue::UNKNOWN));
        $basi = $batas === null || now()->greaterThan($batas);
        $segera = $kedaluwarsa !== null && $kedaluwarsa->lte(now()->addDays($this->hariPeringatan()));

        return $basi || $segera ? 'Perlu ditinjau' : 'Terkini';
    }

    /**
     * Jam sesudah verifikasi terakhir sampai sebuah jenis status perlu ditinjau ulang.
     */
    public function jamTinjau(OfficialStatusValue $nilai): int
    {
        if ($nilai === OfficialStatusValue::OPEN) {
            return (int) config('hiking.field_pack.open_status_stale_after_hours', 24);
        }

        return (int) (config('hiking.freshness.status_review_hours.'.$nilai->value) ?? 168);
    }

    /**
     * Jadwal tinjau berikutnya untuk catatan meja kurator: sesudah jam tinjau jenisnya, tetapi
     * tidak pernah sesudah tanggal berakhir yang disebut pengelola.
     */
    public function jadwalUntuk(OfficialStatusValue $nilai, CarbonInterface $diverifikasi, ?CarbonInterface $berakhir): Carbon
    {
        $jadwal = Carbon::instance($diverifikasi)->addHours($this->jamTinjau($nilai));

        return $berakhir !== null && $berakhir->lessThan($jadwal) ? Carbon::instance($berakhir) : $jadwal;
    }

    /**
     * Syarat "lewat jadwal tinjau" dalam SQL: jadwal tersimpan bila ada, selain itu verifikasi
     * terakhir ditambah jam tinjau jenisnya, atau belum pernah diverifikasi.
     */
    private function lewatJadwal(Builder $q): void
    {
        $kini = now();

        $q->where(fn (Builder $terjadwal) => $terjadwal->whereNotNull('next_review_at')->where('next_review_at', '<', $kini))
            ->orWhere(function (Builder $lama) use ($kini) {
                $lama->whereNull('next_review_at')->where(function (Builder $v) use ($kini) {
                    $v->whereNull('verified_at');

                    foreach (OfficialStatusValue::cases() as $nilai) {
                        $v->orWhere(fn (Builder $jenis) => $jenis
                            ->where('status', $nilai->value)
                            ->where('verified_at', '<', $kini->copy()->subHours($this->jamTinjau($nilai))));
                    }
                });
            });
    }

    private function dasar(): Builder
    {
        // Antrean tinjau berkala hanya memuat catatan yang dibaca pendaki; draf dan pembukaan
        // yang menunggu verifikasi punya antreannya sendiri di meja kurator (W8). Catatan yang
        // digantikan sudah berakhir karena penggantinya, bukan kedaluwarsa.
        return OfficialStatus::query()->terlihatPendaki()->whereNull('superseded_by_id')->with('statusable');
    }

    private function hariPeringatan(): int
    {
        return (int) config('hiking.freshness.status_expiry_warning_days', 14);
    }
}
```

`app/Services/DataResmi/TerbitStatus.php`:

1. Ganti:

```php
use App\Services\AuditLogService;
use App\Support\Timezone;
```

dengan:

```php
use App\Services\AuditLogService;
use App\Services\DataFreshnessService;
use App\Support\Timezone;
```

2. Ganti:

```php
        'status', 'verification_state', 'visible_before_verification', 'verified_by', 'verified_at',
        'effective_at', 'expires_at', 'evidence_sha256', 'superseded_by_id',
    ];
```

dengan:

```php
        'status', 'verification_state', 'visible_before_verification', 'verified_by', 'verified_at',
        'effective_at', 'expires_at', 'evidence_sha256', 'superseded_by_id', 'next_review_at',
    ];
```

3. Ganti:

```php
        private readonly PemeriksaanKonsistensi $konsistensi,
    ) {}
```

dengan:

```php
        private readonly PemeriksaanKonsistensi $konsistensi,
        private readonly DataFreshnessService $kesegaran,
    ) {}
```

4. Ganti:

```php
                'fetched_at' => $draf->captured_at ?? now(),
            ])->save();
```

dengan:

```php
                'fetched_at' => $draf->captured_at ?? now(),
                'next_review_at' => $this->kesegaran->jadwalUntuk($draf->status, now(), $draf->expires_at),
            ])->save();
```

5. Ganti:

```php
                'verified_at' => now(),
            ])->save();
```

dengan:

```php
                'verified_at' => now(),
                'next_review_at' => $this->kesegaran->jadwalUntuk($catatan->status, now(), $catatan->expires_at),
            ])->save();
```

6. Ganti:

```php
            $this->audit->record($pemeriksa, 'official_status.verified', $catatan, $sebelum, $catatan->only(self::DIAUDIT));

            return $catatan;
```

dengan:

```php
            $this->audit->record($pemeriksa, 'official_status.verified', $catatan, $sebelum, $catatan->only(self::DIAUDIT));

            return $catatan;
        });
    }

    /**
     * Tinjau berkala (spec v3 bagian 10.7 langkah 6): kurator mencocokkan ulang catatan yang
     * sedang terbaca dengan sumbernya dan keadaannya masih sama. Waktu verifikasi dan jadwal
     * tinjau berikutnya diperbarui; nilai catatan tidak berubah. Bila keadaannya berubah, kurator
     * mencatat catatan baru yang menggantikannya, bukan memakai langkah ini.
     */
    public function tinjauUlang(User $kurator, OfficialStatus $catatan): OfficialStatus
    {
        Gate::forUser($kurator)->authorize('review', $catatan);

        return DB::transaction(function () use ($kurator, $catatan): OfficialStatus {
            $sebelum = $catatan->only(self::DIAUDIT);
            $catatan->forceFill([
                'verified_at' => now(),
                'next_review_at' => $this->kesegaran->jadwalUntuk($catatan->status, now(), $catatan->expires_at),
            ])->save();
            $this->audit->record($kurator, 'official_status.reviewed', $catatan, $sebelum, $catatan->only(self::DIAUDIT));

            return $catatan;
```

Tulis ulang `app/Services/OfficialStatusService.php` utuh:

```php
<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class OfficialStatusService
{
    /**
     * Catatan status yang sedang berlaku untuk sebuah entitas: catatan terlihat terakhir yang
     * sudah dimulai, selama belum berakhir.
     *
     * Bila catatan terakhir itu sudah berakhir, jawabannya kosong (UNKNOWN), bukan catatan yang
     * lebih lama: spec v3 bagian 10.7, kedaluwarsa menjadi Belum diketahui dan tidak pernah
     * otomatis Buka (ruling W8-R14). Sebelum W8 penutupan yang berakhir mengembalikan catatan Buka
     * yang lebih lama.
     */
    public function currentFor(Model $statusable): ?OfficialStatus
    {
        return $this->terakhirPada($statusable, Carbon::now());
    }

    private function terakhirPada(Model $statusable, CarbonInterface $waktu): ?OfficialStatus
    {
        $terakhir = OfficialStatus::query()
            ->where('statusable_type', $statusable->getMorphClass())
            ->where('statusable_id', $statusable->getKey())
            ->terlihatPendaki()
            ->dimulaiPalingLambat($waktu)
            ->terbaruDulu()
            ->first();

        return $terakhir?->berlakuPada($waktu) ? $terakhir : null;
    }

    /**
     * BR-07: absence of information is reported as UNKNOWN, never assumed to be OPEN.
     */
    public function currentValueFor(Model $statusable): OfficialStatusValue
    {
        return $this->currentFor($statusable)?->status ?? OfficialStatusValue::UNKNOWN;
    }

    /**
     * PRD §42: a mountain being OPEN says nothing about its individual trails, so an OPEN
     * mountain never upgrades a trail. Explicit mountain-level restrictions do cascade down.
     */
    public function effectiveStatusForTrail(Trail $trail): OfficialStatusValue
    {
        $jalur = $this->currentFor($trail);
        $mountain = $trail->relationLoaded('mountain') ? $trail->mountain : $trail->mountain()->first();
        $gunung = $mountain instanceof Mountain ? $this->currentFor($mountain) : null;

        return self::catatanPenentu($jalur, $gunung)?->status ?? OfficialStatusValue::UNKNOWN;
    }

    /**
     * Catatan yang menentukan status efektif sebuah jalur menurut kaskade §42 (R-009).
     *
     * Rincian jalur (`snapshotForTrail()`) dan daftar (`StatusJalurRingkas::untuk()`)
     * memakai fungsi yang sama, sehingga keduanya selalu mengutip sumber yang sama untuk
     * jalur yang sama: gunung CLOSED menentukan lewat catatan gunung, gunung RESTRICTED
     * (dan jalur belum CLOSED) juga lewat catatan gunung, selain itu catatan jalur sendiri
     * yang menentukan (boleh null bila tidak ada catatan yang sedang berlaku). Sebelum
     * fungsi ini ada, snapshotForTrail() mengutip catatan jalur untuk field deskriptif
     * (sumber, jenis sumber, cakupan, waktu) walau kaskade menentukan lewat gunung.
     */
    public static function catatanPenentu(?OfficialStatus $jalur, ?OfficialStatus $gunung): ?OfficialStatus
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
     * Status yang berlaku pada satu tanggal, bukan hari ini.
     *
     * Dipakai ketika yang dinilai adalah rencana, bukan keadaan sekarang. Penutupan
     * tahunan diumumkan jauh hari, jadi rencana untuk Januari harus dinilai terhadap
     * Januari.
     */
    public function statusOnDate(Model $statusable, CarbonInterface $date): OfficialStatusValue
    {
        return $this->terakhirPada($statusable, $date)?->status ?? OfficialStatusValue::UNKNOWN;
    }

    /**
     * Status efektif sebuah jalur pada satu tanggal, lengkap dengan kaskade §42.
     *
     * Pembatasan turun dari gunung ke jalur; kelonggaran tidak. Aturannya sama dengan
     * versi "hari ini", hanya tanggalnya yang berbeda.
     */
    public function effectiveStatusForTrailOn(Trail $trail, CarbonInterface $date): OfficialStatusValue
    {
        $trailStatus = $this->statusOnDate($trail, $date);
        $mountain = $trail->relationLoaded('mountain') ? $trail->mountain : $trail->mountain()->first();

        if (! $mountain instanceof Mountain) {
            return $trailStatus;
        }

        $mountainStatus = $this->statusOnDate($mountain, $date);

        if ($mountainStatus === OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::CLOSED;
        }

        if ($mountainStatus === OfficialStatusValue::RESTRICTED && $trailStatus !== OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::RESTRICTED;
        }

        return $trailStatus;
    }

    /**
     * Status efektif untuk banyak jalur sekaligus.
     *
     * Versi per-jalur membutuhkan dua query masing-masing, sehingga mesin rekomendasi
     * tumbuh linear terhadap jumlah kandidat. Di sini seluruh status jalur diambil
     * dalam satu query dan seluruh status gunung dalam satu query lagi, lalu aturan
     * kaskade §42 diterapkan di memori.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, OfficialStatusValue> berkunci id jalur
     */
    public function effectiveStatusesForTrails(Collection $trails): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        $trailStatuses = $this->latestStatusesFor((new Trail)->getMorphClass(), $trails->pluck('id'));
        $mountainIds = $trails->pluck('mountain_id')->filter()->unique();
        $mountainStatuses = $this->latestStatusesFor((new Mountain)->getMorphClass(), $mountainIds);

        $effective = [];

        foreach ($trails as $trail) {
            $trailStatus = $trailStatuses[$trail->id] ?? OfficialStatusValue::UNKNOWN;
            $mountainStatus = $mountainStatuses[$trail->mountain_id] ?? OfficialStatusValue::UNKNOWN;

            $effective[$trail->id] = $this->cascade($trailStatus, $mountainStatus);
        }

        return $effective;
    }

    /**
     * Pembatasan segmen untuk banyak jalur sekaligus, dalam dua query.
     *
     * @param  Collection<int, Trail>  $trails
     * @return array<int, array<int, array{segment: string, status: OfficialStatusValue, reason: ?string}>>
     */
    public function segmentRestrictionsForTrails(Collection $trails): array
    {
        $restrictions = array_fill_keys($trails->pluck('id')->all(), []);

        if ($trails->isEmpty()) {
            return $restrictions;
        }

        $segments = TrailSegment::query()
            ->whereIn('trail_id', $trails->pluck('id'))
            ->get(['id', 'trail_id', 'name']);

        if ($segments->isEmpty()) {
            return $restrictions;
        }

        $kini = Carbon::now();
        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->terlihatPendaki()
            ->dimulaiPalingLambat($kini)
            ->terbaruDulu()
            ->get();

        foreach ($segments as $segment) {
            $latest = $statuses->firstWhere('statusable_id', $segment->id);

            if ($latest === null || ! $latest->berlakuPada($kini) || ! $this->isRestrictive($latest->status)) {
                continue;
            }

            $restrictions[$segment->trail_id][] = [
                'segment' => $segment->name,
                'status' => $latest->status,
                'reason' => $latest->reason,
            ];
        }

        return $restrictions;
    }

    /**
     * Status terbaru yang sedang berlaku untuk setiap entitas dari satu tipe.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $ids
     * @return array<int, OfficialStatusValue>
     */
    private function latestStatusesFor(string $morphClass, $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $kini = Carbon::now();
        $rows = OfficialStatus::query()
            ->where('statusable_type', $morphClass)
            ->whereIn('statusable_id', $ids)
            ->terlihatPendaki()
            ->dimulaiPalingLambat($kini)
            ->terbaruDulu()
            ->get(['statusable_id', 'status', 'expires_at']);

        $latest = [];

        foreach ($rows as $row) {
            // Urutan sudah menurun, jadi kemunculan pertama adalah yang terakhir dimulai; bila
            // sudah berakhir, entitas itu Belum diketahui (ruling W8-R14).
            $latest[$row->statusable_id] ??= $row->berlakuPada($kini) ? $row->status : OfficialStatusValue::UNKNOWN;
        }

        return $latest;
    }

    /**
     * PRD §42: pembatasan turun dari gunung ke jalur; kelonggaran tidak pernah naik.
     */
    private function cascade(OfficialStatusValue $trailStatus, OfficialStatusValue $mountainStatus): OfficialStatusValue
    {
        if ($mountainStatus === OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::CLOSED;
        }

        if ($mountainStatus === OfficialStatusValue::RESTRICTED && $trailStatus !== OfficialStatusValue::CLOSED) {
            return OfficialStatusValue::RESTRICTED;
        }

        return $trailStatus;
    }

    private function isRestrictive(OfficialStatusValue $status): bool
    {
        return in_array($status, [OfficialStatusValue::CLOSED, OfficialStatusValue::RESTRICTED], true);
    }

    /**
     * PRD §42: status dapat melekat pada SEGMENT, bukan hanya gunung dan jalur.
     *
     * Sebuah jalur boleh saja berstatus OPEN sementara salah satu segmennya ditutup:
     * kasus nyata Semeru 2026, ketika pendakian dibuka tetapi hanya sampai Ranu Kumbolo.
     * Pembatasan seperti ini tidak mengeksklusi jalur (PRD §26 memisahkan pembatasan dari
     * pengecualian), tetapi wajib terlihat oleh pendaki.
     *
     * Satu query untuk seluruh segmen, bukan per segmen.
     *
     * @return array<int, array{segment: string, status: OfficialStatusValue, reason: ?string}>
     */
    public function segmentRestrictionsForTrail(Trail $trail): array
    {
        $segments = $trail->segments()->get(['id', 'name']);

        if ($segments->isEmpty()) {
            return [];
        }

        $kini = Carbon::now();
        $statuses = OfficialStatus::query()
            ->where('statusable_type', (new TrailSegment)->getMorphClass())
            ->whereIn('statusable_id', $segments->pluck('id'))
            ->terlihatPendaki()
            ->dimulaiPalingLambat($kini)
            ->terbaruDulu()
            ->get();

        $restrictions = [];

        foreach ($segments as $segment) {
            // Status paling akhir berlaku untuk tiap segmen; urutan sudah menurun.
            $latest = $statuses->firstWhere('statusable_id', $segment->id);

            if ($latest === null || ! $latest->berlakuPada($kini) || ! in_array($latest->status, [
                OfficialStatusValue::CLOSED,
                OfficialStatusValue::RESTRICTED,
            ], true)) {
                continue;
            }

            $restrictions[] = [
                'segment' => $segment->name,
                'status' => $latest->status,
                'reason' => $latest->reason,
            ];
        }

        return $restrictions;
    }

    /**
     * PRD §97: hanya data publik yang boleh masuk cache bersama. Snapshot status resmi
     * adalah data publik yang sama untuk setiap pengguna, sehingga aman dibagikan.
     * Tidak ada apa pun yang terikat pengguna di dalamnya.
     */
    public function cachedSnapshotForTrail(Trail $trail): array
    {
        $kunci = self::snapshotCacheKey($trail->id);
        $tersimpan = Cache::get($kunci);

        if (is_array($tersimpan)) {
            return $tersimpan;
        }

        $snapshot = $this->snapshotForTrail($trail);
        Cache::put($kunci, $snapshot, $this->umurSnapshot($trail));

        return $snapshot;
    }

    /**
     * Umur snapshot bercache: TTL publik, tetapi tidak pernah melewati saat catatan jalur atau
     * gunungnya berikutnya mulai atau berhenti berlaku (titipan W4-6h OOS6).
     *
     * Status yang berlaku karena waktunya tiba tidak memicu event model, jadi tanpa batas ini
     * snapshot dapat menyebut status lama sampai lima menit, sementara pembaca langsung
     * (kartu status, StatusJalurRingkas) sudah memakai yang baru. Satu kueri, hanya saat
     * snapshot dibangun.
     */
    private function umurSnapshot(Trail $trail): int
    {
        $ttl = (int) config('hiking.cache.public_ttl_seconds');
        $kini = Carbon::now();
        $sasaran = [[(new Trail)->getMorphClass(), $trail->id]];

        if ($trail->mountain_id !== null) {
            $sasaran[] = [(new Mountain)->getMorphClass(), $trail->mountain_id];
        }

        $baris = OfficialStatus::query()
            ->where(function ($q) use ($sasaran) {
                foreach ($sasaran as [$jenis, $id]) {
                    $q->orWhere(fn ($w) => $w->where('statusable_type', $jenis)->where('statusable_id', $id));
                }
            })
            ->terlihatPendaki()
            ->where(fn ($q) => $q->where('effective_at', '>', $kini)->orWhere('expires_at', '>', $kini))
            ->get(['effective_at', 'expires_at']);

        $batas = $baris
            ->flatMap(fn (OfficialStatus $s) => [$s->effective_at, $s->expires_at])
            ->filter(fn ($waktu) => $waktu !== null && $waktu->greaterThan($kini))
            ->min();

        if ($batas === null) {
            return $ttl;
        }

        // Satu detik sesudah batasnya: expires_at masih berlaku pada detik itu sendiri
        // (scopeEffectiveOn memakai >=).
        return max(1, min($ttl, (int) $kini->diffInSeconds($batas) + 1));
    }

    /**
     * Dipanggil ketika admin mengubah status resmi, supaya perubahan yang menyangkut
     * pembatasan jalur tidak tertahan di cache sampai TTL-nya habis.
     */
    public static function forgetCachedSnapshot(int $trailId): void
    {
        Cache::forget(self::snapshotCacheKey($trailId));
    }

    /**
     * Snapshot membawa jenis sumber dari data_sources (R-009), jadi mengubah jenis sebuah
     * sumber membuang snapshot setiap jalur yang statusnya mengutip sumber itu (titipan
     * W3-1j), dengan kaskade yang sama seperti perubahan status.
     */
    public static function forgetSnapshotsForSource(int $dataSourceId): void
    {
        OfficialStatus::query()
            ->where('data_source_id', $dataSourceId)
            ->get(['id', 'statusable_type', 'statusable_id'])
            ->each(fn (OfficialStatus $status) => $status->forgetAffectedTrailCaches());
    }

    private static function snapshotCacheKey(int $trailId): string
    {
        return "trail:{$trailId}:official-status-snapshot";
    }

    /**
     * @return array<string, mixed> snapshot used by readiness checks and condition aggregation
     */
    public function snapshotForTrail(Trail $trail): array
    {
        // Catatan jalur dan catatan gunung masing-masing diambil sekali (tanpa kueri
        // ganda); catatanPenentu() menentukan yang mana benar-benar berlaku (§42), dan
        // field deskriptif di bawah selalu mengutip catatan itu, bukan selalu catatan
        // jalur.
        $jalur = $this->currentFor($trail);
        $mountain = $trail->relationLoaded('mountain') ? $trail->mountain : $trail->mountain()->first();
        $gunung = $mountain instanceof Mountain ? $this->currentFor($mountain) : null;
        $record = self::catatanPenentu($jalur, $gunung);
        $status = $record?->status ?? OfficialStatusValue::UNKNOWN;

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'scope' => $record?->scope?->value,
            'source' => $record?->source,
            // Jenis sumber dari data_sources (R-009): halaman menyebut "Resmi" hanya bila
            // catatannya memang bersumber OFFICIAL, bukan karena tersimpan di tabel ini.
            'source_type' => $record?->dataSource?->source_type?->value,
            'source_url' => $record?->source_url,
            'published_at' => $record?->published_at?->toIso8601String(),
            'verified_at' => $record?->verified_at?->toIso8601String(),
            'fetched_at' => $record?->fetched_at?->toIso8601String(),
            // Stempel untuk waktu yang dikutip beserta awalannya dan kesegaran di rincian
            // (I1 tinjauan akhir W3): kesegaran dihitung saat halaman dirender, bukan disimpan
            // di snapshot yang bercache.
            'effective_at' => $record?->effective_at?->toIso8601String(),
            'expires_at' => $record?->expires_at?->toIso8601String(),
            // Jadwal tinjau catatan meja kurator (W8, ruling W8-R13); kosong untuk catatan lama.
            'next_review_at' => $record?->next_review_at?->toIso8601String(),
            'reason' => $record?->reason,
            'notes' => $record?->notes,
        ];
    }
}
```

`app/Services/Trails/StatusJalurRingkas.php`:

1. Ganti:

```php
        }

        $baris = OfficialStatus::query()
```

dengan:

```php
        }

        $kini = Carbon::now();
        $baris = OfficialStatus::query()
```

2. Ganti:

```php
            ->terlihatPendaki()
            ->currentlyEffective()
            ->with('dataSource:id,source_type')
```

dengan:

```php
            ->terlihatPendaki()
            ->dimulaiPalingLambat($kini)
            ->with('dataSource:id,source_type')
```

3. Ganti:

```php
        $terbaru = [];

        foreach ($baris as $satu) {
            // Urutan sudah menurun, jadi kemunculan pertama adalah yang terbaru.
            $terbaru[$satu->statusable_id] ??= $satu;
        }
```

dengan:

```php
        $terbaru = [];
        $dilihat = [];

        foreach ($baris as $satu) {
            // Urutan sudah menurun, jadi kemunculan pertama adalah yang terakhir dimulai; bila
            // sudah berakhir, entitas itu Belum diketahui (ruling W8-R14), sama dengan
            // OfficialStatusService::currentFor().
            if (isset($dilihat[$satu->statusable_id])) {
                continue;
            }

            $dilihat[$satu->statusable_id] = true;

            if ($satu->berlakuPada($kini)) {
                $terbaru[$satu->statusable_id] = $satu;
            }
        }
```

`app/Services/Trails/TrailDetailPage.php`:

1. Ganti:

```php
            'kesegaran' => $adaCatatan
                ? $this->kesegaran->keadaanDari($this->stempel($snapshot['verified_at'] ?? null), $this->stempel($snapshot['expires_at'] ?? null))
                : null,
```

dengan:

```php
            'kesegaran' => $adaCatatan
                ? $this->kesegaran->keadaanDari($this->stempel($snapshot['verified_at'] ?? null), $this->stempel($snapshot['expires_at'] ?? null), $nilai, $this->stempel($snapshot['next_review_at'] ?? null))
                : null,
```

`config/hiking.php`:

1. Ganti:

```php
    'freshness' => [
        // Status yang tidak diverifikasi ulang selama ini dianggap perlu ditinjau.
        'status_review_days' => 90,

        // Peringatan sebelum masa berlaku habis, agar sempat diperpanjang sebelum
```

dengan:

```php
    'freshness' => [
        // Jadwal tinjau per jenis status (Q9; ruling W8-R13), menggantikan ambang tunggal 90 hari.
        // Status yang tidak diverifikasi ulang selama ini dianggap perlu ditinjau, dan meja kurator
        // mengantrekannya. Buka tidak tercantum di sini: ia memakai field_pack.open_status_stale_after_hours
        // (24 jam, W6-R8), supaya status Buka turun pada batas yang sama di layar daring dan di paket
        // luring; Buka paling berisiko terbaca aman (R-012). Pembatasan dan Belum diketahui: tujuh hari,
        // sama dengan masa "terkini" laporan komunitas (conditions.community_current_days), jadi status
        // pembatasan tidak dianggap lebih segar daripada kabar lapangan terkini yang paling lama.
        'status_review_hours' => [
            'RESTRICTED' => 168,
            'CLOSED' => 168,
            'UNKNOWN' => 168,
        ],

        // Peringatan sebelum masa berlaku habis, agar sempat diperpanjang sebelum
```

- [ ] **Step 4: Build dan tipe.** Tidak ada TypeScript, rute, atau daftar rute bukan halaman React yang berubah; build
tidak perlu.

Run: `node_modules/.bin/tsc --noEmit`
Expected: tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Models/OfficialStatus.php app/Policies/OfficialStatusPolicy.php app/Services/DataFreshnessService.php app/Services/DataResmi/TerbitStatus.php app/Services/OfficialStatusService.php app/Services/Trails/StatusJalurRingkas.php app/Services/Trails/TrailDetailPage.php config/hiking.php tests/Feature/DataFreshnessReviewTest.php tests/Feature/OfficialStatusSeederTest.php tests/Feature/TrustGrammarTest.php tests/Spatial/OfficialStatusReviewScheduleTest.php tests/Unit/DataFreshnessServiceTest.php`, lalu `php artisan test --compact --filter='DataFreshnessReview|DataFreshnessService|TrustGrammar|OfficialStatusSeeder|SchedulerHealth|OfficialStatusReviewSchedule'`
Expected: PASS. Pada simulasi rencana: 62 test, 60 lulus, 2 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.191 test, 2.189 lulus, 2 dilewati (sebelum tugas: 2.185).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 36 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 8: Meja kurator, dan formulir status admin dipensiunkan

Spec v3 bagian 10.7 (alur tujuh langkah), induk W8 butir 9; ruling W8-R15 dan W8-R16. Tiga halaman meja kurator:
`Curator/Desk` ("Keanggotaan Anda", "Menunggu verifikasi Anda", "Tinjau berkala", "Dipersoalkan", "Draf Anda"),
`Curator/Status` (rincian catatan dengan sumber, arsip, temuan konsistensi, langkah, dan aksi "Kirim untuk terbit",
"Verifikasi dan terbitkan", "Sudah dicek ulang ke sumber", "Ubah draf", "Catat pengganti", "Persoalkan", "Hapus draf"),
dan `Curator/StatusForm` (sasaran dan badan hanya dari daftar kawasan kurator, nilai, kategori alasan, cara berakhir,
nomor dan tanggal surat, kutipan, ringkasan, jenis bukti, dan berkas bukti). Controller tipis memanggil `TerbitStatus`;
kelas halaman `MejaKurator` dan `HalamanCatatanStatus` merakit props dalam jumlah kueri tetap. Pintu "Meja kurator" di
menu akun hanya untuk kurator aktif (`auth.user.isKurator`). Rute tulis admin `admin.statuses.store` dan
`StoreOfficialStatusRequest` dicabut; `Admin/OfficialStatuses` menjadi ringkasan dan antrean baca saja yang menunjuk meja.

**Files:**
- Create: `app/Http/Controllers/Curator/CuratorDeskController.php`, `app/Http/Controllers/Curator/CuratorStatusController.php`, `app/Http/Requests/Curator/PersoalkanStatusRequest.php`, `app/Http/Requests/Curator/SimpanCatatanStatusRequest.php`, `app/Services/DataResmi/HalamanCatatanStatus.php`, `app/Services/DataResmi/MejaKurator.php`, `resources/js/components/kurator/DaftarAntrean.tsx`, `resources/js/components/kurator/types.ts`, `resources/js/pages/Curator/Desk.tsx`, `resources/js/pages/Curator/Status.tsx`, `resources/js/pages/Curator/StatusForm.tsx`
- Modify: `app/Http/Controllers/Admin/OfficialStatusController.php` (ditulis ulang utuh), `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Resources/Admin/OfficialStatusRowResource.php`, `app/Policies/OfficialStatusPolicy.php`, `app/Services/DataFreshnessService.php` (ditulis ulang utuh), `resources/js/components/akun/PintuAkun.tsx`, `resources/js/layouts/MenuAkun.tsx`, `resources/js/pages/Admin/OfficialStatuses.tsx` (ditulis ulang utuh), `resources/js/types/index.d.ts`, `routes/web.php`
- Delete: `app/Http/Requests/Admin/StoreOfficialStatusRequest.php`
- Test: `tests/Feature/CuratorDeskTest.php` (baru), `tests/Feature/EmptyStateCoverageTest.php`, `tests/Feature/OfficialStatusPageTest.php`, `tests/Feature/OfficialStatusSafetySentenceTest.php`, `tests/Feature/OfficialStatusTargetTest.php` (ditulis ulang utuh), `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `TerbitStatus` (Tugas 5 sampai 7), `PemeriksaanKonsistensi` (Tugas 6), `Yurisdiksi` dan `OfficialStatusPolicy` (Tugas 3 sampai 7), `ArsipBukti` (Tugas 4), `DataFreshnessService` (Tugas 7); primitif `PageHeader`, `Button`, `Alert`, `Lencana`, `Tautan`, `EmptyState`, `Dialog`, `SelectField`, `TextareaField`, `StatusAkses`.
- Produces:
  - controller `Curator\CuratorDeskController::index()` dan `Curator\CuratorStatusController` (`create`, `store`, `show`, `edit`, `update`, `destroy`, `submit`, `verify`, `dispute`, `review`); Form Request `Curator\SimpanCatatanStatusRequest` (`sasaran(): ?Model`, `otoritas(): Authority`, `kolom(Model): array`) dan `Curator\PersoalkanStatusRequest`;
  - rute `curator.desk` (GET `kurator`), `curator.statuses.create`, `.store`, `.show`, `.edit`, `.update`, `.destroy`, `.submit`, `.verify`, `.dispute`, `.review`; tulisan berbatas laju `meja-kurator`;
  - `App\Services\DataResmi\MejaKurator`: `props(User): array`, `lingkup(User): Closure`, `baris(OfficialStatus, ?string): array`; `App\Services\DataResmi\HalamanCatatanStatus`: `formulir(User, ?OfficialStatus, ?string): array`, `rincian(User, OfficialStatus): array`, dan pembantu statis `namaSasaran()`, `kunciSasaran()`, `masaBerlaku()`, `zona()`;
  - `DataFreshnessService::expired()`, `expiringSoon()`, `stale()`, dan `review()` menerima `?Closure $lingkup`;
  - prop bersama `auth.user.isKurator`; tipe TypeScript di `resources/js/components/kurator/types.ts` (`BarisAntrean`, `BadanKurator`, `AntreanKurator`, `Temuan`, `LangkahCatatan`, `IsianStatus`, `KelompokSasaran`, `PilihanFormulir`, `DrafKurator`, `RincianCatatan`, `AksiCatatan`, `JejakCatatan`); komponen `DaftarAntrean`;
  - halaman pratinjau `meja-kurator`, `catatan-kurator`, `catatan-kurator-draf`, dan `formulir-kurator`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction CuratorDeskTest`, lalu isinya diganti persis. `OfficialStatusTargetTest` ditulis ulang utuh (sasaran
kini diperiksa formulir meja kurator), dan `OfficialStatusPageTest`, `StatusCacheInvalidationTest`,
`OfficialStatusSafetySentenceTest`, serta `EmptyStateCoverageTest` diperbarui dengan alasan di docblock.

Buat `tests/Feature/CuratorDeskTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusVerificationState;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\DataResmi\TerbitStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Meja kurator lewat HTTP (spec v3 bagian 10.7; rencana induk W8 butir 9; ruling W8-R15 dan
 * W8-R16): akses per peran, antrean yang hanya memuat kawasan kurator, formulir yang mengambil
 * pencatat dan penerbit dari sesi, alur kirim, verifikasi, dan persoalkan, batas laju, anggaran
 * kueri, dan draf yang tidak pernah terlihat pendaki.
 */
class CuratorDeskTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isianFormulir(array $ganti = []): array
    {
        return [
            'sasaran' => 'MOUNTAIN:'.$this->gunung->id,
            'otoritas' => $this->balai->id,
            'status' => OfficialStatusValue::CLOSED->value,
            'jenis_bukti' => 'OFFICIAL_LETTER',
            'url_sumber' => 'https://gedepangrango.org/pengumuman',
            'bukti' => $this->suratPdf(),
            'nomor_surat' => 'PG.12/BBTNGGP/2026',
            'tanggal_surat' => now()->subDay()->toDateString(),
            'mulai_berlaku' => now()->toDateString(),
            'cara_berakhir' => 'UNTIL_NOTICE',
            'golongan_alasan' => 'FIRE',
            'ringkasan' => 'Pendakian ditutup karena kebakaran hutan.',
            ...$ganti,
        ];
    }

    public function test_only_active_curators_open_the_desk(): void
    {
        $this->get(route('curator.desk'))->assertRedirect(route('login'));

        foreach ([UserRole::HIKER, UserRole::MODERATOR, UserRole::ADMIN] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran->value]))->get(route('curator.desk'))->assertForbidden();
        }

        $tanpaKeanggotaan = User::factory()->create(['role' => UserRole::CURATOR->value]);
        $this->actingAs($tanpaKeanggotaan)->get(route('curator.desk'))->assertForbidden();

        $this->actingAs($this->kurator())
            ->get(route('curator.desk'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Curator/Desk')
                ->where('auth.user.isKurator', true)
                ->where('badan.0.nama', 'Balai Besar TN Gede Pangrango (BBTNGGP)'));
    }

    public function test_the_desk_lists_only_its_area_and_never_asks_you_to_verify_yourself(): void
    {
        $saya = $this->kurator();
        $rekan = $this->kurator();
        $menungguSaya = app(TerbitStatus::class)->kirim($rekan, $this->draf($rekan, OfficialStatusValue::OPEN));
        app(TerbitStatus::class)->kirim($saya, $this->draf($saya, OfficialStatusValue::RESTRICTED, ['effective_at' => now()->addDay()]));
        $drafSaya = $this->draf($saya, OfficialStatusValue::CLOSED);
        $this->draf($rekan, OfficialStatusValue::CLOSED);

        $luar = Mountain::factory()->create();
        OfficialStatus::factory()->closed()->create(['statusable_type' => $luar->getMorphClass(), 'statusable_id' => $luar->id, 'scope' => 'MOUNTAIN', 'expires_at' => now()->subDay()]);
        $kedaluwarsa = $this->catatanLama(OfficialStatusValue::CLOSED, ['effective_at' => now()->subMonths(2), 'expires_at' => now()->subDay()], Trail::factory()->for($this->gunung)->create());

        $this->actingAs($saya)
            ->get(route('curator.desk'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('antrean.verifikasi', 1)
                ->where('antrean.verifikasi.0.id', $menungguSaya->id)
                ->has('antrean.draf', 1)
                ->where('antrean.draf.0.id', $drafSaya->id)
                ->has('antrean.tinjau', 1)
                ->where('antrean.tinjau.0.id', $kedaluwarsa->id)
                ->where('antrean.tinjau.0.alasanTinjau', 'Kedaluwarsa')
                ->where('antrean.tinjau.0.keadaan', 'Catatan lama'));
    }

    /**
     * Pencatat, penerbit, dan keadaan tidak pernah diambil dari isian, walau isiannya
     * menyelundupkannya; bukti diarsipkan dengan sidiknya.
     */
    public function test_a_draft_is_saved_with_who_and_on_whose_behalf_from_the_session(): void
    {
        // 10.00 WIB, tanggal UTC dan WIB sama: mulai berlaku dibaca menurut zona gunung.
        $this->travelTo(Carbon::parse('2026-10-08 03:00:00', 'UTC'));
        $kurator = $this->kurator();
        $lain = User::factory()->create();

        $this->actingAs($kurator)
            ->post(route('curator.statuses.store'), $this->isianFormulir([
                'recorded_by' => $lain->id,
                'verified_by' => $lain->id,
                'verification_state' => 'VERIFIED',
                'authority_id' => Authority::factory()->create()->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Draf tersimpan. Periksa temuan pemeriksaan sebelum mengirim.');

        $draf = OfficialStatus::sole();

        $this->assertSame($kurator->id, $draf->recorded_by);
        $this->assertNull($draf->verified_by);
        $this->assertSame($this->balai->id, $draf->authority_id);
        $this->assertSame(StatusVerificationState::DRAFT, $draf->verification_state);
        $this->assertSame('PG.12/BBTNGGP/2026', $draf->document_number);
        $this->assertNotNull($draf->evidence_sha256);
        $this->assertSame('2026-10-07 17:00:00', $draf->effective_at->utc()->format('Y-m-d H:i:s'), 'Tanggal mulai dibaca menurut zona gunung (00.00 WIB).');
    }

    /**
     * Niat lama OfficialStatusTargetTest (galat pada isiannya, bukan 404) berlaku di formulir meja
     * kurator, ditambah yurisdiksi.
     */
    public function test_a_target_or_body_outside_the_area_is_reported_on_its_field(): void
    {
        $kurator = $this->kurator();
        $luar = Mountain::factory()->create();
        $badanLain = tap(Authority::factory()->create(), fn (Authority $a) => $a->mountains()->attach($this->gunung));

        $this->actingAs($kurator)->post(route('curator.statuses.store'), $this->isianFormulir(['sasaran' => 'MOUNTAIN:'.$luar->id]))
            ->assertSessionHasErrors(['sasaran' => 'Sasaran ini di luar kawasan badan Anda.']);
        $this->actingAs($kurator)->post(route('curator.statuses.store'), $this->isianFormulir(['otoritas' => $badanLain->id]))
            ->assertSessionHasErrors('otoritas');
        $this->actingAs($kurator)->post(route('curator.statuses.store'), $this->isianFormulir(['sasaran' => 'TRAIL:999999']))
            ->assertSessionHasErrors(['sasaran' => 'Sasaran itu tidak ditemukan. Pilih dari daftar.']);

        $this->assertSame(0, OfficialStatus::count());
    }

    public function test_the_flow_runs_through_http_and_the_recorder_cannot_verify(): void
    {
        $pencatat = $this->kurator();
        $pemeriksa = $this->kurator();
        $this->catatanLama(OfficialStatusValue::CLOSED);
        $this->actingAs($pencatat)->post(route('curator.statuses.store'), $this->isianFormulir(['status' => 'OPEN', 'golongan_alasan' => '', 'ringkasan' => 'Pendakian dibuka kembali.']));
        $draf = OfficialStatus::where('verification_state', StatusVerificationState::DRAFT->value)->sole();

        $this->actingAs($pencatat)->post(route('curator.statuses.submit', $draf))
            ->assertRedirect(route('curator.statuses.show', $draf))
            ->assertSessionHas('status', 'Terkirim. Pendaki membacanya sesudah kurator kedua memverifikasi.');
        $this->actingAs($pencatat)->post(route('curator.statuses.verify', $draf))->assertForbidden();

        $this->actingAs($pemeriksa)->get(route('curator.statuses.show', $draf))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Curator/Status')
                ->where('catatan.keadaan', 'Menunggu verifikasi kedua')
                ->where('aksi.verifikasi', route('curator.statuses.verify', $draf))
                ->where('aksi.kirim', null));

        $this->actingAs($pemeriksa)->post(route('curator.statuses.verify', $draf))
            ->assertSessionHas('status', 'Terverifikasi dan terbaca pendaki.');
        $this->actingAs($pemeriksa)->post(route('curator.statuses.dispute', $draf), ['alasan' => 'pendek'])
            ->assertSessionHasErrors('alasan');
        $this->actingAs($pemeriksa)->post(route('curator.statuses.dispute', $draf), ['alasan' => 'Laman balai menarik pengumuman ini.'])
            ->assertSessionHas('status', 'Ditandai dipersoalkan dan tidak lagi terbaca pendaki.');

        $this->assertSame(StatusVerificationState::DISPUTED, $draf->fresh()->verification_state);
    }

    public function test_only_the_recorder_sees_a_draft(): void
    {
        $pencatat = $this->kurator();
        $draf = $this->draf($pencatat, OfficialStatusValue::CLOSED);

        $this->actingAs($pencatat)->get(route('curator.statuses.show', $draf))->assertOk();
        $this->actingAs($pencatat)->get(route('curator.statuses.edit', $draf))->assertOk();
        $this->actingAs($this->kurator())->get(route('curator.statuses.show', $draf))->assertForbidden();
        $this->actingAs($this->kurator())->get(route('curator.evidence.show', $draf))->assertForbidden();
        $this->actingAs($this->kurator())->delete(route('curator.statuses.destroy', $draf))->assertForbidden();
        $this->assertModelExists($draf);
    }

    /**
     * Draf dan pembukaan yang belum diverifikasi tidak pernah sampai ke halaman pendaki.
     */
    public function test_drafts_and_unverified_openings_never_reach_hiker_pages(): void
    {
        // Status terlihat jalur ini Belum diketahui (catatan pabrik); draf Tutup atau pembukaan
        // yang menunggu verifikasi yang bocor akan mengubahnya menjadi Tutup atau Buka.
        $jalur = Trail::factory()->easy()->published()->for($this->gunung)->create();
        $pencatat = $this->kurator();
        $this->draf($pencatat, OfficialStatusValue::CLOSED, [], $jalur);
        app(TerbitStatus::class)->kirim($pencatat, $this->draf($pencatat, OfficialStatusValue::OPEN, [], $jalur));
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('statusResmi.status', null));
        $this->actingAs($pendaki)->get(route('trails.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('trails.data.0.status.status', null));
        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('status.status', null)->where('jalur.0.status.status', null));
    }

    public function test_curator_writes_are_rate_limited_on_their_own_key(): void
    {
        $kurator = $this->kurator();

        foreach (range(1, (int) config('hiking.rate_limits.personal_writes_per_minute')) as $i) {
            $this->actingAs($kurator)->post(route('curator.statuses.store'), [])->assertSessionHasErrors();
        }

        $this->actingAs($kurator)->post(route('curator.statuses.store'), [])->assertStatus(429);
    }

    public function test_the_desk_does_not_grow_with_the_number_of_records(): void
    {
        $kurator = $this->kurator();
        $rekan = $this->kurator();
        $buat = function (int $n) use ($rekan): void {
            foreach (range(1, $n) as $i) {
                $jalur = Trail::factory()->for($this->gunung)->create();
                app(TerbitStatus::class)->kirim($rekan, $this->draf($rekan, OfficialStatusValue::OPEN, [], $jalur));
                $this->catatanLama(OfficialStatusValue::CLOSED, ['expires_at' => now()->subDay()], $jalur);
            }
        };

        $buat(2);
        $sedikit = $this->hitungKueri($kurator);
        $buat(10);

        $this->assertSame($sedikit, $this->hitungKueri($kurator));
    }

    private function hitungKueri(User $kurator): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });
        // Instans pengguna baru, seperti setiap permintaan sungguhan: relasi yang sudah dimuat
        // permintaan sebelumnya tidak mengurangi hitungan.
        $this->actingAs($kurator->fresh())->get(route('curator.desk'))->assertOk();
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }
}
```

`tests/Feature/EmptyStateCoverageTest.php`:

1. Ganti:

```php
            'tags' => 'seluruh ConditionTag::cases()',
        ],
```

dengan:

```php
            'tags' => 'seluruh ConditionTag::cases()',
        ],
        'pages/Curator/Desk.tsx' => [
            'badan' => 'halamannya menjawab 403 tanpa keanggotaan aktif (OfficialStatusPolicy::viewDesk), jadi selalu ada paling sedikit satu badan',
        ],
        'pages/Curator/Status.tsx' => [
            'langkah' => 'lima langkah tetap HalamanCatatanStatus::langkah()',
        ],
```

`tests/Feature/OfficialStatusPageTest.php`:

1. Ganti:

```php
/**
 * Halaman status resmi (React). Yang dijaga: hanya admin yang mencatat status resmi,
 * setiap catatan masuk jejak audit (§61), riwayatnya menampilkan sumber dan kapan
 * terakhir diverifikasi (§41), dan status yang kedaluwarsa tampil sebagai pekerjaan
 * yang dapat langsung ditindak.
 */
```

dengan:

```php
/**
 * Halaman status resmi (React). Yang dijaga: hanya admin yang membukanya, riwayatnya
 * menampilkan sumber, kapan terakhir diverifikasi (§41), dan keadaan verifikasinya, dan status
 * yang kedaluwarsa tampil sebagai pekerjaan yang perlu ditindak.
 *
 * Diperbarui W8 (ruling W8-R16): status resmi dicatat di meja kurator, tempat aturan dua orang
 * dan aturan terbit asimetris ditegakkan (AsymmetricPublishRuleTest, CuratorDeskTest, termasuk
 * jejak audit setiap langkah). Formulir pencatatan admin dipensiunkan supaya tidak ada jalan tulis
 * yang melewati aturan itu; niat "hanya yang berwenang mencatat dan setiap catatan diaudit" tetap.
 */
```

2. Ganti:

```php
    use RefreshDatabase;

    public function test_a_hiker_can_neither_open_the_page_nor_record_a_status(): void
    {
        $trail = Trail::factory()->create();
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->get(route('admin.statuses'))->assertForbidden();
        $this->actingAs($pendaki)
            ->post(route('admin.statuses.store'), ['scope' => 'TRAIL', 'statusable_id' => $trail->id, 'status' => 'OPEN'])
            ->assertForbidden();

        $this->assertSame(0, OfficialStatus::where('recorded_by', $pendaki->id)->count());
    }

    public function test_recording_a_status_is_audited_and_announced(): void
    {
        $trail = Trail::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.statuses.store'), ['scope' => 'TRAIL', 'statusable_id' => $trail->id, 'status' => 'CLOSED', 'source' => 'Balai TN'])
            ->assertRedirect(route('admin.statuses'))
            ->assertSessionHas('status', 'Status resmi tercatat.');

        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'official_status.recorded']);
        $this->assertDatabaseHas('official_statuses', ['statusable_id' => $trail->id, 'status' => 'CLOSED', 'recorded_by' => $admin->id]);
    }
```

dengan:

```php
    use RefreshDatabase;

    public function test_a_hiker_cannot_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.statuses'))->assertForbidden();
    }

    public function test_nobody_records_a_status_here_anymore(): void
    {
        $trail = Trail::factory()->create();

        $this->actingAs($this->admin())
            ->post('/admin/official-statuses', ['scope' => 'TRAIL', 'statusable_id' => $trail->id, 'status' => 'OPEN'])
            ->assertStatus(405);

        $this->assertSame(0, OfficialStatus::count());
        $this->actingAs($this->admin())
            ->get(route('admin.statuses'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->missing('targets')
                ->where('badanUrl', route('admin.authorities')));
    }
```

3. Ganti:

```php
    /**
     * Temuan yang perlu ditinjau membawa sasarannya, supaya form dapat diisi ulang
     * dengan satu klik alih-alih dicari lagi dari daftar.
     */
```

dengan:

```php
    /**
     * Status yang kedaluwarsa didaftar lebih dulu dengan nama sasarannya. Sebelum W8 baris ini
     * juga membawa cakupan dan id untuk mengisi ulang formulir admin; formulir itu pindah ke meja
     * kurator, yang menawarkan "Catat pengganti" dengan sasaran yang sama (CuratorDeskTest).
     */
```

4. Ganti:

```php
                ->has('tinjau.kedaluwarsa', 1)
                ->where('tinjau.kedaluwarsa.0.scope', StatusScope::TRAIL->value)
                ->where('tinjau.kedaluwarsa.0.statusableId', (string) $status->statusable_id)
                ->where('tinjau.kedaluwarsa.0.keterangan', fn (string $teks) => str_starts_with($teks, 'berlaku sampai')));
```

dengan:

```php
                ->has('tinjau.kedaluwarsa', 1)
                ->where('tinjau.kedaluwarsa.0.nama', $status->statusable->name)
                ->where('tinjau.kedaluwarsa.0.keterangan', fn (string $teks) => str_starts_with($teks, 'berlaku sampai')));
```

`tests/Feature/OfficialStatusSafetySentenceTest.php`:

1. Ganti:

```php
     * Pengecualian bernama:
     * - Admin/OfficialStatuses: meja kurator yang mencatat status, bukan layar pembaca yang
     *   memutuskan berangkat (ruling W3-S5).
     */
    private const PENGECUALIAN = ['Admin/OfficialStatuses'];

    public function test_every_reader_page_that_shows_an_official_status_says_it_is_not_a_safety_guarantee(): void
```

dengan:

```php
     * Pengecualian bernama:
     * - Admin/OfficialStatuses: halaman admin yang meninjau status, bukan layar pembaca yang
     *   memutuskan berangkat (ruling W3-S5);
     * - Curator/Desk dan Curator/Status: meja kurator W8 yang mencatat status dari sumber
     *   resmi, juga bukan layar pembaca (ruling W8-R15).
     */
    private const PENGECUALIAN = ['Admin/OfficialStatuses', 'Curator/Desk', 'Curator/Status'];

    public function test_every_reader_page_that_shows_an_official_status_says_it_is_not_a_safety_guarantee(): void
```

Tulis ulang `tests/Feature/OfficialStatusTargetTest.php` utuh:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\OfficialStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Sasaran status resmi ditunjuk dengan jenis dan id mentah. Salah ketik satu angka dulu
 * menghasilkan halaman 404 alih-alih galat pada isiannya, sehingga pekerjaan yang sudah diisi
 * pada form itu hilang dan tidak ada yang menunjukkan isian mana yang keliru (WCAG 3.3.1).
 *
 * Diperbarui W8 (ruling W8-R16): pencatatan pindah dari halaman admin ke meja kurator, jadi niat
 * yang sama kini dijaga pada formulir meja kurator (SimpanCatatanStatusRequest). Pemeriksaannya
 * tetap mengikuti jenis sasaran: id gunung yang sah bukan id jalur yang sah.
 */
class OfficialStatusTargetTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(string $sasaran): array
    {
        return [
            'sasaran' => $sasaran,
            'otoritas' => $this->balai->id,
            'status' => 'OPEN',
            'jenis_bukti' => 'OFFICIAL_PAGE',
            'mulai_berlaku' => now()->toDateString(),
            'cara_berakhir' => 'NOT_STATED',
            'ringkasan' => 'Pendakian dibuka.',
        ];
    }

    public function test_a_target_that_does_not_exist_is_reported_on_the_field(): void
    {
        $this->actingAs($this->kurator())
            ->post(route('curator.statuses.store'), $this->isian('MOUNTAIN:999999'))
            ->assertSessionHasErrors('sasaran');
    }

    public function test_the_check_follows_the_selected_kind_of_target(): void
    {
        $gunungLain = Mountain::factory()->create();

        $this->actingAs($this->kurator())
            ->post(route('curator.statuses.store'), $this->isian('TRAIL:'.$gunungLain->id))
            ->assertSessionHasErrors('sasaran');
    }

    public function test_a_real_target_still_saves(): void
    {
        $this->actingAs($this->kurator())
            ->post(route('curator.statuses.store'), $this->isian('MOUNTAIN:'.$this->gunung->id))
            ->assertSessionHasNoErrors();

        $this->assertSame($this->gunung->id, OfficialStatus::sole()->statusable_id);
    }
}
```

`tests/Feature/StatusCacheInvalidationTest.php`:

1. Ganti:

```php
    }

    public function test_closing_a_trail_is_visible_before_the_cache_expires(): void
    {
        $trail = Trail::factory()->easy()->create();

        // Menghangatkan cache lebih dulu, persis seperti pendaki yang membuka halaman
```

dengan:

```php
    }

    /**
     * Penutupan dicatat lewat meja kurator sejak W8 (ruling W8-R16): satu kurator dengan bukti
     * dari pengelola, terbit seketika. Niatnya sama dengan sebelum W8.
     */
    private function tutupLewatMeja(string $sasaran, string $alasan): void
    {
        $kurator = $this->kurator();

        $this->actingAs($kurator)
            ->post(route('curator.statuses.store'), [
                'sasaran' => $sasaran,
                'otoritas' => $this->balai->id,
                'status' => OfficialStatusValue::CLOSED->value,
                'jenis_bukti' => 'OFFICIAL_LETTER',
                'bukti' => $this->suratPdf(),
                'mulai_berlaku' => now()->subDay()->toDateString(),
                'cara_berakhir' => 'UNTIL_NOTICE',
                'alasan' => $alasan,
                'ringkasan' => $alasan,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($kurator)->post(route('curator.statuses.submit', OfficialStatus::latest('id')->firstOrFail()))->assertSessionHasNoErrors();
    }

    public function test_closing_a_trail_is_visible_before_the_cache_expires(): void
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $trail = Trail::factory()->easy()->for($this->gunung)->create();

        // Menghangatkan cache lebih dulu, persis seperti pendaki yang membuka halaman
```

2. Ganti:

```php
        $this->assertSame(OfficialStatusValue::UNKNOWN->value, $this->statusTercache($trail));

        $this->actingAs($this->admin())
            ->post(route('admin.statuses.store'), [
                'scope' => 'TRAIL',
                'statusable_id' => $trail->id,
                'status' => OfficialStatusValue::CLOSED->value,
                'reason' => 'Kebakaran lahan di jalur pendakian.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
```

dengan:

```php
        $this->assertSame(OfficialStatusValue::UNKNOWN->value, $this->statusTercache($trail));

        $this->tutupLewatMeja('TRAIL:'.$trail->id, 'Kebakaran lahan di jalur pendakian.');

        $this->assertSame(
```

3. Ganti:

```php
    {
        $mountain = Mountain::factory()->create();
        $trails = Trail::factory()->count(3)->easy()->for($mountain)->create();

        foreach ($trails as $trail) {
```

dengan:

```php
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->siapkanKawasan();
        $trails = Trail::factory()->count(3)->easy()->for($this->gunung)->create();

        foreach ($trails as $trail) {
```

4. Ganti:

```php
        }

        $this->actingAs($this->admin())
            ->post(route('admin.statuses.store'), [
                'scope' => 'MOUNTAIN',
                'statusable_id' => $mountain->id,
                'status' => OfficialStatusValue::CLOSED->value,
                'reason' => 'Aktivitas vulkanik meningkat.',
            ])
            ->assertSessionHasNoErrors();

        foreach ($trails as $trail) {
```

dengan:

```php
        }

        $this->tutupLewatMeja('MOUNTAIN:'.$this->gunung->id, 'Aktivitas vulkanik meningkat.');

        foreach ($trails as $trail) {
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='CuratorDesk|OfficialStatusTarget|OfficialStatusPage|StatusCacheInvalidation|OfficialStatusSafetySentence|EmptyStateCoverage'`
Expected: FAIL. Pada simulasi rencana: 28 test, 12 lulus, 3 gagal, 13 error. Yang merah: delapan test `CuratorDeskTest`, ketiga test `OfficialStatusTargetTest`, dan
dua test `StatusCacheInvalidationTest` (rute `curator.*` belum ada); `OfficialStatusPageTest::test_nobody_records_a_status_here_anymore`
(rute tulis admin masih menerima); `OfficialStatusSafetySentenceTest` dan `EmptyStateCoverageTest` (halaman
`Curator/Desk` belum ada).

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:controller --no-interaction Curator/CuratorDeskController`, `... make:controller --no-interaction Curator/CuratorStatusController`, `... make:request --no-interaction Curator/PersoalkanStatusRequest`, `... make:request --no-interaction Curator/SimpanCatatanStatusRequest`, `... make:class --no-interaction Services/DataResmi/HalamanCatatanStatus`, dan `... make:class --no-interaction Services/DataResmi/MejaKurator`, lalu isinya diganti persis. Berkas lain, `resources/js/components/kurator/DaftarAntrean.tsx`, `resources/js/components/kurator/types.ts`, `resources/js/pages/Curator/Desk.tsx`, `resources/js/pages/Curator/Status.tsx`, dan `resources/js/pages/Curator/StatusForm.tsx`, dibuat langsung. `Admin/OfficialStatusController.php`,
`DataFreshnessService.php`, dan `Admin/OfficialStatuses.tsx` ditulis ulang utuh (Pra-cek butir 1a), dan
`StoreOfficialStatusRequest.php` dihapus.

Buat `app/Http/Controllers/Curator/CuratorDeskController.php`:

```php
<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Models\OfficialStatus;
use App\Services\DataResmi\MejaKurator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 9). Hanya kurator dengan keanggotaan
 * aktif di badan yang berwenang menyatakan status; admin dan moderator bukan kurator.
 */
class CuratorDeskController extends Controller
{
    public function index(Request $request, MejaKurator $meja): Response
    {
        $this->authorize('viewDesk', OfficialStatus::class);

        return Inertia::render('Curator/Desk', $meja->props($request->user()));
    }
}
```

Buat `app/Http/Controllers/Curator/CuratorStatusController.php`:

```php
<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Curator\PersoalkanStatusRequest;
use App\Http\Requests\Curator\SimpanCatatanStatusRequest;
use App\Models\OfficialStatus;
use App\Services\DataResmi\HalamanCatatanStatus;
use App\Services\DataResmi\TerbitStatus;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catatan status resmi di meja kurator: formulir (masukan dan penstrukturan), rincian
 * (pemeriksaan konsistensi, verifikasi, terbit), dan aksinya (spec v3 bagian 10.7; ruling W8-R15).
 * Setiap aksi tulis memanggil layanan TerbitStatus, yang menegakkan aturan dua orang, aturan
 * terbit asimetris, dan Policy; controller hanya memilih halaman dan kalimat kabarnya.
 */
class CuratorStatusController extends Controller
{
    public function create(Request $request, HalamanCatatanStatus $halaman): Response
    {
        $this->authorize('viewDesk', OfficialStatus::class);

        return Inertia::render('Curator/StatusForm', $halaman->formulir($request->user(), null, $request->string('sasaran')->toString() ?: null));
    }

    public function store(SimpanCatatanStatusRequest $request, TerbitStatus $terbit): RedirectResponse
    {
        $sasaran = $request->sasaran();
        $draf = $terbit->buatDraf($request->user(), $sasaran, $request->otoritas(), $request->kolom($sasaran), $request->file('bukti'));

        return redirect()->route('curator.statuses.show', $draf)->with('status', 'Draf tersimpan. Periksa temuan pemeriksaan sebelum mengirim.');
    }

    public function show(Request $request, OfficialStatus $status, HalamanCatatanStatus $halaman): Response
    {
        $this->authorize('view', $status);

        return Inertia::render('Curator/Status', $halaman->rincian($request->user(), $status));
    }

    public function edit(Request $request, OfficialStatus $status, HalamanCatatanStatus $halaman): Response
    {
        $this->authorize('updateDraft', $status);

        return Inertia::render('Curator/StatusForm', $halaman->formulir($request->user(), $status, null));
    }

    public function update(SimpanCatatanStatusRequest $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terbit->ubahDraf($request->user(), $status, $request->kolom($status->statusable), $request->file('bukti'));

        return redirect()->route('curator.statuses.show', $status)->with('status', 'Draf diperbarui.');
    }

    public function destroy(Request $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terbit->hapusDraf($request->user(), $status);

        return redirect()->route('curator.desk')->with('status', 'Draf dihapus beserta salinan buktinya.');
    }

    public function submit(Request $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terkirim = $terbit->kirim($request->user(), $status);

        return redirect()->route('curator.statuses.show', $terkirim)->with('status', $terkirim->terlihatPendaki()
            ? 'Terbit dan terbaca pendaki, bertanda menunggu verifikasi kedua.'
            : 'Terkirim. Pendaki membacanya sesudah kurator kedua memverifikasi.');
    }

    public function verify(Request $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terbit->verifikasi($request->user(), $status);

        return redirect()->route('curator.statuses.show', $status)->with('status', 'Terverifikasi dan terbaca pendaki.');
    }

    public function dispute(PersoalkanStatusRequest $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $terbit->persoalkan($request->user(), $status, $request->validated('alasan'));

        return redirect()->route('curator.statuses.show', $status)->with('status', $status->fresh()->terlihatPendaki()
            ? 'Ditandai dipersoalkan. Pembatasan ini tetap terbaca pendaki sampai catatan penggantinya terbit.'
            : 'Ditandai dipersoalkan dan tidak lagi terbaca pendaki.');
    }

    public function review(Request $request, OfficialStatus $status, TerbitStatus $terbit): RedirectResponse
    {
        $diperiksa = $terbit->tinjauUlang($request->user(), $status);

        return redirect()->route('curator.statuses.show', $diperiksa)->with('status', 'Dicek ulang ke sumbernya. Tinjau berikutnya '
            .Timezone::display($diperiksa->next_review_at, HalamanCatatanStatus::zona($diperiksa->statusable), 'j M Y H.i').'.');
    }
}
```

Buat `app/Http/Requests/Curator/PersoalkanStatusRequest.php`:

```php
<?php

namespace App\Http\Requests\Curator;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alasan mempersoalkan sebuah catatan status (ruling W8-R10). Policy diperiksa layanan
 * TerbitStatus; di sini hanya isinya.
 */
class PersoalkanStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dispute', $this->route('status')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Tulis apa yang tidak cocok dengan sumbernya, supaya kurator lain dapat memeriksanya.',
            'alasan.min' => 'Tulis apa yang tidak cocok dengan sumbernya, paling sedikit 10 karakter.',
        ];
    }
}
```

Buat `app/Http/Requests/Curator/SimpanCatatanStatusRequest.php`:

```php
<?php

namespace App\Http\Requests\Curator;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Services\DataResmi\ArsipBukti;
use App\Services\DataResmi\Yurisdiksi;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Isian langkah 1 dan 2 meja kurator: masukan (bukti) dan penstrukturan (spec v3 bagian 10.7;
 * ruling W8-R15). Sasaran dan penerbit dipilih dari daftar yang dikirim server, lalu dicocokkan
 * ulang dengan yurisdiksi pengguna sesi; pencatat, keadaan, dan pemeriksa tidak pernah diterima
 * dari isian (layanan TerbitStatus menulisnya). Tanggal surat, mulai berlaku, dan berakhir
 * dibaca menurut zona gunung sasarannya.
 */
class SimpanCatatanStatusRequest extends FormRequest
{
    private ?Model $sasaranTerpilih = null;

    public function authorize(): bool
    {
        $catatan = $this->route('status');

        return $catatan instanceof OfficialStatus
            ? $this->user()?->can('updateDraft', $catatan) ?? false
            : $this->user()?->can('viewDesk', OfficialStatus::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $baru = ! $this->route('status') instanceof OfficialStatus;

        return [
            'sasaran' => [$baru ? 'required' : 'prohibited', 'string', 'regex:/^(MOUNTAIN|TRAIL|SEGMENT):[0-9]{1,18}$/'],
            'otoritas' => [$baru ? 'required' : 'prohibited', 'integer'],
            'status' => ['required', new Enum(OfficialStatusValue::class)],
            'jenis_bukti' => ['required', new Enum(EvidenceType::class)],
            'url_sumber' => ['nullable', 'url:https,http', 'max:500'],
            'waktu_tangkap' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'bukti' => ['nullable', ...ArsipBukti::aturan()],
            'nomor_surat' => ['nullable', 'string', 'max:120'],
            'tanggal_surat' => ['nullable', 'date_format:Y-m-d'],
            'mulai_berlaku' => ['required', 'date_format:Y-m-d'],
            'cara_berakhir' => ['required', new Enum(StatusEndCondition::class)],
            'berlaku_sampai' => ['nullable', 'required_if:cara_berakhir,'.StatusEndCondition::ON_DATE->value, 'prohibited_unless:cara_berakhir,'.StatusEndCondition::ON_DATE->value, 'date_format:Y-m-d', 'after_or_equal:mulai_berlaku'],
            'batas' => ['nullable', 'string', 'max:200'],
            'golongan_alasan' => ['nullable', new Enum(StatusReasonCategory::class)],
            'alasan' => ['nullable', 'string', 'max:500'],
            'dasar_hukum' => ['nullable', 'string', 'max:255'],
            'kutipan' => ['nullable', 'string', 'max:1000'],
            'ringkasan' => ['required', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sasaran.required' => 'Pilih gunung, jalur, atau segmen yang dikenai status ini.',
            'sasaran.regex' => 'Pilih sasaran dari daftar.',
            'otoritas.required' => 'Pilih badan yang menerbitkan status ini.',
            'mulai_berlaku.required' => 'Tulis tanggal status ini mulai berlaku menurut pengelola.',
            'berlaku_sampai.required_if' => 'Tulis tanggal berakhir yang disebut pengelola.',
            'berlaku_sampai.prohibited_unless' => 'Tanggal berakhir hanya diisi bila pengelola menyebut tanggalnya.',
            'berlaku_sampai.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai berlaku.',
            'ringkasan.required' => 'Tulis ringkasan satu kalimat dalam bahasa sederhana untuk pendaki.',
        ];
    }

    /**
     * Sasaran harus ada dan berada di kawasan badan pengguna; penerbit harus salah satu badan
     * itu. Galat ditulis pada isiannya, bukan halaman 404 yang menghapus isian lain.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $catatan = $this->route('status');
            $sasaran = $catatan instanceof OfficialStatus ? $catatan->statusable : $this->sasaran();
            $tangkap = $this->validated('waktu_tangkap');

            // Waktu tangkap ditulis menurut jam gunung, jadi dibandingkan di zona itu, bukan UTC.
            if ($sasaran !== null && $tangkap !== null && Carbon::parse($tangkap, $this->zona($sasaran))->isFuture()) {
                $validator->errors()->add('waktu_tangkap', 'Waktu tangkap tidak boleh di masa depan.');
            }

            if ($catatan instanceof OfficialStatus) {
                return;
            }

            if ($sasaran === null) {
                $validator->errors()->add('sasaran', 'Sasaran itu tidak ditemukan. Pilih dari daftar.');

                return;
            }

            $badan = app(Yurisdiksi::class)->otoritasUntuk($this->user(), $sasaran);

            if ($badan->isEmpty()) {
                $validator->errors()->add('sasaran', 'Sasaran ini di luar kawasan badan Anda.');
            } elseif (! $badan->contains('id', (int) $this->validated('otoritas'))) {
                $validator->errors()->add('otoritas', 'Pilih badan yang berwenang atas sasaran ini dan tempat Anda menjadi anggota.');
            }
        }];
    }

    public function sasaran(): ?Model
    {
        if ($this->sasaranTerpilih !== null) {
            return $this->sasaranTerpilih;
        }

        [$jenis, $id] = explode(':', (string) $this->input('sasaran')) + [null, null];

        return $this->sasaranTerpilih = match ($jenis) {
            'MOUNTAIN' => Mountain::find($id),
            'TRAIL' => Trail::find($id),
            'SEGMENT' => TrailSegment::find($id),
            default => null,
        };
    }

    public function otoritas(): Authority
    {
        return Authority::findOrFail((int) $this->validated('otoritas'));
    }

    /**
     * Kolom isi catatan untuk TerbitStatus, dengan tanggal yang dibaca menurut zona gunung.
     *
     * @return array<string, mixed>
     */
    public function kolom(Model $sasaran): array
    {
        $data = $this->validated();
        $zona = $this->zona($sasaran);

        return [
            'status' => $data['status'],
            'evidence_type' => $data['jenis_bukti'],
            'source_url' => $data['url_sumber'] ?? null,
            'captured_at' => isset($data['waktu_tangkap']) ? Carbon::parse($data['waktu_tangkap'], $zona)->utc() : now(),
            'document_number' => $data['nomor_surat'] ?? null,
            'document_date' => $data['tanggal_surat'] ?? null,
            'effective_at' => Carbon::parse($data['mulai_berlaku'], $zona)->startOfDay()->utc(),
            'end_condition' => $data['cara_berakhir'],
            'expires_at' => isset($data['berlaku_sampai']) ? Carbon::parse($data['berlaku_sampai'], $zona)->endOfDay()->utc() : null,
            'limit_description' => $data['batas'] ?? null,
            'reason_category' => $data['golongan_alasan'] ?? null,
            'reason' => $data['alasan'] ?? null,
            'legal_basis' => $data['dasar_hukum'] ?? null,
            'official_excerpt' => $data['kutipan'] ?? null,
            'plain_summary' => $data['ringkasan'],
        ];
    }

    private function zona(Model $sasaran): string
    {
        $gunung = match (true) {
            $sasaran instanceof Mountain => $sasaran,
            $sasaran instanceof Trail => $sasaran->mountain,
            $sasaran instanceof TrailSegment => $sasaran->trail?->mountain,
            default => null,
        };

        return Timezone::isSupported($gunung?->timezone) ? $gunung->timezone : Timezone::DEFAULT;
    }
}
```

Buat `app/Services/DataResmi/HalamanCatatanStatus.php`:

```php
<?php

namespace App\Services\DataResmi;

use App\Enums\EvidenceType;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\StatusVerificationState;
use App\Models\AuditLog;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Support\Timezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Kelas halaman formulir dan rincian satu catatan meja kurator (spec v3 bagian 10.7, alur tujuh
 * langkah; ruling W8-R15). Formulir hanya menawarkan sasaran dan badan di yurisdiksi kurator
 * sesi; server memeriksanya ulang (SimpanCatatanStatusRequest). Rincian menyebut langkah yang
 * sudah dan belum dilalui, temuan pemeriksaan konsistensi, arsip bukti beserta keutuhannya,
 * rantai penggantian, jejak audit catatan itu, dan aksi yang boleh diambil kurator sesi menurut
 * Policy.
 */
final class HalamanCatatanStatus
{
    private const BATAS_JEJAK = 20;

    public function __construct(
        private readonly Yurisdiksi $yurisdiksi,
        private readonly PemeriksaanKonsistensi $konsistensi,
        private readonly ArsipBukti $arsip,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function formulir(User $kurator, ?OfficialStatus $draf, ?string $sasaranAwal): array
    {
        return [
            'title' => $draf === null ? 'Catat status resmi' : 'Ubah draf status resmi',
            'draf' => $draf === null ? null : [
                'id' => $draf->id,
                'sasaran' => self::namaSasaran($draf->statusable),
                'badan' => $draf->authority?->displayName(),
                'isian' => $this->isianDraf($draf),
                'adaArsip' => $draf->evidence_archive_path !== null,
                'url' => route('curator.statuses.show', $draf),
            ],
            'sasaran' => $draf === null ? $this->pilihanSasaran($kurator) : [],
            'sasaranAwal' => $sasaranAwal,
            'pilihan' => [
                'status' => array_map(fn (OfficialStatusValue $n) => ['value' => $n->value, 'label' => $n->label()], OfficialStatusValue::cases()),
                'jenisBukti' => array_map(fn (EvidenceType $j) => ['value' => $j->value, 'label' => $j->label().($j->isPrimary() ? '' : ' (bukan bukti primer)')], EvidenceType::cases()),
                'caraBerakhir' => array_map(fn (StatusEndCondition $c) => ['value' => $c->value, 'label' => $c->label()], StatusEndCondition::cases()),
                'golonganAlasan' => array_map(fn (StatusReasonCategory $g) => ['value' => $g->value, 'label' => $g->label()], StatusReasonCategory::cases()),
            ],
            'batasBuktiKb' => (int) config('hiking.uploads.evidence_max_kb'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rincian(User $kurator, OfficialStatus $s): array
    {
        $s->loadMissing(['statusable', 'authority', 'recordedBy:id,name', 'verifiedBy:id,name', 'replaces', 'supersededBy', 'dataSource:id,source_type']);
        $zona = self::zona($s->statusable);
        $gerbang = Gate::forUser($kurator);
        $nilai = $s->status;

        return [
            'title' => 'Catatan status #'.$s->id,
            'catatan' => [
                'id' => $s->id,
                'sasaran' => self::namaSasaran($s->statusable),
                'status' => $nilai === OfficialStatusValue::UNKNOWN ? null : $nilai->value,
                'statusLabel' => $nilai->label(),
                'keadaan' => $s->verification_state?->label() ?? 'Catatan lama (sebelum meja kurator)',
                'terlihatPendaki' => $s->terlihatPendaki(),
                'menungguVerifikasiKedua' => $s->menungguVerifikasiKedua(),
                'badan' => $s->authority?->displayName() ?? $s->source,
                'surat' => trim(($s->document_number ?? '').($s->document_date !== null ? ', '.$s->document_date->translatedFormat('j M Y') : ''), ', ') ?: null,
                'jenisBukti' => $s->evidence_type?->label(),
                'urlSumber' => $s->source_url,
                'ditangkap' => Timezone::display($s->captured_at, $zona, 'j M Y H.i'),
                'berlaku' => self::masaBerlaku($s, $zona),
                'caraBerakhir' => $s->end_condition?->label(),
                'batas' => $s->limit_description,
                'alasan' => trim(($s->reason_category?->label() ?? '').($s->reason ? ': '.$s->reason : ''), ': ') ?: null,
                'dasarHukum' => $s->legal_basis,
                'kutipan' => $s->official_excerpt,
                'ringkasan' => $s->plain_summary,
                'pencatat' => $s->recordedBy?->name,
                'pemeriksa' => $s->verifiedBy?->name,
                'diverifikasi' => Timezone::display($s->verified_at, $zona, 'j M Y H.i'),
                'tinjauBerikutnya' => Timezone::display($s->next_review_at, $zona, 'j M Y H.i'),
            ],
            'arsip' => $s->evidence_archive_path === null ? null : [
                'url' => route('curator.evidence.show', $s),
                'sidik' => substr((string) $s->evidence_sha256, 0, 12),
                'utuh' => $this->arsip->utuh($s),
            ],
            'langkah' => $this->langkah($s),
            'temuan' => $s->verification_state === null || $s->superseded_by_id !== null ? [] : $this->konsistensi->periksa($s),
            'rantai' => [
                'menggantikan' => $s->replaces === null ? null : ['id' => $s->replaces->id, 'url' => route('curator.statuses.show', $s->replaces)],
                'digantikan' => $s->supersededBy === null ? null : ['id' => $s->supersededBy->id, 'url' => route('curator.statuses.show', $s->supersededBy)],
            ],
            'jejak' => AuditLog::query()
                ->where('entity_type', $s->getMorphClass())
                ->where('entity_id', $s->id)
                ->where('action', 'like', 'official_status.%')
                ->with('actor:id,name')
                ->latest('id')
                ->limit(self::BATAS_JEJAK)
                ->get()
                ->map(fn (AuditLog $l) => [
                    'id' => $l->id,
                    'tindakan' => self::kataTindakan($l->action),
                    'oleh' => $l->actor?->name ?? 'Sistem',
                    'waktu' => Timezone::display($l->created_at, $zona, 'j M Y H.i'),
                    'alasan' => is_array($l->after) ? ($l->after['alasan'] ?? null) : null,
                ])->values()->all(),
            'aksi' => [
                'ubah' => $gerbang->allows('updateDraft', $s) ? route('curator.statuses.edit', $s) : null,
                'kirim' => $gerbang->allows('updateDraft', $s) ? route('curator.statuses.submit', $s) : null,
                'hapus' => $gerbang->allows('updateDraft', $s) ? route('curator.statuses.destroy', $s) : null,
                'verifikasi' => $gerbang->allows('verify', $s) ? route('curator.statuses.verify', $s) : null,
                'persoalkan' => $gerbang->allows('dispute', $s) ? route('curator.statuses.dispute', $s) : null,
                'tinjauUlang' => $gerbang->allows('review', $s) ? route('curator.statuses.review', $s) : null,
                'catatPengganti' => $s->statusable !== null && $s->verification_state !== StatusVerificationState::DRAFT
                    ? route('curator.statuses.create', ['sasaran' => self::kunciSasaran($s->statusable)])
                    : null,
            ],
            'meja' => route('curator.desk'),
        ];
    }

    /**
     * Langkah 1 sampai 5 alur meja kurator untuk catatan ini; langkah 6 (tinjau berkala) dan 7
     * (konflik) berupa antrean di meja.
     *
     * @return array<int, array{nomor: int, nama: string, selesai: bool, keterangan: string}>
     */
    private function langkah(OfficialStatus $s): array
    {
        $keadaan = $s->verification_state;
        $lama = $keadaan === null;
        $terkirim = in_array($keadaan, [StatusVerificationState::PENDING, StatusVerificationState::VERIFIED, StatusVerificationState::DISPUTED], true);

        return [
            ['nomor' => 1, 'nama' => 'Masukan', 'selesai' => $lama || $s->evidence_archive_path !== null, 'keterangan' => $s->evidence_archive_path !== null ? 'Arsip bukti tersimpan.' : 'Belum ada salinan bukti.'],
            ['nomor' => 2, 'nama' => 'Penstrukturan', 'selesai' => true, 'keterangan' => 'Penerbit, sasaran, nilai, dan masa berlaku tercatat.'],
            ['nomor' => 3, 'nama' => 'Pemeriksaan konsistensi', 'selesai' => $lama || $terkirim, 'keterangan' => $terkirim ? 'Lolos saat dikirim.' : 'Diperiksa saat dikirim.'],
            ['nomor' => 4, 'nama' => 'Verifikasi orang kedua', 'selesai' => $lama || $keadaan === StatusVerificationState::VERIFIED, 'keterangan' => match (true) {
                $lama => 'Catatan lama, sebelum meja kurator.',
                $keadaan === StatusVerificationState::VERIFIED => 'Diverifikasi '.($s->verifiedBy?->name ?? 'kurator kedua').'.',
                $keadaan === StatusVerificationState::DISPUTED => 'Dipersoalkan.',
                default => 'Menunggu kurator kedua.',
            }],
            ['nomor' => 5, 'nama' => 'Terbit', 'selesai' => $s->terlihatPendaki(), 'keterangan' => $s->terlihatPendaki()
                ? ($s->menungguVerifikasiKedua() ? 'Terbaca pendaki, bertanda menunggu verifikasi kedua.' : 'Terbaca pendaki.')
                : 'Belum terbaca pendaki.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function isianDraf(OfficialStatus $s): array
    {
        $zona = self::zona($s->statusable);

        return [
            'status' => $s->status->value,
            'jenis_bukti' => $s->evidence_type?->value ?? EvidenceType::OFFICIAL_LETTER->value,
            'url_sumber' => $s->source_url ?? '',
            'waktu_tangkap' => $s->captured_at?->copy()->setTimezone($zona)->format('Y-m-d\TH:i') ?? '',
            'nomor_surat' => $s->document_number ?? '',
            'tanggal_surat' => $s->document_date?->toDateString() ?? '',
            'mulai_berlaku' => $s->effective_at?->copy()->setTimezone($zona)->toDateString() ?? '',
            'cara_berakhir' => $s->end_condition?->value ?? StatusEndCondition::NOT_STATED->value,
            'berlaku_sampai' => $s->expires_at?->copy()->setTimezone($zona)->toDateString() ?? '',
            'batas' => $s->limit_description ?? '',
            'golongan_alasan' => $s->reason_category?->value ?? '',
            'alasan' => $s->reason ?? '',
            'dasar_hukum' => $s->legal_basis ?? '',
            'kutipan' => $s->official_excerpt ?? '',
            'ringkasan' => $s->plain_summary ?? '',
        ];
    }

    /**
     * Sasaran di kawasan badan kurator, dikelompokkan per gunung, beserta badan yang boleh
     * dipilih sebagai penerbit untuk sasaran itu.
     *
     * @return array<int, array{gunung: string, badan: array<int, array{value: int, label: string}>, pilihan: array<int, array{value: string, label: string}>}>
     */
    private function pilihanSasaran(User $kurator): array
    {
        $keanggotaan = $this->yurisdiksi->keanggotaan($kurator);
        $gunung = Mountain::query()
            ->whereKey($this->yurisdiksi->gunung($kurator)->all())
            ->with(['trails' => fn ($q) => $q->active()->orderBy('name')->with('segments:id,trail_id,name,sequence')])
            ->orderBy('name')
            ->get();

        return $gunung->map(function (Mountain $m) use ($keanggotaan) {
            $badan = $keanggotaan
                ->filter(fn (AuthorityMember $a) => $a->authority->mountains->contains('id', $m->id))
                ->map(fn (AuthorityMember $a) => ['value' => $a->authority->id, 'label' => $a->authority->displayName()])
                ->unique('value')->values()->all();

            $pilihan = [['value' => 'MOUNTAIN:'.$m->id, 'label' => $m->name.' (seluruh gunung)']];

            foreach ($m->trails as $jalur) {
                $pilihan[] = ['value' => 'TRAIL:'.$jalur->id, 'label' => $jalur->name];

                foreach ($jalur->segments as $segmen) {
                    $pilihan[] = ['value' => 'SEGMENT:'.$segmen->id, 'label' => $jalur->name.', segmen '.$segmen->name];
                }
            }

            return ['gunung' => $m->name, 'badan' => $badan, 'pilihan' => $pilihan];
        })->values()->all();
    }

    public static function namaSasaran(?Model $sasaran): string
    {
        return match (true) {
            $sasaran instanceof Mountain => $sasaran->name.' (seluruh gunung)',
            $sasaran instanceof Trail => $sasaran->name.($sasaran->mountain ? ', '.$sasaran->mountain->name : ''),
            $sasaran instanceof TrailSegment => ($sasaran->trail?->name ?? 'Jalur').', segmen '.$sasaran->name,
            default => 'Sasaran terhapus',
        };
    }

    public static function kunciSasaran(?Model $sasaran): ?string
    {
        return match (true) {
            $sasaran instanceof Mountain => 'MOUNTAIN:'.$sasaran->id,
            $sasaran instanceof Trail => 'TRAIL:'.$sasaran->id,
            $sasaran instanceof TrailSegment => 'SEGMENT:'.$sasaran->id,
            default => null,
        };
    }

    public static function masaBerlaku(OfficialStatus $s, string $zona): string
    {
        $mulai = $s->effective_at === null ? 'Tanggal mulai tidak disebut' : 'Mulai '.Timezone::display($s->effective_at, $zona, 'j M Y');

        return match (true) {
            $s->superseded_by_id !== null && $s->expires_at !== null => $mulai.', digantikan '.Timezone::display($s->expires_at, $zona, 'j M Y'),
            $s->expires_at !== null => $mulai.' sampai '.Timezone::display($s->expires_at, $zona, 'j M Y'),
            $s->end_condition === StatusEndCondition::UNTIL_NOTICE => $mulai.', sampai pemberitahuan lebih lanjut',
            default => $mulai.', berakhirnya tidak disebut',
        };
    }

    public static function zona(?Model $sasaran): string
    {
        $gunung = match (true) {
            $sasaran instanceof Mountain => $sasaran,
            $sasaran instanceof Trail => $sasaran->mountain,
            $sasaran instanceof TrailSegment => $sasaran->trail?->mountain,
            default => null,
        };

        return Timezone::isSupported($gunung?->timezone) ? $gunung->timezone : Timezone::DEFAULT;
    }

    private static function kataTindakan(string $aksi): string
    {
        return match ($aksi) {
            'official_status.drafted' => 'Draf dibuat',
            'official_status.draft_updated' => 'Draf diubah',
            'official_status.submitted' => 'Dikirim',
            'official_status.verified' => 'Diverifikasi orang kedua',
            'official_status.disputed' => 'Dipersoalkan',
            'official_status.reviewed' => 'Dicek ulang ke sumbernya',
            'official_status.superseded' => 'Digantikan catatan baru',
            default => 'Diubah',
        };
    }
}
```

Buat `app/Services/DataResmi/MejaKurator.php`:

```php
<?php

namespace App\Services\DataResmi;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusVerificationState;
use App\Models\AuthorityMember;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailSegment;
use App\Models\User;
use App\Services\DataFreshnessService;
use App\Support\Timezone;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Kelas halaman meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 9; ruling W8-R15):
 * antrean yang menunggu tindakan kurator ini, hanya di kawasan badannya. Verifikasi orang kedua
 * (catatan kurator lain yang menunggu), tinjau berkala (kedaluwarsa, segera kedaluwarsa, lewat
 * jadwal tinjau, termasuk catatan lama dari sebelum meja kurator), catatan yang dipersoalkan, dan
 * draf miliknya sendiri. Jumlah kueri tetap, tidak tumbuh bersama jumlah catatan.
 */
final class MejaKurator
{
    public const BATAS = 50;

    public function __construct(
        private readonly Yurisdiksi $yurisdiksi,
        private readonly DataFreshnessService $kesegaran,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(User $kurator): array
    {
        $lingkup = $this->lingkup($kurator);
        $tinjau = array_map(
            fn (EloquentCollection $daftar) => $daftar->take(self::BATAS)
                ->loadMorph('statusable', [Trail::class => ['mountain:id,name'], TrailSegment::class => ['trail:id,name,mountain_id']])
                ->load(['recordedBy:id,name', 'authority:id,name,abbreviation']),
            $this->kesegaran->review($lingkup),
        );
        $dasar = fn () => OfficialStatus::query()->where($lingkup)->with($this->muatan())->orderBy('created_at')->limit(self::BATAS);

        return [
            'title' => 'Meja kurator',
            'badan' => $this->yurisdiksi->keanggotaan($kurator)->map(fn (AuthorityMember $a) => [
                'nama' => $a->authority->displayName(),
                'peran' => $a->role->label(),
                'berlakuSampai' => $a->valid_until?->translatedFormat('j M Y'),
            ])->values()->all(),
            'antrean' => [
                'verifikasi' => $dasar()
                    ->where('verification_state', StatusVerificationState::PENDING->value)
                    ->where('recorded_by', '!=', $kurator->id)
                    ->get()->map(fn (OfficialStatus $s) => $this->baris($s))->values()->all(),
                'tinjau' => [
                    ...$tinjau['kedaluwarsa']->map(fn (OfficialStatus $s) => $this->baris($s, 'Kedaluwarsa'))->all(),
                    ...$tinjau['segera']->map(fn (OfficialStatus $s) => $this->baris($s, 'Segera kedaluwarsa'))->all(),
                    ...$tinjau['basi']->map(fn (OfficialStatus $s) => $this->baris($s, 'Lewat jadwal tinjau'))->all(),
                ],
                'dipersoalkan' => $dasar()
                    ->where('verification_state', StatusVerificationState::DISPUTED->value)
                    ->get()->map(fn (OfficialStatus $s) => $this->baris($s))->values()->all(),
                'draf' => $dasar()
                    ->where('verification_state', StatusVerificationState::DRAFT->value)
                    ->where('recorded_by', $kurator->id)
                    ->get()->map(fn (OfficialStatus $s) => $this->baris($s))->values()->all(),
            ],
        ];
    }

    /**
     * Syarat "sasaran di kawasan badan kurator" untuk kueri catatan status: gunungnya, jalur di
     * gunung itu, dan segmen jalur itu. Tiga kueri id, sekali per halaman.
     *
     * @return Closure(Builder): void
     */
    public function lingkup(User $kurator): Closure
    {
        $gunung = $this->yurisdiksi->gunung($kurator)->all();
        $jalur = Trail::query()->whereIn('mountain_id', $gunung)->pluck('id')->all();
        $segmen = TrailSegment::query()->whereIn('trail_id', $jalur)->pluck('id')->all();

        return function (Builder $q) use ($gunung, $jalur, $segmen): void {
            $q->where(fn (Builder $g) => $g->where('statusable_type', (new Mountain)->getMorphClass())->whereIn('statusable_id', $gunung))
                ->orWhere(fn (Builder $t) => $t->where('statusable_type', (new Trail)->getMorphClass())->whereIn('statusable_id', $jalur))
                ->orWhere(fn (Builder $s) => $s->where('statusable_type', (new TrailSegment)->getMorphClass())->whereIn('statusable_id', $segmen));
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function muatan(): array
    {
        return [
            'statusable' => fn (MorphTo $m) => $m->morphWith([
                Trail::class => ['mountain:id,name'],
                TrailSegment::class => ['trail:id,name,mountain_id'],
            ]),
            'recordedBy:id,name',
            'authority:id,name,abbreviation',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function baris(OfficialStatus $s, ?string $alasanTinjau = null): array
    {
        $nilai = $s->status;

        return [
            'id' => $s->id,
            'sasaran' => HalamanCatatanStatus::namaSasaran($s->statusable),
            'status' => $nilai === OfficialStatusValue::UNKNOWN ? null : $nilai->value,
            'statusLabel' => $nilai->label(),
            'keadaan' => $s->verification_state?->label() ?? 'Catatan lama',
            'pencatat' => $s->recordedBy?->name,
            'badan' => $s->authority?->displayName() ?? $s->source,
            'berlaku' => HalamanCatatanStatus::masaBerlaku($s, Timezone::DEFAULT),
            'alasanTinjau' => $alasanTinjau,
            'sasaranKunci' => HalamanCatatanStatus::kunciSasaran($s->statusable),
            'url' => route('curator.statuses.show', $s),
        ];
    }
}
```

Buat `resources/js/components/kurator/DaftarAntrean.tsx`:

```tsx
import type { ReactNode } from 'react';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import type { BarisAntrean } from './types';

interface DaftarAntreanProps {
    id: string;
    judul: string;
    keterangan: string;
    baris: BarisAntrean[];
    kosong: string;
    /** Aksi tambahan per baris, misalnya "Catat pengganti" di antrean tinjau. */
    aksi?: (b: BarisAntrean) => ReactNode;
}

/**
 * Satu antrean meja kurator: judul bagian, kalimat yang menyebut apa yang ditunggu, dan daftar
 * catatan. Setiap baris menyebut sasaran, status dengan bentuk dan kata (DK-32), keadaan
 * verifikasinya, badan, dan masa berlakunya; nama sasaran membuka rincian catatan. Antrean kosong
 * menulis kalimat, bukan ruang kosong.
 */
export function DaftarAntrean({ id, judul, keterangan, baris, kosong, aksi }: DaftarAntreanProps) {
    return (
        <section aria-labelledby={id} className="border-t border-subtle pt-6">
            <h2 id={id} className="text-bagian text-primary">
                {judul} <span className="text-secondary tabular-nums">({baris.length})</span>
            </h2>
            <p className="mt-1 max-w-prose text-kecil text-secondary">{keterangan}</p>
            {baris.length === 0 ? (
                <p className="mt-3 text-kecil text-muted">{kosong}</p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle rounded-kartu border border-subtle bg-surface">
                    {baris.map((b) => (
                        <li key={`${id}-${b.id}`} className="flex flex-col gap-2 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                                <Tautan href={b.url} className="inline-flex min-h-11 items-center break-words font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:rounded-control focus-visible:ring-2 focus-visible:ring-brand-600">
                                    {b.sasaran}
                                </Tautan>
                                <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <StatusAkses nilai={b.status} label={b.statusLabel} />
                                    <Lencana nada={b.keadaan === 'Draf' ? 'netral' : 'merek'}>{b.keadaan}</Lencana>
                                    {b.alasanTinjau ? <Lencana nada="peringatan">{b.alasanTinjau}</Lencana> : null}
                                </div>
                                <p className="mt-1 text-meta text-muted">
                                    {[b.badan, b.berlaku, b.pencatat ? `Dicatat ${b.pencatat}` : null].filter(Boolean).join('. ')}
                                </p>
                            </div>
                            {aksi ? <div className="flex shrink-0 flex-wrap gap-2">{aksi(b)}</div> : null}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
```

Buat `resources/js/components/kurator/types.ts`:

```ts
import type { Opsi } from '@/components/discovery/Isian';
import type { StatusResmi } from '@/components/trails/StatusAkses';

/** Satu baris antrean meja kurator (MejaKurator::baris). */
export interface BarisAntrean {
    id: number;
    sasaran: string;
    status: StatusResmi;
    statusLabel: string;
    keadaan: string;
    pencatat: string | null;
    badan: string | null;
    berlaku: string;
    alasanTinjau: string | null;
    sasaranKunci: string | null;
    url: string;
}

export interface BadanKurator {
    nama: string;
    peran: string;
    berlakuSampai: string | null;
}

export interface AntreanKurator {
    verifikasi: BarisAntrean[];
    tinjau: BarisAntrean[];
    dipersoalkan: BarisAntrean[];
    draf: BarisAntrean[];
}

/** Temuan pemeriksaan konsistensi (PemeriksaanKonsistensi::periksa). */
export interface Temuan {
    kode: string;
    tingkat: 'blokir' | 'peringatan';
    pesan: string;
}

export interface LangkahCatatan {
    nomor: number;
    nama: string;
    selesai: boolean;
    keterangan: string;
}

/** Isian formulir langkah 1 dan 2 (SimpanCatatanStatusRequest). */
export interface IsianStatus {
    sasaran: string;
    otoritas: string;
    status: string;
    jenis_bukti: string;
    url_sumber: string;
    waktu_tangkap: string;
    bukti: File | null;
    nomor_surat: string;
    tanggal_surat: string;
    mulai_berlaku: string;
    cara_berakhir: string;
    berlaku_sampai: string;
    batas: string;
    golongan_alasan: string;
    alasan: string;
    dasar_hukum: string;
    kutipan: string;
    ringkasan: string;
}

export interface KelompokSasaran {
    gunung: string;
    badan: Array<{ value: number; label: string }>;
    pilihan: Opsi[];
}

export interface PilihanFormulir {
    status: Opsi[];
    jenisBukti: Opsi[];
    caraBerakhir: Opsi[];
    golonganAlasan: Opsi[];
}

export interface DrafKurator {
    id: number;
    sasaran: string;
    badan: string | null;
    isian: Omit<IsianStatus, 'sasaran' | 'otoritas' | 'bukti'>;
    adaArsip: boolean;
    url: string;
}

export interface RincianCatatan {
    id: number;
    sasaran: string;
    status: StatusResmi;
    statusLabel: string;
    keadaan: string;
    terlihatPendaki: boolean;
    menungguVerifikasiKedua: boolean;
    badan: string | null;
    surat: string | null;
    jenisBukti: string | null;
    urlSumber: string | null;
    ditangkap: string | null;
    berlaku: string;
    caraBerakhir: string | null;
    batas: string | null;
    alasan: string | null;
    dasarHukum: string | null;
    kutipan: string | null;
    ringkasan: string | null;
    pencatat: string | null;
    pemeriksa: string | null;
    diverifikasi: string | null;
    tinjauBerikutnya: string | null;
}

export interface AksiCatatan {
    ubah: string | null;
    kirim: string | null;
    hapus: string | null;
    verifikasi: string | null;
    persoalkan: string | null;
    tinjauUlang: string | null;
    catatPengganti: string | null;
}

export interface JejakCatatan {
    id: number;
    tindakan: string;
    oleh: string;
    waktu: string | null;
    alasan: string | null;
}
```

Buat `resources/js/pages/Curator/Desk.tsx`:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DaftarAntrean } from '@/components/kurator/DaftarAntrean';
import type { AntreanKurator, BadanKurator } from '@/components/kurator/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { create } from '@/routes/curator/statuses';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    badan: BadanKurator[];
    antrean: AntreanKurator;
}

/**
 * Meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 9). Kurator menyalin status dari
 * sumber resmi badannya. Halaman ini menjawab "apa yang menunggu saya": verifikasi orang kedua,
 * tinjau berkala (termasuk status lama yang kedaluwarsa dan kini terbaca Belum diketahui),
 * catatan yang dipersoalkan, dan draf sendiri. Hanya kawasan badan kurator yang tampil; server
 * memeriksa ulang setiap aksi.
 */
export default function Desk() {
    const { flash, badan, antrean } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Meja kurator"
                    description="Salin status dari sumber resmi badan Anda. Pembatasan dengan bukti dari pengelola terbit seketika dan menunggu kurator kedua; pembukaan baru terbaca pendaki sesudah kurator kedua memverifikasinya."
                />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-kecil font-bold text-primary">Keanggotaan Anda</p>
                        <ul className="mt-1 space-y-0.5 text-kecil text-secondary">
                            {badan.map((b) => (
                                <li key={b.nama}>
                                    {b.nama}: {b.peran}
                                    {b.berlakuSampai ? `, berlaku sampai ${b.berlakuSampai}` : ''}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <Button href={create.url()}>Catat status resmi</Button>
                </div>

                <div className="mt-8 space-y-8">
                    <DaftarAntrean
                        id="antrean-verifikasi"
                        judul="Menunggu verifikasi Anda"
                        keterangan="Catatan kurator lain di kawasan Anda. Periksa bukti dan isinya terhadap sumber sebelum memverifikasi; pencatat tidak dapat memverifikasi catatannya sendiri."
                        baris={antrean.verifikasi}
                        kosong="Tidak ada catatan kurator lain yang menunggu Anda."
                    />
                    <DaftarAntrean
                        id="antrean-tinjau"
                        judul="Tinjau berkala"
                        keterangan="Status yang kedaluwarsa, segera kedaluwarsa, atau lewat jadwal tinjau jenisnya. Status yang kedaluwarsa kini terbaca Belum diketahui, tidak pernah Buka. Cek ulang ke sumbernya, atau catat penggantinya bila keadaannya berubah."
                        baris={antrean.tinjau}
                        kosong="Semua status di kawasan Anda masih dalam jadwal tinjau."
                        aksi={(b) =>
                            b.sasaranKunci ? (
                                <Button variant="secondary" size="sm" href={create.url({ query: { sasaran: b.sasaranKunci } })} aria-label={`Catat pengganti untuk ${b.sasaran}`}>
                                    Catat pengganti
                                </Button>
                            ) : null
                        }
                    />
                    <DaftarAntrean
                        id="antrean-dipersoalkan"
                        judul="Dipersoalkan"
                        keterangan="Catatan yang dinilai tidak cocok dengan sumbernya. Pembatasan tetap terbaca pendaki sampai penggantinya terbit; pembukaan berhenti terbaca."
                        baris={antrean.dipersoalkan}
                        kosong="Tidak ada catatan yang dipersoalkan."
                    />
                    <DaftarAntrean id="antrean-draf" judul="Draf Anda" keterangan="Belum terbaca siapa pun selain Anda." baris={antrean.draf} kosong="Belum ada draf." />
                </div>
            </div>
        </div>
    );
}

Desk.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

Buat `resources/js/pages/Curator/Status.tsx`:

```tsx
import { router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState, type ReactNode } from 'react';
import { TextareaField } from '@/components/discovery/Isian';
import type { AksiCatatan, JejakCatatan, LangkahCatatan, RincianCatatan, Temuan } from '@/components/kurator/types';
import { StatusAkses } from '@/components/trails/StatusAkses';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
import { Lencana } from '@/components/ui/Lencana';
import { Dialog } from '@/components/ui/Lembar';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    catatan: RincianCatatan;
    arsip: { url: string; sidik: string; utuh: boolean } | null;
    langkah: LangkahCatatan[];
    temuan: Temuan[];
    rantai: { menggantikan: { id: number; url: string } | null; digantikan: { id: number; url: string } | null };
    jejak: JejakCatatan[];
    aksi: AksiCatatan;
    meja: string;
}

/**
 * Satu catatan meja kurator (spec v3 bagian 10.7, langkah 3 sampai 5): temuan pemeriksaan
 * konsistensi, verifikasi orang kedua, dan terbit, beserta isi catatan, arsip buktinya, rantai
 * penggantian, dan jejak audit. Tombol aksi hanya tampil bila Policy server mengizinkannya untuk
 * kurator sesi; server tetap menolak aksi yang tidak berhak. Mempersoalkan dan menghapus draf
 * meminta konfirmasi lewat dialog yang menyebut akibatnya.
 */
export default function Status() {
    const { flash, errors, catatan, arsip, langkah, temuan, rantai, jejak, aksi, meja } = usePage<Props>().props;
    const [dialog, setDialog] = useState<'hapus' | 'persoalkan' | null>(null);
    const [mengirim, setMengirim] = useState<string | null>(null);
    const pemicu = useRef<HTMLElement | null>(null);
    const soal = useForm({ alasan: '' });
    const blokir = temuan.filter((t) => t.tingkat === 'blokir');
    const peringatan = temuan.filter((t) => t.tingkat === 'peringatan');
    const galatAksi = [errors.konsistensi, errors.bukti, errors.evidence_type].filter(Boolean);

    const kirimAksi = (kunci: string, url: string) => {
        setMengirim(kunci);
        router.post(url, {}, { preserveScroll: true, onFinish: () => setMengirim(null) });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: meja, label: 'Meja kurator' }} title={catatan.sasaran} description={`Catatan status #${catatan.id}`} />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}
                {galatAksi.length > 0 ? (
                    <Alert variant="danger" className="mb-4">
                        {galatAksi.join(' ')}
                    </Alert>
                ) : null}

                <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <StatusAkses nilai={catatan.status} label={catatan.statusLabel} ukuran="bagian" />
                    <Lencana nada={catatan.terlihatPendaki ? 'merek' : 'netral'}>{catatan.terlihatPendaki ? 'Terbaca pendaki' : 'Belum terbaca pendaki'}</Lencana>
                    <Lencana nada={catatan.menungguVerifikasiKedua ? 'peringatan' : 'netral'}>{catatan.keadaan}</Lencana>
                </div>
                {catatan.ringkasan ? <p className="mt-3 max-w-prose text-isi text-primary">{catatan.ringkasan}</p> : null}

                <section aria-labelledby="judul-langkah" className="mt-8">
                    <h2 id="judul-langkah" className="text-bagian text-primary">
                        Langkah
                    </h2>
                    <ol className="mt-3 space-y-2">
                        {langkah.map((l) => (
                            <li key={l.nomor} className="flex items-start gap-3">
                                <span className={l.selesai ? 'mt-0.5 text-brand-ink' : 'mt-0.5 text-muted'} aria-hidden="true">
                                    <Icon name={l.selesai ? 'check' : 'minus'} size={20} />
                                </span>
                                <span>
                                    <span className="font-bold text-primary">
                                        {l.nomor}. {l.nama}
                                    </span>{' '}
                                    <span className="text-kecil text-secondary">
                                        {l.selesai ? 'Selesai.' : 'Belum.'} {l.keterangan}
                                    </span>
                                </span>
                            </li>
                        ))}
                    </ol>
                </section>

                {temuan.length > 0 ? (
                    <section aria-labelledby="judul-temuan" className="mt-8 space-y-3">
                        <h2 id="judul-temuan" className="text-bagian text-primary">
                            Pemeriksaan konsistensi
                        </h2>
                        {blokir.length > 0 ? (
                            <Alert variant="danger" title="Menahan kiriman">
                                <ul className="list-disc space-y-1 pl-5">
                                    {blokir.map((t) => (
                                        <li key={t.kode + t.pesan}>{t.pesan}</li>
                                    ))}
                                </ul>
                            </Alert>
                        ) : null}
                        {peringatan.length > 0 ? (
                            <Alert variant="warning" title="Periksa sebelum melanjutkan">
                                <ul className="list-disc space-y-1 pl-5">
                                    {peringatan.map((t) => (
                                        <li key={t.kode + t.pesan}>{t.pesan}</li>
                                    ))}
                                </ul>
                            </Alert>
                        ) : null}
                    </section>
                ) : null}

                <section aria-label="Aksi catatan" className="mt-8 flex flex-wrap gap-3">
                    {aksi.kirim ? (
                        <Button onClick={() => kirimAksi('kirim', aksi.kirim as string)} processing={mengirim === 'kirim'} disabled={blokir.length > 0}>
                            Kirim untuk terbit
                        </Button>
                    ) : null}
                    {aksi.verifikasi ? (
                        <Button onClick={() => kirimAksi('verifikasi', aksi.verifikasi as string)} processing={mengirim === 'verifikasi'} disabled={blokir.length > 0}>
                            Verifikasi dan terbitkan
                        </Button>
                    ) : null}
                    {aksi.tinjauUlang ? (
                        <Button variant="secondary" onClick={() => kirimAksi('tinjau', aksi.tinjauUlang as string)} processing={mengirim === 'tinjau'}>
                            Sudah dicek ulang ke sumber
                        </Button>
                    ) : null}
                    {aksi.ubah ? (
                        <Button variant="secondary" href={aksi.ubah}>
                            Ubah draf
                        </Button>
                    ) : null}
                    {aksi.catatPengganti ? (
                        <Button variant="secondary" href={aksi.catatPengganti}>
                            Catat pengganti
                        </Button>
                    ) : null}
                    {aksi.persoalkan ? (
                        <Button
                            variant="secondary"
                            onClick={(e) => {
                                pemicu.current = e.currentTarget;
                                setDialog('persoalkan');
                            }}
                        >
                            Persoalkan
                        </Button>
                    ) : null}
                    {aksi.hapus ? (
                        <Button
                            variant="danger"
                            onClick={(e) => {
                                pemicu.current = e.currentTarget;
                                setDialog('hapus');
                            }}
                        >
                            Hapus draf
                        </Button>
                    ) : null}
                </section>

                <section aria-labelledby="judul-isi" className="mt-8">
                    <h2 id="judul-isi" className="text-bagian text-primary">
                        Isi catatan
                    </h2>
                    <dl className="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-[minmax(0,12rem)_1fr]">
                        {(
                            [
                                ['Penerbit', catatan.badan],
                                ['Surat', catatan.surat],
                                ['Berlaku', catatan.berlaku],
                                ['Cara berakhir', catatan.caraBerakhir],
                                ['Batas', catatan.batas],
                                ['Alasan', catatan.alasan],
                                ['Dasar hukum', catatan.dasarHukum],
                                ['Kutipan resmi', catatan.kutipan],
                                ['Jenis bukti', catatan.jenisBukti],
                                ['Dibaca kurator', catatan.ditangkap],
                                ['Dicatat', catatan.pencatat],
                                ['Dicocokkan ke sumber', catatan.diverifikasi],
                                ['Pemeriksa kedua', catatan.pemeriksa],
                                ['Tinjau berikutnya', catatan.tinjauBerikutnya],
                            ] as Array<[string, string | null]>
                        )
                            .filter(([, nilai]) => nilai)
                            .map(([label, nilai]) => (
                                <div key={label} className="contents">
                                    <dt className="text-kecil font-bold text-secondary">{label}</dt>
                                    <dd className="break-words text-isi text-primary">{nilai}</dd>
                                </div>
                            ))}
                    </dl>
                    {catatan.urlSumber ? (
                        <p className="mt-3 text-kecil">
                            <a href={catatan.urlSumber} rel="noopener noreferrer" target="_blank" className="inline-flex min-h-11 items-center gap-1 break-all font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                Buka sumber
                                <Icon name="arrow-square-out" size={16} />
                                <span className="sr-only"> (membuka tab baru)</span>
                            </a>
                        </p>
                    ) : null}
                    {arsip ? (
                        <p className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-kecil text-secondary">
                            <a href={arsip.url} className="inline-flex min-h-11 items-center font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                Unduh arsip bukti
                            </a>
                            <span>
                                Sidik SHA-256 {arsip.sidik}, {arsip.utuh ? 'cocok dengan arsip.' : 'tidak lagi cocok dengan arsip.'}
                            </span>
                        </p>
                    ) : (
                        <p className="mt-3 text-kecil text-secondary">Belum ada salinan bukti.</p>
                    )}
                    {rantai.menggantikan || rantai.digantikan ? (
                        <p className="mt-3 text-kecil text-secondary">
                            {rantai.menggantikan ? (
                                <>
                                    Menggantikan{' '}
                                    <Tautan href={rantai.menggantikan.url} className="font-bold text-brand-ink underline underline-offset-2">
                                        catatan #{rantai.menggantikan.id}
                                    </Tautan>
                                    .{' '}
                                </>
                            ) : null}
                            {rantai.digantikan ? (
                                <>
                                    Digantikan{' '}
                                    <Tautan href={rantai.digantikan.url} className="font-bold text-brand-ink underline underline-offset-2">
                                        catatan #{rantai.digantikan.id}
                                    </Tautan>
                                    .
                                </>
                            ) : null}
                        </p>
                    ) : null}
                </section>

                <section aria-labelledby="judul-jejak" className="mt-8">
                    <h2 id="judul-jejak" className="text-bagian text-primary">
                        Jejak audit
                    </h2>
                    {jejak.length === 0 ? (
                        <p className="mt-2 text-kecil text-muted">Catatan lama ini dibuat sebelum meja kurator dan tidak punya jejak di sini.</p>
                    ) : (
                        <ul className="mt-3 space-y-2">
                            {jejak.map((j) => (
                                <li key={j.id} className="text-kecil text-secondary">
                                    <span className="font-bold text-primary">{j.tindakan}</span>, {j.oleh}
                                    {j.waktu ? `, ${j.waktu}` : ''}
                                    {j.alasan ? <span className="block text-primary">"{j.alasan}"</span> : null}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <Dialog
                terbuka={dialog === 'persoalkan'}
                onTutup={() => setDialog(null)}
                judul="Persoalkan catatan ini?"
                keterangan="Pembukaan berhenti terbaca pendaki; pembatasan tetap terbaca sampai penggantinya terbit. Alasan Anda tercatat di jejak audit."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)}>
                            Batal
                        </Button>
                        <Button
                            processing={soal.processing}
                            onClick={() =>
                                aksi.persoalkan &&
                                soal.post(aksi.persoalkan, { preserveScroll: true, onSuccess: () => { setDialog(null); soal.reset(); } })
                            }
                        >
                            Persoalkan
                        </Button>
                    </>
                }
            >
                <TextareaField name="alasan" label="Apa yang tidak cocok dengan sumbernya?" rows={3} value={soal.data.alasan} onChange={(e) => soal.setData('alasan', e.target.value)} error={soal.errors.alasan} />
            </Dialog>

            <Dialog
                terbuka={dialog === 'hapus'}
                onTutup={() => setDialog(null)}
                judul="Hapus draf ini?"
                keterangan="Draf dan salinan buktinya dihapus permanen. Catatan yang sudah dikirim tidak dapat dihapus."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)}>
                            Batal
                        </Button>
                        <Button variant="danger" processing={mengirim === 'hapus'} onClick={() => aksi.hapus && (setMengirim('hapus'), router.delete(aksi.hapus, { onFinish: () => setMengirim(null) }))}>
                            Hapus draf
                        </Button>
                    </>
                }
            >
                <p className="text-kecil text-secondary">{catatan.sasaran}</p>
            </Dialog>
        </div>
    );
}

Status.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

Buat `resources/js/pages/Curator/StatusForm.tsx`:

```tsx
import { useForm, usePage } from '@inertiajs/react';
import { useMemo, type FormEvent, type ReactNode } from 'react';
import { SelectField, TextareaField } from '@/components/discovery/Isian';
import type { DrafKurator, IsianStatus, KelompokSasaran, PilihanFormulir } from '@/components/kurator/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { FormField, InputError, Label, Select } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { desk } from '@/routes/curator';
import { store, update } from '@/routes/curator/statuses';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    title: string;
    draf: DrafKurator | null;
    sasaran: KelompokSasaran[];
    sasaranAwal: string | null;
    pilihan: PilihanFormulir;
    batasBuktiKb: number;
}

const kosong: Omit<IsianStatus, 'sasaran' | 'otoritas'> = {
    status: 'CLOSED',
    jenis_bukti: 'OFFICIAL_LETTER',
    url_sumber: '',
    waktu_tangkap: '',
    bukti: null,
    nomor_surat: '',
    tanggal_surat: '',
    mulai_berlaku: '',
    cara_berakhir: 'UNTIL_NOTICE',
    berlaku_sampai: '',
    batas: '',
    golongan_alasan: '',
    alasan: '',
    dasar_hukum: '',
    kutipan: '',
    ringkasan: '',
};

/**
 * Langkah 1 (masukan) dan 2 (penstrukturan) meja kurator (spec v3 bagian 10.7). Sasaran dan badan
 * penerbit hanya dari kawasan kurator; server memeriksa keduanya ulang, dan pencatat serta
 * keadaan tidak pernah dikirim dari sini. Menyimpan membuat draf yang hanya terbaca pencatatnya;
 * pemeriksaan konsistensi dan kiriman ada di halaman catatan.
 */
export default function StatusForm() {
    const { title, draf, sasaran, sasaranAwal, pilihan, batasBuktiKb, errors } = usePage<Props>().props;
    const awalSasaran = sasaranAwal && sasaran.some((k) => k.pilihan.some((p) => p.value === sasaranAwal)) ? sasaranAwal : '';
    const form = useForm<IsianStatus>({ sasaran: awalSasaran, otoritas: '', ...kosong, ...(draf?.isian ?? {}), bukti: null });

    const badanPilihan = useMemo(() => sasaran.find((k) => k.pilihan.some((p) => p.value === form.data.sasaran))?.badan ?? [], [sasaran, form.data.sasaran]);
    const otoritasTerpilih = badanPilihan.length === 1 ? String(badanPilihan[0]?.value) : form.data.otoritas;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        const opsi = { forceFormData: true, preserveScroll: true };

        if (draf) {
            form.transform((d) => ({ ...d, sasaran: undefined, otoritas: undefined, _method: 'put' }));
            form.post(update.url(draf.id), opsi);
        } else {
            form.transform((d) => ({ ...d, otoritas: otoritasTerpilih }));
            form.post(store.url(), opsi);
        }
    };

    const isian = (nama: keyof Omit<IsianStatus, 'bukti'>, label: string, extra: { type?: string; hint?: string; required?: boolean } = {}) => (
        <FormField name={nama} label={label} {...extra} value={form.data[nama]} onChange={(e) => form.setData(nama, e.target.value)} error={form.errors[nama]} />
    );

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={draf ? { href: draf.url, label: `Catatan #${draf.id}` } : { href: desk.url(), label: 'Meja kurator' }} title={title} />

                {draf ? (
                    <p className="mb-6 text-kecil text-secondary">
                        {draf.sasaran}
                        {draf.badan ? `, diterbitkan ${draf.badan}` : ''}. Sasaran dan badan tidak diubah di draf; hapus draf ini dan catat yang baru bila keduanya keliru.
                    </p>
                ) : null}

                {errors.konsistensi ? <Alert variant="danger" className="mb-4">{errors.konsistensi}</Alert> : null}

                {!draf && sasaran.length === 0 ? (
                    <EmptyState
                        jenis="menunggu"
                        title="Belum ada kawasan di badan Anda"
                        menunggu="Menunggu admin Memount mengaitkan gunung ke badan Anda."
                        description="Status hanya dicatat untuk gunung, jalur, dan segmen di kawasan badan tempat Anda menjadi anggota."
                    />
                ) : (
                    <form onSubmit={kirim} className="space-y-8" noValidate>
                        <fieldset className="space-y-4">
                            <legend className="text-bagian text-primary">1. Masukan</legend>
                            <p className="max-w-prose text-kecil text-secondary">
                                Salin dari surat, laman, atau akun resmi pengelola. Berita media hanya bukti sekunder: catatan berbukti berita menunggu kurator kedua dan tidak pernah dapat membuka jalur.
                            </p>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField name="jenis_bukti" label="Jenis bukti" required options={pilihan.jenisBukti} value={form.data.jenis_bukti} onChange={(e) => form.setData('jenis_bukti', e.target.value)} error={form.errors.jenis_bukti} />
                                {isian('url_sumber', 'Alamat sumber (opsional)', { type: 'url', hint: 'Laman atau unggahan yang Anda salin.' })}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {isian('waktu_tangkap', 'Waktu Anda membaca sumbernya', { type: 'datetime-local', hint: 'Jam gunung. Kosong berarti saat disimpan.' })}
                                <div>
                                    <Label htmlFor="bukti">Salinan bukti{draf?.adaArsip ? ' (ganti)' : ''}</Label>
                                    <input
                                        id="bukti"
                                        name="bukti"
                                        type="file"
                                        accept="application/pdf,image/jpeg,image/png,image/webp"
                                        onChange={(e) => form.setData('bukti', e.target.files?.[0] ?? null)}
                                        aria-describedby={form.errors.bukti ? 'bukti-hint bukti-error' : 'bukti-hint'}
                                        aria-invalid={form.errors.bukti ? true : undefined}
                                        className="mt-1 block min-h-12 w-full rounded-tombol border border-control bg-surface px-3 py-2 text-kecil text-primary file:mr-3 file:min-h-11 file:rounded-control file:border-0 file:bg-surface-sunken file:px-3 file:font-bold file:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                    />
                                    <p id="bukti-hint" className="mt-1 text-kecil text-muted">
                                        PDF atau foto JPG, PNG, WebP, paling besar {Math.round(batasBuktiKb / 1024)} MB. Foto ditulis ulang tanpa metadata lokasi; sidik SHA-256 dicatat.
                                    </p>
                                    <InputError id="bukti-error" messages={form.errors.bukti} className="mt-2" />
                                </div>
                            </div>
                        </fieldset>

                        <fieldset className="space-y-4">
                            <legend className="text-bagian text-primary">2. Penstrukturan</legend>
                            {draf ? null : (
                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="sasaran">Gunung, jalur, atau segmen</Label>
                                        <Select
                                            id="sasaran"
                                            name="sasaran"
                                            required
                                            value={form.data.sasaran}
                                            onChange={(e) => form.setData({ ...form.data, sasaran: e.target.value, otoritas: '' })}
                                            aria-describedby={form.errors.sasaran ? 'sasaran-error' : undefined}
                                            aria-invalid={form.errors.sasaran ? true : undefined}
                                        >
                                            <option value="">Pilih sasaran</option>
                                            {sasaran.map((k) => (
                                                <optgroup key={k.gunung} label={k.gunung}>
                                                    {k.pilihan.map((p) => (
                                                        <option key={p.value} value={p.value}>
                                                            {p.label}
                                                        </option>
                                                    ))}
                                                </optgroup>
                                            ))}
                                        </Select>
                                        <InputError id="sasaran-error" messages={form.errors.sasaran} className="mt-2" />
                                    </div>
                                    <SelectField
                                        name="otoritas"
                                        label="Badan penerbit"
                                        required
                                        placeholder={form.data.sasaran ? 'Pilih badan' : 'Pilih sasaran lebih dulu'}
                                        options={badanPilihan.map((b) => ({ value: String(b.value), label: b.label }))}
                                        value={otoritasTerpilih}
                                        onChange={(e) => form.setData('otoritas', e.target.value)}
                                        error={form.errors.otoritas}
                                        disabled={badanPilihan.length === 0}
                                    />
                                </div>
                            )}
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {isian('nomor_surat', 'Nomor surat atau pengumuman')}
                                {isian('tanggal_surat', 'Tanggal surat', { type: 'date' })}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField name="status" label="Status akses" required options={pilihan.status} value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} error={form.errors.status} />
                                {isian('batas', 'Batas pembukaan atau penutupan (opsional)', { hint: 'Misalnya "sampai Ranu Kumbolo".' })}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {isian('mulai_berlaku', 'Mulai berlaku', { type: 'date', required: true })}
                                <SelectField
                                    name="cara_berakhir"
                                    label="Cara berakhir"
                                    required
                                    options={pilihan.caraBerakhir}
                                    value={form.data.cara_berakhir}
                                    onChange={(e) => form.setData({ ...form.data, cara_berakhir: e.target.value, berlaku_sampai: e.target.value === 'ON_DATE' ? form.data.berlaku_sampai : '' })}
                                    error={form.errors.cara_berakhir}
                                />
                                {form.data.cara_berakhir === 'ON_DATE' ? isian('berlaku_sampai', 'Berlaku sampai', { type: 'date', required: true }) : null}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField name="golongan_alasan" label="Golongan alasan" placeholder="Tidak disebut" options={pilihan.golonganAlasan} value={form.data.golongan_alasan} onChange={(e) => form.setData('golongan_alasan', e.target.value)} error={form.errors.golongan_alasan} />
                                {isian('dasar_hukum', 'Dasar hukum (opsional)')}
                            </div>
                            <TextareaField name="alasan" label="Alasan menurut pengelola (opsional)" rows={2} value={form.data.alasan} onChange={(e) => form.setData('alasan', e.target.value)} error={form.errors.alasan} />
                            <TextareaField name="kutipan" label="Kutipan resmi singkat (opsional)" rows={3} value={form.data.kutipan} onChange={(e) => form.setData('kutipan', e.target.value)} error={form.errors.kutipan} />
                            <TextareaField
                                name="ringkasan"
                                label="Ringkasan untuk pendaki"
                                required
                                rows={2}
                                value={form.data.ringkasan}
                                onChange={(e) => form.setData('ringkasan', e.target.value)}
                                error={form.errors.ringkasan}
                            />
                        </fieldset>

                        <div className="flex flex-wrap gap-3">
                            <Button type="submit" processing={form.processing}>
                                Simpan draf
                            </Button>
                            <Button variant="secondary" href={draf ? draf.url : desk.url()}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}

StatusForm.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

Tulis ulang `app/Http/Controllers/Admin/OfficialStatusController.php` utuh:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OfficialStatusRowResource;
use App\Models\OfficialStatus;
use App\Services\DataFreshnessService;
use App\Support\PaginasiInertia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-11 status resmi, halaman admin: riwayat seluruh catatan dan antrean tinjau (PRD §41, §93).
 *
 * Sejak W8 status resmi dicatat di meja kurator (spec v3 bagian 10.7; ruling W8-R16), tempat
 * aturan dua orang dan aturan terbit asimetris ditegakkan layanan TerbitStatus. Formulir pencatatan
 * admin dipensiunkan supaya tidak ada jalan tulis yang melewati aturan itu; admin memberi peran
 * kurator lewat keanggotaan badan resmi dan tetap melihat riwayat serta antrean di sini.
 */
class OfficialStatusController extends Controller
{
    public function index(Request $request, DataFreshnessService $kesegaran): Response
    {
        $this->authorize('viewAny', OfficialStatus::class);

        $tinjau = $kesegaran->review();

        return Inertia::render('Admin/OfficialStatuses', [
            'title' => 'Kelola Status Resmi',
            'tinjau' => [
                'kedaluwarsa' => $tinjau['kedaluwarsa']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'kedaluwarsa'))->values()->all(),
                'segera' => $tinjau['segera']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'segera'))->values()->all(),
                'basi' => $tinjau['basi']->map(fn (OfficialStatus $s) => $this->barisTinjau($s, 'basi'))->values()->all(),
            ],
            'records' => PaginasiInertia::props(
                OfficialStatus::query()->with('statusable', 'recordedBy', 'dataSource')->orderByDesc('created_at')->paginate(10)->withQueryString(),
                fn (OfficialStatus $s) => (new OfficialStatusRowResource($s))->resolve($request),
            ),
            'badanUrl' => route('admin.authorities'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function barisTinjau(OfficialStatus $s, string $kategori): array
    {
        $keterangan = $kategori === 'basi'
            ? ($s->verified_at
                ? 'terakhir diverifikasi '.$s->verified_at->translatedFormat('d M Y')
                : 'belum pernah diverifikasi')
            : 'berlaku sampai '.$s->expires_at->translatedFormat('d M Y');

        return [
            'id' => $s->id,
            'nama' => $s->statusable?->name ?? 'Objek terhapus',
            'status' => $s->status->value,
            'statusText' => $s->status->label(),
            'keterangan' => $keterangan,
        ];
    }
}
```

`app/Http/Middleware/HandleInertiaRequests.php`:

1. Ganti:

```php
use App\Services\DataFreshnessService;
use App\Services\Profil\FotoProfil;
```

dengan:

```php
use App\Services\DataFreshnessService;
use App\Services\DataResmi\Yurisdiksi;
use App\Services\Profil\FotoProfil;
```

2. Ganti:

```php
                    'isAdmin' => $user->isAdmin(),
                    'hasCompletedProfile' => $user->hasCompletedProfile(),
```

dengan:

```php
                    'isAdmin' => $user->isAdmin(),
                    // Pintu meja kurator (W8): satu kueri, hanya untuk pengguna berperan kurator.
                    'isKurator' => $user->isCurator() && app(Yurisdiksi::class)->kuratorAktif($user),
                    'hasCompletedProfile' => $user->hasCompletedProfile(),
```

`app/Http/Resources/Admin/OfficialStatusRowResource.php`:

1. Ganti:

```php
            'dicatatOleh' => $this->recordedBy?->name ?? 'Sistem',
        ];
```

dengan:

```php
            'dicatatOleh' => $this->recordedBy?->name ?? 'Sistem',
            // Keadaan meja kurator (W8): admin melihat draf dan catatan yang belum terbaca pendaki.
            'keadaan' => $this->verification_state?->label() ?? 'Catatan lama',
        ];
```

`app/Policies/OfficialStatusPolicy.php`:

1. Ganti:

```php
 *
 * Halaman admin status resmi hanya untuk admin. Meja kurator hanya untuk kurator dengan
 * keanggotaan aktif di badan yang berwenang menyatakan status; mencatat dan memeriksa hanya di
 * kawasan badannya, atas nama badan tempat ia menjadi anggota, dan pemeriksa kedua tidak pernah
 * pencatatnya sendiri. Admin dan moderator bukan kurator.
 */
```

dengan:

```php
 *
 * Halaman admin status resmi hanya untuk admin dan hanya membaca. Meja kurator hanya untuk
 * kurator dengan keanggotaan aktif di badan yang berwenang menyatakan status; mencatat dan
 * memeriksa hanya di kawasan badannya, atas nama badan tempat ia menjadi anggota, dan pemeriksa
 * kedua tidak pernah pencatatnya sendiri. Admin dan moderator bukan kurator.
 */
```

2. Ganti:

```php
    public function __construct(private readonly Yurisdiksi $yurisdiksi) {}

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, OfficialStatus $status): bool
    {
```

dengan:

```php
    public function __construct(private readonly Yurisdiksi $yurisdiksi) {}

    /**
     * Halaman admin status resmi: riwayat dan antrean tinjau, tanpa formulir pencatatan (W8).
     */
    public function viewAny(User $user): bool
    {
```

3. Ganti:

```php
    /**
     * Melihat catatan meja kurator: kurator yang berwenang atas kawasannya.
     */
```

dengan:

```php
    /**
     * Melihat catatan meja kurator: kurator yang berwenang atas kawasannya. Draf hanya untuk
     * pencatatnya sampai dikirim.
     */
```

4. Ganti:

```php
    {
        return $status->statusable !== null && $this->yurisdiksi->berwenang($user, $status->statusable);
```

dengan:

```php
    {
        if ($status->verification_state === StatusVerificationState::DRAFT && $status->recorded_by !== $user->id) {
            return false;
        }

        return $status->statusable !== null && $this->yurisdiksi->berwenang($user, $status->statusable);
```

Tulis ulang `app/Services/DataFreshnessService.php` utuh:

```php
<?php

namespace App\Services;

use App\Enums\OfficialStatusValue;
use App\Models\OfficialStatus;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Pemeriksaan data basi untuk status resmi (PRD §93, §98).
 *
 * Status yang kedaluwarsa diam-diam kembali menjadi UNKNOWN. Itu aman menurut §95,
 * tetapi senyap: jalur yang kemarin berstatus BUKA hari ini tidak punya status sama
 * sekali, dan tidak ada yang memberi tahu admin bahwa itu terjadi.
 *
 * Sejak W8 jadwal tinjaunya per jenis status (Q9; ruling W8-R13): Buka mengikuti ambang turun
 * status Buka paket luring, jenis lain `hiking.freshness.status_review_hours`. Catatan dari meja
 * kurator menyimpan jadwalnya di `next_review_at` (paling lambat tanggal berakhirnya); catatan
 * lama menghitungnya dari `verified_at`. Antrean admin, meja kurator, dan kata kesegaran untuk
 * pendaki membaca satu aturan yang sama.
 */
class DataFreshnessService
{
    /**
     * Sudah lewat masa berlakunya. Jalurnya sekarang berjalan tanpa status resmi.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function expired(?Closure $lingkup = null): Collection
    {
        return $this->dasar($lingkup)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }

    /**
     * Akan kedaluwarsa dalam waktu dekat, jadi masih sempat diperpanjang sebelum jalurnya
     * kehilangan status.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function expiringSoon(?Closure $lingkup = null): Collection
    {
        return $this->dasar($lingkup)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($this->hariPeringatan())])
            ->get();
    }

    /**
     * Sudah lewat jadwal tinjaunya, atau belum pernah diverifikasi sama sekali.
     *
     * @return Collection<int, OfficialStatus>
     */
    public function stale(?Closure $lingkup = null): Collection
    {
        return $this->dasar($lingkup)
            ->where(fn (Builder $q) => $this->lewatJadwal($q))
            ->get();
    }

    /**
     * Jumlah status yang perlu ditinjau, dalam satu query karena angka ini tampil di
     * setiap halaman admin.
     *
     * Kedaluwarsa, akan kedaluwarsa, dan basi dihitung sebagai kategori yang saling
     * lepas supaya satu status tidak terhitung dua kali.
     */
    public function reviewCount(): int
    {
        return $this->dasar()
            ->where(function (Builder $q) {
                $q->where('expires_at', '<', now())
                    ->orWhereBetween('expires_at', [now(), now()->addDays($this->hariPeringatan())])
                    ->orWhere(fn (Builder $basi) => $this->lewatJadwal($basi));
            })
            ->count();
    }

    /**
     * Antrean tinjau, untuk seluruh catatan atau, dengan $lingkup, hanya catatan di kawasan
     * seorang kurator (meja kurator W8).
     *
     * @param  (Closure(Builder): mixed)|null  $lingkup
     * @return array{kedaluwarsa: Collection<int, OfficialStatus>, segera: Collection<int, OfficialStatus>, basi: Collection<int, OfficialStatus>}
     */
    public function review(?Closure $lingkup = null): array
    {
        $kedaluwarsa = $this->expired($lingkup);

        return [
            'kedaluwarsa' => $kedaluwarsa,
            'segera' => $this->expiringSoon($lingkup),

            // Yang sudah kedaluwarsa tidak diulang di daftar basi; admin sudah melihatnya
            // di kategori yang lebih mendesak.
            'basi' => $this->stale($lingkup)->reject(fn (OfficialStatus $s) => $kedaluwarsa->contains('id', $s->id)),
        ];
    }

    /**
     * Keadaan satu catatan status untuk pendaki (spec v3 bagian 7): "Terkini" bila
     * diverifikasi dalam jadwal tinjaunya dan tidak segera kedaluwarsa, "Perlu ditinjau" bila
     * belum pernah diverifikasi, lewat jadwal tinjaunya, atau segera kedaluwarsa, dan
     * "Kedaluwarsa" bila masa berlakunya sudah lewat. Aturannya sama dengan antrean tinjau
     * admin di atas, jadi pendaki dan admin tidak pernah membaca dua penilaian.
     */
    public function keadaan(OfficialStatus $status): string
    {
        return $this->keadaanDari($status->verified_at, $status->expires_at, $status->status, $status->next_review_at);
    }

    /**
     * Keadaan kesegaran dari stempelnya saja, fungsi murni yang dipakai keadaan() dan rincian
     * jalur (I1 tinjauan akhir W3). Rincian membaca snapshot status yang bercache, jadi
     * kesegarannya dihitung dari stempel pada saat halaman dirender, bukan ikut dibekukan di
     * cache, dan kartu Jelajah serta rincian menyebut kata yang sama untuk catatan yang sama.
     */
    public function keadaanDari(?CarbonInterface $diverifikasi, ?CarbonInterface $kedaluwarsa, ?OfficialStatusValue $nilai = null, ?CarbonInterface $jadwal = null): string
    {
        if ($kedaluwarsa !== null && $kedaluwarsa->isPast()) {
            return 'Kedaluwarsa';
        }

        $batas = $jadwal ?? $diverifikasi?->copy()->addHours($this->jamTinjau($nilai ?? OfficialStatusValue::UNKNOWN));
        $basi = $batas === null || now()->greaterThan($batas);
        $segera = $kedaluwarsa !== null && $kedaluwarsa->lte(now()->addDays($this->hariPeringatan()));

        return $basi || $segera ? 'Perlu ditinjau' : 'Terkini';
    }

    /**
     * Jam sesudah verifikasi terakhir sampai sebuah jenis status perlu ditinjau ulang.
     */
    public function jamTinjau(OfficialStatusValue $nilai): int
    {
        if ($nilai === OfficialStatusValue::OPEN) {
            return (int) config('hiking.field_pack.open_status_stale_after_hours', 24);
        }

        return (int) (config('hiking.freshness.status_review_hours.'.$nilai->value) ?? 168);
    }

    /**
     * Jadwal tinjau berikutnya untuk catatan meja kurator: sesudah jam tinjau jenisnya, tetapi
     * tidak pernah sesudah tanggal berakhir yang disebut pengelola.
     */
    public function jadwalUntuk(OfficialStatusValue $nilai, CarbonInterface $diverifikasi, ?CarbonInterface $berakhir): Carbon
    {
        $jadwal = Carbon::instance($diverifikasi)->addHours($this->jamTinjau($nilai));

        return $berakhir !== null && $berakhir->lessThan($jadwal) ? Carbon::instance($berakhir) : $jadwal;
    }

    /**
     * Syarat "lewat jadwal tinjau" dalam SQL: jadwal tersimpan bila ada, selain itu verifikasi
     * terakhir ditambah jam tinjau jenisnya, atau belum pernah diverifikasi.
     */
    private function lewatJadwal(Builder $q): void
    {
        $kini = now();

        $q->where(fn (Builder $terjadwal) => $terjadwal->whereNotNull('next_review_at')->where('next_review_at', '<', $kini))
            ->orWhere(function (Builder $lama) use ($kini) {
                $lama->whereNull('next_review_at')->where(function (Builder $v) use ($kini) {
                    $v->whereNull('verified_at');

                    foreach (OfficialStatusValue::cases() as $nilai) {
                        $v->orWhere(fn (Builder $jenis) => $jenis
                            ->where('status', $nilai->value)
                            ->where('verified_at', '<', $kini->copy()->subHours($this->jamTinjau($nilai))));
                    }
                });
            });
    }

    private function dasar(?Closure $lingkup = null): Builder
    {
        // Antrean tinjau berkala hanya memuat catatan yang dibaca pendaki; draf dan pembukaan
        // yang menunggu verifikasi punya antreannya sendiri di meja kurator (W8). Catatan yang
        // digantikan sudah berakhir karena penggantinya, bukan kedaluwarsa.
        return OfficialStatus::query()
            ->terlihatPendaki()
            ->whereNull('superseded_by_id')
            ->when($lingkup !== null, fn (Builder $q) => $q->where($lingkup))
            ->with('statusable');
    }

    private function hariPeringatan(): int
    {
        return (int) config('hiking.freshness.status_expiry_warning_days', 14);
    }
}
```

`resources/js/components/akun/PintuAkun.tsx`:

1. Ganti:

```tsx
                {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null)} className={tombolMenu}>
```

dengan:

```tsx
                {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                {pengguna.isKurator ? <TautanMenu href="/kurator">Meja kurator</TautanMenu> : null}
                <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null)} className={tombolMenu}>
```

`resources/js/layouts/MenuAkun.tsx`:

1. Ganti:

```tsx
                        {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                        <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null)} className={tombolMenu}>
```

dengan:

```tsx
                        {pengguna.isAdmin ? <TautanMenu href="/admin/trails">Admin</TautanMenu> : null}
                        {pengguna.isKurator ? <TautanMenu href="/kurator">Meja kurator</TautanMenu> : null}
                        <button type="button" onClick={() => void keluar(perangkat?.kunci ?? null)} className={tombolMenu}>
```

Tulis ulang `resources/js/pages/Admin/OfficialStatuses.tsx` utuh:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { BarisKosong, BingkaiTabel, type Paginasi } from '@/components/admin/Tabel';
import { Alert } from '@/components/ui/Alert';
import { Card } from '@/components/ui/Card';
import { Kesegaran } from '@/components/trails/Kesegaran';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Tautan } from '@/components/ui/Tautan';
import { StatusAkses, type StatusResmi } from '@/components/trails/StatusAkses';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import type { PropsBersama } from '@/types';

interface ItemTinjau {
    id: number;
    nama: string;
    status: string;
    statusText: string;
    keterangan: string;
}

interface BarisStatus {
    id: number;
    objek: string;
    cakupan: string;
    status: string;
    statusText: string;
    sumber: string;
    sumberUrl: string | null;
    diverifikasi: string | null;
    berlaku: string;
    dicatatOleh: string;
    keadaan: string;
}

interface Props extends PropsBersama {
    tinjau: { kedaluwarsa: ItemTinjau[]; segera: ItemTinjau[]; basi: ItemTinjau[] };
    records: Paginasi<BarisStatus>;
    badanUrl: string;
}

const kategoriTinjau = [
    ['kedaluwarsa', 'Sudah kedaluwarsa', 'Jalur ini sekarang berjalan tanpa status resmi dan ditampilkan sebagai belum diketahui.'],
    ['segera', 'Akan segera kedaluwarsa', 'Masih sempat diperpanjang sebelum statusnya hilang.'],
    ['basi', 'Lama tidak diverifikasi', 'Catatannya masih berlaku, tetapi sudah lewat jadwal tinjau jenis statusnya.'],
] as const;

/** UNKNOWN tidak punya warna operasional: StatusAkses menggambarnya lingkaran putus-putus. */
const badge = (status: string): StatusResmi => (status === 'OPEN' || status === 'RESTRICTED' || status === 'CLOSED' ? status : null);

/**
 * FR-11 status resmi, halaman admin. Status dicatat kurator di meja kurator sejak W8, tempat aturan
 * dua orang dan aturan terbit asimetris ditegakkan; halaman ini menyajikan antrean tinjau dan
 * riwayat seluruh catatan, termasuk keadaan verifikasinya. Yang perlu ditinjau diletakkan sebelum
 * riwayat: status yang kedaluwarsa membuat jalurnya berjalan tanpa status resmi, dan §95 membuat
 * kemundurannya senyap.
 */
export default function OfficialStatuses() {
    const { flash, tinjau, records, badanUrl } = usePage<Props>().props;
    const adaTinjauan = tinjau.kedaluwarsa.length + tinjau.segera.length + tinjau.basi.length > 0;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Kelola Status Resmi"
                    description="Status dapat melekat pada gunung, jalur, atau segmen. Status gunung yang terbuka tidak otomatis berarti semua jalurnya terbuka."
                />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <p className="mb-6 max-w-prose text-kecil text-secondary">
                    Status resmi dicatat kurator di meja kurator: pembatasan boleh terbit oleh satu kurator dengan bukti dari pengelola, pembukaan wajib diperiksa kurator kedua. Peran kurator diberikan lewat{' '}
                    <Tautan href={badanUrl} className="font-semibold text-brand-ink underline underline-offset-2">
                        keanggotaan badan resmi
                    </Tautan>
                    .
                </p>

                {adaTinjauan ? (
                    <Card className="mb-6" title="Perlu ditinjau">
                        <div className="space-y-5">
                            {kategoriTinjau.map(([kunci, judul, penjelasan]) =>
                                tinjau[kunci].length > 0 ? (
                                    <div key={kunci}>
                                        <h3 className="text-sm font-semibold text-primary">{judul} ({tinjau[kunci].length})</h3>
                                        <p className="mt-0.5 text-xs text-secondary">{penjelasan}</p>
                                        <ul className="mt-2 space-y-1 text-sm">
                                            {tinjau[kunci].map((item) => (
                                                <li key={item.id} className="flex flex-wrap items-center gap-x-2 gap-y-1 text-secondary">
                                                    <span className="font-medium text-primary">{item.nama}</span>
                                                    <StatusAkses nilai={badge(item.status)} label={item.statusText} />
                                                    <span className="text-xs text-secondary">{item.keterangan}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : null,
                            )}
                        </div>
                    </Card>
                ) : null}

                <BingkaiTabel keterangan="Riwayat status resmi" kolom={['Objek', 'Cakupan', 'Status', 'Sumber', 'Berlaku', 'Dicatat oleh']}>
                    {records.data.length === 0 ? (
                        <BarisKosong
                            kolom={6}
                            judul="Belum ada status resmi tercatat"
                            penjelasan="Jalur tanpa status resmi bukan berarti jalur terbuka. Sistem menampilkannya sebagai belum diketahui, dan itu memang keadaan yang sebenarnya."
                        />
                    ) : (
                        records.data.map((r) => (
                            <tr key={r.id} className="border-b border-subtle align-top">
                                <td className="p-4 font-medium text-primary">{r.objek}</td>
                                <td className="p-4 text-secondary">{r.cakupan}</td>
                                <td className="p-4">
                                    <StatusAkses nilai={badge(r.status)} label={r.statusText} />
                                    <span className="mt-1 block text-meta text-muted">{r.keadaan}</span>
                                </td>
                                <td className="p-4 text-secondary">
                                    {r.sumberUrl ? (
                                        <a href={r.sumberUrl} rel="noopener noreferrer" target="_blank" className="text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                            {r.sumber}
                                            <span className="sr-only"> (membuka tab baru)</span>
                                        </a>
                                    ) : (
                                        r.sumber
                                    )}
                                    <Kesegaran waktu={r.diverifikasi} awalan="Diverifikasi" />
                                </td>
                                <td className="p-4 text-secondary">{r.berlaku}</td>
                                <td className="p-4 text-secondary">{r.dicatatOleh}</td>
                            </tr>
                        ))
                    )}
                </BingkaiTabel>

                <div className="mt-6">
                    <Pagination links={records.links} />
                </div>
            </div>
        </div>
    );
}

OfficialStatuses.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`resources/js/types/index.d.ts`:

1. Ganti:

```ts
    isAdmin: boolean;
    hasCompletedProfile: boolean;
```

dengan:

```ts
    isAdmin: boolean;
    /** Kurator dengan keanggotaan aktif (W8): menampilkan pintu meja kurator. */
    isKurator: boolean;
    hasCompletedProfile: boolean;
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Community\TrailNewsController;
use App\Http\Controllers\Curator\EvidenceController;
```

dengan:

```php
use App\Http\Controllers\Community\TrailNewsController;
use App\Http\Controllers\Curator\CuratorDeskController;
use App\Http\Controllers\Curator\CuratorStatusController;
use App\Http\Controllers\Curator\EvidenceController;
```

2. Ganti:

```php
    Route::get('kurator/status/{status}/bukti', [EvidenceController::class, 'show'])->whereNumber('status')->name('curator.evidence.show');
});
```

dengan:

```php
    Route::get('kurator/status/{status}/bukti', [EvidenceController::class, 'show'])->whereNumber('status')->name('curator.evidence.show');
    Route::get('kurator', [CuratorDeskController::class, 'index'])->name('curator.desk');
    Route::get('kurator/status/baru', [CuratorStatusController::class, 'create'])->name('curator.statuses.create');
    Route::get('kurator/status/{status}', [CuratorStatusController::class, 'show'])->whereNumber('status')->name('curator.statuses.show');
    Route::get('kurator/status/{status}/ubah', [CuratorStatusController::class, 'edit'])->whereNumber('status')->name('curator.statuses.edit');
    // Tulisan meja kurator dibatasi laju dengan hitungannya sendiri (meja-kurator).
    Route::middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,meja-kurator')->group(function () {
        Route::post('kurator/status', [CuratorStatusController::class, 'store'])->name('curator.statuses.store');
        Route::put('kurator/status/{status}', [CuratorStatusController::class, 'update'])->whereNumber('status')->name('curator.statuses.update');
        Route::delete('kurator/status/{status}', [CuratorStatusController::class, 'destroy'])->whereNumber('status')->name('curator.statuses.destroy');
        Route::post('kurator/status/{status}/kirim', [CuratorStatusController::class, 'submit'])->whereNumber('status')->name('curator.statuses.submit');
        Route::post('kurator/status/{status}/verifikasi', [CuratorStatusController::class, 'verify'])->whereNumber('status')->name('curator.statuses.verify');
        Route::post('kurator/status/{status}/persoalkan', [CuratorStatusController::class, 'dispute'])->whereNumber('status')->name('curator.statuses.dispute');
        Route::post('kurator/status/{status}/tinjau', [CuratorStatusController::class, 'review'])->whereNumber('status')->name('curator.statuses.review');
    });
});
```

3. Ganti:

```php
    Route::post('sources/{source}/perdebatkan', [DataSourceController::class, 'dispute'])->name('sources.dispute');
    Route::get('official-statuses', [OfficialStatusController::class, 'index'])->name('statuses');
    Route::post('official-statuses', [OfficialStatusController::class, 'store'])->name('statuses.store');
    Route::get('permits', [PermitController::class, 'index'])->name('permits');
```

dengan:

```php
    Route::post('sources/{source}/perdebatkan', [DataSourceController::class, 'dispute'])->name('sources.dispute');
    // Ringkasan dan antrean tinjau status resmi; pencatatan pindah ke meja kurator (W8, ruling W8-R16).
    Route::get('official-statuses', [OfficialStatusController::class, 'index'])->name('statuses');
    Route::get('permits', [PermitController::class, 'index'])->name('permits');
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
namespace Tests\Pratinjau;

use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\HikingSessionStatus;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\Checkpoint;
```

dengan:

```php
namespace Tests\Pratinjau;

use App\Enums\AuthorityType;
use App\Enums\CancellationReason;
use App\Enums\CompletionState;
use App\Enums\EvidenceType;
use App\Enums\HikingSessionStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\StatusEndCondition;
use App\Enums\StatusReasonCategory;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\Checkpoint;
```

2. Ganti:

```php
use App\Models\MountainFollow;
use App\Models\Trail;
```

dengan:

```php
use App\Models\MountainFollow;
use App\Models\OfficialStatus;
use App\Models\Trail;
```

3. Ganti:

```php
use App\Services\ConsiderationService;
use App\Services\PreparationService;
```

dengan:

```php
use App\Services\ConsiderationService;
use App\Services\DataResmi\TerbitStatus;
use App\Services\PreparationService;
```

4. Ganti:

```php
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
```

dengan:

```php
use Database\Seeders\DemoSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
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
     * Meja kurator (W8, spec v3 bagian 10.7): Citra, kurator balai Gunung Gede, dengan pembukaan
     * dari rekannya yang menunggu verifikasinya, status contoh yang lewat jadwal tinjau, dan satu
     * draf berbukti berita. Akun kurator hanya dibuat di test ini, tidak di data contoh (Q11).
     */
    public function test_meja_kurator(): void
    {
        $this->actingAs($this->mejaKuratorContoh()['citra']);

        $this->tulisPratinjau('meja-kurator', $this->get(self::ASAL.'/kurator'), 'Curator/Desk');
    }

    /**
     * Rincian pembukaan yang menunggu verifikasi kedua, dilihat Citra sebagai pemeriksa.
     */
    public function test_catatan_kurator(): void
    {
        $contoh = $this->mejaKuratorContoh();
        $this->actingAs($contoh['citra']);

        $this->tulisPratinjau('catatan-kurator', $this->get(self::ASAL.'/kurator/status/'.$contoh['menunggu']->id), 'Curator/Status');
    }

    /**
     * Draf Citra berbukti berita: peringatan pemeriksaan konsistensi dan aksi pencatat.
     */
    public function test_catatan_kurator_draf(): void
    {
        $contoh = $this->mejaKuratorContoh();
        $this->actingAs($contoh['citra']);

        $this->tulisPratinjau('catatan-kurator-draf', $this->get(self::ASAL.'/kurator/status/'.$contoh['draf']->id), 'Curator/Status');
    }

    public function test_formulir_kurator(): void
    {
        $contoh = $this->mejaKuratorContoh();
        $this->actingAs($contoh['citra']);

        $this->tulisPratinjau('formulir-kurator', $this->get(self::ASAL.'/kurator/status/baru?sasaran=TRAIL:'.$contoh['jalur']->id), 'Curator/StatusForm');
    }

    /**
     * @return array{citra: User, menunggu: OfficialStatus, draf: OfficialStatus, jalur: Trail}
     */
    private function mejaKuratorContoh(): array
    {
        Storage::fake(config('filesystems.evidence_disk'));
        $this->seed(DemoSeeder::class);

        $jalur = Trail::where('slug', 'gede-via-cibodas')->firstOrFail();
        $balai = Authority::create(['name' => 'Balai Besar TN Gede Pangrango', 'slug' => 'bbtnggp-contoh', 'type' => AuthorityType::NATIONAL_PARK->value, 'abbreviation' => 'BBTNGGP']);
        $balai->mountains()->attach($jalur->mountain_id);

        $kurator = function (string $nama, string $surel) use ($balai): User {
            $user = User::factory()->create(['name' => $nama, 'email' => $surel, 'role' => UserRole::CURATOR->value]);
            AuthorityMember::factory()->for($user)->for($balai)->create(['appointment_reference' => 'Surat tugas DATA CONTOH']);

            return $user;
        };
        $citra = $kurator('Citra Lestari', 'citra@contoh.test');
        $dimas = $kurator('Dimas Pratama', 'dimas@contoh.test');
        $surat = fn () => UploadedFile::fake()->createWithContent('surat.pdf', "%PDF-1.4\n%%EOF\n");
        $isian = fn (string $status, array $ganti = []) => [
            'status' => $status,
            'document_number' => 'DATA CONTOH/PG.12/2026',
            'document_date' => now()->subDay()->toDateString(),
            'evidence_type' => EvidenceType::OFFICIAL_LETTER->value,
            'source_url' => 'https://contoh.test/pengumuman',
            'captured_at' => now()->subHours(3),
            'effective_at' => now()->subHour(),
            'end_condition' => StatusEndCondition::UNTIL_NOTICE->value,
            'expires_at' => null,
            'reason_category' => null,
            'reason' => null,
            'limit_description' => null,
            'legal_basis' => null,
            'official_excerpt' => 'DATA CONTOH. Pendakian jalur Cibodas dibuka kembali.',
            'plain_summary' => 'DATA CONTOH. Jalur Cibodas dibuka kembali dengan kuota harian.',
            ...$ganti,
        ];

        $layanan = app(TerbitStatus::class);
        $menunggu = $layanan->kirim($dimas, $layanan->buatDraf($dimas, $jalur, $balai, $isian(OfficialStatusValue::OPEN->value), $surat()));
        $draf = $layanan->buatDraf($citra, $jalur->mountain, $balai, $isian(OfficialStatusValue::RESTRICTED->value, [
            'evidence_type' => EvidenceType::NEWS->value,
            'limit_description' => 'Sampai Kandang Badak',
            'reason_category' => StatusReasonCategory::WEATHER->value,
            'official_excerpt' => 'DATA CONTOH. Pendakian dibatasi sampai Pos Kandang Badak.',
            'plain_summary' => 'DATA CONTOH. Pendakian dibatasi sampai Kandang Badak karena cuaca.',
        ]), $surat());

        return ['citra' => $citra, 'menunggu' => $menunggu, 'draf' => $draf, 'jalur' => $jalur];
    }
}
```

Hapus berkas `app/Http/Requests/Admin/StoreOfficialStatusRequest.php`.

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `curator.desk` dan tidak lagi memuat `admin.statuses.store`; tsc tanpa
keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Admin/OfficialStatusController.php app/Http/Controllers/Curator/CuratorDeskController.php app/Http/Controllers/Curator/CuratorStatusController.php app/Http/Middleware/HandleInertiaRequests.php app/Http/Requests/Curator/PersoalkanStatusRequest.php app/Http/Requests/Curator/SimpanCatatanStatusRequest.php app/Http/Resources/Admin/OfficialStatusRowResource.php app/Policies/OfficialStatusPolicy.php app/Services/DataFreshnessService.php app/Services/DataResmi/HalamanCatatanStatus.php app/Services/DataResmi/MejaKurator.php routes/web.php tests/Feature/CuratorDeskTest.php tests/Feature/EmptyStateCoverageTest.php tests/Feature/OfficialStatusPageTest.php tests/Feature/OfficialStatusSafetySentenceTest.php tests/Feature/OfficialStatusTargetTest.php tests/Feature/StatusCacheInvalidationTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='CuratorDesk|OfficialStatusTarget|OfficialStatusPage|StatusCacheInvalidation|OfficialStatusSafetySentence|EmptyStateCoverage'`
Expected: PASS. Pada simulasi rencana: 28 test, 28 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.200 test, 2.198 lulus, 2 dilewati (sebelum tugas: 2.191).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 40 test lulus (sebelum tugas: 36; halaman baru `meja-kurator`, `catatan-kurator`, `catatan-kurator-draf`, `formulir-kurator`).

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 9: Laporkan kesalahan data

Spec v3 bagian 10.7 (langkah 7 dan "tautan Laporkan kesalahan data ke antrean kurator"), induk W8 butir 9; ruling
W8-R17; R-010; Konflik 8. Kartu status jalur di rincian jalur dan kartu status gunung di halaman gunung memuat
"Laporkan kesalahan data": dialog berisi bagian yang keliru (status, izin, rute, lainnya) dan uraian 10 sampai 1.000
karakter, paling banyak 5 laporan per jam per pendaki (`laporan-kesalahan`). Laporan menyimpan pendaki, gunung, jalur,
dan catatan status yang tampil saat itu dari server, bukan dari isian, dan tidak mengubah status yang tampil. Kurator
kawasan itu melihatnya di "Laporan kesalahan data" di meja, tanpa identitas pelapor, dan menandainya sudah dicek dengan
catatan hasil (diaudit). Laporan terhapus bersama akun pelapornya. Migrasi `2026_10_09_000300` membuat
`data_error_reports` dengan dua CHECK dan RLS di PostgreSQL.

**Files:**
- Create: `app/Enums/DataErrorCategory.php`, `app/Enums/DataErrorState.php`, `app/Http/Controllers/Community/DataErrorReportController.php`, `app/Http/Controllers/Curator/DataErrorCheckController.php`, `app/Http/Requests/Community/StoreDataErrorReportRequest.php`, `app/Http/Requests/Curator/CekLaporanKesalahanRequest.php`, `app/Models/DataErrorReport.php`, `app/Policies/DataErrorReportPolicy.php`, `database/factories/DataErrorReportFactory.php`, `database/migrations/2026_10_09_000300_create_data_error_reports_table.php`, `resources/js/components/kurator/DaftarLaporan.tsx`, `resources/js/components/trails/LaporkanKesalahan.tsx`
- Modify: `app/Services/DataResmi/MejaKurator.php`, `app/Services/Mountains/HalamanGunung.php`, `app/Services/Trails/TrailDetailPage.php`, `config/hiking.php`, `database/README.md`, `resources/js/components/kurator/types.ts`, `resources/js/components/trails/gunung/KartuStatusGunung.tsx`, `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/pages/Curator/Desk.tsx`, `resources/js/pages/Mountains/Show.tsx`, `resources/js/pages/Trails/Show.tsx`, `routes/web.php`
- Test: `tests/Feature/DataErrorReportTest.php` (baru), `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Spatial/DataErrorReportSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `Yurisdiksi` (Tugas 3), `MejaKurator::props()` dan halaman `Curator/Desk` (Tugas 8), `OfficialStatusService::currentFor()` (Tugas 7), `HalamanGunung` dan `TrailDetailPage` (W3, W5), `KartuStatusGunung` dan `rinci/Kondisi` (W3), `AuditLogService::record()`.
- Produces:
  - enum `DataErrorCategory` (`STATUS`, `PERMIT`, `ROUTE`, `OTHER`; `label()`, `pilihan()` statis) dan `DataErrorState` (`OPEN`, `CHECKED`; `label()`); model `DataErrorReport` (isian massal hanya `category` dan `note`; `scopeTerbuka()`), pabrik, dan `DataErrorReportPolicy::check(User, DataErrorReport): bool`;
  - rute `trails.data-errors.store` (POST `trails/{trail:slug}/laporkan-kesalahan`), `mountains.data-errors.store` (POST `mountains/{mountain:slug}/laporkan-kesalahan`), keduanya berbatas laju `laporan-kesalahan`, dan `curator.data-errors.check` (POST `kurator/laporan/{laporan}/dicek`, berbatas laju `meja-kurator`);
  - config `hiking.rate_limits.data_error_reports_per_hour` (5);
  - prop `laporKesalahan` di rincian jalur dan halaman gunung; kunci `antrean.laporan` di props meja; komponen `LaporkanKesalahan` dan `DaftarLaporan`; tipe `BarisLaporan`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction DataErrorReportTest`, lalu isinya diganti persis. Berkas lain, `tests/Spatial/DataErrorReportSchemaTest.php`, dibuat langsung.

Buat `tests/Feature/DataErrorReportTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\DataErrorState;
use App\Enums\OfficialStatusValue;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\DataErrorReport;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\OfficialStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * "Laporkan kesalahan data" dari layar pendaki ke antrean cek ulang kurator (spec v3 bagian 10.7
 * langkah 7; rencana induk W8 butir 9; ruling W8-R17). Laporan tidak pernah mengubah status
 * (R-010); pelapor, sasaran, dan catatan yang terbaca dari sesi, rute, dan server; batas laju;
 * yurisdiksi kurator; identitas pelapor tidak sampai ke kurator; dan laporan ikut terhapus
 * bersama akun pelapornya (kebijakan data pribadi bagian 3).
 */
class DataErrorReportTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    private Trail $jalur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siapkanKawasan();
        $this->jalur = Trail::factory()->published()->easy()->for($this->gunung)->create(['slug' => 'gede-via-cibodas']);
        $this->catatanLama(OfficialStatusValue::OPEN, ['effective_at' => now()->subHour()], $this->jalur);
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isian(array $ganti = []): array
    {
        return [
            'category' => 'STATUS',
            'note' => 'Akun resmi balai mengumumkan jalur ini ditutup sejak kemarin karena kebakaran.',
            ...$ganti,
        ];
    }

    private function pendaki(): User
    {
        return User::factory()->create(['name' => 'Rani Pelapor', 'email' => 'rani.pelapor@contoh.test']);
    }

    public function test_a_hiker_reports_from_a_trail_and_the_status_does_not_change(): void
    {
        $pendaki = $this->pendaki();
        $terbaca = app(OfficialStatusService::class)->currentFor($this->jalur);

        $this->actingAs($pendaki)
            ->from(route('trails.show', $this->jalur))
            ->post(route('trails.data-errors.store', $this->jalur), $this->isian())
            ->assertRedirect(route('trails.show', $this->jalur))
            ->assertSessionHas('pengumuman');

        $laporan = DataErrorReport::sole();
        $this->assertSame($pendaki->id, $laporan->user_id);
        $this->assertSame($this->gunung->id, $laporan->mountain_id);
        $this->assertSame($this->jalur->id, $laporan->trail_id);
        $this->assertSame($terbaca->id, $laporan->official_status_id);
        $this->assertSame(DataErrorState::OPEN, $laporan->state);
        $this->assertSame(OfficialStatusValue::OPEN, app(OfficialStatusService::class)->effectiveStatusForTrail($this->jalur->fresh()), 'Laporan tidak pernah mengubah status (R-010).');
    }

    public function test_a_hiker_reports_from_a_mountain(): void
    {
        $this->actingAs($this->pendaki())
            ->post(route('mountains.data-errors.store', $this->gunung), $this->isian(['category' => 'PERMIT']))
            ->assertSessionHasNoErrors();

        $laporan = DataErrorReport::sole();
        $this->assertSame($this->gunung->id, $laporan->mountain_id);
        $this->assertNull($laporan->trail_id);
        $this->assertNull($laporan->official_status_id, 'Gunung ini belum punya catatan status yang terbaca.');
    }

    /**
     * Pelapor, sasaran, keadaan, dan pemeriksa tidak pernah dari isian (mass assignment).
     */
    public function test_identity_target_and_state_never_come_from_the_payload(): void
    {
        $pendaki = $this->pendaki();
        $lain = User::factory()->create();
        $gunungLain = Mountain::factory()->create();

        $this->actingAs($pendaki)->post(route('trails.data-errors.store', $this->jalur), $this->isian([
            'user_id' => $lain->id,
            'mountain_id' => $gunungLain->id,
            'trail_id' => null,
            'official_status_id' => 999,
            'state' => 'CHECKED',
            'checked_by' => $lain->id,
            'resolution' => 'Sudah benar.',
        ]))->assertSessionHasNoErrors();

        $laporan = DataErrorReport::sole();
        $this->assertSame($pendaki->id, $laporan->user_id);
        $this->assertSame($this->gunung->id, $laporan->mountain_id);
        $this->assertSame($this->jalur->id, $laporan->trail_id);
        $this->assertSame(DataErrorState::OPEN, $laporan->state);
        $this->assertNull($laporan->checked_by);
        $this->assertNull($laporan->resolution);
    }

    public function test_a_report_needs_a_section_and_a_description(): void
    {
        $this->actingAs($this->pendaki())
            ->post(route('trails.data-errors.store', $this->jalur), ['category' => 'TEBAKAN', 'note' => 'salah'])
            ->assertSessionHasErrors(['category', 'note']);

        $this->assertSame(0, DataErrorReport::count());
    }

    public function test_reports_are_rate_limited(): void
    {
        $pendaki = $this->pendaki();
        $batas = (int) config('hiking.rate_limits.data_error_reports_per_hour');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($pendaki)->post(route('trails.data-errors.store', $this->jalur), $this->isian())->assertSessionHasNoErrors();
        }

        $this->actingAs($pendaki)->post(route('trails.data-errors.store', $this->jalur), $this->isian())->assertStatus(429);
        $this->assertSame($batas, DataErrorReport::count());
    }

    public function test_drafts_archives_and_guests_are_refused(): void
    {
        $draf = Trail::factory()->unpublished()->for($this->gunung)->create();
        $arsip = Mountain::factory()->create(['archived_at' => now()]);
        $pendaki = $this->pendaki();

        $this->actingAs($pendaki)->post(route('trails.data-errors.store', $draf), $this->isian())->assertNotFound();
        $this->actingAs($pendaki)->post(route('mountains.data-errors.store', $arsip), $this->isian())->assertNotFound();

        auth()->logout();
        $this->post(route('trails.data-errors.store', $this->jalur), $this->isian())->assertRedirect(route('login'));
        $this->assertSame(0, DataErrorReport::count());
    }

    public function test_trail_and_mountain_pages_offer_the_link(): void
    {
        $pendaki = $this->pendaki();

        $this->actingAs($pendaki)->get(route('trails.show', $this->jalur))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('laporKesalahan.url', route('trails.data-errors.store', $this->jalur))
            ->has('laporKesalahan.kategori', 4));

        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('laporKesalahan.url', route('mountains.data-errors.store', $this->gunung))
            ->has('laporKesalahan.kategori', 4));
    }

    /**
     * Kurator kawasan melihat isi laporan dan halaman yang dilaporkan, tanpa nama atau surel
     * pelapor (minimisasi data, PRD §83).
     */
    public function test_the_area_curator_sees_the_report_without_the_reporter(): void
    {
        $this->actingAs($this->pendaki())->post(route('trails.data-errors.store', $this->jalur), $this->isian());

        $respons = $this->actingAs($this->kurator()->fresh())->get(route('curator.desk'));

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->has('antrean.laporan', 1)
            ->where('antrean.laporan.0.bagian', 'Status akses')
            ->where('antrean.laporan.0.halaman', route('trails.show', $this->jalur))
            ->where('antrean.laporan.0.isi', $this->isian()['note']));
        $respons->assertDontSee('Rani Pelapor');
        $respons->assertDontSee('rani.pelapor@contoh.test');
    }

    public function test_a_curator_outside_the_area_neither_sees_nor_checks_it(): void
    {
        $this->actingAs($this->pendaki())->post(route('trails.data-errors.store', $this->jalur), $this->isian());
        $luar = $this->kurator(Authority::factory()->create());

        $this->actingAs($luar)->get(route('curator.desk'))->assertInertia(fn (AssertableInertia $page) => $page->has('antrean.laporan', 0));
        $this->actingAs($luar)->post(route('curator.data-errors.check', DataErrorReport::sole()), ['hasil' => 'Sudah saya cek ke balai.'])->assertForbidden();
        $this->assertSame(DataErrorState::OPEN, DataErrorReport::sole()->state);
    }

    public function test_checking_records_the_result_and_audit_and_changes_no_status(): void
    {
        $this->actingAs($this->pendaki())->post(route('trails.data-errors.store', $this->jalur), $this->isian());
        $kurator = $this->kurator();
        $laporan = DataErrorReport::sole();

        $this->actingAs($kurator)
            ->post(route('curator.data-errors.check', $laporan), ['hasil' => 'Akun balai belum mengumumkan penutupan; status Buka tetap.'])
            ->assertRedirect(route('curator.desk'));

        $laporan->refresh();
        $this->assertSame(DataErrorState::CHECKED, $laporan->state);
        $this->assertSame($kurator->id, $laporan->checked_by);
        $this->assertNotNull($laporan->checked_at);
        $this->assertSame('Akun balai belum mengumumkan penutupan; status Buka tetap.', $laporan->resolution);

        $jejak = AuditLog::where('action', 'data_error_report.checked')->sole();
        $this->assertSame('OPEN', $jejak->before['state']);
        $this->assertSame('CHECKED', $jejak->after['state']);
        $this->assertSame(OfficialStatusValue::OPEN, app(OfficialStatusService::class)->effectiveStatusForTrail($this->jalur->fresh()));

        // Sekali saja: laporan yang sudah dicek tidak dapat ditandai ulang.
        $this->actingAs($kurator)->post(route('curator.data-errors.check', $laporan), ['hasil' => 'Dicek lagi hari ini.'])->assertForbidden();
    }

    public function test_the_result_is_required_and_admins_are_not_curators(): void
    {
        $this->actingAs($this->pendaki())->post(route('trails.data-errors.store', $this->jalur), $this->isian());
        $laporan = DataErrorReport::sole();

        $this->actingAs($this->kurator())->post(route('curator.data-errors.check', $laporan), ['hasil' => ''])->assertSessionHasErrors('hasil');
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))->post(route('curator.data-errors.check', $laporan), ['hasil' => 'Sudah saya cek ke balai.'])->assertForbidden();
        $this->assertSame(DataErrorState::OPEN, $laporan->fresh()->state);
    }

    public function test_deleting_the_reporter_deletes_the_report(): void
    {
        $pendaki = $this->pendaki();
        $this->actingAs($pendaki)->post(route('trails.data-errors.store', $this->jalur), $this->isian());

        $pendaki->delete();

        $this->assertSame(0, DataErrorReport::count());
    }
}
```

Buat `tests/Spatial/DataErrorReportSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\DataErrorReport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel laporan kesalahan data di PostgreSQL (R-024; ruling W8-R17): keadaan dan bagian dari daftar
 * tertutup, laporan ikut terhapus bersama akun pelapor dan gunungnya, pemeriksa dan catatan status
 * menjadi kosong bila rujukannya hilang, indeks antrean ada, dan RLS menutup REST API. Suite SQLite
 * tidak membuktikan CHECK dan RLS. Migrasinya tidak dijalankan di Supabase sebelum izin pemilik.
 */
class DataErrorReportSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'data_error_reports'")->relrowsecurity, 'RLS lapis kedua harus menyala.');

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'data_error_reports'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('data_error_reports_keadaan_sah', $cek);
        $this->assertContains('data_error_reports_bagian_sah', $cek);

        $this->assertNotNull(DB::selectOne("SELECT 1 AS ada FROM pg_indexes WHERE tablename = 'data_error_reports' AND indexname = 'data_error_reports_antrean'"));

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'data_error_reports'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('c', $hapus['user_id']);
        $this->assertSame('c', $hapus['mountain_id']);
        $this->assertSame('c', $hapus['trail_id']);
        $this->assertSame('n', $hapus['official_status_id']);
        $this->assertSame('n', $hapus['checked_by']);
    }

    public function test_an_unknown_state_is_refused(): void
    {
        $laporan = DataErrorReport::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('data_error_reports')->where('id', $laporan->id)->update(['state' => 'ACCEPTED']);
    }

    public function test_an_unknown_section_is_refused(): void
    {
        $laporan = DataErrorReport::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('data_error_reports')->where('id', $laporan->id)->update(['category' => 'WEATHER']);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='DataErrorReport|RepoPolicyFiles|CuratorDesk'`
Expected: FAIL. Pada simulasi rencana: 30 test, 15 lulus, 12 error, 3 dilewati. Yang merah: kedua belas test `DataErrorReportTest` (rute `trails.data-errors.store` dan
`mountains.data-errors.store` belum ada). 3 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction DataErrorCategory`, `... make:enum --no-interaction DataErrorState`, `... make:controller --no-interaction Community/DataErrorReportController`, `... make:controller --no-interaction Curator/DataErrorCheckController`, `... make:request --no-interaction Community/StoreDataErrorReportRequest`, `... make:request --no-interaction Curator/CekLaporanKesalahanRequest`, `... make:model --no-interaction DataErrorReport`, `... make:policy --no-interaction DataErrorReportPolicy`, `... make:factory --no-interaction DataErrorReportFactory`, dan `... make:migration --no-interaction create_data_error_reports_table` (ganti nama berkas migrasi hasilnya menjadi `2026_10_09_000300_create_data_error_reports_table.php`), lalu isinya diganti persis. Berkas lain, `resources/js/components/kurator/DaftarLaporan.tsx` dan `resources/js/components/trails/LaporkanKesalahan.tsx`, dibuat langsung.

Buat `app/Enums/DataErrorCategory.php`:

```php
<?php

namespace App\Enums;

/**
 * Bagian data yang dilaporkan keliru oleh pendaki (ruling W8-R17). Daftar tertutup supaya
 * antrean kurator dapat dipilah tanpa membaca isi laporan lebih dulu.
 */
enum DataErrorCategory: string
{
    case STATUS = 'STATUS';
    case PERMIT = 'PERMIT';
    case ROUTE = 'ROUTE';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::STATUS => 'Status akses',
            self::PERMIT => 'Perizinan',
            self::ROUTE => 'Data jalur: jarak, pos, atau peta',
            self::OTHER => 'Lainnya',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pilihan(): array
    {
        return array_map(fn (self $bagian) => ['value' => $bagian->value, 'label' => $bagian->label()], self::cases());
    }
}
```

Buat `app/Enums/DataErrorState.php`:

```php
<?php

namespace App\Enums;

/**
 * Keadaan laporan kesalahan data (ruling W8-R17): menunggu cek ulang kurator, atau sudah dicek
 * beserta hasilnya. Tidak ada keadaan "diterima" yang mengubah data: perubahan status tetap
 * lewat catatan baru di meja kurator (R-010).
 */
enum DataErrorState: string
{
    case OPEN = 'OPEN';
    case CHECKED = 'CHECKED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Menunggu cek ulang',
            self::CHECKED => 'Sudah dicek',
        };
    }
}
```

Buat `app/Http/Controllers/Community/DataErrorReportController.php`:

```php
<?php

namespace App\Http\Controllers\Community;

use App\Enums\DataErrorState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreDataErrorReportRequest;
use App\Models\DataErrorReport;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use App\Services\OfficialStatusService;
use Illuminate\Http\RedirectResponse;

/**
 * "Laporkan kesalahan data" dari kartu status jalur dan gunung (spec v3 bagian 10.7; rencana
 * induk W8 butir 9; ruling W8-R17). Laporan masuk antrean cek ulang kurator kawasan itu dan
 * tidak pernah mengubah status yang tampil (R-010). Pelapor dari sesi, sasaran dari rute, dan
 * catatan status yang sedang terbaca dibaca server.
 */
class DataErrorReportController extends Controller
{
    public const TERKIRIM = 'Laporan terkirim. Kurator Memount mengecek ulang ke sumber resminya; status yang tampil tidak berubah karena laporan ini.';

    public function __construct(private readonly OfficialStatusService $status) {}

    public function storeForTrail(StoreDataErrorReportRequest $request, Trail $trail): RedirectResponse
    {
        abort_unless($trail->is_published && $trail->archived_at === null, 404);

        $trail->loadMissing('mountain');
        $catatan = OfficialStatusService::catatanPenentu(
            $this->status->currentFor($trail),
            $trail->mountain !== null ? $this->status->currentFor($trail->mountain) : null,
        );

        $this->simpan($request, $request->user(), $trail->mountain_id, $trail->id, $catatan);

        return back()->with('pengumuman', self::TERKIRIM);
    }

    public function storeForMountain(StoreDataErrorReportRequest $request, Mountain $mountain): RedirectResponse
    {
        abort_if($mountain->isArchived(), 404);

        $this->simpan($request, $request->user(), $mountain->id, null, $this->status->currentFor($mountain));

        return back()->with('pengumuman', self::TERKIRIM);
    }

    private function simpan(StoreDataErrorReportRequest $request, User $pelapor, int $gunung, ?int $jalur, ?OfficialStatus $catatan): void
    {
        $laporan = new DataErrorReport($request->validated());
        $laporan->forceFill([
            'user_id' => $pelapor->id,
            'mountain_id' => $gunung,
            'trail_id' => $jalur,
            'official_status_id' => $catatan?->id,
            'state' => DataErrorState::OPEN->value,
        ])->save();
    }
}
```

Buat `app/Http/Controllers/Curator/DataErrorCheckController.php`:

```php
<?php

namespace App\Http\Controllers\Curator;

use App\Enums\DataErrorState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Curator\CekLaporanKesalahanRequest;
use App\Models\DataErrorReport;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Menandai laporan kesalahan data sudah dicek (spec v3 bagian 10.7 langkah 7; ruling W8-R17).
 * Hanya menutup tugas cek ulang beserta hasilnya; status resmi tidak disentuh. Bila sumbernya
 * memang berubah, kurator mencatat status pengganti lewat formulir meja kurator.
 */
class DataErrorCheckController extends Controller
{
    public function __invoke(CekLaporanKesalahanRequest $request, DataErrorReport $laporan, AuditLogService $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $laporan, $audit): void {
            $sebelum = $laporan->only(['state', 'checked_by', 'checked_at', 'resolution']);
            $laporan->forceFill([
                'state' => DataErrorState::CHECKED->value,
                'checked_by' => $request->user()->id,
                'checked_at' => now(),
                'resolution' => $request->validated('hasil'),
            ])->save();
            $audit->record($request->user(), 'data_error_report.checked', $laporan, $sebelum, $laporan->only(['state', 'checked_by', 'checked_at', 'resolution']));
        });

        return to_route('curator.desk')->with('status', 'Laporan ditandai sudah dicek.');
    }
}
```

Buat `app/Http/Requests/Community/StoreDataErrorReportRequest.php`:

```php
<?php

namespace App\Http\Requests\Community;

use App\Enums\DataErrorCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Isi laporan kesalahan data (ruling W8-R17): bagian dan uraian saja. Pelapor, gunung, jalur,
 * dan catatan status yang terbaca ditetapkan controller dari sesi dan rute.
 */
class StoreDataErrorReportRequest extends FormRequest
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
            'category' => ['required', new Enum(DataErrorCategory::class)],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Pilih bagian yang keliru.',
            'note.required' => 'Tulis apa yang keliru, supaya kurator tahu apa yang harus dicek.',
            'note.min' => 'Tulis apa yang keliru, paling sedikit 10 karakter.',
            'note.max' => 'Laporan paling panjang 1.000 karakter.',
        ];
    }
}
```

Buat `app/Http/Requests/Curator/CekLaporanKesalahanRequest.php`:

```php
<?php

namespace App\Http\Requests\Curator;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Hasil cek ulang sebuah laporan kesalahan data (ruling W8-R17). Wajib ditulis, karena
 * "sudah dicek" tanpa hasil tidak dapat ditelusuri ulang.
 */
class CekLaporanKesalahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('check', $this->route('laporan')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hasil' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hasil.required' => 'Tulis hasil cek ulang Anda, misalnya sumber yang Anda periksa dan apa isinya.',
            'hasil.min' => 'Tulis hasil cek ulang Anda, paling sedikit 10 karakter.',
        ];
    }
}
```

Buat `app/Models/DataErrorReport.php`:

```php
<?php

namespace App\Models;

use App\Enums\DataErrorCategory;
use App\Enums\DataErrorState;
use Database\Factories\DataErrorReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan kesalahan data dari layar pendaki (spec v3 bagian 10.7 langkah 7; ruling W8-R17).
 * Hanya bagian dan isi yang diisi pendaki; pelapor, gunung, jalur, catatan status yang terbaca,
 * keadaan, dan pemeriksa ditulis server lewat forceFill, tidak pernah dari isian.
 */
#[Fillable(['category', 'note'])]
class DataErrorReport extends Model
{
    /** @use HasFactory<DataErrorReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => DataErrorCategory::class,
            'state' => DataErrorState::class,
            'checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function officialStatus(): BelongsTo
    {
        return $this->belongsTo(OfficialStatus::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->where('state', DataErrorState::OPEN->value);
    }
}
```

Buat `app/Policies/DataErrorReportPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Enums\DataErrorState;
use App\Models\DataErrorReport;
use App\Models\User;
use App\Services\DataResmi\Yurisdiksi;

/**
 * Laporan kesalahan data (ruling W8-R17). Pendaki yang masuk boleh melapor lewat rute yang
 * dibatasi laju; yang menandai sudah dicek hanya kurator aktif yang berwenang atas gunung
 * laporan itu, sekali saja. Admin dan moderator bukan kurator.
 */
class DataErrorReportPolicy
{
    public function __construct(private readonly Yurisdiksi $yurisdiksi) {}

    public function check(User $user, DataErrorReport $laporan): bool
    {
        return $laporan->state === DataErrorState::OPEN
            && $this->yurisdiksi->gunung($user)->contains($laporan->mountain_id);
    }
}
```

Buat `database/factories/DataErrorReportFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\DataErrorCategory;
use App\Enums\DataErrorState;
use App\Models\DataErrorReport;
use App\Models\Mountain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataErrorReport>
 */
class DataErrorReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mountain_id' => Mountain::factory(),
            'category' => DataErrorCategory::STATUS->value,
            'note' => 'Pengumuman balai di akun resminya menyebut jalur ini sudah dibuka kembali.',
            'state' => DataErrorState::OPEN->value,
        ];
    }
}
```

Buat `database/migrations/2026_10_09_000300_create_data_error_reports_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan kesalahan data dari layar pendaki ke antrean kurator (spec v3 bagian 10.7 langkah 7
 * "konflik"; rencana induk W8 butir 9; ruling W8-R17). Laporan tidak pernah mengubah status
 * (R-010): kurator mendapat tugas cek ulang ke sumber resminya, lalu menandainya sudah dicek
 * beserta hasilnya.
 *
 * Sasarannya selalu gunung, dan jalur bila laporan dikirim dari halaman jalur, supaya antrean
 * kurator disaring per kawasan dengan satu kolom. `official_status_id` adalah catatan yang
 * sedang terbaca pendaki saat ia melapor, diisi server, tidak pernah dari isian.
 *
 * Laporan ikut terhapus bersama akun pelapornya: kebijakan data pribadi bagian 3 memperlakukan
 * kategori yang tidak punya dasar retensi terdokumentasi sebagai dihapus. Kurator pemeriksa
 * menjadi kosong bila akunnya hilang. Di PostgreSQL dua CHECK menjaga keadaan dan bagian, dan
 * RLS menutup REST API (lapis kedua 2026_09_20_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_error_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mountain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('official_status_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20);
            $table->text('note');
            $table->string('state', 20)->default('OPEN');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->string('resolution', 500)->nullable();
            $table->timestamps();

            // Antrean meja kurator: laporan terbuka per gunung, terlama dulu.
            $table->index(['state', 'mountain_id', 'created_at'], 'data_error_reports_antrean');
            $table->index('user_id');
            $table->index('mountain_id');
            $table->index('trail_id');
            $table->index('official_status_id');
            $table->index('checked_by');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE data_error_reports ADD CONSTRAINT data_error_reports_keadaan_sah CHECK (state IN ('OPEN', 'CHECKED'))");
            DB::statement("ALTER TABLE data_error_reports ADD CONSTRAINT data_error_reports_bagian_sah CHECK (category IN ('STATUS', 'PERMIT', 'ROUTE', 'OTHER'))");
            DB::statement('ALTER TABLE public."data_error_reports" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('data_error_reports');
    }
};
```

Buat `resources/js/components/kurator/DaftarLaporan.tsx`:

```tsx
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { TextareaField } from '@/components/discovery/Isian';
import { Button } from '@/components/ui/Button';
import { Dialog } from '@/components/ui/Lembar';
import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import type { BarisLaporan } from './types';

const tautan = 'inline-flex min-h-11 items-center break-words font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:rounded-control focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Antrean cek ulang dari "Laporkan kesalahan data" (spec v3 bagian 10.7 langkah 7; ruling W8-R17).
 * Laporan tidak mengubah status: kurator membuka halaman yang dilaporkan, mengecek ulang ke sumber
 * resminya, lalu menandainya sudah dicek dengan hasilnya. Bila sumbernya memang berubah, kurator
 * mencatat status pengganti. Identitas pelapor tidak tampil.
 */
export function DaftarLaporan({ baris }: { baris: BarisLaporan[] }) {
    const [dipilih, setDipilih] = useState<BarisLaporan | null>(null);
    const pemicu = useRef<HTMLElement | null>(null);
    const form = useForm({ hasil: '' });

    const tutup = () => {
        setDipilih(null);
        form.reset();
        form.clearErrors();
    };

    return (
        <section aria-labelledby="antrean-laporan" className="border-t border-subtle pt-6">
            <h2 id="antrean-laporan" className="text-bagian text-primary">
                Laporan kesalahan data <span className="text-secondary tabular-nums">({baris.length})</span>
            </h2>
            <p className="mt-1 max-w-prose text-kecil text-secondary">
                Dari pendaki yang melihat data keliru di kawasan Anda. Laporan tidak mengubah status: cek ulang ke sumber resminya, lalu tandai sudah dicek. Bila sumbernya memang berubah, catat status penggantinya.
            </p>
            {baris.length === 0 ? (
                <p className="mt-3 text-kecil text-muted">Tidak ada laporan yang menunggu cek ulang.</p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle rounded-kartu border border-subtle bg-surface">
                    {baris.map((l) => (
                        <li key={l.id} className="flex flex-col gap-2 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                                <Tautan href={l.halaman} className={tautan}>
                                    {l.sasaran}
                                </Tautan>
                                <div className="mt-1">
                                    <Lencana nada="peringatan">{l.bagian}</Lencana>
                                </div>
                                <blockquote className="mt-2 max-w-prose break-words border-l-2 border-subtle pl-3 text-kecil text-primary">{l.isi}</blockquote>
                                <p className="mt-1 text-meta text-muted">Dilaporkan pendaki, {l.waktu}.</p>
                                {l.catatan ? (
                                    <Tautan href={l.catatan} className={tautan + ' text-kecil'}>
                                        Catatan status yang terbaca saat itu
                                    </Tautan>
                                ) : null}
                            </div>
                            <div className="flex shrink-0 flex-wrap gap-2">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    aria-label={`Tandai sudah dicek: ${l.sasaran}`}
                                    onClick={(e) => {
                                        pemicu.current = e.currentTarget;
                                        setDipilih(l);
                                    }}
                                >
                                    Tandai sudah dicek
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <Dialog
                terbuka={dipilih !== null}
                onTutup={tutup}
                judul="Tandai laporan sudah dicek?"
                keterangan="Tulis apa yang Anda periksa dan hasilnya. Hasil ini tercatat di jejak audit; status yang tampil tidak berubah karena langkah ini."
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={tutup}>
                            Batal
                        </Button>
                        <Button processing={form.processing} onClick={() => dipilih && form.post(dipilih.cek, { preserveScroll: true, onSuccess: tutup })}>
                            Tandai sudah dicek
                        </Button>
                    </>
                }
            >
                {dipilih ? <p className="mb-3 text-kecil text-secondary">{dipilih.sasaran}</p> : null}
                <TextareaField name="hasil" label="Hasil cek ulang" rows={3} value={form.data.hasil} onChange={(e) => form.setData('hasil', e.target.value)} error={form.errors.hasil} />
            </Dialog>
        </section>
    );
}
```

Buat `resources/js/components/trails/LaporkanKesalahan.tsx`:

```tsx
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { Button } from '@/components/ui/Button';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Dialog } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';

/** Alamat kiriman dan pilihan bagian (DataErrorReportController, DataErrorCategory::pilihan()). */
export interface LaporKesalahan {
    url: string;
    kategori: Opsi[];
}

/**
 * Tautan "Laporkan kesalahan data" di kartu status jalur dan gunung (spec v3 bagian 10.7; ruling
 * W8-R17). Laporan masuk antrean cek ulang kurator kawasan itu dan tidak mengubah status yang
 * tampil (R-010); kalimat itu disebut sebelum dan sesudah mengirim. Pelapor dari sesi dan
 * sasarannya dari alamat kiriman, bukan dari isian.
 */
export function LaporkanKesalahan({ lapor, sasaran }: { lapor: LaporKesalahan; sasaran: string }) {
    const [terbuka, setTerbuka] = useState(false);
    const [terkirim, setTerkirim] = useState(false);
    const pemicu = useRef<HTMLElement | null>(null);
    const form = useForm({ category: '', note: '' });

    const tutup = () => {
        setTerbuka(false);
        form.clearErrors();
    };

    return (
        <div className="mt-2">
            <button
                type="button"
                className={kelas('inline-flex min-h-11 items-center rounded-control text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
                onClick={(e) => {
                    pemicu.current = e.currentTarget;
                    setTerkirim(false);
                    setTerbuka(true);
                }}
            >
                Laporkan kesalahan data
            </button>
            {terkirim ? <p className="text-meta text-secondary">Laporan terkirim. Kurator Memount mengecek ulang ke sumber resminya.</p> : null}

            <Dialog
                terbuka={terbuka}
                onTutup={tutup}
                judul="Laporkan kesalahan data"
                keterangan={`${sasaran}. Laporan Anda tidak mengubah status yang tampil; kurator Memount mengecek ulang ke sumber resminya.`}
                pemicu={pemicu}
                aksi={
                    <>
                        <Button variant="secondary" onClick={tutup}>
                            Batal
                        </Button>
                        <Button
                            processing={form.processing}
                            onClick={() =>
                                form.post(lapor.url, {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        form.reset();
                                        setTerkirim(true);
                                        tutup();
                                    },
                                })
                            }
                        >
                            Kirim laporan
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <SelectField
                        name="category"
                        label="Bagian yang keliru"
                        required
                        placeholder="Pilih bagian"
                        options={lapor.kategori}
                        value={form.data.category}
                        onChange={(e) => form.setData('category', e.target.value)}
                        error={form.errors.category}
                    />
                    <div>
                        <TextareaField
                            name="note"
                            label="Apa yang keliru?"
                            required
                            rows={4}
                            maxLength={1000}
                            aria-describedby={form.errors.note ? 'note-petunjuk note-error' : 'note-petunjuk'}
                            value={form.data.note}
                            onChange={(e) => form.setData('note', e.target.value)}
                            error={form.errors.note}
                        />
                        <p id="note-petunjuk" className="mt-1 text-meta text-muted">
                            Sebut sumber yang Anda lihat bila ada, misalnya pengumuman balai bertanggal.
                        </p>
                    </div>
                </div>
            </Dialog>
        </div>
    );
}
```

`app/Services/DataResmi/MejaKurator.php`:

1. Ganti:

```php
use App\Models\AuthorityMember;
use App\Models\Mountain;
```

dengan:

```php
use App\Models\AuthorityMember;
use App\Models\DataErrorReport;
use App\Models\Mountain;
```

2. Ganti:

```php
 * (catatan kurator lain yang menunggu), tinjau berkala (kedaluwarsa, segera kedaluwarsa, lewat
 * jadwal tinjau, termasuk catatan lama dari sebelum meja kurator), catatan yang dipersoalkan, dan
 * draf miliknya sendiri. Jumlah kueri tetap, tidak tumbuh bersama jumlah catatan.
 */
```

dengan:

```php
 * (catatan kurator lain yang menunggu), tinjau berkala (kedaluwarsa, segera kedaluwarsa, lewat
 * jadwal tinjau, termasuk catatan lama dari sebelum meja kurator), laporan kesalahan data dari
 * pendaki yang menunggu cek ulang (ruling W8-R17), catatan yang dipersoalkan, dan draf miliknya
 * sendiri. Jumlah kueri tetap, tidak tumbuh bersama jumlah catatan.
 */
```

3. Ganti:

```php
                ],
                'dipersoalkan' => $dasar()
```

dengan:

```php
                ],
                'laporan' => DataErrorReport::query()
                    ->terbuka()
                    ->whereIn('mountain_id', $this->yurisdiksi->gunung($kurator)->all())
                    ->with(['mountain:id,name,slug', 'trail:id,name,slug,mountain_id'])
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->limit(self::BATAS)
                    ->get()
                    ->map(fn (DataErrorReport $l) => $this->barisLaporan($l))
                    ->values()
                    ->all(),
                'dipersoalkan' => $dasar()
```

4. Ganti:

```php
    }

    /**
     * @return array<string, mixed>
     */
    public function baris(OfficialStatus $s, ?string $alasanTinjau = null): array
```

dengan:

```php
    }

    /**
     * Satu laporan kesalahan data. Identitas pelapor tidak dikirim ke kurator (minimisasi data,
     * PRD §83): yang dicek adalah datanya, bukan orangnya.
     *
     * @return array<string, mixed>
     */
    private function barisLaporan(DataErrorReport $l): array
    {
        $jalur = $l->trail;

        return [
            'id' => $l->id,
            'sasaran' => $jalur !== null ? $jalur->name.', '.$l->mountain->name : $l->mountain->name.' (seluruh gunung)',
            'halaman' => $jalur !== null ? route('trails.show', $jalur) : route('mountains.show', $l->mountain),
            'bagian' => $l->category->label(),
            'isi' => $l->note,
            'waktu' => Timezone::display($l->created_at, Timezone::DEFAULT, 'j M Y H.i'),
            'catatan' => $l->official_status_id !== null ? route('curator.statuses.show', $l->official_status_id) : null,
            'cek' => route('curator.data-errors.check', $l),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function baris(OfficialStatus $s, ?string $alasanTinjau = null): array
```

`app/Services/Mountains/HalamanGunung.php`:

1. Ganti:

```php
namespace App\Services\Mountains;

use App\Enums\RouteFitLabel;
```

dengan:

```php
namespace App\Services\Mountains;

use App\Enums\DataErrorCategory;
use App\Enums\RouteFitLabel;
```

2. Ganti:

```php
            'status' => $this->statusGunung($gunung, $zona),
            'jalur' => $jalur->map(fn (Trail $t) => [
```

dengan:

```php
            'status' => $this->statusGunung($gunung, $zona),
            // "Laporkan kesalahan data" ke antrean cek ulang kurator (W8, ruling W8-R17).
            'laporKesalahan' => ['url' => route('mountains.data-errors.store', $gunung), 'kategori' => DataErrorCategory::pilihan()],
            'jalur' => $jalur->map(fn (Trail $t) => [
```

`app/Services/Trails/TrailDetailPage.php`:

1. Ganti:

```php
namespace App\Services\Trails;

use App\Enums\FreshnessState;
```

dengan:

```php
namespace App\Services\Trails;

use App\Enums\DataErrorCategory;
use App\Enums\FreshnessState;
```

2. Ganti:

```php
            'statusResmi' => $this->statusResmi($kondisi['official_status'], $zona),
            'perizinan' => $this->perizinan($izin, $zona),
```

dengan:

```php
            'statusResmi' => $this->statusResmi($kondisi['official_status'], $zona),
            // "Laporkan kesalahan data" ke antrean cek ulang kurator (W8, ruling W8-R17).
            'laporKesalahan' => ['url' => route('trails.data-errors.store', $trail), 'kategori' => DataErrorCategory::pilihan()],
            'perizinan' => $this->perizinan($izin, $zona),
```

`config/hiking.php`:

1. Ganti:

```php
        'recommendation_runs_per_hour' => 20,

        // Aksi tulis data pribadi (rencana, pengingat, catatan pendakian, target,
```

dengan:

```php
        'recommendation_runs_per_hour' => 20,

        // Laporan kesalahan data ke antrean kurator (W8, ruling W8-R17): sama dengan laporan
        // kondisi, karena keduanya ditulis pendaki dan dibaca manusia satu per satu.
        'data_error_reports_per_hour' => 5,

        // Aksi tulis data pribadi (rencana, pengingat, catatan pendakian, target,
```

`database/README.md`:

1. Ganti:

```markdown
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain`, `authority_members` | Hierarki otoritas (R-009); anggota meja kurator per badan resmi, dengan masa berlaku dan pencabutan (W8) |
| Kondisi dan kepercayaan data | `official_statuses`, `weather_snapshots`, `trail_condition_reports`, `report_thanks`, `permit_requirements`, `mountain_follows` | Laporan komunitas tidak menimpa status resmi (R-010); cuaca bersumber dan berkesegaran (R-013) |
| Route Fit dan rekomendasi | `recommendation_rules`, `recommendation_runs`, `recommendation_results` | Skor internal tidak pernah keluar dari server (BR-09) |
```

dengan:

```markdown
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain`, `authority_members` | Hierarki otoritas (R-009); anggota meja kurator per badan resmi, dengan masa berlaku dan pencabutan (W8) |
| Kondisi dan kepercayaan data | `official_statuses`, `weather_snapshots`, `trail_condition_reports`, `report_thanks`, `permit_requirements`, `mountain_follows`, `data_error_reports` | Laporan komunitas tidak menimpa status resmi (R-010); cuaca bersumber dan berkesegaran (R-013); laporan kesalahan data pendaki menjadi tugas cek ulang kurator, tidak pernah mengubah status (W8) |
| Route Fit dan rekomendasi | `recommendation_rules`, `recommendation_runs`, `recommendation_results` | Skor internal tidak pernah keluar dari server (BR-09) |
```

`resources/js/components/kurator/types.ts`:

1. Ganti:

```ts
}

export interface AntreanKurator {
```

dengan:

```ts
}

/** Satu laporan kesalahan data yang menunggu cek ulang (MejaKurator::barisLaporan), tanpa identitas pelapor. */
export interface BarisLaporan {
    id: number;
    sasaran: string;
    halaman: string;
    bagian: string;
    isi: string;
    waktu: string;
    catatan: string | null;
    cek: string;
}

export interface AntreanKurator {
```

2. Ganti:

```ts
    tinjau: BarisAntrean[];
    dipersoalkan: BarisAntrean[];
```

dengan:

```ts
    tinjau: BarisAntrean[];
    laporan: BarisLaporan[];
    dipersoalkan: BarisAntrean[];
```

`resources/js/components/trails/gunung/KartuStatusGunung.tsx`:

1. Ganti:

```tsx
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
```

dengan:

```tsx
import { LabelSumber } from '@/components/trails/LabelSumber';
import { LaporkanKesalahan, type LaporKesalahan } from '@/components/trails/LaporkanKesalahan';
import { StatusAkses } from '@/components/trails/StatusAkses';
```

2. Ganti:

```tsx
 */
export function KartuStatusGunung({ status }: { status: StatusRingkas }) {
    const resmi = status.sumber?.jenis === 'OFFICIAL';
```

dengan:

```tsx
 */
export function KartuStatusGunung({ status, lapor, sasaran }: { status: StatusRingkas; lapor?: LaporKesalahan; sasaran?: string }) {
    const resmi = status.sumber?.jenis === 'OFFICIAL';
```

3. Ganti:

```tsx
            {status.sumber ? <Kesegaran waktu={status.waktu} awalan={status.awalanWaktu ?? undefined} keadaan={status.kesegaran} className="mt-1" /> : null}
        </section>
```

dengan:

```tsx
            {status.sumber ? <Kesegaran waktu={status.waktu} awalan={status.awalanWaktu ?? undefined} keadaan={status.kesegaran} className="mt-1" /> : null}
            {lapor && sasaran ? <LaporkanKesalahan lapor={lapor} sasaran={sasaran} /> : null}
        </section>
```

`resources/js/components/trails/rinci/Kondisi.tsx`:

1. Ganti:

```tsx
import { LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
```

dengan:

```tsx
import { LabelSumber } from '@/components/trails/LabelSumber';
import { LaporkanKesalahan, type LaporKesalahan } from '@/components/trails/LaporkanKesalahan';
import { StatusAkses } from '@/components/trails/StatusAkses';
```

2. Ganti:

```tsx
 */

export function StatusResmiKartu({ status }: { status: StatusResmiRinci }) {
    return (
```

dengan:

```tsx
 */

export function StatusResmiKartu({ status, lapor, sasaran }: { status: StatusResmiRinci; lapor?: LaporKesalahan; sasaran?: string }) {
    return (
```

3. Ganti:

```tsx
            <p className="mt-2 text-xs text-muted">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
        </Card>
```

dengan:

```tsx
            <p className="mt-2 text-xs text-muted">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
            {lapor && sasaran ? <LaporkanKesalahan lapor={lapor} sasaran={sasaran} /> : null}
        </Card>
```

`resources/js/pages/Curator/Desk.tsx`:

1. Ganti:

```tsx
import { DaftarAntrean } from '@/components/kurator/DaftarAntrean';
import type { AntreanKurator, BadanKurator } from '@/components/kurator/types';
```

dengan:

```tsx
import { DaftarAntrean } from '@/components/kurator/DaftarAntrean';
import { DaftarLaporan } from '@/components/kurator/DaftarLaporan';
import type { AntreanKurator, BadanKurator } from '@/components/kurator/types';
```

2. Ganti:

```tsx
 * tinjau berkala (termasuk status lama yang kedaluwarsa dan kini terbaca Belum diketahui),
 * catatan yang dipersoalkan, dan draf sendiri. Hanya kawasan badan kurator yang tampil; server
 * memeriksa ulang setiap aksi.
```

dengan:

```tsx
 * tinjau berkala (termasuk status lama yang kedaluwarsa dan kini terbaca Belum diketahui),
 * laporan kesalahan data dari pendaki, catatan yang dipersoalkan, dan draf sendiri. Hanya kawasan badan kurator yang tampil; server
 * memeriksa ulang setiap aksi.
```

3. Ganti:

```tsx
                        }
                    />
                    <DaftarAntrean
                        id="antrean-dipersoalkan"
```

dengan:

```tsx
                        }
                    />
                    <DaftarLaporan baris={antrean.laporan} />
                    <DaftarAntrean
                        id="antrean-dipersoalkan"
```

`resources/js/pages/Mountains/Show.tsx`:

1. Ganti:

```tsx
import { VisualProfilGunung } from '@/components/trails/gunung/VisualProfilGunung';
import type { StatusRingkas } from '@/components/trails/types';
```

dengan:

```tsx
import { VisualProfilGunung } from '@/components/trails/gunung/VisualProfilGunung';
import type { LaporKesalahan } from '@/components/trails/LaporkanKesalahan';
import type { StatusRingkas } from '@/components/trails/types';
```

2. Ganti:

```tsx
    status: StatusRingkas;
    jalur: JalurGunung[];
```

dengan:

```tsx
    status: StatusRingkas;
    laporKesalahan: LaporKesalahan;
    jalur: JalurGunung[];
```

3. Ganti:

```tsx
export default function Show() {
    const { gunung, mengikuti, bolehDiikuti, status, jalur, jumlahMenunggu, profil, izin, kondisi, hariLaporan, provenans, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const [memproses, setMemproses] = useState(false);
```

dengan:

```tsx
export default function Show() {
    const { gunung, mengikuti, bolehDiikuti, status, laporKesalahan, jalur, jumlahMenunggu, profil, izin, kondisi, hariLaporan, provenans, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const [memproses, setMemproses] = useState(false);
```

4. Ganti:

```tsx
                    </h2>
                    <KartuStatusGunung status={status} />
                </section>
```

dengan:

```tsx
                    </h2>
                    <KartuStatusGunung status={status} lapor={laporKesalahan} sasaran={gunung.nama} />
                </section>
```

`resources/js/pages/Trails/Show.tsx`:

1. Ganti:

```tsx
import { useState, type ReactNode } from 'react';
import { ProfilElevasi } from '@/components/trails/ProfilElevasi';
```

dengan:

```tsx
import { useState, type ReactNode } from 'react';
import type { LaporKesalahan } from '@/components/trails/LaporkanKesalahan';
import { ProfilElevasi } from '@/components/trails/ProfilElevasi';
```

2. Ganti:

```tsx
    statusResmi: StatusResmiRinci;
    perizinan: Perizinan | null;
```

dengan:

```tsx
    statusResmi: StatusResmiRinci;
    laporKesalahan: LaporKesalahan;
    perizinan: Perizinan | null;
```

3. Ganti:

```tsx
        statusResmi,
        perizinan,
```

dengan:

```tsx
        statusResmi,
        laporKesalahan,
        perizinan,
```

4. Ganti:

```tsx
                            </h2>
                            <StatusResmiKartu status={statusResmi} />
                            {perizinan ? <PerizinanKartu izin={perizinan} /> : null}
```

dengan:

```tsx
                            </h2>
                            <StatusResmiKartu status={statusResmi} lapor={laporKesalahan} sasaran={trail.name} />
                            {perizinan ? <PerizinanKartu izin={perizinan} /> : null}
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Community\ConditionReportController;
use App\Http\Controllers\Community\HikeNumbersController;
```

dengan:

```php
use App\Http\Controllers\Community\ConditionReportController;
use App\Http\Controllers\Community\DataErrorReportController;
use App\Http\Controllers\Community\HikeNumbersController;
```

2. Ganti:

```php
use App\Http\Controllers\Curator\CuratorStatusController;
use App\Http\Controllers\Curator\EvidenceController;
```

dengan:

```php
use App\Http\Controllers\Curator\CuratorStatusController;
use App\Http\Controllers\Curator\DataErrorCheckController;
use App\Http\Controllers\Curator\EvidenceController;
```

3. Ganti:

```php
    Route::get('mountains/{mountain:slug}', MountainPageController::class)->name('mountains.show');

    Route::get('trips', [TripController::class, 'index'])->name('trips.index');
```

dengan:

```php
    Route::get('mountains/{mountain:slug}', MountainPageController::class)->name('mountains.show');
    // "Laporkan kesalahan data" (W8, ruling W8-R17): antrean cek ulang kurator, dibatasi laju per jam.
    Route::post('trails/{trail:slug}/laporkan-kesalahan', [DataErrorReportController::class, 'storeForTrail'])->middleware('throttle:'.config('hiking.rate_limits.data_error_reports_per_hour').',60,laporan-kesalahan')->name('trails.data-errors.store');
    Route::post('mountains/{mountain:slug}/laporkan-kesalahan', [DataErrorReportController::class, 'storeForMountain'])->middleware('throttle:'.config('hiking.rate_limits.data_error_reports_per_hour').',60,laporan-kesalahan')->name('mountains.data-errors.store');

    Route::get('trips', [TripController::class, 'index'])->name('trips.index');
```

4. Ganti:

```php
        Route::post('kurator/status/{status}/tinjau', [CuratorStatusController::class, 'review'])->whereNumber('status')->name('curator.statuses.review');
    });
```

dengan:

```php
        Route::post('kurator/status/{status}/tinjau', [CuratorStatusController::class, 'review'])->whereNumber('status')->name('curator.statuses.review');
        Route::post('kurator/laporan/{laporan}/dicek', DataErrorCheckController::class)->whereNumber('laporan')->name('curator.data-errors.check');
    });
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use App\Enums\CompletionState;
use App\Enums\EvidenceType;
```

dengan:

```php
use App\Enums\CompletionState;
use App\Enums\DataErrorCategory;
use App\Enums\DataErrorState;
use App\Enums\EvidenceType;
```

2. Ganti:

```php
use App\Models\Checkpoint;
use App\Models\HikingHistory;
```

dengan:

```php
use App\Models\Checkpoint;
use App\Models\DataErrorReport;
use App\Models\HikingHistory;
```

3. Ganti:

```php
     * Meja kurator (W8, spec v3 bagian 10.7): Citra, kurator balai Gunung Gede, dengan pembukaan
     * dari rekannya yang menunggu verifikasinya, status contoh yang lewat jadwal tinjau, dan satu
     * draf berbukti berita. Akun kurator hanya dibuat di test ini, tidak di data contoh (Q11).
     */
```

dengan:

```php
     * Meja kurator (W8, spec v3 bagian 10.7): Citra, kurator balai Gunung Gede, dengan pembukaan
     * dari rekannya yang menunggu verifikasinya, status contoh yang lewat jadwal tinjau, satu
     * laporan kesalahan data dari pendaki, dan satu draf berbukti berita. Akun kurator hanya
     * dibuat di test ini, tidak di data contoh (Q11).
     */
```

4. Ganti:

```php
        ]), $surat());

        return ['citra' => $citra, 'menunggu' => $menunggu, 'draf' => $draf, 'jalur' => $jalur];
```

dengan:

```php
        ]), $surat());

        $laporan = new DataErrorReport([
            'category' => DataErrorCategory::STATUS->value,
            'note' => 'DATA CONTOH. Akun resmi balai mengumumkan kuota harian jalur Cibodas dikurangi mulai pekan ini.',
        ]);
        $laporan->forceFill([
            'user_id' => User::factory()->create()->id,
            'mountain_id' => $jalur->mountain_id,
            'trail_id' => $jalur->id,
            'state' => DataErrorState::OPEN->value,
        ])->save();

        return ['citra' => $citra, 'menunggu' => $menunggu, 'draf' => $draf, 'jalur' => $jalur];
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `trails.data-errors.store` dan `curator.data-errors.check`; tsc tanpa
keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/DataErrorCategory.php app/Enums/DataErrorState.php app/Http/Controllers/Community/DataErrorReportController.php app/Http/Controllers/Curator/DataErrorCheckController.php app/Http/Requests/Community/StoreDataErrorReportRequest.php app/Http/Requests/Curator/CekLaporanKesalahanRequest.php app/Models/DataErrorReport.php app/Policies/DataErrorReportPolicy.php app/Services/DataResmi/MejaKurator.php app/Services/Mountains/HalamanGunung.php app/Services/Trails/TrailDetailPage.php config/hiking.php database/factories/DataErrorReportFactory.php database/migrations/2026_10_09_000300_create_data_error_reports_table.php routes/web.php tests/Feature/DataErrorReportTest.php tests/Pratinjau/HalamanPratinjauTest.php tests/Spatial/DataErrorReportSchemaTest.php`, lalu `php artisan test --compact --filter='DataErrorReport|RepoPolicyFiles|CuratorDesk'`
Expected: PASS. Pada simulasi rencana: 30 test, 27 lulus, 3 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.212 test, 2.210 lulus, 2 dilewati (sebelum tugas: 2.200).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 40 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 10: Status gunung api PVMBG

DK-43, spec v3 bagian 10.7 ("Level PVMBG diinput kurator dengan atribusi ... dan tautan laporan, tanpa scraping"), induk
W8 butir 7, titipan W4-R29 (kartu di Cek kesiapan); ruling W8-R18. Migrasi `2026_10_09_000400` membuat
`volcano_statuses` (gunung, level I sampai IV, rekomendasi, tautan laporan, waktu terbit, waktu verifikasi, pencatat)
dengan CHECK level dan RLS. Kurator mencatat level untuk gunung di kawasannya lewat "Catat status gunung api"
(`Curator/VolcanoForm`): waktu terbit tidak boleh di masa depan dan tautan harus `https`. `StatusGunungApi` mengambil
catatan terbit terakhir per gunung dalam satu kueri, dan `KartuGunungApi` menampilkannya di halaman gunung, rincian jalur,
dan Cek kesiapan, hanya bila ada, dengan atribusi "PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)", waktu terbit,
kesegaran (perlu ditinjau sesudah 168 jam, `freshness.volcano_review_hours`), dan kalimat bahwa level gunung api bukan
status akses. Level tidak pernah dibaca status akses, kecocokan, atau rekomendasi. Data contoh mendapat satu level
berpenanda DATA CONTOH untuk Gunung Gede.

**Files:**
- Create: `app/Enums/VolcanoLevel.php`, `app/Http/Controllers/Curator/VolcanoStatusController.php`, `app/Http/Requests/Curator/SimpanStatusGunungApiRequest.php`, `app/Models/VolcanoStatus.php`, `app/Policies/VolcanoStatusPolicy.php`, `app/Services/DataResmi/StatusGunungApi.php`, `database/factories/VolcanoStatusFactory.php`, `database/migrations/2026_10_09_000400_create_volcano_statuses_table.php`, `resources/js/components/kurator/DaftarGunungApi.tsx`, `resources/js/components/trails/KartuGunungApi.tsx`, `resources/js/pages/Curator/VolcanoForm.tsx`
- Modify: `app/Services/DataResmi/MejaKurator.php`, `app/Services/Mountains/HalamanGunung.php`, `app/Services/Trails/TrailDetailPage.php`, `app/Services/Trips/HalamanKesiapan.php`, `config/hiking.php`, `database/README.md`, `database/seeders/DemoSeeder.php`, `resources/js/components/kurator/types.ts`, `resources/js/components/trails/types.ts`, `resources/js/pages/Curator/Desk.tsx`, `resources/js/pages/Mountains/Show.tsx`, `resources/js/pages/Trails/Show.tsx`, `resources/js/pages/Trips/Readiness.tsx`, `routes/web.php`
- Test: `tests/Feature/DemoSeederTest.php`, `tests/Feature/VolcanoStatusTest.php` (baru), `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Spatial/VolcanoStatusSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `Yurisdiksi` (Tugas 3), `DataFreshnessService::keadaanDari()` (Tugas 7), `MejaKurator::props()` dan `Curator/Desk` (Tugas 8 dan 9), `HalamanGunung`, `TrailDetailPage`, `HalamanKesiapan` (W3 sampai W5), `AuditLogService::record()`, `Timezone`.
- Produces:
  - enum `VolcanoLevel` (int: `NORMAL` 1, `WASPADA` 2, `SIAGA` 3, `AWAS` 4; `label()`, `pilihan()` statis); model `VolcanoStatus` (isian massal hanya `level`, `recommendation`, `report_url`, `issued_at`), pabrik, dan `VolcanoStatusPolicy` (`viewAny(User)`, `create(User, Mountain)`);
  - `App\Services\DataResmi\StatusGunungApi`: `terkini(int $gunung): ?VolcanoStatus`, `terkiniUntuk(iterable $gunung): array<int, VolcanoStatus>`, `bentuk(?VolcanoStatus, string $zona): ?array`, `untukGunung(int, string): ?array`;
  - rute `curator.volcano.create` (GET `kurator/gunung-api/baru`) dan `curator.volcano.store` (POST `kurator/gunung-api`, berbatas laju `meja-kurator`); Form Request `Curator\SimpanStatusGunungApiRequest` (`gunung(): ?Mountain`, `diterbitkanUtc(Mountain): Carbon`);
  - config `hiking.freshness.volcano_review_hours` (168); prop `gunungApi` di halaman gunung, rincian jalur, dan Cek kesiapan; kunci `gunungApi` di props meja;
  - komponen `KartuGunungApi` (ekspor juga `BarisGunungApi` dan `KALIMAT_TERPISAH`), `DaftarGunungApi`, halaman `Curator/VolcanoForm`; tipe `GunungApi` dan `BarisGunungApi`; halaman pratinjau `formulir-gunung-api`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction VolcanoStatusTest`, lalu isinya diganti persis. Berkas lain, `tests/Spatial/VolcanoStatusSchemaTest.php`, dibuat langsung. `DemoSeederTest` diperluas ke rekomendasi level contoh.

Buat `tests/Feature/VolcanoStatusTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Enums\VolcanoLevel;
use App\Models\AuditLog;
use App\Models\Authority;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\VolcanoStatus;
use App\Services\OfficialStatusService;
use App\Services\PreparationService;
use App\Services\Trails\StatusJalurRingkas;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\MejaKurator;
use Tests\TestCase;

/**
 * Status gunung api PVMBG (DK-43; spec v3 bagian 6.3, 6.4, 6.11, 9.3, 10.7; rencana induk W8 butir 7;
 * ruling W8-R18): tabel sendiri per gunung, diisi kurator kawasan dengan atribusi dan tautan laporan,
 * tidak pernah dipetakan ke Buka atau Tutup, dan tampil hanya bila ada: di halaman gunung, rincian
 * jalur, dan Cek kesiapan (W4-R29).
 */
class VolcanoStatusTest extends TestCase
{
    use MejaKurator;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siapkanKawasan();
    }

    private function catat(VolcanoLevel $level, array $ganti = []): VolcanoStatus
    {
        return VolcanoStatus::factory()->for($this->gunung)->create(['level' => $level->value, ...$ganti]);
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isian(array $ganti = []): array
    {
        return [
            'gunung' => $this->gunung->id,
            'level' => (string) VolcanoLevel::WASPADA->value,
            'rekomendasi' => 'Masyarakat dan pendaki tidak beraktivitas dalam radius 3 km dari kawah.',
            'tautan_laporan' => 'https://magma.esdm.go.id/v1/gunung-api/laporan/12345',
            'diterbitkan' => '2026-10-08T06:00',
            ...$ganti,
        ];
    }

    public function test_the_mountain_page_shows_the_level_only_when_recorded(): void
    {
        $pendaki = User::factory()->create();
        Trail::factory()->published()->for($this->gunung)->create();

        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('gunungApi', null));

        $this->catat(VolcanoLevel::WASPADA, ['report_url' => 'https://magma.esdm.go.id/v1/gunung-api/laporan/77']);

        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gunungApi.level', 2)
                ->where('gunungApi.label', 'Level II (Waspada)')
                ->where('gunungApi.atribusi', 'PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)')
                ->where('gunungApi.tautan', 'https://magma.esdm.go.id/v1/gunung-api/laporan/77')
                ->where('gunungApi.kesegaran', 'Terkini'));
    }

    /**
     * Yang tampil adalah laporan yang terbit paling akhir, bukan baris yang dicatat paling akhir.
     */
    public function test_the_latest_issued_report_wins(): void
    {
        $this->catat(VolcanoLevel::SIAGA, ['issued_at' => now()->subHours(2)]);
        $this->catat(VolcanoLevel::WASPADA, ['issued_at' => now()->subDays(3)]);

        $this->actingAs(User::factory()->create())->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('gunungApi.label', 'Level III (Siaga)'));
    }

    /**
     * DK-43: level IV tidak pernah menjadi Tutup, dan level I tidak pernah menjadi Buka.
     */
    public function test_a_level_never_becomes_an_access_status(): void
    {
        $jalur = Trail::factory()->published()->for($this->gunung)->create();
        $this->catat(VolcanoLevel::AWAS);

        $layanan = app(OfficialStatusService::class);
        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->effectiveStatusForTrail($jalur->fresh()));
        $this->assertSame(OfficialStatusValue::UNKNOWN, $layanan->currentValueFor($this->gunung));
        $this->assertNull(app(StatusJalurRingkas::class)->untuk(Collection::make([$jalur->fresh()->load('mountain')]))[$jalur->id]['status']);
    }

    public function test_trail_detail_and_readiness_show_the_mountains_level(): void
    {
        $jalur = Trail::factory()->published()->for($this->gunung)->easy()->create();
        $this->catat(VolcanoLevel::WASPADA);
        $pendaki = User::factory()->create();
        $trip = TripPlan::create([
            'user_id' => $pendaki->id,
            'trail_id' => $jalur->id,
            'name' => 'Uji gunung api',
            'planned_date' => now()->addDays(5)->toDateString(),
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
        app(PreparationService::class)->generateFor($trip);

        $this->actingAs($pendaki)->get(route('trails.show', $jalur))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('gunungApi.label', 'Level II (Waspada)'));
        $this->actingAs($pendaki)->get(route('trips.readiness', $trip))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('kondisi.gunungApi.label', 'Level II (Waspada)'));
    }

    public function test_the_freshness_turns_to_review_after_a_week(): void
    {
        $this->catat(VolcanoLevel::NORMAL, ['verified_at' => now()]);
        $this->travel((int) config('hiking.freshness.volcano_review_hours') + 1)->hours();

        $this->actingAs(User::factory()->create())->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('gunungApi.kesegaran', 'Perlu ditinjau'));
    }

    /**
     * Pencatat dan waktu verifikasi dari sesi dan server; waktu terbit dibaca menurut jam gunung.
     */
    public function test_a_curator_records_a_level_in_the_area(): void
    {
        $this->travelTo('2026-10-08 03:00:00');
        $kurator = $this->kurator();

        $this->actingAs($kurator)
            ->post(route('curator.volcano.store'), $this->isian(['recorded_by' => 999, 'verified_at' => '2020-01-01 00:00:00', 'mountain_id' => 999]))
            ->assertRedirect(route('curator.desk'))
            ->assertSessionHas('status');

        $catatan = VolcanoStatus::sole();
        $this->assertSame($this->gunung->id, $catatan->mountain_id);
        $this->assertSame(VolcanoLevel::WASPADA, $catatan->level);
        $this->assertSame($kurator->id, $catatan->recorded_by);
        $this->assertSame('2026-10-08 03:00:00', $catatan->verified_at->utc()->toDateTimeString());
        $this->assertSame('2026-10-07 23:00:00', $catatan->issued_at->utc()->toDateTimeString(), '06.00 WIB adalah 23.00 UTC hari sebelumnya.');

        $jejak = AuditLog::where('action', 'volcano_status.recorded')->sole();
        $this->assertSame($kurator->id, $jejak->actor_id);
        $this->assertSame(VolcanoLevel::WASPADA->value, $jejak->after['level']);
    }

    public function test_outside_the_area_and_non_curators_are_refused(): void
    {
        $luar = $this->kurator(Authority::factory()->create());

        $this->actingAs($luar)->post(route('curator.volcano.store'), $this->isian())->assertSessionHasErrors('gunung');
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))->post(route('curator.volcano.store'), $this->isian())->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('curator.volcano.create'))->assertForbidden();
        $this->assertSame(0, VolcanoStatus::count());
    }

    public function test_a_future_issue_time_and_a_plain_http_link_are_refused(): void
    {
        $this->travelTo('2026-10-08 03:00:00');
        $kurator = $this->kurator();

        $this->actingAs($kurator)->post(route('curator.volcano.store'), $this->isian(['diterbitkan' => '2026-10-08T12:00']))->assertSessionHasErrors('diterbitkan');
        $this->actingAs($kurator)->post(route('curator.volcano.store'), $this->isian(['tautan_laporan' => 'http://magma.esdm.go.id/laporan']))->assertSessionHasErrors('tautan_laporan');
        $this->assertSame(0, VolcanoStatus::count());
    }

    public function test_the_desk_lists_the_latest_level_per_mountain(): void
    {
        $this->catat(VolcanoLevel::WASPADA);

        $this->actingAs($this->kurator()->fresh())->get(route('curator.desk'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('gunungApi', 1)
                ->where('gunungApi.0.gunung', 'Gunung Gede')
                ->where('gunungApi.0.label', 'Level II (Waspada)')
                ->where('gunungApi.0.perbarui', route('curator.volcano.create', ['gunung' => $this->gunung->id])));

        $this->actingAs($this->kurator())->get(route('curator.volcano.create', ['gunung' => $this->gunung->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Curator/VolcanoForm')
                ->where('gunungAwal', (string) $this->gunung->id)
                ->where('gunung.0.terkini', 'Level II (Waspada)')
                ->has('level', 4));
    }
}
```

Buat `tests/Spatial/VolcanoStatusSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\VolcanoLevel;
use App\Models\Mountain;
use App\Models\VolcanoStatus;
use App\Services\DataResmi\StatusGunungApi;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel status gunung api di PostgreSQL (R-024; DK-43; ruling W8-R18): level di rentang I sampai IV,
 * RLS menutup REST API, pencatatan ikut terhapus bersama gunungnya, pencatat menjadi kosong, dan
 * kueri "terbit paling akhir per gunung" (subkueri berkorelasi) memilih baris yang sama seperti di
 * SQLite.
 */
class VolcanoStatusSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'volcano_statuses'")->relrowsecurity, 'RLS lapis kedua harus menyala.');

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'volcano_statuses'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('volcano_statuses_level_sah', $cek);

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'volcano_statuses'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('c', $hapus['mountain_id']);
        $this->assertSame('n', $hapus['recorded_by']);
    }

    public function test_a_level_outside_one_to_four_is_refused(): void
    {
        $catatan = VolcanoStatus::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('volcano_statuses')->where('id', $catatan->id)->update(['level' => 5]);
    }

    public function test_the_latest_issued_report_per_mountain_matches_sqlite(): void
    {
        $gede = Mountain::factory()->create();
        $merapi = Mountain::factory()->create();
        VolcanoStatus::factory()->for($gede)->create(['level' => VolcanoLevel::SIAGA->value, 'issued_at' => now()->subHour()]);
        VolcanoStatus::factory()->for($gede)->create(['level' => VolcanoLevel::WASPADA->value, 'issued_at' => now()->subDays(2)]);
        VolcanoStatus::factory()->for($merapi)->create(['level' => VolcanoLevel::NORMAL->value, 'issued_at' => now()->subDays(5)]);

        $terkini = app(StatusGunungApi::class)->terkiniUntuk([$gede->id, $merapi->id]);

        $this->assertSame(VolcanoLevel::SIAGA, $terkini[$gede->id]->level);
        $this->assertSame(VolcanoLevel::NORMAL, $terkini[$merapi->id]->level);
    }
}
```

`tests/Feature/DemoSeederTest.php`:

1. Ganti:

```php
use App\Models\User;
use App\Services\ConsiderationService;
```

dengan:

```php
use App\Models\User;
use App\Models\VolcanoStatus;
use App\Services\ConsiderationService;
```

2. Ganti:

```php
            ...TrailConditionReport::pluck('note')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
        ];
```

dengan:

```php
            ...TrailConditionReport::pluck('note')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
            // W8: rekomendasi gunung api contoh (DK-43).
            ...VolcanoStatus::pluck('recommendation')->reject(fn (string $t): bool => str_starts_with($t, DemoSeeder::PENANDA.'.'))->all(),
        ];
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='VolcanoStatus|DemoSeeder|RepoPolicyFiles'`
Expected: FAIL. Pada simulasi rencana: 28 test, 15 lulus, 1 gagal, 9 error, 3 dilewati. Yang merah: kesembilan test `VolcanoStatusTest` (enum `VolcanoLevel`, rute
`curator.volcano.store`, dan prop `gunungApi` belum ada) dan `DemoSeederTest::test_every_text_that_reads_as_fact_is_marked`
(model `VolcanoStatus` belum ada). 3 test Spatial melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction VolcanoLevel`, `... make:controller --no-interaction Curator/VolcanoStatusController`, `... make:request --no-interaction Curator/SimpanStatusGunungApiRequest`, `... make:model --no-interaction VolcanoStatus`, `... make:policy --no-interaction VolcanoStatusPolicy`, `... make:class --no-interaction Services/DataResmi/StatusGunungApi`, `... make:factory --no-interaction VolcanoStatusFactory`, dan `... make:migration --no-interaction create_volcano_statuses_table` (ganti nama berkas migrasi hasilnya menjadi `2026_10_09_000400_create_volcano_statuses_table.php`), lalu isinya diganti persis. Berkas lain, `resources/js/components/kurator/DaftarGunungApi.tsx`, `resources/js/components/trails/KartuGunungApi.tsx`, dan `resources/js/pages/Curator/VolcanoForm.tsx`, dibuat langsung.

Buat `app/Enums/VolcanoLevel.php`:

```php
<?php

namespace App\Enums;

/**
 * Tingkat aktivitas gunung api menurut PVMBG (DK-43): Level I Normal, II Waspada, III Siaga,
 * IV Awas. Bukan status akses dan tidak pernah dipetakan ke Buka atau Tutup; yang menentukan
 * boleh tidaknya pendakian tetap status resmi pengelola.
 */
enum VolcanoLevel: int
{
    case NORMAL = 1;
    case WASPADA = 2;
    case SIAGA = 3;
    case AWAS = 4;

    /** Atribusi wajib setiap tampilan level (spec v3 bagian 10.7). */
    public const ATRIBUSI = 'PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)';

    /**
     * Nama level seperti di laporan PVMBG, dengan kurung; titik tengah hanya untuk baris ukuran
     * yang padat (DESIGN.md bagian Tipografi).
     */
    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Level I (Normal)',
            self::WASPADA => 'Level II (Waspada)',
            self::SIAGA => 'Level III (Siaga)',
            self::AWAS => 'Level IV (Awas)',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pilihan(): array
    {
        return array_map(fn (self $level) => ['value' => (string) $level->value, 'label' => $level->label()], self::cases());
    }
}
```

Buat `app/Http/Controllers/Curator/VolcanoStatusController.php`:

```php
<?php

namespace App\Http\Controllers\Curator;

use App\Enums\VolcanoLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Curator\SimpanStatusGunungApiRequest;
use App\Models\Mountain;
use App\Models\VolcanoStatus;
use App\Services\AuditLogService;
use App\Services\DataResmi\StatusGunungApi;
use App\Services\DataResmi\Yurisdiksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pencatatan status gunung api PVMBG di meja kurator (DK-43; spec v3 bagian 10.7; ruling W8-R18).
 * Kurator menyalin level, rekomendasi radius, dan tautan laporan dari MAGMA Indonesia, tanpa
 * scraping. Satu kurator cukup karena level bukan status akses; setiap pencatatan diaudit.
 */
class VolcanoStatusController extends Controller
{
    private const DIAUDIT = ['mountain_id', 'level', 'recommendation', 'report_url', 'issued_at', 'verified_at', 'recorded_by'];

    public function create(Request $request, Yurisdiksi $yurisdiksi, StatusGunungApi $gunungApi): Response
    {
        $this->authorize('viewAny', VolcanoStatus::class);

        $gunung = Mountain::query()->whereIn('id', $yurisdiksi->gunung($request->user())->all())->orderBy('name')->get(['id', 'name', 'timezone']);
        $terkini = $gunungApi->terkiniUntuk($gunung->pluck('id'));
        $awal = (string) $request->integer('gunung');

        return Inertia::render('Curator/VolcanoForm', [
            'title' => 'Catat status gunung api',
            'gunung' => $gunung->map(fn (Mountain $m) => [
                'value' => (string) $m->id,
                'label' => $m->name,
                'terkini' => isset($terkini[$m->id]) ? $terkini[$m->id]->level->label() : null,
            ])->values()->all(),
            'gunungAwal' => $gunung->contains('id', (int) $awal) ? $awal : null,
            'level' => VolcanoLevel::pilihan(),
            'atribusi' => VolcanoLevel::ATRIBUSI,
        ]);
    }

    public function store(SimpanStatusGunungApiRequest $request, AuditLogService $audit): RedirectResponse
    {
        $gunung = $request->gunung();
        $data = $request->validated();

        $catatan = new VolcanoStatus([
            'level' => (int) $data['level'],
            'recommendation' => $data['rekomendasi'],
            'report_url' => $data['tautan_laporan'],
            'issued_at' => $request->diterbitkanUtc($gunung),
        ]);
        $catatan->forceFill([
            'mountain_id' => $gunung->id,
            'recorded_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        DB::transaction(function () use ($catatan, $audit, $request): void {
            $catatan->save();
            $audit->record($request->user(), 'volcano_status.recorded', $catatan, null, $catatan->only(self::DIAUDIT));
        });

        return to_route('curator.desk')->with('status', 'Status gunung api '.$gunung->name.' dicatat: '.$catatan->level->label().'.');
    }
}
```

Buat `app/Http/Requests/Curator/SimpanStatusGunungApiRequest.php`:

```php
<?php

namespace App\Http\Requests\Curator;

use App\Enums\VolcanoLevel;
use App\Models\Mountain;
use App\Models\VolcanoStatus;
use App\Support\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Isian status gunung api PVMBG (DK-43; ruling W8-R18): gunung dari daftar kawasan kurator, level,
 * rekomendasi radius, tautan laporan PVMBG, dan waktu terbitnya menurut jam gunung. Pencatat dan
 * waktu verifikasi tidak pernah dari isian.
 */
class SimpanStatusGunungApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', VolcanoStatus::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gunung' => ['required', 'integer'],
            'level' => ['required', new Enum(VolcanoLevel::class)],
            'rekomendasi' => ['required', 'string', 'min:10', 'max:500'],
            'tautan_laporan' => ['required', 'url:https', 'max:500'],
            'diterbitkan' => ['required', 'date_format:Y-m-d\TH:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gunung.required' => 'Pilih gunung dari daftar.',
            'level.required' => 'Pilih level yang tertulis di laporan PVMBG.',
            'rekomendasi.required' => 'Salin rekomendasi PVMBG, termasuk radius yang disebutkan.',
            'tautan_laporan.required' => 'Tempel tautan laporan PVMBG yang Anda salin.',
            'tautan_laporan.url' => 'Tautan laporan harus alamat https.',
            'diterbitkan.required' => 'Tulis waktu laporan itu diterbitkan PVMBG.',
        ];
    }

    /**
     * Gunung harus ada dan berada di kawasan badan pengguna; waktu terbit tidak di masa depan menurut
     * jam gunungnya.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $gunung = $this->gunung();

            if ($gunung === null || ! $this->user()->can('create', [VolcanoStatus::class, $gunung])) {
                $validator->errors()->add('gunung', 'Gunung itu di luar kawasan badan Anda. Pilih dari daftar.');

                return;
            }

            if ($this->diterbitkanUtc($gunung)->isFuture()) {
                $validator->errors()->add('diterbitkan', 'Waktu terbit tidak boleh di masa depan.');
            }
        }];
    }

    public function gunung(): ?Mountain
    {
        return Mountain::find((int) $this->input('gunung'));
    }

    public function diterbitkanUtc(Mountain $gunung): Carbon
    {
        $zona = Timezone::isSupported($gunung->timezone) ? $gunung->timezone : Timezone::DEFAULT;

        return Carbon::parse((string) $this->input('diterbitkan'), $zona)->utc();
    }
}
```

Buat `app/Models/VolcanoStatus.php`:

```php
<?php

namespace App\Models;

use App\Enums\VolcanoLevel;
use Database\Factories\VolcanoStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pencatatan level gunung api PVMBG (DK-43; ruling W8-R18). Gunung, pencatat, dan waktu
 * verifikasi ditulis server lewat forceFill; isian hanya level, rekomendasi, tautan laporan, dan
 * waktu terbitnya.
 */
#[Fillable(['level', 'recommendation', 'report_url', 'issued_at'])]
class VolcanoStatus extends Model
{
    /** @use HasFactory<VolcanoStatusFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => VolcanoLevel::class,
            'issued_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
```

Buat `app/Policies/VolcanoStatusPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Mountain;
use App\Models\User;
use App\Services\DataResmi\Yurisdiksi;

/**
 * Status gunung api PVMBG (DK-43; ruling W8-R18): dicatat kurator aktif, hanya untuk gunung di
 * kawasan badannya. Admin dan moderator bukan kurator.
 */
class VolcanoStatusPolicy
{
    public function __construct(private readonly Yurisdiksi $yurisdiksi) {}

    public function viewAny(User $user): bool
    {
        return $this->yurisdiksi->kuratorAktif($user);
    }

    public function create(User $user, Mountain $gunung): bool
    {
        return $this->yurisdiksi->berwenang($user, $gunung);
    }
}
```

Buat `app/Services/DataResmi/StatusGunungApi.php`:

```php
<?php

namespace App\Services\DataResmi;

use App\Enums\VolcanoLevel;
use App\Models\VolcanoStatus;
use App\Services\DataFreshnessService;
use App\Support\Timezone;

/**
 * Status gunung api PVMBG untuk layar pendaki dan meja kurator (DK-43; rencana induk W8 butir 7;
 * ruling W8-R18). Yang tampil adalah pencatatan yang terbit paling akhir per gunung, selalu dengan
 * atribusi PVMBG dan tautan laporannya, dan hanya bila ada. Tidak pernah dibaca oleh status akses,
 * kecocokan, atau rekomendasi: level gunung api bukan Buka atau Tutup.
 */
final class StatusGunungApi
{
    public function __construct(private readonly DataFreshnessService $kesegaran) {}

    public function terkini(int $gunung): ?VolcanoStatus
    {
        return $this->terkiniUntuk([$gunung])[$gunung] ?? null;
    }

    /**
     * Pencatatan terakhir per gunung untuk sekumpulan gunung, satu kueri.
     *
     * @param  iterable<int, int>  $gunung
     * @return array<int, VolcanoStatus> berkunci id gunung
     */
    public function terkiniUntuk(iterable $gunung): array
    {
        $id = collect($gunung)->filter()->unique()->values()->all();

        if ($id === []) {
            return [];
        }

        return VolcanoStatus::query()
            ->whereIn('mountain_id', $id)
            ->where('issued_at', fn ($q) => $q->selectRaw('max(v.issued_at)')->from('volcano_statuses as v')->whereColumn('v.mountain_id', 'volcano_statuses.mountain_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('mountain_id')
            ->keyBy('mountain_id')
            ->all();
    }

    /**
     * Bentuk untuk halaman, atau null bila gunung itu belum punya catatan (tampil hanya bila ada).
     *
     * @return array{level: int, label: string, rekomendasi: string, atribusi: string, tautan: string, diterbitkan: string, diverifikasi: string, kesegaran: string}|null
     */
    public function bentuk(?VolcanoStatus $s, string $zona): ?array
    {
        if ($s === null) {
            return null;
        }

        $jam = (int) config('hiking.freshness.volcano_review_hours', 168);

        return [
            'level' => $s->level->value,
            'label' => $s->level->label(),
            'rekomendasi' => $s->recommendation,
            'atribusi' => VolcanoLevel::ATRIBUSI,
            'tautan' => $s->report_url,
            'diterbitkan' => (string) Timezone::display($s->issued_at, $zona, 'j M Y H.i'),
            'diverifikasi' => (string) Timezone::display($s->verified_at, $zona, 'j M Y H.i'),
            'kesegaran' => $this->kesegaran->keadaanDari($s->verified_at, null, null, $s->verified_at->copy()->addHours($jam)),
        ];
    }

    /**
     * Satu gunung, langsung dalam bentuk halaman.
     *
     * @return array{level: int, label: string, rekomendasi: string, atribusi: string, tautan: string, diterbitkan: string, diverifikasi: string, kesegaran: string}|null
     */
    public function untukGunung(int $gunung, string $zona): ?array
    {
        return $this->bentuk($this->terkini($gunung), $zona);
    }
}
```

Buat `database/factories/VolcanoStatusFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\VolcanoLevel;
use App\Models\Mountain;
use App\Models\VolcanoStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolcanoStatus>
 */
class VolcanoStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mountain_id' => Mountain::factory(),
            'level' => VolcanoLevel::NORMAL->value,
            'recommendation' => 'Masyarakat dan pendaki tidak mendekati kawah dalam radius 1 km.',
            'report_url' => 'https://magma.esdm.go.id/v1/gunung-api/laporan',
            'issued_at' => now()->subHours(6),
            'verified_at' => now()->subHour(),
        ];
    }
}
```

Buat `database/migrations/2026_10_09_000400_create_volcano_statuses_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status gunung api PVMBG per gunung (DK-43; spec v3 bagian 9.3 dan 10.7; rencana induk W8 butir 7;
 * ruling W8-R18): level I sampai IV, rekomendasi radius, tautan laporan sumber, waktu terbit, dan
 * waktu verifikasi kurator. Disimpan terpisah dari status akses dan tidak pernah dipetakan ke Buka
 * atau Tutup.
 *
 * Riwayat dipertahankan: setiap pencatatan level adalah baris baru, dan yang tampil adalah yang
 * terbit paling akhir. Pencatat menjadi kosong bila akunnya hilang. Di PostgreSQL CHECK menjaga
 * level di rentang I sampai IV, dan RLS menutup REST API (lapis kedua 2026_09_20_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volcano_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mountain_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->text('recommendation');
            $table->string('report_url', 500);
            $table->timestamp('issued_at');
            $table->timestamp('verified_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mountain_id', 'issued_at'], 'volcano_statuses_terbaru');
            $table->index('recorded_by');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE volcano_statuses ADD CONSTRAINT volcano_statuses_level_sah CHECK (level BETWEEN 1 AND 4)');
            DB::statement('ALTER TABLE public."volcano_statuses" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('volcano_statuses');
    }
};
```

Buat `resources/js/components/kurator/DaftarGunungApi.tsx`:

```tsx
import { Kesegaran } from '@/components/trails/Kesegaran';
import { Button } from '@/components/ui/Button';
import { create } from '@/routes/curator/volcano';
import type { BarisGunungApi } from './types';

/**
 * Status gunung api PVMBG di kawasan kurator (DK-43; ruling W8-R18): level terakhir per gunung
 * yang sudah punya catatan, kapan PVMBG menerbitkannya, dan kesegaran pencocokannya. Level dicatat
 * terpisah dari status akses dan tidak pernah dipetakan ke Buka atau Tutup.
 */
export function DaftarGunungApi({ baris }: { baris: BarisGunungApi[] }) {
    return (
        <section aria-labelledby="antrean-gunung-api" className="border-t border-subtle pt-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="antrean-gunung-api" className="text-bagian text-primary">
                        Status gunung api (PVMBG) <span className="text-secondary tabular-nums">({baris.length})</span>
                    </h2>
                    <p className="mt-1 max-w-prose text-kecil text-secondary">
                        Salin level, rekomendasi radius, dan tautan laporan dari MAGMA Indonesia. Level tampil di halaman gunung, rincian jalur, dan Cek kesiapan, terpisah dari status akses.
                    </p>
                </div>
                <Button variant="secondary" size="sm" href={create.url()} className="shrink-0">
                    Catat status gunung api
                </Button>
            </div>
            {baris.length === 0 ? (
                <p className="mt-3 text-kecil text-muted">Belum ada status gunung api yang dicatat di kawasan Anda.</p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle rounded-kartu border border-subtle bg-surface">
                    {baris.map((g) => (
                        <li key={g.id} className="flex flex-col gap-2 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                                <p className="font-bold text-primary">{g.gunung}</p>
                                <p className="mt-1 text-kecil text-primary">{g.label}</p>
                                <p className="mt-1 text-meta text-muted">Diterbitkan PVMBG {g.diterbitkan}.</p>
                                <Kesegaran waktu={g.diverifikasi} awalan="Dicocokkan" keadaan={g.kesegaran} className="mt-1" />
                            </div>
                            <div className="flex shrink-0 flex-wrap gap-2">
                                <Button variant="secondary" size="sm" href={g.perbarui} aria-label={`Catat level terbaru ${g.gunung}`}>
                                    Catat level terbaru
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
```

Buat `resources/js/components/trails/KartuGunungApi.tsx`:

```tsx
import { Kesegaran, nadaKesegaran } from '@/components/trails/Kesegaran';
import { LabelSumber } from '@/components/trails/LabelSumber';
import type { GunungApi } from '@/components/trails/types';
import { Icon } from '@/components/ui/Icon';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Lencana } from '@/components/ui/Lencana';
import { kelas } from '@/lib/kelas';

/** Kalimat tetap DK-43: level gunung api bukan status akses. */
export const KALIMAT_TERPISAH = 'Disimpan terpisah dari status akses, dan tidak pernah diterjemahkan menjadi Buka atau Tutup.';

function TautanLaporan({ href }: { href: string }) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className={kelas('inline-flex min-h-11 items-center gap-1 rounded-control text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat)}
        >
            Buka laporan PVMBG
            <Icon name="arrow-square-out" size={16} />
            <span className="sr-only"> (membuka tab baru)</span>
        </a>
    );
}

/**
 * Status gunung api PVMBG (DK-43; spec v3 bagian 6.3 dan 6.4, prototype 03 dan 04; ruling W8-R18):
 * level dan rekomendasi radius, atribusi "PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)", waktu
 * terbit, kesegaran, dan tautan laporannya. Tampil hanya bila ada; kalimat tetapnya menyatakan
 * bahwa level tidak pernah menjadi Buka atau Tutup.
 */
export function KartuGunungApi({ data }: { data: GunungApi }) {
    return (
        <section aria-labelledby="judul-gunung-api" className="rounded-lg border-[1.5px] border-brand-ink bg-surface p-5">
            <div className="flex flex-wrap items-center gap-2">
                <LabelSumber jenis="OFFICIAL" />
                <h3 id="judul-gunung-api" className="text-meta font-bold text-secondary">
                    Status gunung api (PVMBG)
                </h3>
            </div>
            <p className="mt-2 text-bagian text-primary">{data.label}</p>
            <p className="mt-1 max-w-prose break-words text-kecil text-primary">{data.rekomendasi}</p>
            <p className="mt-2 text-kecil text-secondary">{KALIMAT_TERPISAH}</p>
            <p className="mt-2 break-words text-meta text-muted">
                Sumber: {data.atribusi}. Diterbitkan {data.diterbitkan}.
            </p>
            <Kesegaran waktu={data.diverifikasi} awalan="Dicocokkan kurator" keadaan={data.kesegaran} className="mt-1" />
            <TautanLaporan href={data.tautan} />
        </section>
    );
}

/** Baris ringkas untuk "Kondisi terkini" Cek kesiapan (prototype 10; W4-R29). */
export function BarisGunungApi({ data }: { data: GunungApi }) {
    return (
        <div className="border-t border-subtle pt-3">
            <p className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-meta text-secondary">Gunung api</span>
                <Lencana nada={nadaKesegaran(data.kesegaran)}>{data.kesegaran}</Lencana>
            </p>
            <p className="mt-1 text-kecil font-bold text-primary">{data.label}</p>
            <p className="mt-1 break-words text-kecil text-secondary">{data.rekomendasi}</p>
            <p className="mt-1 text-meta text-secondary">{KALIMAT_TERPISAH}</p>
            <p className="mt-1 break-words text-meta text-muted">
                Sumber: {data.atribusi}, diterbitkan {data.diterbitkan}; dicocokkan kurator {data.diverifikasi}.
            </p>
            <TautanLaporan href={data.tautan} />
        </div>
    );
}
```

Buat `resources/js/pages/Curator/VolcanoForm.tsx`:

```tsx
import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { SelectField, TextareaField } from '@/components/discovery/Isian';
import { KALIMAT_TERPISAH } from '@/components/trails/KartuGunungApi';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { desk } from '@/routes/curator';
import { store } from '@/routes/curator/volcano';
import type { PropsBersama } from '@/types';

interface PilihanGunung {
    value: string;
    label: string;
    terkini: string | null;
}

interface Props extends PropsBersama {
    title: string;
    gunung: PilihanGunung[];
    gunungAwal: string | null;
    level: Array<{ value: string; label: string }>;
    atribusi: string;
}

/**
 * Pencatatan status gunung api PVMBG (DK-43; spec v3 bagian 10.7; ruling W8-R18). Kurator menyalin
 * dari laporan MAGMA Indonesia; gunung hanya dari kawasan badannya, dan server memeriksanya ulang.
 * Pencatat dan waktu verifikasi tidak dikirim dari sini.
 */
export default function VolcanoForm() {
    const { title, gunung, gunungAwal, level, atribusi } = usePage<Props>().props;
    const form = useForm({ gunung: gunungAwal ?? '', level: '', rekomendasi: '', tautan_laporan: '', diterbitkan: '' });
    const terkini = gunung.find((g) => g.value === form.data.gunung)?.terkini ?? null;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), { preserveScroll: true });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: desk.url(), label: 'Meja kurator' }} title={title} description={`Salin dari laporan ${atribusi}. ${KALIMAT_TERPISAH}`} />

                {gunung.length === 0 ? (
                    <EmptyState
                        jenis="menunggu"
                        title="Belum ada gunung di kawasan Anda"
                        menunggu="Menunggu admin Memount mengaitkan gunung ke badan Anda."
                        description="Status gunung api hanya dicatat untuk gunung di kawasan badan tempat Anda menjadi anggota."
                    />
                ) : (
                    <form onSubmit={kirim} className="space-y-4" noValidate>
                        <SelectField
                            name="gunung"
                            label="Gunung"
                            required
                            placeholder="Pilih gunung"
                            options={gunung.map((g) => ({ value: g.value, label: g.label }))}
                            hint={terkini ? `Level tercatat sekarang: ${terkini}.` : undefined}
                            value={form.data.gunung}
                            onChange={(e) => form.setData('gunung', e.target.value)}
                            error={form.errors.gunung}
                        />
                        <SelectField
                            name="level"
                            label="Level menurut PVMBG"
                            required
                            placeholder="Pilih level"
                            options={level}
                            value={form.data.level}
                            onChange={(e) => form.setData('level', e.target.value)}
                            error={form.errors.level}
                        />
                        <TextareaField
                            name="rekomendasi"
                            label="Rekomendasi PVMBG, termasuk radiusnya"
                            required
                            rows={3}
                            value={form.data.rekomendasi}
                            onChange={(e) => form.setData('rekomendasi', e.target.value)}
                            error={form.errors.rekomendasi}
                        />
                        <FormField
                            name="tautan_laporan"
                            label="Tautan laporan PVMBG"
                            type="url"
                            required
                            hint="Alamat https laporan yang Anda salin."
                            value={form.data.tautan_laporan}
                            onChange={(e) => form.setData('tautan_laporan', e.target.value)}
                            error={form.errors.tautan_laporan}
                        />
                        <FormField
                            name="diterbitkan"
                            label="Waktu laporan diterbitkan"
                            type="datetime-local"
                            required
                            hint="Jam gunung, seperti tertulis di laporan."
                            value={form.data.diterbitkan}
                            onChange={(e) => form.setData('diterbitkan', e.target.value)}
                            error={form.errors.diterbitkan}
                        />
                        <div className="flex flex-wrap gap-3 pt-2">
                            <Button type="submit" processing={form.processing}>
                                Catat status gunung api
                            </Button>
                            <Button variant="secondary" href={desk.url()}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}

VolcanoForm.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`app/Services/DataResmi/MejaKurator.php`:

1. Ganti:

```php
use App\Models\User;
use App\Services\DataFreshnessService;
```

dengan:

```php
use App\Models\User;
use App\Models\VolcanoStatus;
use App\Services\DataFreshnessService;
```

2. Ganti:

```php
        private readonly DataFreshnessService $kesegaran,
    ) {}
```

dengan:

```php
        private readonly DataFreshnessService $kesegaran,
        private readonly StatusGunungApi $gunungApi,
    ) {}
```

3. Ganti:

```php
            ])->values()->all(),
            'antrean' => [
```

dengan:

```php
            ])->values()->all(),
            'gunungApi' => $this->gunungApi($kurator),
            'antrean' => [
```

4. Ganti:

```php
    /**
     * Satu laporan kesalahan data. Identitas pelapor tidak dikirim ke kurator (minimisasi data,
```

dengan:

```php
    /**
     * Level gunung api terakhir per gunung di kawasan kurator (DK-43; ruling W8-R18), hanya gunung
     * yang sudah punya catatan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function gunungApi(User $kurator): array
    {
        $terkini = EloquentCollection::make(array_values($this->gunungApi->terkiniUntuk($this->yurisdiksi->gunung($kurator))))
            ->load('mountain:id,name,timezone')
            ->sortBy(fn (VolcanoStatus $s) => $s->mountain->name);

        return $terkini->map(fn (VolcanoStatus $s) => [
            'id' => $s->id,
            'gunung' => $s->mountain->name,
            ...$this->gunungApi->bentuk($s, Timezone::isSupported($s->mountain->timezone) ? $s->mountain->timezone : Timezone::DEFAULT),
            'perbarui' => route('curator.volcano.create', ['gunung' => $s->mountain_id]),
        ])->values()->all();
    }

    /**
     * Satu laporan kesalahan data. Identitas pelapor tidak dikirim ke kurator (minimisasi data,
```

`app/Services/Mountains/HalamanGunung.php`:

1. Ganti:

```php
use App\Services\ConditionAggregatorService;
use App\Services\OfficialStatusService;
```

dengan:

```php
use App\Services\ConditionAggregatorService;
use App\Services\DataResmi\StatusGunungApi;
use App\Services\OfficialStatusService;
```

2. Ganti:

```php
        private readonly ConditionAggregatorService $kondisi,
    ) {}
```

dengan:

```php
        private readonly ConditionAggregatorService $kondisi,
        private readonly StatusGunungApi $gunungApi,
    ) {}
```

3. Ganti:

```php
            'laporKesalahan' => ['url' => route('mountains.data-errors.store', $gunung), 'kategori' => DataErrorCategory::pilihan()],
            'jalur' => $jalur->map(fn (Trail $t) => [
```

dengan:

```php
            'laporKesalahan' => ['url' => route('mountains.data-errors.store', $gunung), 'kategori' => DataErrorCategory::pilihan()],
            // Level PVMBG terpisah dari status akses dan hanya bila ada (DK-43, ruling W8-R18).
            'gunungApi' => $this->gunungApi->untukGunung($gunung->id, $zona),
            'jalur' => $jalur->map(fn (Trail $t) => [
```

`app/Services/Trails/TrailDetailPage.php`:

1. Ganti:

```php
use App\Services\DataFreshnessService;
use App\Services\PermitService;
```

dengan:

```php
use App\Services\DataFreshnessService;
use App\Services\DataResmi\StatusGunungApi;
use App\Services\PermitService;
```

2. Ganti:

```php
        private readonly DataFreshnessService $kesegaran,
    ) {}
```

dengan:

```php
        private readonly DataFreshnessService $kesegaran,
        private readonly StatusGunungApi $gunungApi,
    ) {}
```

3. Ganti:

```php
            'laporKesalahan' => ['url' => route('trails.data-errors.store', $trail), 'kategori' => DataErrorCategory::pilihan()],
            'perizinan' => $this->perizinan($izin, $zona),
```

dengan:

```php
            'laporKesalahan' => ['url' => route('trails.data-errors.store', $trail), 'kategori' => DataErrorCategory::pilihan()],
            // Level PVMBG gunungnya, terpisah dari status akses dan hanya bila ada (DK-43, ruling W8-R18).
            'gunungApi' => $this->gunungApi->untukGunung($trail->mountain_id, $zona),
            'perizinan' => $this->perizinan($izin, $zona),
```

`app/Services/Trips/HalamanKesiapan.php`:

1. Ganti:

```php
use App\Services\ConditionAggregatorService;
use App\Services\PermitService;
```

dengan:

```php
use App\Services\ConditionAggregatorService;
use App\Services\DataResmi\StatusGunungApi;
use App\Services\PermitService;
```

2. Ganti:

```php
        private readonly ConditionAggregatorService $kondisi,
    ) {}
```

dengan:

```php
        private readonly ConditionAggregatorService $kondisi,
        private readonly StatusGunungApi $gunungApi,
    ) {}
```

3. Ganti:

```php
                'status' => $this->statusJalur->untuk(EloquentCollection::make([$trip->trail]))[$trip->trail->id],
                'prakiraan' => $prakiraan,
```

dengan:

```php
                'status' => $this->statusJalur->untuk(EloquentCollection::make([$trip->trail]))[$trip->trail->id],
                // Kartu gunung api prototype 10 (W4-R29), dari tabel DK-43 dan hanya bila ada.
                'gunungApi' => $this->gunungApi->untukGunung($trip->trail->mountain_id, $zona),
                'prakiraan' => $prakiraan,
```

`config/hiking.php`:

1. Ganti:

```php
        'status_expiry_warning_days' => 14,
    ],
```

dengan:

```php
        'status_expiry_warning_days' => 14,

        // Level gunung api PVMBG (DK-43; ruling W8-R18): tujuh hari sesudah kurator mencocokkannya
        // dengan laporan PVMBG, sama dengan status pembatasan. PVMBG menerbitkan laporan aktivitas
        // setiap hari, jadi level yang tidak dicocokkan ulang seminggu disebut perlu ditinjau.
        'volcano_review_hours' => 168,
    ],
```

`database/README.md`:

1. Ganti:

```markdown
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain`, `authority_members` | Hierarki otoritas (R-009); anggota meja kurator per badan resmi, dengan masa berlaku dan pencabutan (W8) |
| Kondisi dan kepercayaan data | `official_statuses`, `weather_snapshots`, `trail_condition_reports`, `report_thanks`, `permit_requirements`, `mountain_follows`, `data_error_reports` | Laporan komunitas tidak menimpa status resmi (R-010); cuaca bersumber dan berkesegaran (R-013); laporan kesalahan data pendaki menjadi tugas cek ulang kurator, tidak pernah mengubah status (W8) |
| Route Fit dan rekomendasi | `recommendation_rules`, `recommendation_runs`, `recommendation_results` | Skor internal tidak pernah keluar dari server (BR-09) |
```

dengan:

```markdown
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain`, `authority_members` | Hierarki otoritas (R-009); anggota meja kurator per badan resmi, dengan masa berlaku dan pencabutan (W8) |
| Kondisi dan kepercayaan data | `official_statuses`, `weather_snapshots`, `trail_condition_reports`, `report_thanks`, `permit_requirements`, `mountain_follows`, `data_error_reports`, `volcano_statuses` | Laporan komunitas tidak menimpa status resmi (R-010); cuaca bersumber dan berkesegaran (R-013); laporan kesalahan data pendaki menjadi tugas cek ulang kurator, tidak pernah mengubah status (W8); level gunung api PVMBG disimpan terpisah dan tidak pernah dipetakan ke Buka atau Tutup (DK-43, W8) |
| Route Fit dan rekomendasi | `recommendation_rules`, `recommendation_runs`, `recommendation_results` | Skor internal tidak pernah keluar dari server (BR-09) |
```

`database/seeders/DemoSeeder.php`:

1. Ganti:

```php
use App\Enums\VerificationStatus;
use App\Enums\WaterAvailability;
```

dengan:

```php
use App\Enums\VerificationStatus;
use App\Enums\VolcanoLevel;
use App\Enums\WaterAvailability;
```

2. Ganti:

```php
use App\Models\User;
use App\Services\Trips\TripReminderScheduler;
```

dengan:

```php
use App\Models\User;
use App\Models\VolcanoStatus;
use App\Services\Trips\TripReminderScheduler;
```

3. Ganti:

```php
        $this->statusGunung($sumber, $jalur);
        $this->laporan($jalur);
```

dengan:

```php
        $this->statusGunung($sumber, $jalur);
        $this->gunungApi($jalur);
        $this->laporan($jalur);
```

4. Ganti:

```php
    /**
     * Satu laporan kondisi yang lolos moderasi di Gede via Cibodas, dari pendaki contoh kedua
```

dengan:

```php
    /**
     * Satu level gunung api PVMBG untuk Gunung Gede (DK-43, W8), supaya kartu gunung api di
     * halaman gunung, rincian jalur, dan Cek kesiapan punya isi. Rekomendasinya berpenanda DATA
     * CONTOH; tautannya ke beranda MAGMA Indonesia, bukan laporan tertentu.
     *
     * @param  array<string, Trail>  $jalur
     */
    private function gunungApi(array $jalur): void
    {
        $catatan = new VolcanoStatus([
            'level' => VolcanoLevel::NORMAL->value,
            'recommendation' => self::PENANDA.'. Pendaki dan wisatawan tidak mendekati kawah aktif dalam radius 1 km.',
            'report_url' => 'https://magma.esdm.go.id/',
            'issued_at' => now()->subHours(5),
        ]);
        $catatan->forceFill(['mountain_id' => $jalur['gede-via-cibodas']->mountain_id, 'verified_at' => now()->subHours(2)])->save();
    }

    /**
     * Satu laporan kondisi yang lolos moderasi di Gede via Cibodas, dari pendaki contoh kedua
```

`resources/js/components/kurator/types.ts`:

1. Ganti:

```ts
import type { StatusResmi } from '@/components/trails/StatusAkses';

/** Satu baris antrean meja kurator (MejaKurator::baris). */
```

dengan:

```ts
import type { StatusResmi } from '@/components/trails/StatusAkses';
import type { GunungApi } from '@/components/trails/types';

/** Satu baris antrean meja kurator (MejaKurator::baris). */
```

2. Ganti:

```ts
    sasaranKunci: string | null;
    url: string;
}

export interface BadanKurator {
```

dengan:

```ts
    sasaranKunci: string | null;
    url: string;
}

/** Level gunung api terakhir per gunung di kawasan kurator (MejaKurator::gunungApi). */
export interface BarisGunungApi extends GunungApi {
    id: number;
    gunung: string;
    perbarui: string;
}

export interface BadanKurator {
```

`resources/js/components/trails/types.ts`:

1. Ganti:

```ts
    kesegaran: string | null;
}
```

dengan:

```ts
    kesegaran: string | null;
}

/**
 * Status gunung api PVMBG (StatusGunungApi::bentuk, DK-43): level, rekomendasi, atribusi, tautan
 * laporan, waktu terbit dan verifikasi dalam zona gunung, dan kesegarannya. Bukan status akses.
 */
export interface GunungApi {
    level: number;
    label: string;
    rekomendasi: string;
    atribusi: string;
    tautan: string;
    diterbitkan: string;
    diverifikasi: string;
    kesegaran: string;
}
```

`resources/js/pages/Curator/Desk.tsx`:

1. Ganti:

```tsx
import { DaftarAntrean } from '@/components/kurator/DaftarAntrean';
import { DaftarLaporan } from '@/components/kurator/DaftarLaporan';
import type { AntreanKurator, BadanKurator } from '@/components/kurator/types';
import { Alert } from '@/components/ui/Alert';
```

dengan:

```tsx
import { DaftarAntrean } from '@/components/kurator/DaftarAntrean';
import { DaftarGunungApi } from '@/components/kurator/DaftarGunungApi';
import { DaftarLaporan } from '@/components/kurator/DaftarLaporan';
import type { AntreanKurator, BadanKurator, BarisGunungApi } from '@/components/kurator/types';
import { Alert } from '@/components/ui/Alert';
```

2. Ganti:

```tsx
    badan: BadanKurator[];
    antrean: AntreanKurator;
```

dengan:

```tsx
    badan: BadanKurator[];
    gunungApi: BarisGunungApi[];
    antrean: AntreanKurator;
```

3. Ganti:

```tsx
export default function Desk() {
    const { flash, badan, antrean } = usePage<Props>().props;

    return (
```

dengan:

```tsx
export default function Desk() {
    const { flash, badan, gunungApi, antrean } = usePage<Props>().props;

    return (
```

4. Ganti:

```tsx
                    <DaftarAntrean id="antrean-draf" judul="Draf Anda" keterangan="Belum terbaca siapa pun selain Anda." baris={antrean.draf} kosong="Belum ada draf." />
                </div>
```

dengan:

```tsx
                    <DaftarAntrean id="antrean-draf" judul="Draf Anda" keterangan="Belum terbaca siapa pun selain Anda." baris={antrean.draf} kosong="Belum ada draf." />
                    <DaftarGunungApi baris={gunungApi} />
                </div>
```

`resources/js/pages/Mountains/Show.tsx`:

1. Ganti:

```tsx
import { KartuStatusGunung } from '@/components/trails/gunung/KartuStatusGunung';
import { KondisiPendaki } from '@/components/trails/gunung/KondisiPendaki';
```

dengan:

```tsx
import { KartuStatusGunung } from '@/components/trails/gunung/KartuStatusGunung';
import { KartuGunungApi } from '@/components/trails/KartuGunungApi';
import { KondisiPendaki } from '@/components/trails/gunung/KondisiPendaki';
```

2. Ganti:

```tsx
import type { LaporKesalahan } from '@/components/trails/LaporkanKesalahan';
import type { StatusRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
```

dengan:

```tsx
import type { LaporKesalahan } from '@/components/trails/LaporkanKesalahan';
import type { GunungApi, StatusRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
```

3. Ganti:

```tsx
    laporKesalahan: LaporKesalahan;
    jalur: JalurGunung[];
```

dengan:

```tsx
    laporKesalahan: LaporKesalahan;
    gunungApi: GunungApi | null;
    jalur: JalurGunung[];
```

4. Ganti:

```tsx
export default function Show() {
    const { gunung, mengikuti, bolehDiikuti, status, laporKesalahan, jalur, jumlahMenunggu, profil, izin, kondisi, hariLaporan, provenans, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const [memproses, setMemproses] = useState(false);
```

dengan:

```tsx
export default function Show() {
    const { gunung, mengikuti, bolehDiikuti, status, laporKesalahan, gunungApi, jalur, jumlahMenunggu, profil, izin, kondisi, hariLaporan, provenans, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const [memproses, setMemproses] = useState(false);
```

5. Ganti:

```tsx
                    <KartuStatusGunung status={status} lapor={laporKesalahan} sasaran={gunung.nama} />
                </section>
```

dengan:

```tsx
                    <KartuStatusGunung status={status} lapor={laporKesalahan} sasaran={gunung.nama} />
                    {gunungApi ? <KartuGunungApi data={gunungApi} /> : null}
                </section>
```

`resources/js/pages/Trails/Show.tsx`:

1. Ganti:

```tsx
import { useState, type ReactNode } from 'react';
import type { LaporKesalahan } from '@/components/trails/LaporkanKesalahan';
```

dengan:

```tsx
import { useState, type ReactNode } from 'react';
import { KartuGunungApi } from '@/components/trails/KartuGunungApi';
import type { LaporKesalahan } from '@/components/trails/LaporkanKesalahan';
```

2. Ganti:

```tsx
} from '@/components/trails/rinci/types';
import { TombolSimpan } from '@/components/trails/TombolSimpan';
```

dengan:

```tsx
} from '@/components/trails/rinci/types';
import type { GunungApi } from '@/components/trails/types';
import { TombolSimpan } from '@/components/trails/TombolSimpan';
```

3. Ganti:

```tsx
    laporKesalahan: LaporKesalahan;
    perizinan: Perizinan | null;
```

dengan:

```tsx
    laporKesalahan: LaporKesalahan;
    gunungApi: GunungApi | null;
    perizinan: Perizinan | null;
```

4. Ganti:

```tsx
        laporKesalahan,
        perizinan,
```

dengan:

```tsx
        laporKesalahan,
        gunungApi,
        perizinan,
```

5. Ganti:

```tsx
                            <StatusResmiKartu status={statusResmi} lapor={laporKesalahan} sasaran={trail.name} />
                            {perizinan ? <PerizinanKartu izin={perizinan} /> : null}
```

dengan:

```tsx
                            <StatusResmiKartu status={statusResmi} lapor={laporKesalahan} sasaran={trail.name} />
                            {gunungApi ? <KartuGunungApi data={gunungApi} /> : null}
                            {perizinan ? <PerizinanKartu izin={perizinan} /> : null}
```

`resources/js/pages/Trips/Readiness.tsx`:

1. Ganti:

```tsx
import { BarisStatus } from '@/components/trails/BarisStatus';
import { nadaKesegaran } from '@/components/trails/Kesegaran';
```

dengan:

```tsx
import { BarisStatus } from '@/components/trails/BarisStatus';
import { BarisGunungApi } from '@/components/trails/KartuGunungApi';
import { nadaKesegaran } from '@/components/trails/Kesegaran';
```

2. Ganti:

```tsx
import { LabelKomunitas } from '@/components/trails/LabelSumber';
import type { StatusRingkas } from '@/components/trails/types';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
```

dengan:

```tsx
import { LabelKomunitas } from '@/components/trails/LabelSumber';
import type { GunungApi, StatusRingkas } from '@/components/trails/types';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
```

3. Ganti:

```tsx
        status: StatusRingkas;
        prakiraan: Prakiraan;
```

dengan:

```tsx
        status: StatusRingkas;
        gunungApi: GunungApi | null;
        prakiraan: Prakiraan;
```

4. Ganti:

```tsx
                            </div>

                            <div className="border-t border-subtle pt-3">
```

dengan:

```tsx
                            </div>

                            {kondisi.gunungApi ? <BarisGunungApi data={kondisi.gunungApi} /> : null}

                            <div className="border-t border-subtle pt-3">
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Curator\EvidenceController;
use App\Http\Controllers\DashboardController;
```

dengan:

```php
use App\Http\Controllers\Curator\EvidenceController;
use App\Http\Controllers\Curator\VolcanoStatusController;
use App\Http\Controllers\DashboardController;
```

2. Ganti:

```php
    Route::get('kurator/status/{status}/ubah', [CuratorStatusController::class, 'edit'])->whereNumber('status')->name('curator.statuses.edit');
    // Tulisan meja kurator dibatasi laju dengan hitungannya sendiri (meja-kurator).
```

dengan:

```php
    Route::get('kurator/status/{status}/ubah', [CuratorStatusController::class, 'edit'])->whereNumber('status')->name('curator.statuses.edit');
    Route::get('kurator/gunung-api/baru', [VolcanoStatusController::class, 'create'])->name('curator.volcano.create');
    // Tulisan meja kurator dibatasi laju dengan hitungannya sendiri (meja-kurator).
```

3. Ganti:

```php
        Route::post('kurator/laporan/{laporan}/dicek', DataErrorCheckController::class)->whereNumber('laporan')->name('curator.data-errors.check');
    });
```

dengan:

```php
        Route::post('kurator/laporan/{laporan}/dicek', DataErrorCheckController::class)->whereNumber('laporan')->name('curator.data-errors.check');
        Route::post('kurator/gunung-api', [VolcanoStatusController::class, 'store'])->name('curator.volcano.store');
    });
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
    /**
     * @return array{citra: User, menunggu: OfficialStatus, draf: OfficialStatus, jalur: Trail}
```

dengan:

```php
    /**
     * Formulir status gunung api PVMBG (DK-43): Gunung Gede terpilih, level contoh sudah tercatat.
     */
    public function test_formulir_gunung_api(): void
    {
        $contoh = $this->mejaKuratorContoh();
        $this->actingAs($contoh['citra']);

        $this->tulisPratinjau('formulir-gunung-api', $this->get(self::ASAL.'/kurator/gunung-api/baru?gunung='.$contoh['jalur']->mountain_id), 'Curator/VolcanoForm');
    }

    /**
     * @return array{citra: User, menunggu: OfficialStatus, draf: OfficialStatus, jalur: Trail}
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `curator.volcano.store`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/VolcanoLevel.php app/Http/Controllers/Curator/VolcanoStatusController.php app/Http/Requests/Curator/SimpanStatusGunungApiRequest.php app/Models/VolcanoStatus.php app/Policies/VolcanoStatusPolicy.php app/Services/DataResmi/MejaKurator.php app/Services/DataResmi/StatusGunungApi.php app/Services/Mountains/HalamanGunung.php app/Services/Trails/TrailDetailPage.php app/Services/Trips/HalamanKesiapan.php config/hiking.php database/factories/VolcanoStatusFactory.php database/migrations/2026_10_09_000400_create_volcano_statuses_table.php database/seeders/DemoSeeder.php routes/web.php tests/Feature/DemoSeederTest.php tests/Feature/VolcanoStatusTest.php tests/Pratinjau/HalamanPratinjauTest.php tests/Spatial/VolcanoStatusSchemaTest.php`, lalu `php artisan test --compact --filter='VolcanoStatus|DemoSeeder|RepoPolicyFiles'`
Expected: PASS. Pada simulasi rencana: 28 test, 25 lulus, 3 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.221 test, 2.219 lulus, 2 dilewati (sebelum tugas: 2.212).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 41 test lulus (sebelum tugas: 40; halaman baru `formulir-gunung-api`).

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 11: Foto gunung berlisensi

DK-37, spec v3 bagian 8.7 dan 9.3 (tabel media gunung, "admin saja"), induk W8 butir 8; ruling W8-R19; R-017;
Konflik 7. Migrasi `2026_10_09_000500` membuat `mountain_photos` (satu foto per gunung, ukuran, SHA-256, pembuat,
lisensi, sumber, atribusi, teks alternatif, pengunggah) dengan CHECK lisensi dan SHA-256 serta RLS. Admin memasang,
mengganti, atau menghapus foto di halaman "Foto gunung" (`Admin/MountainPhoto`, dari daftar gunung konsol): JPG, PNG, atau
WebP paling besar 8 MB, ditulis ulang tanpa EXIF dengan sisi terpanjang 2.000 px, disimpan dengan nama acak di
`filesystems.mountain_photos_disk` (`MOUNTAIN_PHOTOS_DISK`, bawaan disk privat lokal); berkas lama dihapus, berkas baru
dihapus lagi bila penyimpanan basis data gagal, dan setiap simpan dan hapus diaudit. Halaman gunung menampilkan foto
dengan atribusi di pojok lewat `mountains.photo`; tanpa foto, kepala tetap visual data. Foto komunitas tidak pernah
menjadi kepala halaman.

**Files:**
- Create: `app/Enums/PhotoLicense.php`, `app/Http/Controllers/Admin/MountainPhotoManagerController.php`, `app/Http/Controllers/Trails/MountainPhotoController.php`, `app/Http/Requests/Admin/SimpanFotoGunungRequest.php`, `app/Models/MountainPhoto.php`, `app/Services/Mountains/FotoGunung.php`, `database/factories/MountainPhotoFactory.php`, `database/migrations/2026_10_09_000500_create_mountain_photos_table.php`, `resources/js/components/trails/gunung/FotoGunung.tsx`, `resources/js/pages/Admin/MountainPhoto.tsx`
- Modify: `.env.example`, `app/Http/Resources/Admin/MountainRowResource.php`, `app/Models/Mountain.php`, `app/Services/Mountains/HalamanGunung.php`, `app/Support/PenjagaDataContoh.php`, `config/filesystems.php`, `config/hiking.php`, `database/README.md`, `resources/js/components/trails/gunung/types.ts`, `resources/js/lib/bukanHalamanReact.json`, `resources/js/pages/Admin/Mountains.tsx`, `resources/js/pages/Mountains/Show.tsx`, `routes/web.php`
- Test: `tests/Feature/MountainPhotoLicenseTest.php` (baru), `tests/Pratinjau/HalamanPratinjauTest.php`, `tests/Spatial/MountainPhotoSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `ImageSanitizer::sanitize(string, int)`, `AuditLogService::record()`, `PenjagaDataContoh`, halaman `Admin/Mountains` dan `MountainRowResource` (W3), `HalamanGunung` dan `Mountains/Show` (W3, W5).
- Produces:
  - enum `PhotoLicense` (`CC_BY`, `CC_BY_SA`, `PUBLIC_DOMAIN`, `OWN`; `label()`, `pilihan()` statis); model `MountainPhoto` (isian massal hanya `creator`, `license`, `source_url`, `attribution`, `alt_text`), pabrik, dan `Mountain::photo(): HasOne`;
  - `App\Services\Mountains\FotoGunung`: `simpan(Mountain, UploadedFile, array $isian, User $admin): MountainPhoto`, `hapus(Mountain, User): void`, `sah(?string): bool` (statis), `disk(): string` (statis), `bentuk(?MountainPhoto, Mountain): ?array` (statis);
  - rute `admin.mountains.photo` (GET `admin/mountains/{mountain}/foto`), `admin.mountains.photo.store` dan `admin.mountains.photo.destroy` (berbatas laju `foto-gunung`), dan `mountains.photo` (GET `mountains/{mountain:slug}/foto`, terdaftar di `bukanHalamanReact.json`);
  - config `filesystems.mountain_photos_disk`, `hiking.uploads.mountain_photo_max_kb` (8192) dan `mountain_photo_dimension` (2000); kunci `.env.example` `MOUNTAIN_PHOTOS_DISK=local`;
  - prop `foto` di halaman gunung (tipe `FotoGunungData`), komponen `trails/gunung/FotoGunung`, halaman `Admin/MountainPhoto`; halaman pratinjau `gunung-berfoto`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction MountainPhotoLicenseTest`, lalu isinya diganti persis. Berkas lain, `tests/Spatial/MountainPhotoSchemaTest.php`, dibuat langsung.

Buat `tests/Feature/MountainPhotoLicenseTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Enums\PhotoLicense;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Mountain;
use App\Models\MountainPhoto;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Mountains\FotoGunung;
use App\Support\PenjagaDataContoh;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use RuntimeException;
use Tests\TestCase;

/**
 * Foto gunung berlisensi (DK-37; spec v3 bagian 6.3, 8.7, 9.3; rencana induk W8 butir 8; ruling
 * W8-R19): unggahan admin dengan kolom wajib lisensinya, EXIF dibuang dan sidik SHA-256 dicatat,
 * atribusi di pojok kepala halaman gunung, visual data bila tanpa foto, dan foto komunitas tidak
 * pernah menjadi kepala halaman (R-017).
 */
class MountainPhotoLicenseTest extends TestCase
{
    use RefreshDatabase;

    private Mountain $gunung;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.mountain_photos_disk'));
        $this->gunung = Mountain::factory()->create(['name' => 'Gunung Gede']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }

    /**
     * JPEG 2400 x 1500 dengan segmen EXIF berisi tag Make (pola ProfilePhotoTest).
     */
    private function fotoDenganExif(): UploadedFile
    {
        $gambar = imagecreatetruecolor(2400, 1500);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 40, 90, 60));
        ob_start();
        imagejpeg($gambar);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);

        $make = "Kamera\0";
        $ifd = pack('v', 1).pack('vvVV', 0x010F, 2, strlen($make), 8 + 2 + 12 + 4).pack('V', 0).$make;
        $muatan = "Exif\0\0"."II\x2a\x00".pack('V', 8).$ifd;
        $exif = "\xFF\xE1".pack('n', strlen($muatan) + 2).$muatan;

        return UploadedFile::fake()->createWithContent('gede-dari-alun-alun.jpg', substr($jpeg, 0, 2).$exif.substr($jpeg, 2));
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isian(array $ganti = []): array
    {
        return [
            'foto' => $this->fotoDenganExif(),
            'creator' => 'Rani Prasetyo',
            'license' => PhotoLicense::CC_BY->value,
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Gede.jpg',
            'attribution' => 'Foto: Rani Prasetyo, CC BY 4.0, Wikimedia Commons',
            'alt_text' => 'Puncak Gunung Gede dilihat dari alun-alun Surya Kencana.',
            ...$ganti,
        ];
    }

    public function test_an_admin_uploads_a_licensed_photo_without_location_metadata(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['mountain_id' => 999, 'uploaded_by' => 999, 'path' => '../.env', 'sha256' => str_repeat('0', 64)]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.mountains.photo', $this->gunung));

        $foto = MountainPhoto::sole();
        $this->assertSame($this->gunung->id, $foto->mountain_id);
        $this->assertSame($admin->id, $foto->uploaded_by);
        $this->assertTrue(FotoGunung::sah($foto->path));
        $this->assertStringNotContainsString('alun', $foto->path, 'Nama berkas acak, bukan dari nama berkas asal.');

        $berkas = Storage::disk(FotoGunung::disk())->path($foto->path);
        $this->assertSame(hash_file('sha256', $berkas), $foto->sha256);
        $this->assertSame([(int) config('hiking.uploads.mountain_photo_dimension'), 1250], [$foto->width, $foto->height]);
        $exif = @exif_read_data($berkas);
        $this->assertTrue($exif === false || ! isset($exif['Make']), 'Metadata tidak boleh tersisa.');

        $jejak = AuditLog::where('action', 'mountain_photo.saved')->sole();
        $this->assertNull($jejak->before);
        $this->assertSame('Rani Prasetyo', $jejak->after['creator']);
    }

    public function test_every_license_field_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['creator' => '', 'license' => 'BEBAS', 'source_url' => '', 'attribution' => '', 'alt_text' => '']))
            ->assertSessionHasErrors(['creator', 'license', 'attribution', 'alt_text']);

        $this->actingAs($this->admin())
            ->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['source_url' => '']))
            ->assertSessionHasErrors('source_url');

        $this->assertSame(0, MountainPhoto::count());

        // Foto milik Memount boleh tanpa alamat sumber.
        $this->actingAs($this->admin())
            ->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['license' => PhotoLicense::OWN->value, 'source_url' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull(MountainPhoto::sole()->source_url);
    }

    public function test_svg_scripts_and_tiny_images_are_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['foto' => UploadedFile::fake()->createWithContent('foto.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>')]))->assertSessionHasErrors('foto');
        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['foto' => UploadedFile::fake()->createWithContent('foto.jpg', "<?php system(\$_GET['c']); ?>\n")]))->assertSessionHasErrors('foto');
        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['foto' => UploadedFile::fake()->image('kecil.jpg', 300, 200)]))->assertSessionHasErrors('foto');

        $this->assertSame(0, MountainPhoto::count());
        $this->assertSame([], Storage::disk(FotoGunung::disk())->allFiles());
    }

    public function test_only_admins_manage_photos(): void
    {
        $kurator = User::factory()->create(['role' => UserRole::CURATOR->value]);
        $pendaki = User::factory()->create();

        $this->actingAs($kurator)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian())->assertForbidden();
        $this->actingAs($pendaki)->get(route('admin.mountains.photo', $this->gunung))->assertForbidden();
        $this->assertSame(0, MountainPhoto::count());
    }

    /**
     * Atribusi, lisensi, dan teks alternatif sampai ke kepala halaman gunung; berkasnya disajikan
     * dengan cache privat.
     */
    public function test_the_mountain_page_shows_the_photo_with_its_attribution(): void
    {
        $this->actingAs($this->admin())->post(route('admin.mountains.photo.store', $this->gunung), $this->isian());
        $foto = MountainPhoto::sole();
        $pendaki = User::factory()->create();

        $this->actingAs($pendaki)->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('foto.url', route('mountains.photo', ['mountain' => $this->gunung, 'v' => substr($foto->sha256, 0, 12)]))
                ->where('foto.atribusi', 'Foto: Rani Prasetyo, CC BY 4.0, Wikimedia Commons')
                ->where('foto.lisensi', 'CC BY')
                ->where('foto.alt', 'Puncak Gunung Gede dilihat dari alun-alun Surya Kencana.')
                ->where('foto.sumber', 'https://commons.wikimedia.org/wiki/File:Gede.jpg'));

        $respons = $this->actingAs($pendaki)->get(route('mountains.photo', $this->gunung));
        $respons->assertOk();
        $this->assertStringContainsString('private', (string) $respons->headers->get('Cache-Control'));
    }

    /**
     * R-017: tanpa foto berlisensi kepala halamannya visual data, walaupun ada laporan komunitas
     * berfoto yang sudah disetujui di gunung itu.
     */
    public function test_a_community_photo_never_becomes_the_hero(): void
    {
        $jalur = Trail::factory()->published()->for($this->gunung)->create();
        TrailConditionReport::factory()->for($jalur)->create([
            'moderation_status' => ModerationStatus::APPROVED->value,
            'photo_path' => 'report-photos/contoh.jpg',
        ]);

        $this->actingAs(User::factory()->create())->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('foto', null));
        $this->actingAs(User::factory()->create())->get(route('mountains.photo', $this->gunung))->assertNotFound();
    }

    public function test_replacing_or_deleting_removes_the_old_file_and_is_audited(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian());
        $lama = MountainPhoto::sole()->path;
        $disk = Storage::disk(FotoGunung::disk());

        $this->actingAs($admin)->post(route('admin.mountains.photo.store', $this->gunung), $this->isian(['creator' => 'Dimas Pratama']))->assertSessionHasNoErrors();
        $baru = MountainPhoto::sole();
        $this->assertNotSame($lama, $baru->path);
        $disk->assertMissing($lama);
        $this->assertSame('Rani Prasetyo', AuditLog::where('action', 'mountain_photo.saved')->latest('id')->first()->before['creator']);

        $this->actingAs($admin)->delete(route('admin.mountains.photo.destroy', $this->gunung))->assertRedirect(route('admin.mountains.photo', $this->gunung));
        $this->assertSame(0, MountainPhoto::count());
        $disk->assertMissing($baru->path);
        $this->assertSame(1, AuditLog::where('action', 'mountain_photo.deleted')->count());
    }

    /**
     * Simpan yang gagal di basis data tidak meninggalkan berkas tanpa baris yang menunjuknya.
     */
    public function test_a_failed_save_leaves_no_orphan_file(): void
    {
        $this->mock(AuditLogService::class, fn ($audit) => $audit->shouldReceive('record')->andThrow(new RuntimeException('Audit gagal.')));
        $isian = $this->isian();

        try {
            app(FotoGunung::class)->simpan($this->gunung, $isian['foto'], $isian, $this->admin());
            $this->fail('Galat audit harus diteruskan.');
        } catch (RuntimeException $galat) {
            $this->assertSame('Audit gagal.', $galat->getMessage());
        }

        $this->assertSame(0, MountainPhoto::count());
        $this->assertSame([], Storage::disk(FotoGunung::disk())->allFiles());
    }

    public function test_a_path_not_written_by_the_service_is_never_served(): void
    {
        MountainPhoto::factory()->for($this->gunung)->create(['path' => 'report-photos/laporan-lain.jpg']);
        Storage::disk(FotoGunung::disk())->put('report-photos/laporan-lain.jpg', 'foto laporan komunitas');

        $this->actingAs(User::factory()->create())->get(route('mountains.photo', $this->gunung))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('mountains.show', $this->gunung))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('foto', null));
    }

    /**
     * Saat data contoh disajikan, disk foto gunung diputus dari .env seperti disk unggahan lain.
     */
    public function test_serving_sample_data_keeps_the_photo_disk_local(): void
    {
        config(['filesystems.mountain_photos_disk' => 'supabase']);

        app(PenjagaDataContoh::class)->putuskanSaluranLuar();

        $this->assertSame('local', config('filesystems.mountain_photos_disk'));
    }
}
```

Buat `tests/Spatial/MountainPhotoSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\Mountain;
use App\Models\MountainPhoto;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Tabel foto gunung di PostgreSQL (R-024; DK-37; ruling W8-R19): satu foto per gunung, lisensi dari
 * daftar tertutup, sidik SHA-256 heksadesimal, foto ikut terhapus bersama gunungnya, pengunggah
 * menjadi kosong, dan RLS menutup REST API.
 */
class MountainPhotoSchemaTest extends SpatialTestCase
{
    public function test_the_table_has_its_production_shape(): void
    {
        $this->assertTrue((bool) DB::selectOne("SELECT relrowsecurity FROM pg_class WHERE relname = 'mountain_photos'")->relrowsecurity, 'RLS lapis kedua harus menyala.');

        $cek = collect(DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'mountain_photos'::regclass AND contype = 'c'"))->pluck('conname')->all();
        $this->assertContains('mountain_photos_lisensi_sah', $cek);
        $this->assertContains('mountain_photos_sha256_sah', $cek);

        $hapus = collect(DB::select(
            "SELECT a.attname AS kolom, c.confdeltype AS aksi
             FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = 'mountain_photos'::regclass AND c.contype = 'f'"
        ))->pluck('aksi', 'kolom');

        $this->assertSame('c', $hapus['mountain_id']);
        $this->assertSame('n', $hapus['uploaded_by']);
    }

    public function test_one_photo_per_mountain(): void
    {
        $gunung = Mountain::factory()->create();
        MountainPhoto::factory()->for($gunung)->create();

        $this->expectException(UniqueConstraintViolationException::class);
        MountainPhoto::factory()->for($gunung)->create();
    }

    public function test_an_unknown_license_is_refused(): void
    {
        $foto = MountainPhoto::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('mountain_photos')->where('id', $foto->id)->update(['license' => 'ALL_RIGHTS_RESERVED']);
    }

    public function test_a_malformed_fingerprint_is_refused(): void
    {
        $foto = MountainPhoto::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('mountain_photos')->where('id', $foto->id)->update(['sha256' => str_repeat('Z', 64)]);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='MountainPhotoLicense|InertiaLinkRegistry|RepoPolicyFiles|AdminDataManagement'`
Expected: FAIL. Pada simulasi rencana: 36 test, 26 lulus, 2 gagal, 8 error. Yang merah: kesepuluh test `MountainPhotoLicenseTest` (rute `admin.mountains.photo.store`,
enum `PhotoLicense`, model `MountainPhoto`, prop `foto`, dan disk foto belum ada).

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:enum --no-interaction PhotoLicense`, `... make:controller --no-interaction Admin/MountainPhotoManagerController`, `... make:controller --no-interaction Trails/MountainPhotoController`, `... make:request --no-interaction Admin/SimpanFotoGunungRequest`, `... make:model --no-interaction MountainPhoto`, `... make:class --no-interaction Services/Mountains/FotoGunung`, `... make:factory --no-interaction MountainPhotoFactory`, dan `... make:migration --no-interaction create_mountain_photos_table` (ganti nama berkas migrasi hasilnya menjadi `2026_10_09_000500_create_mountain_photos_table.php`), lalu isinya diganti persis. Berkas lain, `resources/js/components/trails/gunung/FotoGunung.tsx` dan `resources/js/pages/Admin/MountainPhoto.tsx`, dibuat langsung.

Buat `app/Enums/PhotoLicense.php`:

```php
<?php

namespace App\Enums;

/**
 * Lisensi foto gunung yang diterima (spec v3 bagian 8.7; DK-37). Foto tanpa salah satu lisensi ini
 * tidak diunggah; tanpa foto, halaman gunung memakai visual data.
 */
enum PhotoLicense: string
{
    case CC_BY = 'CC_BY';
    case CC_BY_SA = 'CC_BY_SA';
    case PUBLIC_DOMAIN = 'PUBLIC_DOMAIN';
    case OWN = 'OWN';

    public function label(): string
    {
        return match ($this) {
            self::CC_BY => 'CC BY',
            self::CC_BY_SA => 'CC BY-SA',
            self::PUBLIC_DOMAIN => 'Domain publik',
            self::OWN => 'Milik Memount',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pilihan(): array
    {
        return array_map(fn (self $lisensi) => ['value' => $lisensi->value, 'label' => $lisensi->label()], self::cases());
    }
}
```

Buat `app/Http/Controllers/Admin/MountainPhotoManagerController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PhotoLicense;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SimpanFotoGunungRequest;
use App\Models\Mountain;
use App\Services\Mountains\FotoGunung;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Foto gunung berlisensi di konsol admin (DK-37; spec v3 bagian 8.7 dan 9.3; ruling W8-R19):
 * pratinjau dengan atribusinya, unggah atau ganti, dan hapus. Hanya admin.
 */
class MountainPhotoManagerController extends Controller
{
    public function edit(Mountain $mountain): Response
    {
        $this->authorize('update', $mountain);

        return Inertia::render('Admin/MountainPhoto', [
            'title' => 'Foto '.$mountain->name,
            'gunung' => ['id' => $mountain->id, 'nama' => $mountain->name],
            'foto' => FotoGunung::bentuk($mountain->photo, $mountain),
            'lisensi' => PhotoLicense::pilihan(),
            'batasKb' => (int) config('hiking.uploads.mountain_photo_max_kb'),
        ]);
    }

    public function store(SimpanFotoGunungRequest $request, Mountain $mountain, FotoGunung $foto): RedirectResponse
    {
        $foto->simpan($mountain, $request->file('foto'), $request->safe()->except('foto'), $request->user());

        return to_route('admin.mountains.photo', $mountain)->with('status', 'Foto '.$mountain->name.' tersimpan beserta atribusinya.');
    }

    public function destroy(Request $request, Mountain $mountain, FotoGunung $foto): RedirectResponse
    {
        $this->authorize('update', $mountain);

        $foto->hapus($mountain, $request->user());

        return to_route('admin.mountains.photo', $mountain)->with('status', 'Foto '.$mountain->name.' dihapus. Halaman gunung kembali memakai visual data.');
    }
}
```

Buat `app/Http/Controllers/Trails/MountainPhotoController.php`:

```php
<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Models\Mountain;
use App\Services\Mountains\FotoGunung;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Berkas foto gunung berlisensi untuk halaman gunung (DK-37; ruling W8-R19). Hanya berkas yang
 * ditulis FotoGunung yang disajikan; gunung terarsip dan gunung tanpa foto menjawab 404.
 */
class MountainPhotoController extends Controller
{
    public function __invoke(Mountain $mountain): StreamedResponse
    {
        abort_if($mountain->isArchived(), 404);

        $path = $mountain->photo?->path;
        $disk = Storage::disk(FotoGunung::disk());

        abort_if(! FotoGunung::sah($path) || ! $disk->exists($path), 404);

        // Halaman gunung hanya untuk pengguna masuk, jadi tidak disimpan proxy bersama.
        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
```

Buat `app/Http/Requests/Admin/SimpanFotoGunungRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Enums\PhotoLicense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Foto gunung berlisensi (spec v3 bagian 8.7; DK-37; ruling W8-R19). Kolom wajibnya pembuat,
 * lisensi, URL sumber (kecuali foto milik Memount), teks atribusi, dan teks alternatif. MIME dari
 * isi berkas, ekstensi, ukuran, dan dimensi dibatasi bersama (PRD §81); SVG tidak diterima.
 * Gunung dari rute dan pengunggah dari sesi.
 */
class SimpanFotoGunungRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('mountain')) ?? false;
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
                'max:'.(int) config('hiking.uploads.mountain_photo_max_kb'),
                'dimensions:min_width=800,min_height=400,max_width=10000,max_height=10000',
            ],
            'creator' => ['required', 'string', 'max:160'],
            'license' => ['required', new Enum(PhotoLicense::class)],
            'source_url' => ['nullable', 'required_unless:license,'.PhotoLicense::OWN->value, 'url:https,http', 'max:500'],
            'attribution' => ['required', 'string', 'max:300'],
            'alt_text' => ['required', 'string', 'min:10', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Pilih foto lebih dulu.',
            'foto.image' => 'Berkas itu bukan foto. Pilih foto JPG, PNG, atau WebP.',
            'foto.mimes' => 'Pilih foto JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto paling besar '.((int) config('hiking.uploads.mountain_photo_max_kb') / 1024).' MB.',
            'foto.dimensions' => 'Foto paling kecil 800 x 400 piksel supaya tetap tajam di kepala halaman.',
            'creator.required' => 'Tulis nama pembuat foto.',
            'license.required' => 'Pilih lisensi foto.',
            'source_url.required_unless' => 'Tulis alamat sumber foto, kecuali foto milik Memount.',
            'attribution.required' => 'Tulis teks atribusi seperti yang diminta lisensinya.',
            'alt_text.required' => 'Tulis teks alternatif yang menggambarkan isi foto untuk pembaca layar.',
            'alt_text.min' => 'Teks alternatif paling sedikit 10 karakter: gambarkan isi fotonya.',
        ];
    }
}
```

Buat `app/Models/MountainPhoto.php`:

```php
<?php

namespace App\Models;

use App\Enums\PhotoLicense;
use Database\Factories\MountainPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Foto gunung berlisensi (DK-37; ruling W8-R19). Gunung, berkas, ukuran, sidik, dan pengunggah
 * ditulis layanan FotoGunung lewat forceFill; isian hanya keterangan lisensinya.
 */
#[Fillable(['creator', 'license', 'source_url', 'attribution', 'alt_text'])]
class MountainPhoto extends Model
{
    /** @use HasFactory<MountainPhotoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'license' => PhotoLicense::class,
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function mountain(): BelongsTo
    {
        return $this->belongsTo(Mountain::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
```

Buat `app/Services/Mountains/FotoGunung.php`:

```php
<?php

namespace App\Services\Mountains;

use App\Models\Mountain;
use App\Models\MountainPhoto;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Foto gunung berlisensi (DK-37; spec v3 bagian 8.7; rencana induk W8 butir 8; ruling W8-R19).
 * Berkas unggahan admin ditulis ulang lebih dulu (EXIF hilang, sisi terpanjang dibatasi,
 * ImageSanitizer), lalu disimpan dengan nama acak di disk yang dapat diganti
 * (filesystems.mountain_photos_disk) beserta ukuran dan sidik SHA-256-nya. Satu foto per gunung:
 * mengganti foto menghapus berkas lamanya. Setiap simpan dan hapus diaudit.
 */
final class FotoGunung
{
    public const MAP = 'mountain-photos';

    private const DIAUDIT = ['mountain_id', 'path', 'width', 'height', 'sha256', 'creator', 'license', 'source_url', 'attribution', 'alt_text', 'uploaded_by'];

    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array{creator: string, license: string, source_url?: string|null, attribution: string, alt_text: string}  $isian
     */
    public function simpan(Mountain $gunung, UploadedFile $berkas, array $isian, User $admin): MountainPhoto
    {
        $sumber = (string) $berkas->getRealPath();

        try {
            ImageSanitizer::sanitize($sumber, (int) config('hiking.uploads.mountain_photo_dimension'));
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['foto' => 'Foto tidak dapat diproses. Pilih foto JPG, PNG, atau WebP yang lain.']);
        }

        clearstatcache(true, $sumber);
        [$lebar, $tinggi] = getimagesize($sumber) ?: [0, 0];
        $sidik = (string) hash_file('sha256', $sumber);
        $path = $berkas->store(self::MAP, self::disk());

        if ($path === false) {
            throw ValidationException::withMessages(['foto' => 'Foto gagal disimpan. Coba lagi.']);
        }

        $foto = MountainPhoto::query()->firstOrNew(['mountain_id' => $gunung->id]);
        $sebelum = $foto->exists ? $foto->only(self::DIAUDIT) : null;
        $lama = $foto->exists ? $foto->path : null;

        $foto->fill([
            'creator' => $isian['creator'],
            'license' => $isian['license'],
            'source_url' => $isian['source_url'] ?? null,
            'attribution' => $isian['attribution'],
            'alt_text' => $isian['alt_text'],
        ]);
        $foto->forceFill(['mountain_id' => $gunung->id, 'path' => $path, 'width' => $lebar, 'height' => $tinggi, 'sha256' => $sidik, 'uploaded_by' => $admin->id]);

        try {
            DB::transaction(function () use ($foto, $admin, $sebelum): void {
                $foto->save();
                $this->audit->record($admin, 'mountain_photo.saved', $foto, $sebelum, $foto->only(self::DIAUDIT));
            });
        } catch (Throwable $galat) {
            // Berkas baru tidak boleh tertinggal tanpa baris yang menunjuknya.
            $this->hapusBerkas($path);

            throw $galat;
        }

        $this->hapusBerkas($lama);

        return $foto;
    }

    public function hapus(Mountain $gunung, User $admin): void
    {
        $foto = MountainPhoto::query()->where('mountain_id', $gunung->id)->first();

        if ($foto === null) {
            return;
        }

        DB::transaction(function () use ($foto, $admin): void {
            $this->audit->record($admin, 'mountain_photo.deleted', $foto, $foto->only(self::DIAUDIT), null);
            $foto->delete();
        });

        $this->hapusBerkas($foto->path);
    }

    /**
     * Hanya berkas yang ditulis layanan ini: satu nama acak langsung di dalam map foto gunung.
     */
    public static function sah(?string $path): bool
    {
        return $path !== null && preg_match('#^'.self::MAP.'/[A-Za-z0-9]+(\.[A-Za-z0-9]+)?$#', $path) === 1;
    }

    public static function disk(): string
    {
        return (string) config('filesystems.mountain_photos_disk');
    }

    /**
     * Bentuk untuk halaman gunung, dengan penanda versi dari sidik supaya foto baru tidak tertahan
     * cache peramban; null bila gunung belum punya foto (halaman memakai visual data).
     *
     * @return array{url: string, alt: string, atribusi: string, lisensi: string, pembuat: string, sumber: string|null, lebar: int, tinggi: int}|null
     */
    public static function bentuk(?MountainPhoto $foto, Mountain $gunung): ?array
    {
        if ($foto === null || ! self::sah($foto->path)) {
            return null;
        }

        return [
            'url' => route('mountains.photo', ['mountain' => $gunung, 'v' => substr($foto->sha256, 0, 12)]),
            'alt' => $foto->alt_text,
            'atribusi' => $foto->attribution,
            'lisensi' => $foto->license->label(),
            'pembuat' => $foto->creator,
            'sumber' => $foto->source_url,
            'lebar' => $foto->width,
            'tinggi' => $foto->height,
        ];
    }

    private function hapusBerkas(?string $path): void
    {
        if (self::sah($path)) {
            Storage::disk(self::disk())->delete($path);
        }
    }
}
```

Buat `database/factories/MountainPhotoFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\PhotoLicense;
use App\Models\Mountain;
use App\Models\MountainPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MountainPhoto>
 */
class MountainPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mountain_id' => Mountain::factory(),
            'path' => 'mountain-photos/'.fake()->regexify('[A-Za-z0-9]{40}').'.jpg',
            'width' => 1600,
            'height' => 900,
            'sha256' => hash('sha256', fake()->uuid()),
            'creator' => 'Rani Prasetyo',
            'license' => PhotoLicense::CC_BY->value,
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Contoh.jpg',
            'attribution' => 'Foto: Rani Prasetyo, CC BY 4.0, Wikimedia Commons',
            'alt_text' => 'Lereng gunung berhutan di bawah langit cerah.',
        ];
    }
}
```

Buat `database/migrations/2026_10_09_000500_create_mountain_photos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Foto gunung berlisensi (DK-37; spec v3 bagian 8.7 dan 9.3; rencana induk W8 butir 8; ruling
 * W8-R19): satu foto per gunung, diunggah admin, dengan kolom wajib pembuat, lisensi, URL sumber
 * (kecuali milik sendiri), teks atribusi, dan teks alternatif. Berkasnya ditulis ulang tanpa EXIF
 * dan sidik SHA-256-nya dicatat. Foto komunitas tidak pernah masuk tabel ini (R-017).
 *
 * Foto ikut terhapus bersama gunungnya; pengunggah menjadi kosong bila akunnya hilang. Di
 * PostgreSQL CHECK menjaga lisensi dari daftar tertutup dan sidiknya, dan RLS menutup REST API
 * (lapis kedua 2026_09_20_000100).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mountain_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mountain_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->char('sha256', 64);
            $table->string('creator', 160);
            $table->string('license', 20);
            $table->string('source_url', 500)->nullable();
            $table->string('attribution', 300);
            $table->string('alt_text', 300);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('uploaded_by');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE mountain_photos ADD CONSTRAINT mountain_photos_lisensi_sah CHECK (license IN ('CC_BY', 'CC_BY_SA', 'PUBLIC_DOMAIN', 'OWN'))");
            DB::statement("ALTER TABLE mountain_photos ADD CONSTRAINT mountain_photos_sha256_sah CHECK (sha256 ~ '^[0-9a-f]{64}$')");
            DB::statement('ALTER TABLE public."mountain_photos" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mountain_photos');
    }
};
```

Buat `resources/js/components/trails/gunung/FotoGunung.tsx`:

```tsx
import { Icon } from '@/components/ui/Icon';
import { fokusTerlihat } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import type { FotoGunungData } from './types';

const chip = 'absolute bottom-2 right-2 inline-flex min-h-11 max-w-[85%] items-center gap-1 rounded-control bg-surface px-3 py-1 text-meta text-primary shadow-overlay';

/**
 * Foto gunung berlisensi sebagai kepala halaman (spec v3 bagian 6.3 dan 8.7; DK-37; ruling W8-R19).
 * Atribusinya selalu di pojok foto, di atas keping permukaan buram supaya kontrasnya tidak
 * bergantung pada isi foto; bila sumbernya tercatat, keping itu menautkan ke sumber. Hanya foto
 * dari tabel foto gunung yang sampai ke sini: foto laporan komunitas tidak pernah menjadi kepala
 * halaman (R-017).
 */
export function FotoGunung({ foto }: { foto: FotoGunungData }) {
    return (
        <figure className="relative overflow-hidden rounded-utama border border-subtle bg-surface-sunken">
            <img src={foto.url} alt={foto.alt} width={foto.lebar} height={foto.tinggi} decoding="async" className="aspect-[16/9] h-auto w-full object-cover" />
            <figcaption>
                {foto.sumber ? (
                    <a href={foto.sumber} target="_blank" rel="noopener noreferrer" className={kelas(chip, 'underline-offset-2 hover:underline', fokusTerlihat)}>
                        <span className="break-words">{foto.atribusi}</span>
                        <Icon name="arrow-square-out" size={16} />
                        <span className="sr-only"> (sumber foto, membuka tab baru)</span>
                    </a>
                ) : (
                    <span className={chip}>
                        <span className="break-words">{foto.atribusi}</span>
                    </span>
                )}
            </figcaption>
        </figure>
    );
}
```

Buat `resources/js/pages/Admin/MountainPhoto.tsx`:

```tsx
import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { konfirmasiLalu } from '@/components/admin/FormulirAdmin';
import { SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { FotoGunung } from '@/components/trails/gunung/FotoGunung';
import type { FotoGunungData } from '@/components/trails/gunung/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField, InputError, Label } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { mountains } from '@/routes/admin';
import { destroy, store } from '@/routes/admin/mountains/photo';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    title: string;
    gunung: { id: number; nama: string };
    foto: FotoGunungData | null;
    lisensi: Opsi[];
    batasKb: number;
}

interface IsianFoto {
    foto: File | null;
    creator: string;
    license: string;
    source_url: string;
    attribution: string;
    alt_text: string;
}

/**
 * Foto gunung berlisensi (DK-37; spec v3 bagian 8.7; ruling W8-R19): pratinjau persis seperti di
 * halaman gunung, lalu unggah atau ganti dengan kolom wajib lisensinya. Foto ditulis ulang tanpa
 * EXIF di server. Foto komunitas tidak pernah diunggah dari sini (R-017).
 */
export default function MountainPhoto() {
    const { flash, title, gunung, foto, lisensi, batasKb } = usePage<Props>().props;
    const [menghapus, setMenghapus] = useState(false);
    const form = useForm<IsianFoto>({ foto: null, creator: '', license: '', source_url: '', attribution: '', alt_text: '' });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url({ mountain: gunung.id }), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
    };

    const hapus = () =>
        konfirmasiLalu('Hapus foto ini? Halaman gunung kembali memakai visual data dari profil jalurnya.', () => {
            setMenghapus(true);
            router.delete(destroy.url({ mountain: gunung.id }), { preserveScroll: true, onFinish: () => setMenghapus(false) });
        });

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: mountains.url(), label: 'Kelola Gunung' }} title={title} description="Foto berlisensi untuk kepala halaman gunung, dengan atribusi di pojoknya." />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <section aria-labelledby="judul-tampil" className="space-y-3">
                    <h2 id="judul-tampil" className="text-bagian text-primary">
                        Yang tampil di halaman gunung
                    </h2>
                    {foto ? (
                        <>
                            <FotoGunung foto={foto} />
                            <dl className="grid grid-cols-1 gap-x-6 gap-y-2 text-kecil sm:grid-cols-[minmax(0,10rem)_1fr]">
                                <dt className="font-bold text-secondary">Pembuat</dt>
                                <dd className="break-words text-primary">{foto.pembuat}</dd>
                                <dt className="font-bold text-secondary">Lisensi</dt>
                                <dd className="text-primary">{foto.lisensi}</dd>
                                <dt className="font-bold text-secondary">Sumber</dt>
                                <dd className="break-all text-primary">{foto.sumber ?? 'Milik Memount, tanpa sumber luar'}</dd>
                                <dt className="font-bold text-secondary">Teks alternatif</dt>
                                <dd className="break-words text-primary">{foto.alt}</dd>
                            </dl>
                            <Button variant="danger" size="sm" processing={menghapus} onClick={hapus}>
                                Hapus foto
                            </Button>
                        </>
                    ) : (
                        <p className="max-w-prose text-kecil text-secondary">Belum ada foto. Halaman {gunung.nama} memakai visual data dari profil jalurnya.</p>
                    )}
                </section>

                <form onSubmit={kirim} className="mt-8 space-y-4 border-t border-subtle pt-6" noValidate>
                    <h2 className="text-bagian text-primary">{foto ? 'Ganti foto' : 'Unggah foto'}</h2>
                    <div>
                        <Label htmlFor="foto">Berkas foto</Label>
                        <input
                            id="foto"
                            name="foto"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(e) => form.setData('foto', e.target.files?.[0] ?? null)}
                            aria-describedby={form.errors.foto ? 'foto-hint foto-error' : 'foto-hint'}
                            aria-invalid={form.errors.foto ? true : undefined}
                            className="mt-1 block min-h-12 w-full rounded-tombol border border-control bg-surface px-3 py-2 text-kecil text-primary file:mr-3 file:min-h-11 file:rounded-control file:border-0 file:bg-surface-sunken file:px-3 file:font-bold file:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                        />
                        <p id="foto-hint" className="mt-1 text-kecil text-muted">
                            JPG, PNG, atau WebP, paling kecil 800 x 400 piksel, paling besar {Math.round(batasKb / 1024)} MB. Ditulis ulang tanpa metadata lokasi.
                        </p>
                        <InputError id="foto-error" messages={form.errors.foto} className="mt-2" />
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField name="creator" label="Pembuat foto" required value={form.data.creator} onChange={(e) => form.setData('creator', e.target.value)} error={form.errors.creator} />
                        <SelectField name="license" label="Lisensi" required placeholder="Pilih lisensi" options={lisensi} value={form.data.license} onChange={(e) => form.setData('license', e.target.value)} error={form.errors.license} />
                    </div>
                    <FormField
                        name="source_url"
                        label="Alamat sumber foto"
                        type="url"
                        hint="Wajib, kecuali foto milik Memount."
                        value={form.data.source_url}
                        onChange={(e) => form.setData('source_url', e.target.value)}
                        error={form.errors.source_url}
                    />
                    <FormField
                        name="attribution"
                        label="Teks atribusi"
                        required
                        hint='Ringkas, seperti yang diminta lisensinya. Misalnya "Foto: Rani Prasetyo, CC BY 4.0, Wikimedia Commons".'
                        value={form.data.attribution}
                        onChange={(e) => form.setData('attribution', e.target.value)}
                        error={form.errors.attribution}
                    />
                    <TextareaField name="alt_text" label="Teks alternatif" required rows={2} value={form.data.alt_text} onChange={(e) => form.setData('alt_text', e.target.value)} error={form.errors.alt_text} />
                    <Button type="submit" processing={form.processing}>
                        {foto ? 'Ganti foto' : 'Simpan foto'}
                    </Button>
                </form>
            </div>
        </div>
    );
}

MountainPhoto.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`.env.example`:

1. Ganti:

```
EVIDENCE_DISK=local
SUPABASE_STORAGE_ENDPOINT=
```

dengan:

```
EVIDENCE_DISK=local
MOUNTAIN_PHOTOS_DISK=local
SUPABASE_STORAGE_ENDPOINT=
```

`app/Http/Resources/Admin/MountainRowResource.php`:

1. Ganti:

```php
            'archived' => $this->isArchived(),
            'isian' => [
```

dengan:

```php
            'archived' => $this->isArchived(),
            // Halaman foto berlisensi gunung ini (W8, DK-37).
            'foto' => route('admin.mountains.photo', $this->resource),
            'isian' => [
```

`app/Models/Mountain.php`:

1. Ganti:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
```

dengan:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
```

2. Ganti:

```php
    }

    public function scopeActive(Builder $query): Builder
```

dengan:

```php
    }

    /**
     * Foto berlisensi kepala halaman gunung (DK-37, W8). Foto komunitas tidak pernah di sini (R-017).
     */
    public function photo(): HasOne
    {
        return $this->hasOne(MountainPhoto::class);
    }

    public function scopeActive(Builder $query): Builder
```

`app/Services/Mountains/HalamanGunung.php`:

1. Ganti:

```php
            'gunungApi' => $this->gunungApi->untukGunung($gunung->id, $zona),
            'jalur' => $jalur->map(fn (Trail $t) => [
```

dengan:

```php
            'gunungApi' => $this->gunungApi->untukGunung($gunung->id, $zona),
            // Foto berlisensi kepala halaman, hanya dari mountain_photos (DK-37, R-017, ruling W8-R19).
            'foto' => FotoGunung::bentuk($gunung->photo, $gunung),
            'jalur' => $jalur->map(fn (Trail $t) => [
```

`app/Support/PenjagaDataContoh.php`:

1. Ganti:

```php
        config(['filesystems.report_photos_disk' => 'local']);

        // Disk foto profil (W7) dan arsip bukti status resmi (W8) punya kunci .env sendiri juga.
        config(['filesystems.avatars_disk' => 'local', 'filesystems.evidence_disk' => 'local']);

        // Tinjauan W1-9b #2: config/broadcasting.php tidak ada di config/ aplikasi, tapi
```

dengan:

```php
        config(['filesystems.report_photos_disk' => 'local']);

        // Disk foto profil (W7), arsip bukti status resmi, dan foto gunung (W8) punya kunci .env sendiri juga.
        config(['filesystems.avatars_disk' => 'local', 'filesystems.evidence_disk' => 'local', 'filesystems.mountain_photos_disk' => 'local']);

        // Tinjauan W1-9b #2: config/broadcasting.php tidak ada di config/ aplikasi, tapi
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
    | Mountain Photo Disk
    |--------------------------------------------------------------------------
    |
    | Disk foto gunung berlisensi (W8, DK-37). Bawaannya disk privat lokal; fotonya
    | disajikan lewat rute mountains.photo kepada pengguna masuk, bersama atribusinya.
    |
    */

    'mountain_photos_disk' => env('MOUNTAIN_PHOTOS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
```

`config/hiking.php`:

1. Ganti:

```php
        'avatar_dimension' => 512,

        // Arsip bukti status resmi (W8): PDF surat atau foto unggahan pengelola. Foto ditulis
```

dengan:

```php
        'avatar_dimension' => 512,

        // Foto gunung berlisensi (W8, DK-37): unggahan admin, ditulis ulang tanpa EXIF dengan sisi
        // terpanjang 2000 px, cukup untuk kepala halaman 768 px pada layar berkepadatan dua kali.
        'mountain_photo_max_kb' => 8192,
        'mountain_photo_dimension' => 2000,

        // Arsip bukti status resmi (W8): PDF surat atau foto unggahan pengelola. Foto ditulis
```

`database/README.md`:

1. Ganti:

```markdown
| Akun dan profil pendaki | `users`, `password_reset_tokens`, `sessions`, `profiles`, `user_experience`, `user_preferences` | Peran di `users.role`; pengalaman yang diisi sendiri dipakai Route Fit |
| Gunung dan jalur | `mountains`, `trails`, `trail_segments`, `checkpoints`, `restricted_areas` | Mountain dan Trail entitas terpisah (R-001); jalur adalah unit keputusan (R-002) |
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain`, `authority_members` | Hierarki otoritas (R-009); anggota meja kurator per badan resmi, dengan masa berlaku dan pencabutan (W8) |
```

dengan:

```markdown
| Akun dan profil pendaki | `users`, `password_reset_tokens`, `sessions`, `profiles`, `user_experience`, `user_preferences` | Peran di `users.role`; pengalaman yang diisi sendiri dipakai Route Fit |
| Gunung dan jalur | `mountains`, `trails`, `trail_segments`, `checkpoints`, `restricted_areas`, `mountain_photos` | Mountain dan Trail entitas terpisah (R-001); jalur adalah unit keputusan (R-002); satu foto berlisensi per gunung dengan atribusi, foto komunitas tidak pernah menjadi kepala halaman (DK-37, R-017, W8) |
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain`, `authority_members` | Hierarki otoritas (R-009); anggota meja kurator per badan resmi, dengan masa berlaku dan pencabutan (W8) |
```

`resources/js/components/trails/gunung/types.ts`:

1. Ganti:

```ts
    status: StatusRingkas;
}
```

dengan:

```ts
    status: StatusRingkas;
}

/** Foto berlisensi kepala halaman gunung (FotoGunung::bentuk, DK-37); null berarti visual data. */
export interface FotoGunungData {
    url: string;
    alt: string;
    atribusi: string;
    lisensi: string;
    pembuat: string;
    sumber: string | null;
    lebar: number;
    tinggi: number;
}
```

`resources/js/lib/bukanHalamanReact.json`:

1. Ganti:

```json
        "^/kurator/status/[^/]+/bukti$",
        "^/profile/foto$",
```

dengan:

```json
        "^/kurator/status/[^/]+/bukti$",
        "^/mountains/[^/]+/foto$",
        "^/profile/foto$",
```

`resources/js/pages/Admin/Mountains.tsx`:

1. Ganti:

```tsx
    archived: boolean;
    isian: IsianGunung;
```

dengan:

```tsx
    archived: boolean;
    /** Halaman foto berlisensi gunung ini (W8, DK-37). */
    foto: string;
    isian: IsianGunung;
```

2. Ganti:

```tsx
                                    <Button variant="secondary" size="sm" onClick={() => buka(g)} aria-label={`Ubah ${g.name}`}>Ubah</Button>
                                    <Button variant="secondary" size="sm" onClick={() => ubahArsip(g)} aria-label={`${g.archived ? 'Aktifkan' : 'Arsipkan'} ${g.name}`}>
```

dengan:

```tsx
                                    <Button variant="secondary" size="sm" onClick={() => buka(g)} aria-label={`Ubah ${g.name}`}>Ubah</Button>
                                    <Button variant="secondary" size="sm" href={g.foto} aria-label={`Foto ${g.name}`}>Foto</Button>
                                    <Button variant="secondary" size="sm" onClick={() => ubahArsip(g)} aria-label={`${g.archived ? 'Aktifkan' : 'Arsipkan'} ${g.name}`}>
```

`resources/js/pages/Mountains/Show.tsx`:

1. Ganti:

```tsx
import { DaftarJalurGunung } from '@/components/trails/gunung/DaftarJalurGunung';
import { KartuIzin } from '@/components/trails/gunung/KartuIzin';
```

dengan:

```tsx
import { DaftarJalurGunung } from '@/components/trails/gunung/DaftarJalurGunung';
import { FotoGunung } from '@/components/trails/gunung/FotoGunung';
import { KartuIzin } from '@/components/trails/gunung/KartuIzin';
```

2. Ganti:

```tsx
import { KondisiPendaki } from '@/components/trails/gunung/KondisiPendaki';
import type { GunungRingkas, IzinGunung, JalurGunung, LaporanGunung, ProfilGunung, Provenans } from '@/components/trails/gunung/types';
import { VisualProfilGunung } from '@/components/trails/gunung/VisualProfilGunung';
```

dengan:

```tsx
import { KondisiPendaki } from '@/components/trails/gunung/KondisiPendaki';
import type { FotoGunungData, GunungRingkas, IzinGunung, JalurGunung, LaporanGunung, ProfilGunung, Provenans } from '@/components/trails/gunung/types';
import { VisualProfilGunung } from '@/components/trails/gunung/VisualProfilGunung';
```

3. Ganti:

```tsx
    gunungApi: GunungApi | null;
    jalur: JalurGunung[];
```

dengan:

```tsx
    gunungApi: GunungApi | null;
    foto: FotoGunungData | null;
    jalur: JalurGunung[];
```

4. Ganti:

```tsx
 * gunung, pilihan jalur dengan label dan status masing-masing, izin, kondisi dari pendaki,
 * dan asal-usul datanya. Tanpa foto berlisensi (DK-37, W8) kepala halamannya visual data:
 * profil setiap jalur pada skala yang sama.
 */
export default function Show() {
    const { gunung, mengikuti, bolehDiikuti, status, laporKesalahan, gunungApi, jalur, jumlahMenunggu, profil, izin, kondisi, hariLaporan, provenans, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const [memproses, setMemproses] = useState(false);
```

dengan:

```tsx
 * gunung, pilihan jalur dengan label dan status masing-masing, izin, kondisi dari pendaki,
 * dan asal-usul datanya. Kepala halamannya foto berlisensi dengan atribusi di pojoknya (DK-37,
 * W8); tanpa foto, visual data: profil setiap jalur pada skala yang sama.
 */
export default function Show() {
    const { gunung, mengikuti, bolehDiikuti, status, laporKesalahan, gunungApi, foto, jalur, jumlahMenunggu, profil, izin, kondisi, hariLaporan, provenans, kataBelumDinilai, pengumuman } = usePage<Props>().props;
    const [memproses, setMemproses] = useState(false);
```

5. Ganti:

```tsx
                </PageHeader>

                {profil ? <VisualProfilGunung profil={profil} /> : null}

                <section aria-labelledby="judul-puncak" className="rounded-lg border border-subtle bg-surface p-5 sm:flex sm:items-center sm:gap-6">
```

dengan:

```tsx
                </PageHeader>

                {foto ? <FotoGunung foto={foto} /> : profil ? <VisualProfilGunung profil={profil} /> : null}

                <section aria-labelledby="judul-puncak" className="rounded-lg border border-subtle bg-surface p-5 sm:flex sm:items-center sm:gap-6">
```

`routes/web.php`:

1. Ganti:

```php
use App\Http\Controllers\Admin\MountainController;
use App\Http\Controllers\Admin\OfficialStatusController;
```

dengan:

```php
use App\Http\Controllers\Admin\MountainController;
use App\Http\Controllers\Admin\MountainPhotoManagerController;
use App\Http\Controllers\Admin\OfficialStatusController;
```

2. Ganti:

```php
use App\Http\Controllers\Trails\MountainPageController;
use App\Http\Controllers\Trails\ReportThanksController;
```

dengan:

```php
use App\Http\Controllers\Trails\MountainPageController;
use App\Http\Controllers\Trails\MountainPhotoController;
use App\Http\Controllers\Trails\ReportThanksController;
```

3. Ganti:

```php
    Route::get('mountains/{mountain:slug}', MountainPageController::class)->name('mountains.show');
    // "Laporkan kesalahan data" (W8, ruling W8-R17): antrean cek ulang kurator, dibatasi laju per jam.
```

dengan:

```php
    Route::get('mountains/{mountain:slug}', MountainPageController::class)->name('mountains.show');
    // Foto gunung berlisensi (W8, DK-37): berkasnya untuk kepala halaman gunung.
    Route::get('mountains/{mountain:slug}/foto', MountainPhotoController::class)->name('mountains.photo');
    // "Laporkan kesalahan data" (W8, ruling W8-R17): antrean cek ulang kurator, dibatasi laju per jam.
```

4. Ganti:

```php
    Route::post('mountains/{mountain}/arsip', [MountainController::class, 'toggleArchive'])->name('mountains.archive');
    Route::get('trails', [TrailController::class, 'index'])->name('trails');
```

dengan:

```php
    Route::post('mountains/{mountain}/arsip', [MountainController::class, 'toggleArchive'])->name('mountains.archive');
    Route::get('mountains/{mountain}/foto', [MountainPhotoManagerController::class, 'edit'])->name('mountains.photo');
    Route::post('mountains/{mountain}/foto', [MountainPhotoManagerController::class, 'store'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,foto-gunung')->name('mountains.photo.store');
    Route::delete('mountains/{mountain}/foto', [MountainPhotoManagerController::class, 'destroy'])->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,foto-gunung')->name('mountains.photo.destroy');
    Route::get('trails', [TrailController::class, 'index'])->name('trails');
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
```

dengan:

```php
use App\Enums\OfficialStatusValue;
use App\Enums\PhotoLicense;
use App\Enums\PreparationStatus;
```

2. Ganti:

```php
use App\Models\HikingSession;
use App\Models\MountainFollow;
```

dengan:

```php
use App\Models\HikingSession;
use App\Models\Mountain;
use App\Models\MountainFollow;
```

3. Ganti:

```php
use App\Services\DataResmi\TerbitStatus;
use App\Services\PreparationService;
```

dengan:

```php
use App\Services\DataResmi\TerbitStatus;
use App\Services\Mountains\FotoGunung;
use App\Services\PreparationService;
```

4. Ganti:

```php
    /**
     * Penilaian kecocokan W3 (acuan 05): Gede via Cibodas untuk trip camping Budi, jadi
```

dengan:

```php
    /**
     * Halaman gunung dengan foto berlisensi (W8, DK-37): kepala halaman foto dengan atribusi di
     * pojoknya. Fotonya ilustrasi buatan test ini, berlisensi milik Memount dan berpenanda DATA
     * CONTOH; berkasnya disalin ke _pratinjau karena server statis tidak menjalankan rute foto.
     */
    public function test_gunung_berfoto(): void
    {
        Storage::fake(config('filesystems.mountain_photos_disk'));
        $this->sebagaiBudi();

        $gambar = imagecreatetruecolor(1600, 900);
        for ($y = 0; $y < 900; $y++) {
            imageline($gambar, 0, $y, 1599, $y, imagecolorallocate($gambar, 120 + intdiv($y, 12), 160 + intdiv($y, 15), 190 + intdiv($y, 30)));
        }
        imagefilledpolygon($gambar, [0, 900, 420, 380, 640, 520, 980, 210, 1600, 760, 1600, 900], imagecolorallocate($gambar, 46, 82, 58));
        imagefilledpolygon($gambar, [0, 900, 300, 640, 760, 700, 1200, 560, 1600, 820, 1600, 900], imagecolorallocate($gambar, 30, 58, 40));
        ob_start();
        imagejpeg($gambar, null, 85);
        $jpeg = (string) ob_get_clean();
        imagedestroy($gambar);

        $gede = Mountain::where('slug', 'gunung-gede')->firstOrFail();
        app(FotoGunung::class)->simpan($gede, UploadedFile::fake()->createWithContent('ilustrasi.jpg', $jpeg), [
            'creator' => 'DATA CONTOH',
            'license' => PhotoLicense::OWN->value,
            'source_url' => null,
            'attribution' => 'DATA CONTOH, ilustrasi pratinjau, milik Memount',
            'alt_text' => 'Ilustrasi lereng gunung berhutan di bawah langit pagi.',
        ], User::factory()->create(['role' => UserRole::ADMIN->value]));

        $this->tulisPratinjau('gunung-berfoto', $this->get(self::ASAL.'/mountains/gunung-gede'), 'Mountains/Show');

        File::put(public_path('_pratinjau/foto-gunung-contoh.jpg'), $jpeg);
        $halaman = public_path('_pratinjau/gunung-berfoto.html');
        $asli = (string) json_encode(route('mountains.photo', ['mountain' => $gede, 'v' => substr((string) $gede->photo()->value('sha256'), 0, 12)]), JSON_UNESCAPED_SLASHES);
        $isi = str_replace([trim($asli, '"'), str_replace('/', '\/', trim($asli, '"'))], '/_pratinjau/foto-gunung-contoh.jpg', File::get($halaman));
        $this->assertStringContainsString('/_pratinjau/foto-gunung-contoh.jpg', $isi, 'Alamat foto tidak ditemukan di halaman pratinjau.');
        File::put($halaman, $isi);
    }

    /**
     * Penilaian kecocokan W3 (acuan 05): Gede via Cibodas untuk trip camping Budi, jadi
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; keluaran Wayfinder memuat `admin.mountains.photo.store` dan `mountains.photo`; tsc tanpa keluaran
galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Enums/PhotoLicense.php app/Http/Controllers/Admin/MountainPhotoManagerController.php app/Http/Controllers/Trails/MountainPhotoController.php app/Http/Requests/Admin/SimpanFotoGunungRequest.php app/Http/Resources/Admin/MountainRowResource.php app/Models/Mountain.php app/Models/MountainPhoto.php app/Services/Mountains/FotoGunung.php app/Services/Mountains/HalamanGunung.php app/Support/PenjagaDataContoh.php config/filesystems.php config/hiking.php database/factories/MountainPhotoFactory.php database/migrations/2026_10_09_000500_create_mountain_photos_table.php routes/web.php tests/Feature/MountainPhotoLicenseTest.php tests/Pratinjau/HalamanPratinjauTest.php tests/Spatial/MountainPhotoSchemaTest.php`, lalu `php artisan test --compact --filter='MountainPhotoLicense|InertiaLinkRegistry|RepoPolicyFiles|AdminDataManagement'`
Expected: PASS. Pada simulasi rencana: 36 test, 36 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.231 test, 2.229 lulus, 2 dilewati (sebelum tugas: 2.221).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 42 test lulus (sebelum tugas: 41; halaman baru `gunung-berfoto`).

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 12: Kolom kandidat tanpa geometri dan penjaga km pos

Titipan W3 (I7, T4-M5, artefak `hasGeometry`, T6-M8, m11); ruling W8-R20 dan W8-R21; R-024. Kandidat Jelajah,
rekomendasi, simpanan, dan "Jalur lain untuk Anda" memilih kolom eksplisit tanpa `geometry`
(`Trail::KOLOM_TANPA_GEOMETRI`, `scopeTanpaGeometri()`), termasuk relasi `results.trail` yang dimuat halaman hasil
rekomendasi. `Trail::hasGeometry()` mengembalikan `null` bila koneksi tidak dapat menjawab (SQLite), sehingga syarat dasar
di "Cocokkah untuk saya?" tidak lagi menyebut garis peta terpenuhi tanpa ada yang memeriksanya. `PosSepanjangJalur::saring()`
tidak memberi km pada pos yang lebih jauh dari 150 m dari garisnya (`map.batas_jarak_pos_m`), dan tidak memberi km sama
sekali bila km pos berikutnya mundur lebih dari `TOLERANSI_KM` (0,05 km).

**Files:**
- Modify: `app/Http/Controllers/Recommendations/RecommendationController.php`, `app/Models/Trail.php`, `app/Services/Admin/DataHealthSummary.php`, `app/Services/ConsiderationService.php`, `app/Services/RouteFitService.php`, `app/Services/Trails/PenilaianKecocokan.php`, `app/Services/Trails/PosSepanjangJalur.php`, `config/hiking.php`
- Test: `tests/Feature/FitAssessmentPageTest.php`, `tests/Feature/TrailCandidateColumnsTest.php` (baru), `tests/Spatial/PosSepanjangJalurTest.php`, `tests/Spatial/RecommendationGeometryExclusionTest.php`, `tests/Spatial/TrailCandidateColumnsTest.php` (baru, PostgreSQL), `tests/Unit/PosSepanjangJalurGuardTest.php` (baru)

**Interfaces:**
- Consumes: `Trail::scopeWithPublishabilityData()` dan `spatialSupported()` (W3), `ConsiderationService`, `RouteFitService`, `RecommendationController`, `PenilaianKecocokan`, `DataHealthSummary` (W3 sampai W5), `PosSepanjangJalur::km()` (W3).
- Produces:
  - `Trail::KOLOM_TANPA_GEOMETRI` (larik nama kolom `trails` selain `geometry`), `Trail::scopeTanpaGeometri(Builder): Builder`, `Trail::hasGeometry(): ?bool`;
  - `PosSepanjangJalur::saring(array $baris, float $batasJarakMeter): array<int, float>` (statis), `PosSepanjangJalur::TOLERANSI_KM` (0,05);
  - config `hiking.map.batas_jarak_pos_m` (150).

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction TrailCandidateColumnsTest` dan `... make:test --phpunit --unit --no-interaction PosSepanjangJalurGuardTest`, lalu isinya diganti persis. Berkas lain, `tests/Spatial/TrailCandidateColumnsTest.php`, dibuat langsung. `FitAssessmentPageTest`, `PosSepanjangJalurTest`, dan
`RecommendationGeometryExclusionTest` diperbarui.

Buat `tests/Feature/TrailCandidateColumnsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\RouteFitService;
use App\Services\Trails\TrailSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Kandidat jalur memilih kolomnya sendiri, tanpa garis rute (titipan W3 I7 dan T4-M5; ruling
 * W8-R20). Di SQLite kolom geometry memang tidak ada, jadi yang dibuktikan di sini adalah bentuk
 * kuerinya: daftar kolom bernama, bukan "trails.*". Bahwa kolom geografi benar-benar tidak terbawa
 * dibuktikan suite Spatial (TrailCandidateColumnsTest di tests/Spatial).
 */
class TrailCandidateColumnsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kolom baru di tabel trails harus masuk daftar ini, supaya tidak hilang diam-diam dari
     * kandidat Jelajah dan rekomendasi.
     */
    public function test_the_column_list_is_the_schema_without_geometry(): void
    {
        $this->assertEqualsCanonicalizing(Schema::getColumnListing('trails'), Trail::KOLOM_TANPA_GEOMETRI);
    }

    public function test_the_candidate_scope_names_every_column(): void
    {
        $sql = Trail::query()->published()->withPublishabilityData()->toSql();

        $this->assertStringNotContainsString('"trails".*', $sql);
        $this->assertStringStartsWith('select "trails"."id", "trails"."mountain_id"', $sql);

        foreach (Trail::KOLOM_TANPA_GEOMETRI as $kolom) {
            $this->assertStringContainsString('"trails"."'.$kolom.'"', $sql);
        }
    }

    /**
     * Jelajah, rekomendasi, dan simpanan: setiap kueri yang memuat baris jalur memilih kolom
     * bernama. "Jalur lain untuk Anda" memakai scope kandidat yang sama dengan rekomendasi.
     */
    public function test_explore_recommendations_and_saved_trails_load_named_columns(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id]);
        $jalur = Trail::factory()->published()->count(3)->create();
        foreach ($jalur as $j) {
            TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $j->id]);
        }

        DB::enableQueryLog();
        TrailSearch::dariInput([])->kandidat(10);
        app(RouteFitService::class)->recommend($user->fresh(), $goal);
        app(ConsiderationService::class)->simpanan($user);
        app(ConsiderationService::class)->forUser($user);
        $kueri = collect(DB::getQueryLog())->pluck('query')->map(fn (string $q) => self::tanpaSubkueri($q))->filter(fn (string $q) => preg_match('/ from "trails"( |$)/', $q) === 1)->values();
        DB::disableQueryLog();

        $this->assertGreaterThanOrEqual(4, $kueri->count(), 'Keempat jalur pemuatan kandidat harus terukur.');

        foreach ($kueri as $q) {
            $this->assertStringStartsWith('select "trails"."id"', $q, 'Kueri kandidat memuat semua kolom: '.$q);
        }
    }

    /**
     * Kueri tingkat atas saja: isi tanda kurung (subkueri hitungan, exists) dibuang.
     */
    private static function tanpaSubkueri(string $kueri): string
    {
        do {
            $kueri = (string) preg_replace('/\([^()]*\)/', '', $kueri, -1, $jumlah);
        } while ($jumlah > 0);

        return $kueri;
    }
}
```

Buat `tests/Spatial/TrailCandidateColumnsTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\ExperienceLevel;
use App\Models\Checkpoint;
use App\Models\DataSource;
use App\Models\HikingGoal;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\RouteFitService;
use App\Services\Trails\TrailSearch;
use Illuminate\Support\Facades\Schema;

/**
 * Kandidat jalur tanpa garis rute di PostgreSQL (R-024; titipan W3 I7 dan T4-M5; ruling W8-R20):
 * kolom geografi benar-benar tidak terbawa ke Jelajah, rekomendasi, dan simpanan, sedangkan
 * penanda has_geometry tetap ada untuk gerbang kelengkapan.
 */
class TrailCandidateColumnsTest extends SpatialTestCase
{
    public function test_the_column_list_is_the_schema_without_geometry(): void
    {
        $kolom = Schema::getColumnListing('trails');

        $this->assertContains('geometry', $kolom);
        $this->assertEqualsCanonicalizing(array_values(array_diff($kolom, ['geometry'])), Trail::KOLOM_TANPA_GEOMETRI);
    }

    public function test_candidates_carry_the_flag_but_not_the_line(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id]);
        // Geometri ditulis sebelum terbit, pola jalurTerbit di TrailSearchKeywordTest (ruling W3-F6).
        $jalur = Trail::factory()->easy()->create(['data_source_id' => DataSource::factory()->create()->id]);
        $jalur->writeLineString('geometry', [[106.98, -6.78], [106.99, -6.79]]);
        Checkpoint::factory()->for($jalur)->create(['sequence' => 1]);
        OfficialStatus::factory()->create(['statusable_type' => $jalur->getMorphClass(), 'statusable_id' => $jalur->getKey()]);
        $jalur->fresh()->update(['is_published' => true]);
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $jalur->id]);

        $kandidat = TrailSearch::dariInput([])->kandidat(10)->firstWhere('id', $jalur->id);
        $this->assertNotNull($kandidat);
        $this->assertArrayNotHasKey('geometry', $kandidat->getAttributes());
        $this->assertTrue($kandidat->hasGeometry());

        $run = app(RouteFitService::class)->recommend($user->fresh(), $goal);
        foreach ($run->results as $hasil) {
            $this->assertArrayNotHasKey('geometry', $hasil->trail->getAttributes());
        }

        $simpanan = app(ConsiderationService::class)->simpanan($user)->first();
        $this->assertArrayNotHasKey('geometry', $simpanan->trail->getAttributes());
        $this->assertArrayNotHasKey('geometry', app(ConsiderationService::class)->forUser($user)->first()->getAttributes());
    }
}
```

Buat `tests/Unit/PosSepanjangJalurGuardTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\Trails\PosSepanjangJalur;
use PHPUnit\Framework\TestCase;

/**
 * Penjaga km pos sepanjang garis rute (titipan W3 T6-M8 dan m11; ruling W8-R21), tanpa basis data:
 * baris yang diberikan sudah urut menurut urutan pos, persis keluaran kueri PostGIS-nya. Angka yang
 * tampak pasti tetapi keliru lebih buruk daripada tanpa angka (R-005, R-007).
 */
class PosSepanjangJalurGuardTest extends TestCase
{
    /**
     * @return object{id: int, km: float|null, jarak_m: float|null}
     */
    private static function pos(int $id, ?float $km, ?float $jarak = 10.0): object
    {
        return (object) ['id' => $id, 'km' => $km, 'jarak_m' => $jarak];
    }

    public function test_posts_on_the_line_get_their_distance(): void
    {
        $this->assertSame([1 => 0.0, 2 => 2.4, 3 => 5.1], PosSepanjangJalur::saring([self::pos(1, 0.0), self::pos(2, 2.36), self::pos(3, 5.12)], 150));
    }

    public function test_a_post_far_from_the_line_gets_no_distance(): void
    {
        $this->assertSame([1 => 0.0, 3 => 5.1], PosSepanjangJalur::saring([self::pos(1, 0.0), self::pos(2, 2.4, 420.0), self::pos(3, 5.12)], 150));
    }

    public function test_a_backwards_post_withholds_every_distance(): void
    {
        $this->assertSame([], PosSepanjangJalur::saring([self::pos(1, 0.0), self::pos(2, 4.0), self::pos(3, 1.2)], 150));
    }

    /**
     * Dua pos di titik yang sama, atau selisih proyeksi di bawah kelonggaran, bukan urutan mundur.
     */
    public function test_equal_or_nearly_equal_distances_are_not_backwards(): void
    {
        $this->assertSame([1 => 1.0, 2 => 1.0, 3 => 1.0], PosSepanjangJalur::saring([self::pos(1, 1.0), self::pos(2, 1.0), self::pos(3, 0.97)], 150));
    }

    public function test_a_post_without_a_projection_is_skipped(): void
    {
        $this->assertSame([2 => 1.5], PosSepanjangJalur::saring([self::pos(1, null), self::pos(2, 1.5), self::pos(3, 2.0, null)], 150));
    }
}
```

`tests/Feature/FitAssessmentPageTest.php`:

1. Ganti:

```php
     */
    public function test_the_condition_list_never_marks_an_unknown_state_with_a_checkmark(): void
```

dengan:

```php
     */
    /**
     * Garis peta hanya disebut bila koneksi dapat memeriksanya (titipan W3 artefak hasGeometry,
     * ruling W8-R20). Di SQLite kolom geometri tidak ada, jadi syarat data kritis tidak menyebut
     * garis peta sebagai terpenuhi, sedangkan rincian jalur yang sama menulis garis rutenya belum
     * ada.
     */
    public function test_the_critical_data_condition_never_claims_a_map_line_nobody_checked(): void
    {
        $syarat = $this->halaman($this->pendaki(), $this->jalurDenganStatus(OfficialStatusValue::OPEN))->viewData('page')['props']['syaratDasar'][1];

        $this->assertSame('Data kritis jalur lengkap: sumber, jarak, tanjakan, durasi, pos, dan status resmi', $syarat['teks']);
        $this->assertStringNotContainsString('garis peta', $syarat['teks']);
    }

    public function test_the_condition_list_never_marks_an_unknown_state_with_a_checkmark(): void
```

`tests/Spatial/PosSepanjangJalurTest.php`:

1. Ganti:

```php
    }

    public function test_one_query_serves_every_post(): void
```

dengan:

```php
    }

    /**
     * Pos 400 m dari garis tidak diberi km (ruling W8-R21): proyeksinya ke garis tidak bermakna.
     */
    public function test_a_post_far_from_the_line_gets_no_distance(): void
    {
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', [[110.0000, -7.0000], [110.0000, -7.0200]]);
        $dekat = Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        $dekat->setCoordinates(-7.0000, 110.0000);
        $jauh = Checkpoint::factory()->for($trail)->create(['sequence' => 2]);
        $jauh->setCoordinates(-7.0100, 110.0036);

        $km = app(PosSepanjangJalur::class)->km($trail->fresh());

        $this->assertArrayHasKey($dekat->id, $km);
        $this->assertArrayNotHasKey($jauh->id, $km);
    }

    /**
     * Urutan pos yang berlawanan dengan arah garis membuat km mundur: tidak ada km sama sekali.
     */
    public function test_posts_running_against_the_line_get_no_distance(): void
    {
        $trail = Trail::factory()->create();
        $trail->writeLineString('geometry', [[110.0000, -7.0000], [110.0000, -7.0200]]);
        $pertama = Checkpoint::factory()->for($trail)->create(['sequence' => 1]);
        $pertama->setCoordinates(-7.0180, 110.0000);
        $kedua = Checkpoint::factory()->for($trail)->create(['sequence' => 2]);
        $kedua->setCoordinates(-7.0020, 110.0000);

        $this->assertSame([], app(PosSepanjangJalur::class)->km($trail->fresh()));
    }

    public function test_one_query_serves_every_post(): void
```

`tests/Spatial/RecommendationGeometryExclusionTest.php`:

1. Ganti:

```php
 * jalurnya tidak lengkap. Geometri tidak dapat dipakai di sana: kolomnya tidak ada,
 * `hasGeometry()` mengembalikan true tanpa syarat, dan `scopeDataComplete()` melepas
 * klausa `whereNotNull('geometry')`.
```

dengan:

```php
 * jalurnya tidak lengkap. Geometri tidak dapat dipakai di sana: kolomnya tidak ada,
 * `hasGeometry()` menjawab null (tidak dapat diperiksa), dan `scopeDataComplete()` melepas
 * klausa `whereNotNull('geometry')`.
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrailCandidateColumns|PosSepanjangJalur|FitAssessmentPage'`
Expected: FAIL. Pada simulasi rencana: 35 test, 19 lulus, 3 gagal, 6 error, 7 dilewati. Yang merah: ketiga test `TrailCandidateColumnsTest` (konstanta dan scope belum ada,
kandidat masih `trails.*`), kelima test `PosSepanjangJalurGuardTest` (`saring()` belum ada), dan
`FitAssessmentPageTest::test_the_critical_data_condition_never_claims_a_map_line_nobody_checked`. 7 test Spatial
melewati dirinya tanpa `SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.**

`app/Http/Controllers/Recommendations/RecommendationController.php`:

1. Ganti:

```php
        abort_unless((int) $run->user_id === (int) $request->user()->id, 403);

        $run->load('results.trail.mountain', 'hikingGoal');

        $results = $run->results;
```

dengan:

```php
        abort_unless((int) $run->user_id === (int) $request->user()->id, 403);

        // Hasil rekomendasi tanpa garis rute (titipan W3 I7, ruling W8-R20).
        $run->load(['results.trail' => fn ($query) => $query->tanpaGeometri(), 'results.trail.mountain', 'hikingGoal']);

        $results = $run->results;
```

`app/Models/Trail.php`:

1. Ganti:

```php
    use HasSpatialColumns;

    protected function casts(): array
```

dengan:

```php
    use HasSpatialColumns;

    /**
     * Kolom tabel trails selain geometry (titipan W3 I7 dan T4-M5; ruling W8-R20). Kandidat
     * Jelajah, rekomendasi, simpanan, dan "Jalur lain untuk Anda" tidak pernah memuat garis rute:
     * di PostgreSQL kolom geografi itu WKB utuh per baris, padahal daftar hanya butuh penanda
     * ada-tidaknya (has_geometry). TrailCandidateColumnsTest menjaga daftar ini sama dengan skema
     * di SQLite dan PostgreSQL, supaya kolom baru tidak hilang diam-diam dari kandidat.
     *
     * @var list<string>
     */
    public const KOLOM_TANPA_GEOMETRI = [
        'id', 'mountain_id', 'name', 'slug', 'description', 'distance_km', 'elevation_gain_m', 'elevation_loss_m',
        'elevation_profile', 'estimated_duration_minutes', 'technical_demand', 'terrain_character',
        'navigation_complexity', 'water_availability', 'camping_available', 'starting_point', 'weather_adm4_code',
        'weather_reference_area', 'data_source_id', 'contributed_by', 'contributed_credential_id', 'contributed_at',
        'is_published', 'archived_at', 'created_at', 'updated_at',
    ];

    protected function casts(): array
```

2. Ganti:

```php
     *
     * Di SQLite kolomnya tidak ada, sehingga syarat ini dianggap terpenuhi. Itulah
     * sebabnya bukti geometri hanya sah dari suite spasial.
     */
    public function hasGeometry(): bool
    {
        if (! static::spatialSupported()) {
            return true;
        }
```

dengan:

```php
     *
     * Null berarti koneksi ini tidak dapat menjawabnya: di SQLite kolomnya tidak ada (titipan
     * W3 artefak hasGeometry, ruling W8-R20). Gerbang publikasi dan kelengkapan hanya menolak
     * jawaban false, jadi perilakunya di SQLite tetap; yang berubah, halaman tidak lagi
     * menyebut garis peta sebagai terpenuhi ketika tidak ada yang memeriksanya. Bukti geometri
     * hanya sah dari suite spasial.
     */
    public function hasGeometry(): ?bool
    {
        if (! static::spatialSupported()) {
            return null;
        }
```

3. Ganti:

```php
    {
        $query->withCount(['checkpoints', 'statusTerlihat']);
```

dengan:

```php
    {
        // Kolom eksplisit tanpa garis rute, kecuali pemanggil sudah memilih kolomnya sendiri.
        if ($query->getQuery()->columns === null) {
            $query->tanpaGeometri();
        }

        $query->withCount(['checkpoints', 'statusTerlihat']);
```

4. Ganti:

```php
        if (static::spatialSupported()) {
            $query->addSelect(['*', DB::raw('(geometry IS NOT NULL) AS has_geometry')]);
        }
```

dengan:

```php
        if (static::spatialSupported()) {
            $query->addSelect(DB::raw('(trails.geometry IS NOT NULL) AS has_geometry'));
        }
```

5. Ganti:

```php
        }

        return $query;
    }

    /**
     * R-008/R-022: jalur yang kehilangan data kritis tidak boleh masuk rekomendasi.
```

dengan:

```php
        }

        return $query;
    }

    /**
     * Semua kolom jalur kecuali geometry (KOLOM_TANPA_GEOMETRI).
     */
    public function scopeTanpaGeometri(Builder $query): Builder
    {
        return $query->select(array_map(fn (string $kolom) => 'trails.'.$kolom, self::KOLOM_TANPA_GEOMETRI));
    }

    /**
     * R-008/R-022: jalur yang kehilangan data kritis tidak boleh masuk rekomendasi.
```

6. Ganti:

```php
        }

        if (! $this->hasGeometry()) {
            $menunggu[] = 'Jalur pada peta';
```

dengan:

```php
        }

        if ($this->hasGeometry() === false) {
            $menunggu[] = 'Jalur pada peta';
```

7. Ganti:

```php
        // Geometri hanya dapat diperiksa pada koneksi berkemampuan PostGIS; pada
        // SQLite kolomnya memang tidak ada sehingga syarat ini dilewati.
        if (! $this->hasGeometry()) {
            $missing[] = 'Geometri jalur belum tersedia.';
```

dengan:

```php
        // Geometri hanya dapat diperiksa pada koneksi berkemampuan PostGIS; pada
        // SQLite kolomnya memang tidak ada (null) sehingga syarat ini dilewati.
        if ($this->hasGeometry() === false) {
            $missing[] = 'Geometri jalur belum tersedia.';
```

`app/Services/Admin/DataHealthSummary.php`:

1. Ganti:

```php
            'tanpaGeometri' => Trail::spatialSupported()
                ? $belumLengkap->reject(fn (array $b) => $b['jalur']->hasGeometry())->count()
                : null,
```

dengan:

```php
            'tanpaGeometri' => Trail::spatialSupported()
                ? $belumLengkap->reject(fn (array $b) => $b['jalur']->hasGeometry() === true)->count()
                : null,
```

`app/Services/ConsiderationService.php`:

1. Ganti:

```php
        return Trail::query()
            ->active()
```

dengan:

```php
        return Trail::query()
            ->tanpaGeometri()
            ->active()
```

2. Ganti:

```php
        return $this->kueriSimpananAktif($user)
            ->with('trail.mountain')
            ->orderByDesc('created_at')
```

dengan:

```php
        return $this->kueriSimpananAktif($user)
            // Kandidat simpanan tanpa garis rute (titipan W3 I7, ruling W8-R20).
            ->with(['trail' => fn ($query) => $query->tanpaGeometri(), 'trail.mountain'])
            ->orderByDesc('created_at')
```

`app/Services/RouteFitService.php`:

1. Ganti:

```php
            }

            return $run->load('results.trail.mountain');
        });
```

dengan:

```php
            }

            // Hasil rekomendasi tanpa garis rute (titipan W3 I7, ruling W8-R20).
            return $run->load(['results.trail' => fn ($query) => $query->tanpaGeometri(), 'results.trail.mountain']);
        });
```

`app/Services/Trails/PenilaianKecocokan.php`:

1. Ganti:

```php
            ],
            'syaratDasar' => $this->syaratDasar($hasil, $goal, $status, $ringkasStatus),
            // P-2: status Dibatasi/belum diketahui, pembatasan segmen, dan jendela izin,
```

dengan:

```php
            ],
            'syaratDasar' => $this->syaratDasar($hasil, $goal, $status, $ringkasStatus, $trail->hasGeometry()),
            // P-2: status Dibatasi/belum diketahui, pembatasan segmen, dan jendela izin,
```

2. Ganti:

```php
     *
     * @param  array{sumber: array{jenis: string|null, label: string|null, nama: string|null}|null, waktu: string|null, awalanWaktu: string|null}  $ringkasStatus
```

dengan:

```php
     *
     * Garis peta hanya disebut bila koneksi ini dapat memeriksanya ($adaGaris bukan null, ruling
     * W8-R20): di SQLite tidak ada yang memeriksanya, jadi menyebutnya terpenuhi adalah klaim yang
     * tidak dimiliki sistem.
     *
     * @param  array{sumber: array{jenis: string|null, label: string|null, nama: string|null}|null, waktu: string|null, awalanWaktu: string|null}  $ringkasStatus
```

3. Ganti:

```php
     */
    private function syaratDasar(RouteFitResult $hasil, ?HikingGoal $goal, OfficialStatusValue $status, array $ringkasStatus): array
    {
```

dengan:

```php
     */
    private function syaratDasar(RouteFitResult $hasil, ?HikingGoal $goal, OfficialStatusValue $status, array $ringkasStatus, ?bool $adaGaris): array
    {
```

4. Ganti:

```php
            ['teks' => 'Status resmi tidak Tutup (sekarang '.$status->label().'). '.$sumberWaktu, 'keadaan' => $keadaanStatus],
            ['teks' => 'Data kritis jalur lengkap: sumber, jarak, tanjakan, durasi, pos, status resmi, dan garis peta', 'keadaan' => $gagal('trail_data_incomplete') ? 'gagal' : 'terpenuhi'],
        ];
```

dengan:

```php
            ['teks' => 'Status resmi tidak Tutup (sekarang '.$status->label().'). '.$sumberWaktu, 'keadaan' => $keadaanStatus],
            ['teks' => 'Data kritis jalur lengkap: sumber, jarak, tanjakan, durasi, pos, '.($adaGaris === null ? 'dan status resmi' : 'status resmi, dan garis peta'), 'keadaan' => $gagal('trail_data_incomplete') ? 'gagal' : 'terpenuhi'],
        ];
```

`app/Services/Trails/PosSepanjangJalur.php`:

1. Ganti:

```php
 * data tanpa PostGIS (suite SQLite) tidak menghasilkan angka apa pun.
 */
```

dengan:

```php
 * data tanpa PostGIS (suite SQLite) tidak menghasilkan angka apa pun.
 *
 * Dua penjaga (titipan W3 T6-M8 dan m11; ruling W8-R21): pos yang lebih jauh dari garis
 * daripada hiking.map.batas_jarak_pos_m tidak diberi km, karena proyeksinya ke garis tidak
 * bermakna; dan bila km pos mundur menurut urutan pos, seluruh daftar tidak diberi km, karena
 * garis atau koordinat pos keliru dan tidak diketahui yang mana.
 */
```

2. Ganti:

```php
{
    /**
```

dengan:

```php
{
    /** Kelonggaran proyeksi dan pembulatan sebelum km dianggap mundur. */
    public const TOLERANSI_KM = 0.05;

    /**
```

3. Ganti:

```php
            'SELECT c.id,
                    ST_LineLocatePoint(t.geometry::geometry, c.location::geometry) * ST_Length(t.geometry) / 1000 AS km
             FROM checkpoints c
             JOIN trails t ON t.id = c.trail_id
             WHERE c.trail_id = ? AND c.location IS NOT NULL AND t.geometry IS NOT NULL',
            [$trail->id]
```

dengan:

```php
            'SELECT c.id,
                    ST_LineLocatePoint(t.geometry::geometry, c.location::geometry) * ST_Length(t.geometry) / 1000 AS km,
                    ST_Distance(t.geometry, c.location) AS jarak_m
             FROM checkpoints c
             JOIN trails t ON t.id = c.trail_id
             WHERE c.trail_id = ? AND c.location IS NOT NULL AND t.geometry IS NOT NULL
             ORDER BY c.sequence, c.id',
            [$trail->id]
```

4. Ganti:

```php
        );

        $hasil = [];

        foreach ($baris as $b) {
            if ($b->km !== null) {
                $hasil[(int) $b->id] = round((float) $b->km, 1);
            }
        }
```

dengan:

```php
        );

        return self::saring($baris, (float) config('hiking.map.batas_jarak_pos_m'));
    }

    /**
     * Penjaga jarak dan kemonotonan atas baris yang sudah urut menurut urutan pos.
     *
     * @param  array<int, object>  $baris  setiap baris berisi id, km, dan jarak_m (meter dari garis)
     * @return array<int, float>
     */
    public static function saring(array $baris, float $batasJarakMeter): array
    {
        $hasil = [];
        $terjauh = null;

        foreach ($baris as $b) {
            if ($b->km === null || $b->jarak_m === null || (float) $b->jarak_m > $batasJarakMeter) {
                continue;
            }

            $km = (float) $b->km;

            if ($terjauh !== null && $km + self::TOLERANSI_KM < $terjauh) {
                return [];
            }

            $terjauh = max($terjauh ?? $km, $km);
            $hasil[(int) $b->id] = round($km, 1);
        }
```

`config/hiking.php`:

1. Ganti:

```php
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 17),
    ],
```

dengan:

```php
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 17),

        // Pos yang lebih jauh dari garis rutenya daripada ini tidak diberi km sepanjang jalur
        // (W8, ruling W8-R21): koordinat pos atau garisnya keliru. GPS ponsel di bawah tajuk
        // hutan meleset sampai sekitar 30 m, jadi 150 m adalah lima kali meleset wajar.
        'batas_jarak_pos_m' => 150,
    ],
```

- [ ] **Step 4: Build dan tipe.** Tidak ada TypeScript, rute, atau daftar rute bukan halaman React yang berubah; build
tidak perlu.

Run: `node_modules/.bin/tsc --noEmit`
Expected: tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Recommendations/RecommendationController.php app/Models/Trail.php app/Services/Admin/DataHealthSummary.php app/Services/ConsiderationService.php app/Services/RouteFitService.php app/Services/Trails/PenilaianKecocokan.php app/Services/Trails/PosSepanjangJalur.php config/hiking.php tests/Feature/FitAssessmentPageTest.php tests/Feature/TrailCandidateColumnsTest.php tests/Spatial/PosSepanjangJalurTest.php tests/Spatial/RecommendationGeometryExclusionTest.php tests/Spatial/TrailCandidateColumnsTest.php tests/Unit/PosSepanjangJalurGuardTest.php`, lalu `php artisan test --compact --filter='TrailCandidateColumns|PosSepanjangJalur|FitAssessmentPage'`
Expected: PASS. Pada simulasi rencana: 35 test, 28 lulus, 7 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.240 test, 2.238 lulus, 2 dilewati (sebelum tugas: 2.231).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 42 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 13: R-006 di skema jalur dan kurasi turunan

Titipan W1 dan W3 (default yang menyamar sebagai data, "Berkemah: Tidak" di `/b/`, kurasi `elevation_loss_m`, W3-R29);
butir izin akhir 6; ruling W8-R22 dan W8-R23; R-006, R-007; Konflik 9. Migrasi `2026_10_09_000600` membuat
`technical_demand`, `navigation_complexity`, dan `camping_available` di `trails` (dan `technical_demand` di
`trail_segments`) boleh kosong tanpa bawaan; nilai lama tidak diubah. Migrasi data `000700` mengosongkan
`elevation_loss_m` yang sama persis dengan tanjakan (salinan, bukan ukuran) dan tidak dapat dibalik. Isian admin
menawarkan "Belum diketahui"; Route Fit membaca kosong sebagai tidak diketahui, bukan Sedang atau "tanpa area kemah";
rincian jalur, tabel Bandingkan, dan halaman bagikan `/b/` menulis "Belum tersedia"; rincian menampilkan turunan hanya
bila tercatat. `TrailFactory`, `MvpDatasetSeeder`, dan `DemoSeeder` tidak lagi menyalin tanjakan ke turunan.

**Files:**
- Create: `database/migrations/2026_10_09_000600_make_trail_characteristics_nullable.php`, `database/migrations/2026_10_09_000700_clear_copied_elevation_loss_on_trails.php`
- Modify: `app/Http/Controllers/Admin/TrailController.php`, `app/Http/Requests/Admin/TrailRequest.php`, `app/Http/Resources/Admin/TrailAdminRowResource.php`, `app/Models/PreparationItem.php`, `app/Models/RecommendationResult.php`, `app/Services/RouteFit/CompatibilityScorer.php`, `app/Services/Trails/PenilaianKecocokan.php`, `app/Services/Trails/RouteComparisonTable.php`, `app/Services/Trails/TrailDetailPage.php`, `database/factories/TrailFactory.php`, `database/seeders/DemoSeeder.php`, `database/seeders/MvpDatasetSeeder.php`, `resources/js/components/admin/FormulirJalur.tsx`, `resources/views/public/trail.blade.php`
- Test: `tests/Feature/TrailDetailPageTest.php`, `tests/Feature/UnknownTrailCharacteristicsTest.php` (baru), `tests/Spatial/TrailCharacteristicsSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `CompatibilityScorer` (Route Fit, W3), `PenilaianKecocokan` (Tugas 12), `RouteComparisonTable` dan `TrailFigures::BELUM` (W3), `TrailDetailPage` (W3 sampai Tugas 10), formulir jalur admin `FormulirJalur.tsx` dan `TrailRequest` (W3).
- Produces:
  - kolom `trails.technical_demand`, `navigation_complexity`, `camping_available`, dan `trail_segments.technical_demand` yang boleh kosong tanpa bawaan; cast enum yang dapat `null` (`?TechnicalDemand`, `?NavigationComplexity`, `?bool`);
  - Route Fit: tuntutan teknis, kerumitan navigasi, dan area kemah yang kosong terbaca tidak diketahui (R-006, R-007);
  - `TrailFactory` dengan `elevation_loss_m` kosong; migrasi data `000700`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction UnknownTrailCharacteristicsTest`, lalu isinya diganti persis. Berkas lain, `tests/Spatial/TrailCharacteristicsSchemaTest.php`, dibuat langsung. `TrailDetailPageTest` diperbarui: pabrik tidak lagi
menyalin turunan, jadi rincian menampilkan dua angka sampai turunan tercatat (alasan di docblock).

Buat `tests/Feature/UnknownTrailCharacteristicsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompatibilityFactor;
use App\Enums\ExperienceLevel;
use App\Enums\RouteFitLabel;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\HikingGoal;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFit\FactorScore;
use App\Services\RouteFitService;
use App\Services\Trails\RouteComparisonTable;
use App\Services\Trails\TrailFigures;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * R-006 dan R-007 di skema jalur (titipan W1 dan W3; butir izin akhir 6; ruling W8-R22 dan W8-R23):
 * tuntutan teknis, kerumitan navigasi, dan area kemah yang tidak diisi tetap belum diketahui,
 * bukan Sedang atau "tidak ada"; Route Fit menilainya sebagai faktor yang belum diketahui; halaman
 * menulis "Belum tersedia"; dan turunan yang disalin dari tanjakan dikosongkan.
 */
class UnknownTrailCharacteristicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_columns_have_no_disguising_default(): void
    {
        $kolom = collect(Schema::getColumns('trails'))->keyBy('name');

        foreach (['technical_demand', 'navigation_complexity', 'camping_available'] as $nama) {
            $this->assertTrue($kolom[$nama]['nullable'], "{$nama} harus boleh kosong.");
            $this->assertNull($kolom[$nama]['default'], "{$nama} tidak boleh punya nilai bawaan.");
        }

        $segmen = collect(Schema::getColumns('trail_segments'))->keyBy('name');
        $this->assertTrue($segmen['technical_demand']['nullable']);
        $this->assertNull($segmen['technical_demand']['default']);
    }

    public function test_an_admin_trail_without_these_values_stays_unknown(): void
    {
        $gunung = Mountain::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))
            ->post(route('admin.trails.store'), ['mountain_id' => $gunung->id, 'name' => 'Jalur Baru', 'water_availability' => 'UNKNOWN', 'technical_demand' => '', 'navigation_complexity' => '', 'camping_available' => ''])
            ->assertSessionHasNoErrors();

        $jalur = Trail::sole();
        $this->assertNull($jalur->technical_demand);
        $this->assertNull($jalur->navigation_complexity);
        $this->assertNull($jalur->camping_available);
    }

    /**
     * Faktor kritis yang belum diketahui menahan label, persis aturan PRD §95 untuk data lain yang
     * belum ada; area kemah yang belum diketahui tidak dinilai "tidak memiliki area camping".
     */
    public function test_route_fit_reads_them_as_unknown_not_moderate(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $goal = HikingGoal::factory()->create(['user_id' => $user->id, 'trip_type' => TripType::CAMPING->value]);
        $jalur = Trail::factory()->published()->easy()->create(['technical_demand' => null, 'navigation_complexity' => null, 'camping_available' => null]);

        $hasil = app(RouteFitService::class)->evaluate($user->fresh(), $goal, $jalur->fresh());
        $faktor = collect($hasil->factors)->keyBy(fn (FactorScore $f) => $f->factor->value);

        $this->assertTrue($faktor[CompatibilityFactor::TECHNICAL_MATCH->value]->isUnknown);
        $this->assertTrue($faktor[CompatibilityFactor::NAVIGATION_MATCH->value]->isUnknown);
        $this->assertTrue($faktor[CompatibilityFactor::TRIP_PREFERENCE->value]->isUnknown);
        $this->assertStringNotContainsString('tidak memiliki area camping', $faktor[CompatibilityFactor::TRIP_PREFERENCE->value]->detail);
        $this->assertSame(RouteFitLabel::KURANG_COCOK, $hasil->label);
    }

    /**
     * "Berkemah: Tidak" hanya untuk jalur yang memang tercatat tanpa area kemah, juga di halaman
     * publik /b/ yang memakai tabel banding yang sama.
     */
    public function test_comparison_rows_say_unknown_instead_of_no(): void
    {
        $tanpaData = Trail::factory()->create(['camping_available' => null, 'technical_demand' => null]);
        $tidakAda = Trail::factory()->create(['camping_available' => false]);

        $baris = collect(app(RouteComparisonTable::class)->baris(Collection::make([$tanpaData->fresh(), $tidakAda->fresh()])))->keyBy('kunci');

        $this->assertSame([TrailFigures::BELUM, 'Tidak'], $baris['berkemah']['nilai']);
        $this->assertSame(TrailFigures::BELUM, $baris['teknis']['nilai'][0]);
    }

    public function test_trail_detail_says_unknown_and_shows_the_descent_only_when_recorded(): void
    {
        $pendaki = User::factory()->create();
        $tanpaData = Trail::factory()->published()->create(['camping_available' => null, 'navigation_complexity' => null, 'elevation_loss_m' => null]);
        $berturunan = Trail::factory()->published()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 120]);

        $this->actingAs($pendaki)->get(route('trails.show', $tanpaData))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('angkaNaik', 3)
            ->where('tantangan', fn ($baris) => collect($baris)->firstWhere('judul', 'Area kemah')['kata'] === TrailFigures::BELUM
                && collect($baris)->firstWhere('judul', 'Navigasi')['kata'] === TrailFigures::BELUM));

        $this->actingAs($pendaki)->get(route('trails.show', $berturunan))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('angkaNaik', 4)
            ->where('angkaNaik.2.label', 'Turunan')
            ->where('angkaNaik.2.nilai', '120 m'));
    }

    /**
     * Migrasi kurasi turunan: nilai yang sama persis dengan tanjakan adalah salinan dan
     * dikosongkan; turunan yang benar-benar diukur tidak tersentuh.
     */
    public function test_a_descent_copied_from_the_climb_is_cleared(): void
    {
        $salinan = Trail::factory()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 1650]);
        $ukuran = Trail::factory()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 140]);

        (require database_path('migrations/2026_10_09_000700_clear_copied_elevation_loss_on_trails.php'))->up();

        $this->assertNull(DB::table('trails')->where('id', $salinan->id)->value('elevation_loss_m'));
        $this->assertSame(140, (int) DB::table('trails')->where('id', $ukuran->id)->value('elevation_loss_m'));
    }
}
```

Buat `tests/Spatial/TrailCharacteristicsSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Models\Trail;
use Illuminate\Support\Facades\DB;

/**
 * Kolom karakteristik jalur di PostgreSQL sesudah migrasi R-006 (R-024; butir izin akhir 6; ruling
 * W8-R22 dan W8-R23): boleh kosong tanpa nilai bawaan, kolom geografi tidak tersentuh perubahan
 * kolom, dan kurasi turunan memakai perbandingan kolom yang sama seperti di SQLite.
 */
class TrailCharacteristicsSchemaTest extends SpatialTestCase
{
    public function test_the_columns_are_nullable_without_a_default(): void
    {
        $kolom = collect(DB::select(
            "SELECT table_name, column_name, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND ((table_name = 'trails' AND column_name IN ('technical_demand', 'navigation_complexity', 'camping_available'))
                 OR (table_name = 'trail_segments' AND column_name = 'technical_demand'))"
        ));

        $this->assertCount(4, $kolom);

        foreach ($kolom as $k) {
            $this->assertSame('YES', $k->is_nullable, "{$k->table_name}.{$k->column_name} harus boleh kosong.");
            $this->assertNull($k->column_default, "{$k->table_name}.{$k->column_name} tidak boleh punya nilai bawaan.");
        }

        // Perubahan kolom tidak menyentuh garis rute.
        $this->assertSame('geography', DB::selectOne("SELECT udt_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'trails' AND column_name = 'geometry'")->udt_name);
    }

    public function test_a_descent_copied_from_the_climb_is_cleared(): void
    {
        $salinan = Trail::factory()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 1650]);
        $ukuran = Trail::factory()->create(['elevation_gain_m' => 1650, 'elevation_loss_m' => 140]);

        (require database_path('migrations/2026_10_09_000700_clear_copied_elevation_loss_on_trails.php'))->up();

        $this->assertNull(DB::table('trails')->where('id', $salinan->id)->value('elevation_loss_m'));
        $this->assertSame(140, (int) DB::table('trails')->where('id', $ukuran->id)->value('elevation_loss_m'));
    }
}
```

`tests/Feature/TrailDetailPageTest.php`:

1. Ganti:

```php
    /**
     * Angka sekali jalan naik: jarak, tanjakan, dan lama naik. Turunan tidak ditulis karena
     * data pemilik menyimpan turunan pulang (ruling W3). Angka kosong ditulis apa adanya.
     */
```

dengan:

```php
    /**
     * Angka sekali jalan naik: jarak, tanjakan, dan lama naik. Turunan hanya ditulis bila
     * tercatat (W8-R23; sebelumnya ditiadakan karena data pemilik menyimpan salinan tanjakan,
     * ruling W3). Angka kosong ditulis apa adanya.
     */
```

2. Ganti:

```php
    {
        $jalur = $this->jalur(['elevation_loss_m' => 800]);
        $jalur->forceFill(['estimated_duration_minutes' => null])->saveQuietly();
```

dengan:

```php
    {
        $jalur = $this->jalur(['elevation_loss_m' => null]);
        $jalur->forceFill(['estimated_duration_minutes' => null])->saveQuietly();
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='UnknownTrailCharacteristics|TrailDetailPage|DemoSeeder|RouteFit|Recommendation'`
Expected: FAIL. Pada simulasi rencana: 85 test, 76 lulus, 2 gagal, 4 error, 3 dilewati. Yang merah: keenam test `UnknownTrailCharacteristicsTest` (kolom masih wajib berbawaan,
isian admin masih menolak kosong, dan migrasi `000700` belum ada). 3 test Spatial melewati dirinya tanpa
`SPATIAL_TEST_DSN`.

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:migration --no-interaction make_trail_characteristics_nullable` dan `... make:migration --no-interaction clear_copied_elevation_loss_on_trails` (ganti nama berkas migrasi hasilnya menjadi `2026_10_09_000600_make_trail_characteristics_nullable.php` dan `2026_10_09_000700_clear_copied_elevation_loss_on_trails.php`), lalu isinya diganti persis.

Buat `database/migrations/2026_10_09_000600_make_trail_characteristics_nullable.php`:

```php
<?php

use App\Enums\NavigationComplexity;
use App\Enums\TechnicalDemand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * R-006 dan R-007 di skema (titipan W1 dan W3; butir izin akhir 6; ruling W8-R22): tuntutan
 * teknis, kerumitan navigasi, dan ketersediaan area kemah tidak lagi berisi nilai bawaan yang
 * menyamar sebagai data. Sebelumnya jalur yang dibuat tanpa nilai itu tercatat Sedang, Sedang,
 * dan tanpa area kemah, lalu tampil "Berkemah: Tidak" di halaman publik /b/. Sesudahnya kolom
 * yang tidak diisi bernilai kosong, dan layar menulis "Belum tersedia".
 *
 * Nilai yang sudah ada tidak diubah: migrasi tidak dapat membedakan nilai kurasi dari nilai
 * bawaan, jadi kurasinya keputusan pemilik (butir izin akhir 6). Segmen jalur ikut, dengan
 * alasan yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trails', function (Blueprint $table) {
            $table->string('technical_demand')->nullable()->default(null)->change();
            $table->string('navigation_complexity')->nullable()->default(null)->change();
            $table->boolean('camping_available')->nullable()->default(null)->change();
        });

        Schema::table('trail_segments', function (Blueprint $table) {
            $table->string('technical_demand')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('trails')->whereNull('technical_demand')->update(['technical_demand' => TechnicalDemand::MODERATE->value]);
        DB::table('trails')->whereNull('navigation_complexity')->update(['navigation_complexity' => NavigationComplexity::MODERATE->value]);
        DB::table('trails')->whereNull('camping_available')->update(['camping_available' => false]);
        DB::table('trail_segments')->whereNull('technical_demand')->update(['technical_demand' => TechnicalDemand::MODERATE->value]);

        Schema::table('trails', function (Blueprint $table) {
            $table->string('technical_demand')->nullable(false)->default(TechnicalDemand::MODERATE->value)->change();
            $table->string('navigation_complexity')->nullable(false)->default(NavigationComplexity::MODERATE->value)->change();
            $table->boolean('camping_available')->nullable(false)->default(false)->change();
        });

        Schema::table('trail_segments', function (Blueprint $table) {
            $table->string('technical_demand')->nullable(false)->default(TechnicalDemand::MODERATE->value)->change();
        });
    }
};
```

Buat `database/migrations/2026_10_09_000700_clear_copied_elevation_loss_on_trails.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kurasi elevation_loss_m (titipan W3-R29; butir izin akhir 6; ruling W8-R23). Seeder dataset dan
 * data contoh menyalin tanjakan ke kolom turunan, padahal turunan sekali jalan naik dari basecamp
 * ke puncak selalu lebih kecil daripada tanjakannya: nilai yang sama persis adalah salinan, bukan
 * ukuran. Baris itu dikosongkan supaya rincian jalur tidak menampilkan turunan karangan (R-007).
 * Turunan dari GPX (TrailGeometryController::applyElevation) dan isian kurator yang berbeda dari
 * tanjakan tidak tersentuh.
 *
 * Tidak dapat dibalik: nilai salinannya memang tidak boleh kembali.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('trails')
            ->whereNotNull('elevation_loss_m')
            ->whereColumn('elevation_loss_m', 'elevation_gain_m')
            ->update(['elevation_loss_m' => null]);
    }

    public function down(): void
    {
        // Sengaja kosong: salinan tanjakan tidak dipulihkan.
    }
};
```

`app/Http/Controllers/Admin/TrailController.php`:

1. Ganti:

```php
            'estimated_duration_minutes' => $bilangan('estimated_duration_minutes'),
            'technical_demand' => $data['technical_demand'],
            'navigation_complexity' => $data['navigation_complexity'],
            'water_availability' => $data['water_availability'],
            'terrain_character' => array_values($data['terrain_character'] ?? []),
            'camping_available' => (bool) ($data['camping_available'] ?? false),
            'starting_point' => $data['starting_point'] ?? null,
```

dengan:

```php
            'estimated_duration_minutes' => $bilangan('estimated_duration_minutes'),
            'technical_demand' => $data['technical_demand'] ?? null,
            'navigation_complexity' => $data['navigation_complexity'] ?? null,
            'water_availability' => $data['water_availability'],
            'terrain_character' => array_values($data['terrain_character'] ?? []),
            'camping_available' => isset($data['camping_available']) ? (bool) $data['camping_available'] : null,
            'starting_point' => $data['starting_point'] ?? null,
```

`app/Http/Requests/Admin/TrailRequest.php`:

1. Ganti:

```php
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:20160'],
            'technical_demand' => ['required', new Enum(TechnicalDemand::class)],
            'navigation_complexity' => ['required', new Enum(NavigationComplexity::class)],
            'water_availability' => ['required', new Enum(WaterAvailability::class)],
```

dengan:

```php
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:20160'],
            // Kosong berarti belum diketahui (R-006, W8-R22), bukan Sedang.
            'technical_demand' => ['nullable', new Enum(TechnicalDemand::class)],
            'navigation_complexity' => ['nullable', new Enum(NavigationComplexity::class)],
            'water_availability' => ['required', new Enum(WaterAvailability::class)],
```

2. Ganti:

```php
            'terrain_character.*' => [Rule::enum(TerrainCharacter::class)],
            'camping_available' => ['boolean'],
            'starting_point' => ['nullable', 'string', 'max:160'],
```

dengan:

```php
            'terrain_character.*' => [Rule::enum(TerrainCharacter::class)],
            'camping_available' => ['nullable', 'boolean'],
            'starting_point' => ['nullable', 'string', 'max:160'],
```

`app/Http/Resources/Admin/TrailAdminRowResource.php`:

1. Ganti:

```php
                'estimated_duration_minutes' => $this->estimated_duration_minutes !== null ? (string) $this->estimated_duration_minutes : '',
                'technical_demand' => $this->technical_demand->value,
                'navigation_complexity' => $this->navigation_complexity->value,
                'water_availability' => $this->water_availability->value,
                'terrain_character' => array_values($this->terrain_character ?? []),
                'camping_available' => (bool) $this->camping_available,
                'starting_point' => $this->starting_point ?? '',
```

dengan:

```php
                'estimated_duration_minutes' => $this->estimated_duration_minutes !== null ? (string) $this->estimated_duration_minutes : '',
                'technical_demand' => $this->technical_demand?->value ?? '',
                'navigation_complexity' => $this->navigation_complexity?->value ?? '',
                'water_availability' => $this->water_availability->value,
                'terrain_character' => array_values($this->terrain_character ?? []),
                'camping_available' => $this->camping_available === null ? '' : ($this->camping_available ? '1' : '0'),
                'starting_point' => $this->starting_point ?? '',
```

`app/Models/PreparationItem.php`:

1. Ganti:

```php
                'min_elevation_gain_m' => ($trail->elevation_gain_m ?? 0) >= (int) $expected,
                'technical_demand' => $trail->technical_demand->value === $expected,
                'navigation_complexity' => $trail->navigation_complexity->value === $expected,
                default => true,
```

dengan:

```php
                'min_elevation_gain_m' => ($trail->elevation_gain_m ?? 0) >= (int) $expected,
                'technical_demand' => $trail->technical_demand?->value === $expected,
                'navigation_complexity' => $trail->navigation_complexity?->value === $expected,
                default => true,
```

`app/Models/RecommendationResult.php`:

1. Ganti:

```php
            'navigation_complexity' => $angka['navigation_complexity'] ?? null,
            'camping_available' => (bool) ($angka['camping_available'] ?? false),
            'terrain_character' => $medan,
```

dengan:

```php
            'navigation_complexity' => $angka['navigation_complexity'] ?? null,
            'camping_available' => isset($angka['camping_available']) ? (bool) $angka['camping_available'] : null,
            'terrain_character' => $medan,
```

`app/Services/RouteFit/CompatibilityScorer.php`:

1. Ganti:

```php
    {
        $score = $this->rankScore($experienceRank, $trail->technical_demand->expectedExperienceRank());
```

dengan:

```php
    {
        // R-006: tuntutan teknis yang belum tercatat adalah belum diketahui, bukan Sedang (W8-R22).
        if ($trail->technical_demand === null) {
            return new FactorScore(
                CompatibilityFactor::TECHNICAL_MATCH,
                0.6,
                $this->weightFor(CompatibilityFactor::TECHNICAL_MATCH, $weights),
                'Tingkat teknis jalur ini belum tercatat.',
                isUnknown: true,
            );
        }

        $score = $this->rankScore($experienceRank, $trail->technical_demand->expectedExperienceRank());
```

2. Ganti:

```php
    {
        $userRank = ($user->experience?->navigation_experience ?? NavigationExperience::NONE)->rank();
```

dengan:

```php
    {
        // R-006: kerumitan navigasi yang belum tercatat adalah belum diketahui, bukan Sedang (W8-R22).
        if ($trail->navigation_complexity === null) {
            return new FactorScore(
                CompatibilityFactor::NAVIGATION_MATCH,
                0.6,
                $this->weightFor(CompatibilityFactor::NAVIGATION_MATCH, $weights),
                'Kompleksitas navigasi jalur ini belum tercatat.',
                isUnknown: true,
            );
        }

        $userRank = ($user->experience?->navigation_experience ?? NavigationExperience::NONE)->rank();
```

3. Ganti:

```php
        $durationFits = ($trail->estimated_duration_minutes ?? 0) <= $tripType->typicalMaxDurationMinutes();

        $score = match (true) {
```

dengan:

```php
        $durationFits = ($trail->estimated_duration_minutes ?? 0) <= $tripType->typicalMaxDurationMinutes();

        // R-007: area kemah yang belum tercatat tidak dianggap tidak ada (W8-R22).
        if ($needsCamping && $trail->camping_available === null) {
            return new FactorScore(
                CompatibilityFactor::TRIP_PREFERENCE,
                0.6,
                $weight,
                'Ketersediaan area kemah resmi jalur ini belum tercatat.',
                isUnknown: true,
            );
        }

        $score = match (true) {
```

`app/Services/Trails/PenilaianKecocokan.php`:

1. Ganti:

```php
            CompatibilityFactor::TERRAIN_MATCH => $trail->terrainTypes() === [] ? 'Belum diketahui' : TrailFigures::medan($trail),
            CompatibilityFactor::TECHNICAL_MATCH => $trail->technical_demand->label(),
            CompatibilityFactor::ELEVATION_GAIN_MATCH => $trail->elevation_gain_m === null ? 'Belum diketahui' : '↑'.number_format($trail->elevation_gain_m, 0, ',', '.').' m',
            CompatibilityFactor::NAVIGATION_MATCH => $trail->navigation_complexity->label(),
            CompatibilityFactor::TRIP_PREFERENCE => $trail->camping_available ? 'Ada area kemah' : 'Tanpa area kemah resmi',
        };
```

dengan:

```php
            CompatibilityFactor::TERRAIN_MATCH => $trail->terrainTypes() === [] ? 'Belum diketahui' : TrailFigures::medan($trail),
            CompatibilityFactor::TECHNICAL_MATCH => $trail->technical_demand?->label() ?? 'Belum diketahui',
            CompatibilityFactor::ELEVATION_GAIN_MATCH => $trail->elevation_gain_m === null ? 'Belum diketahui' : '↑'.number_format($trail->elevation_gain_m, 0, ',', '.').' m',
            CompatibilityFactor::NAVIGATION_MATCH => $trail->navigation_complexity?->label() ?? 'Belum diketahui',
            CompatibilityFactor::TRIP_PREFERENCE => match ($trail->camping_available) {
                true => 'Ada area kemah',
                false => 'Tanpa area kemah resmi',
                null => 'Area kemah belum diketahui',
            },
        };
```

`app/Services/Trails/RouteComparisonTable.php`:

1. Ganti:

```php
            ['air', 'Air', fn (Trail $t) => $t->water_availability?->label() ?? TrailFigures::BELUM],
            ['berkemah', 'Berkemah', fn (Trail $t) => $t->camping_available ? 'Bisa' : 'Tidak'],
            ['medan', 'Medan', fn (Trail $t) => TrailFigures::medan($t)],
```

dengan:

```php
            ['air', 'Air', fn (Trail $t) => $t->water_availability?->label() ?? TrailFigures::BELUM],
            // Belum tercatat bukan "Tidak" (R-007, butir izin akhir 6), juga di halaman publik /b/.
            ['berkemah', 'Berkemah', fn (Trail $t) => $t->camping_available === null ? TrailFigures::BELUM : ($t->camping_available ? 'Bisa' : 'Tidak')],
            ['medan', 'Medan', fn (Trail $t) => TrailFigures::medan($t)],
```

`app/Services/Trails/TrailDetailPage.php`:

1. Ganti:

```php
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
```

dengan:

```php
            'ujung' => $this->ujung($trail),
            // Angka sekali jalan naik. Turunan ikut hanya bila tercatat (W8-R23): salinan tanjakan
            // di data lama sudah dikosongkan, jadi angka keempat selalu turunan sepanjang jalan
            // naik dari GPX atau kurator, tidak pernah karangan (R-005, R-007).
            'angkaNaik' => array_values(array_filter([
                $this->angka('Jarak', TrailFigures::jarak($trail->distance_km)),
                $this->angka('Tanjakan', TrailFigures::meter($trail->elevation_gain_m)),
                $trail->elevation_loss_m !== null ? $this->angka('Turunan', TrailFigures::meter($trail->elevation_loss_m)) : null,
                $this->angka('Naik', TrailFigures::durasi($trail->estimated_duration_minutes)),
            ])),
            'tantangan' => $this->tantangan($trail),
```

2. Ganti:

```php
            ['judul' => 'Sumber air', 'kata' => $trail->water_availability?->label() ?? TrailFigures::BELUM, 'ruas' => null],
            ['judul' => 'Area kemah', 'kata' => $trail->camping_available ? 'Tersedia' : 'Tidak tersedia', 'ruas' => null],
        ];
```

dengan:

```php
            ['judul' => 'Sumber air', 'kata' => $trail->water_availability?->label() ?? TrailFigures::BELUM, 'ruas' => null],
            ['judul' => 'Area kemah', 'kata' => match ($trail->camping_available) {
                true => 'Tersedia',
                false => 'Tidak tersedia',
                null => TrailFigures::BELUM,
            }, 'ruas' => null],
        ];
```

`database/factories/TrailFactory.php`:

1. Ganti:

```php
            'elevation_gain_m' => 800,
            'elevation_loss_m' => 800,
            'estimated_duration_minutes' => 480,
```

dengan:

```php
            'elevation_gain_m' => 800,
            // Turunan sekali jalan naik tidak disalin dari tanjakan (W8-R23); kosong sampai diukur.
            'elevation_loss_m' => null,
            'estimated_duration_minutes' => 480,
```

`database/seeders/DemoSeeder.php`:

1. Ganti:

```php
                'elevation_gain_m' => $data['naik'],
                'elevation_loss_m' => $data['naik'],
                'elevation_profile' => $data['profil'] ?? null,
```

dengan:

```php
                'elevation_gain_m' => $data['naik'],
                'elevation_loss_m' => null,
                'elevation_profile' => $data['profil'] ?? null,
```

`database/seeders/MvpDatasetSeeder.php`:

1. Ganti:

```php
                        'elevation_gain_m' => $trailData['elevation_gain_m'],
                        'elevation_loss_m' => $trailData['elevation_gain_m'],
                        'estimated_duration_minutes' => $trailData['duration'],
```

dengan:

```php
                        'elevation_gain_m' => $trailData['elevation_gain_m'],
                        // Turunan tidak disalin dari tanjakan (W8-R23): datanya belum ada.
                        'elevation_loss_m' => null,
                        'estimated_duration_minutes' => $trailData['duration'],
```

`resources/js/components/admin/FormulirJalur.tsx`:

1. Ganti:

```tsx
import { Checkbox, FormField, InputError } from '@/components/ui/Field';

export interface IsianJalur {
```

dengan:

```tsx
import { Checkbox, FormField, InputError } from '@/components/ui/Field';

/** Area kemah tiga keadaan (R-007, W8-R22): belum diketahui bukan "tidak ada". */
const pilihanKemah: Pilihan[] = [
    { value: '1', label: 'Ada area kemah resmi' },
    { value: '0', label: 'Tidak ada area kemah resmi' },
];

export interface IsianJalur {
```

2. Ganti:

```tsx
    terrain_character: string[];
    camping_available: boolean;
    starting_point: string;
```

dengan:

```tsx
    terrain_character: string[];
    /** '1' ada, '0' tidak ada, '' belum diketahui. */
    camping_available: string;
    starting_point: string;
```

3. Ganti:

```tsx
    estimated_duration_minutes: '',
    technical_demand: 'MODERATE',
    navigation_complexity: 'MODERATE',
    water_availability: 'UNKNOWN',
    terrain_character: [],
    camping_available: false,
    starting_point: '',
```

dengan:

```tsx
    estimated_duration_minutes: '',
    // Kosong berarti belum diketahui (R-006, W8-R22); formulir tidak menebak Sedang.
    technical_demand: '',
    navigation_complexity: '',
    water_availability: 'UNKNOWN',
    terrain_character: [],
    camping_available: '',
    starting_point: '',
```

4. Ganti:

```tsx
                <FormField name="elevation_gain_m" type="number" label="Elevation gain (m)" value={data.elevation_gain_m} onChange={teks('elevation_gain_m')} error={errors.elevation_gain_m} />
                <FormField name="elevation_loss_m" type="number" label="Elevation loss (m)" value={data.elevation_loss_m} onChange={teks('elevation_loss_m')} error={errors.elevation_loss_m} />
            </div>
```

dengan:

```tsx
                <FormField name="elevation_gain_m" type="number" label="Elevation gain (m)" value={data.elevation_gain_m} onChange={teks('elevation_gain_m')} error={errors.elevation_gain_m} />
                <FormField
                    name="elevation_loss_m"
                    type="number"
                    label="Elevation loss (m)"
                    hint="Turunan sepanjang jalan naik, dari GPX atau sumber tercatat. Kosongkan bila tidak diketahui; jangan salin tanjakan."
                    value={data.elevation_loss_m}
                    onChange={teks('elevation_loss_m')}
                    error={errors.elevation_loss_m}
                />
            </div>
```

5. Ganti:

```tsx
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <PilihanAdmin name="technical_demand" label="Tingkat teknis" required placeholder="Pilih" options={pilihan.technicalLevels} value={data.technical_demand} onChange={(v) => setData('technical_demand', v)} error={errors.technical_demand} />
                <PilihanAdmin name="navigation_complexity" label="Kompleksitas navigasi" required placeholder="Pilih" options={pilihan.navigationLevels} value={data.navigation_complexity} onChange={(v) => setData('navigation_complexity', v)} error={errors.navigation_complexity} />
                <PilihanAdmin name="water_availability" label="Ketersediaan air" required placeholder="Pilih" options={pilihan.waterLevels} value={data.water_availability} onChange={(v) => setData('water_availability', v)} error={errors.water_availability} />
```

dengan:

```tsx
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <PilihanAdmin name="technical_demand" label="Tingkat teknis" placeholder="Belum diketahui" options={pilihan.technicalLevels} value={data.technical_demand} onChange={(v) => setData('technical_demand', v)} error={errors.technical_demand} />
                <PilihanAdmin name="navigation_complexity" label="Kompleksitas navigasi" placeholder="Belum diketahui" options={pilihan.navigationLevels} value={data.navigation_complexity} onChange={(v) => setData('navigation_complexity', v)} error={errors.navigation_complexity} />
                <PilihanAdmin name="water_availability" label="Ketersediaan air" required placeholder="Pilih" options={pilihan.waterLevels} value={data.water_availability} onChange={(v) => setData('water_availability', v)} error={errors.water_availability} />
```

6. Ganti:

```tsx
            </fieldset>

            <label className="flex min-h-11 items-center gap-2 text-sm text-secondary">
                <Checkbox checked={data.camping_available} onChange={(e) => setData('camping_available', e.target.checked)} />
                Tersedia area camping
            </label>

            <FormField name="starting_point" label="Titik awal" value={data.starting_point} onChange={teks('starting_point')} error={errors.starting_point} />
```

dengan:

```tsx
            </fieldset>

            <PilihanAdmin name="camping_available" label="Area kemah" placeholder="Belum diketahui" options={pilihanKemah} value={data.camping_available} onChange={(v) => setData('camping_available', v)} error={errors.camping_available} />

            <FormField name="starting_point" label="Titik awal" value={data.starting_point} onChange={teks('starting_point')} error={errors.starting_point} />
```

`resources/views/public/trail.blade.php`:

1. Ganti:

```php
                        <dt class="text-muted">Tingkat teknis</dt>
                        <dd class="font-medium text-primary">{{ $trail->technical_demand->label() }}</dd>
                    </div>
```

dengan:

```php
                        <dt class="text-muted">Tingkat teknis</dt>
                        <dd class="font-medium text-primary">{{ $trail->technical_demand?->label() ?? 'Belum tersedia' }}</dd>
                    </div>
```

2. Ganti:

```php
                        <dt class="text-muted">Navigasi</dt>
                        <dd class="font-medium text-primary">{{ $trail->navigation_complexity->label() }}</dd>
                    </div>
```

dengan:

```php
                        <dt class="text-muted">Navigasi</dt>
                        <dd class="font-medium text-primary">{{ $trail->navigation_complexity?->label() ?? 'Belum tersedia' }}</dd>
                    </div>
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Admin/TrailController.php app/Http/Requests/Admin/TrailRequest.php app/Http/Resources/Admin/TrailAdminRowResource.php app/Models/PreparationItem.php app/Models/RecommendationResult.php app/Services/RouteFit/CompatibilityScorer.php app/Services/Trails/PenilaianKecocokan.php app/Services/Trails/RouteComparisonTable.php app/Services/Trails/TrailDetailPage.php database/factories/TrailFactory.php database/migrations/2026_10_09_000600_make_trail_characteristics_nullable.php database/migrations/2026_10_09_000700_clear_copied_elevation_loss_on_trails.php database/seeders/DemoSeeder.php database/seeders/MvpDatasetSeeder.php tests/Feature/TrailDetailPageTest.php tests/Feature/UnknownTrailCharacteristicsTest.php tests/Spatial/TrailCharacteristicsSchemaTest.php`, lalu `php artisan test --compact --filter='UnknownTrailCharacteristics|TrailDetailPage|DemoSeeder|RouteFit|Recommendation'`
Expected: PASS. Pada simulasi rencana: 85 test, 82 lulus, 3 dilewati.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.246 test, 2.244 lulus, 2 dilewati (sebelum tugas: 2.240).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 42 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 14: Kunci stabil butir izin SIMAKSI

Titipan W4 (W4-C7b, label data "Booking izin pendakian (SIMAKSI)" dari W3); ruling W8-R24; Konflik 1 dan 10. Migrasi
`2026_10_09_000800` menambah `trip_preparation_items.kind` (indeks `trip_plan_id, kind`); migrasi data `000810` menandai
butir izin lama tanpa templat sebagai `PERMIT` dan mengganti labelnya. `PreparationService` mengenali butir izin lewat
`PreparationService::butirIzin()` (kolom `kind`), bukan labelnya, dan butir baru berlabel "Pesan izin pendakian
(SIMAKSI)". Membangkitkan ulang daftar tetap menyimpan satu butir izin beserta statusnya; butir lain yang kebetulan
berlabel mirip bukan butir izin. Persiapan, Cek kesiapan, tenggat Beranda, dan pengingat izin membaca kunci yang sama.

**Files:**
- Create: `database/migrations/2026_10_09_000800_add_kind_to_trip_preparation_items_table.php`, `database/migrations/2026_10_09_000810_mark_permit_preparation_items.php`
- Modify: `app/Models/TripPreparationItem.php`, `app/Services/PreparationService.php`, `app/Services/Trips/HalamanKesiapan.php`, `app/Services/Trips/HalamanPersiapan.php`
- Test: `tests/Feature/DashboardDeadlineTest.php`, `tests/Feature/DashboardPhaseTest.php`, `tests/Feature/PermitItemKeyTest.php` (baru), `tests/Feature/PermitVisibilityTest.php`, `tests/Feature/PermitWindowReminderTest.php`, `tests/Spatial/TripPreparationKindSchemaTest.php` (baru, PostgreSQL)

**Interfaces:**
- Consumes: `PreparationService::generateFor()` dan pembangkit ulangnya (W4, W7), `TripPreparationItem` dengan `user_owned_item_id` (W7), `HalamanPersiapan` dan `HalamanKesiapan` (W4 sampai Tugas 10).
- Produces:
  - kolom `trip_preparation_items.kind` (`varchar(20)`, boleh kosong) dan `TripPreparationItem::JENIS_IZIN` (`'PERMIT'`);
  - `PreparationService::PERMIT_LABEL` = "Pesan izin pendakian (SIMAKSI)" dan `PreparationService::butirIzin(TripPreparationItem): bool` (statis);
  - migrasi data `000810` (dapat dibalik: label lama kembali, `kind` dikosongkan).

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction PermitItemKeyTest`, lalu isinya diganti persis. Berkas lain, `tests/Spatial/TripPreparationKindSchemaTest.php`, dibuat langsung. `DashboardDeadlineTest`, `DashboardPhaseTest`,
`PermitVisibilityTest`, dan `PermitWindowReminderTest` membaca label baru dan kunci `kind`.

Buat `tests/Feature/PermitItemKeyTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kunci stabil butir izin di daftar persiapan (titipan W8, ruling W4-C7b; ruling W8-R24): butir
 * izin dikenali lewat kolom jenis, labelnya "Pesan izin pendakian (SIMAKSI)", dan butir lama
 * berlabel "Booking izin pendakian (SIMAKSI)" dipindahkan migrasi data tanpa kehilangan statusnya.
 */
class PermitItemKeyTest extends TestCase
{
    use RefreshDatabase;

    private function tripBerizin(): TripPlan
    {
        $jalur = Trail::factory()->published()->create();
        PermitRequirement::factory()->create(['trail_id' => $jalur->id]);

        return TripPlan::factory()->for($jalur)->create();
    }

    public function test_the_permit_item_carries_its_kind_and_the_new_label(): void
    {
        $trip = $this->tripBerizin();

        app(PreparationService::class)->generateFor($trip);

        $butir = $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->sole();
        $this->assertSame('Pesan izin pendakian (SIMAKSI)', $butir->label);
        $this->assertNull($butir->preparation_item_id);
        $this->assertTrue($butir->is_critical);
    }

    /**
     * Membangkitkan ulang menemukan butir yang sama lewat jenisnya, walau labelnya masih label
     * lama, dan statusnya tidak hilang.
     */
    public function test_regenerating_keeps_one_permit_item_and_its_status(): void
    {
        $trip = $this->tripBerizin();
        (new TripPreparationItem)->forceFill([
            'trip_plan_id' => $trip->id,
            'kind' => TripPreparationItem::JENIS_IZIN,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => 'Booking izin pendakian (SIMAKSI)',
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ])->save();

        app(PreparationService::class)->generateFor($trip->fresh());

        $butir = $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->sole();
        $this->assertSame(PreparationStatus::CONFIRMED, $butir->status);
        $this->assertSame(PreparationService::PERMIT_LABEL, $butir->label);
    }

    /**
     * Butir lain yang kebetulan berlabel sama, misalnya barang pribadi, bukan butir izin.
     */
    public function test_a_lookalike_label_is_not_the_permit_item(): void
    {
        $trip = $this->tripBerizin();
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => PreparationService::PERMIT_LABEL,
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        $this->assertFalse(PreparationService::izinDiurus($trip->fresh()->load('preparationItems')));
        $this->assertFalse(PreparationService::butirIzin($trip->preparationItems()->sole()));
    }

    public function test_the_data_migration_moves_old_permit_items_to_the_key(): void
    {
        $trip = $this->tripBerizin();
        $lama = TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => 'Booking izin pendakian (SIMAKSI)',
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
            'note' => 'Sudah dibayar lewat bank.',
        ]);
        $templat = TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => 'Senter',
            'is_critical' => false,
            'status' => PreparationStatus::NOT_CONFIRMED->value,
        ]);

        (require database_path('migrations/2026_10_09_000810_mark_permit_preparation_items.php'))->up();

        $baris = DB::table('trip_preparation_items')->where('id', $lama->id)->first();
        $this->assertSame('PERMIT', $baris->kind);
        $this->assertSame('Pesan izin pendakian (SIMAKSI)', $baris->label);
        $this->assertSame(PreparationStatus::CONFIRMED->value, $baris->status);
        $this->assertSame('Sudah dibayar lewat bank.', $baris->note);
        $this->assertNull(DB::table('trip_preparation_items')->where('id', $templat->id)->value('kind'));
    }
}
```

Buat `tests/Spatial/TripPreparationKindSchemaTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use Illuminate\Support\Facades\DB;

/**
 * Kunci jenis butir persiapan di PostgreSQL (R-024; ruling W4-C7b dan W8-R24): kolom boleh kosong,
 * terindeks bersama trip, dan migrasi datanya memindahkan butir izin berlabel lama.
 */
class TripPreparationKindSchemaTest extends SpatialTestCase
{
    public function test_the_kind_column_is_nullable_and_indexed(): void
    {
        $kolom = DB::selectOne("SELECT is_nullable FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'trip_preparation_items' AND column_name = 'kind'");
        $this->assertSame('YES', $kolom->is_nullable);

        $indeks = collect(DB::select("SELECT indexdef FROM pg_indexes WHERE tablename = 'trip_preparation_items'"))->pluck('indexdef')->implode("\n");
        $this->assertStringContainsString('(trip_plan_id, kind)', $indeks);
    }

    public function test_the_data_migration_marks_old_permit_items(): void
    {
        $trip = TripPlan::factory()->create();
        $lama = TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => 'Booking izin pendakian (SIMAKSI)',
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        (require database_path('migrations/2026_10_09_000810_mark_permit_preparation_items.php'))->up();

        $baris = DB::table('trip_preparation_items')->where('id', $lama->id)->first();
        $this->assertSame('PERMIT', $baris->kind);
        $this->assertSame('Pesan izin pendakian (SIMAKSI)', $baris->label);
    }
}
```

`tests/Feature/DashboardDeadlineTest.php`:

1. Ganti:

```php
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 2]);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
```

dengan:

```php
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 2]);
        (new TripPreparationItem)->forceFill([
            'trip_plan_id' => $trip->id,
            'kind' => TripPreparationItem::JENIS_IZIN,
            'category' => PreparationCategory::LOGISTICS->value,
```

2. Ganti:

```php
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        $this->assertSame(['Prakiraan BMKG untuk 10 Okt mulai terbit'], array_column($this->tenggat($trip)['baris'], 'judul'));
```

dengan:

```php
            'status' => PreparationStatus::CONFIRMED->value,
        ])->save();

        $this->assertSame(['Prakiraan BMKG untuk 10 Okt mulai terbit'], array_column($this->tenggat($trip)['baris'], 'judul'));
```

`tests/Feature/DashboardPhaseTest.php`:

1. Ganti:

```php
        $this->assertSame('Sistem resmi Balai Besar TNGGP. Memount menautkan sistem resmi dan tidak menjual izin.', $aksi['keterangan']);

        $this->butir($trip, PreparationService::PERMIT_LABEL, PreparationStatus::CONFIRMED);

        $this->assertSame('Buka ruang kerja', $this->fase($user)['kartu']['aksi']['label']);
```

dengan:

```php
        $this->assertSame('Sistem resmi Balai Besar TNGGP. Memount menautkan sistem resmi dan tidak menjual izin.', $aksi['keterangan']);

        $this->butir($trip, PreparationService::PERMIT_LABEL, PreparationStatus::CONFIRMED)->forceFill(['kind' => TripPreparationItem::JENIS_IZIN])->save();

        $this->assertSame('Buka ruang kerja', $this->fase($user)['kartu']['aksi']['label']);
```

`tests/Feature/PermitVisibilityTest.php`:

1. Ganti:

```php
use App\Models\TripPlan;
use App\Models\User;
```

dengan:

```php
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
```

2. Ganti:

```php
        app(PreparationService::class)->generateFor($trip);

        $item = $trip->preparationItems()->where('label', 'Booking izin pendakian (SIMAKSI)')->first();

        $this->assertNotNull($item, 'Checklist harus memuat item perizinan.');
```

dengan:

```php
        app(PreparationService::class)->generateFor($trip);

        $item = $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->first();

        $this->assertNotNull($item, 'Checklist harus memuat item perizinan.');
```

3. Ganti:

```php
        $this->assertNull(
            $trip->preparationItems()->where('label', 'Booking izin pendakian (SIMAKSI)')->first()
        );
```

dengan:

```php
        $this->assertNull(
            $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->first()
        );
```

4. Ganti:

```php
        $trip->preparationItems()
            ->where('label', 'Booking izin pendakian (SIMAKSI)')
            ->update(['status' => PreparationStatus::CONFIRMED->value]);
```

dengan:

```php
        $trip->preparationItems()
            ->where('kind', TripPreparationItem::JENIS_IZIN)
            ->update(['status' => PreparationStatus::CONFIRMED->value]);
```

5. Ganti:

```php
            PreparationStatus::CONFIRMED,
            $trip->preparationItems()->where('label', 'Booking izin pendakian (SIMAKSI)')->first()->status
        );
```

dengan:

```php
            PreparationStatus::CONFIRMED,
            $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->first()->status
        );
```

`tests/Feature/PermitWindowReminderTest.php`:

1. Ganti:

```php
        $this->nyalakan($trip);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
```

dengan:

```php
        $this->nyalakan($trip);
        (new TripPreparationItem)->forceFill([
            'trip_plan_id' => $trip->id,
            'kind' => TripPreparationItem::JENIS_IZIN,
            'category' => PreparationCategory::LOGISTICS->value,
```

2. Ganti:

```php
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        $this->travelTo(Carbon::parse('2026-10-20 23:00:00', 'UTC'));
```

dengan:

```php
            'status' => PreparationStatus::CONFIRMED->value,
        ])->save();

        $this->travelTo(Carbon::parse('2026-10-20 23:00:00', 'UTC'));
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='PermitItemKey|PermitVisibility|DashboardDeadline|DashboardPhase|PermitWindowReminder|PreparationUndo|ReadinessDashboard|TrailDetailPage|OwnedEquipment'`
Expected: FAIL. Pada simulasi rencana: 143 test, 133 lulus, 1 gagal, 9 error. Yang merah: keempat test `PermitItemKeyTest` (konstanta `TripPreparationItem::JENIS_IZIN`,
kolom `kind`, dan migrasi `000810` belum ada; butir berlabel mirip masih terbaca butir izin) dan enam test lama yang
kini mengisi `kind` (`DashboardDeadlineTest`, `DashboardPhaseTest`, `PermitVisibilityTest`, `PermitWindowReminderTest`).

- [ ] **Step 3: Tulis implementasinya.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:migration --no-interaction add_kind_to_trip_preparation_items_table` dan `... make:migration --no-interaction mark_permit_preparation_items` (ganti nama berkas migrasi hasilnya menjadi `2026_10_09_000800_add_kind_to_trip_preparation_items_table.php` dan `2026_10_09_000810_mark_permit_preparation_items.php`), lalu isinya diganti persis.

Periksa lebih dulu (Pra-cek butir 1b): bila `app/Services/PreparationService.php` di repo nyata sudah memuat
`public static function butirIzin` (perbaikan akhir W4), blok 3 dan 4 `PreparationService.php` di bawah diganti tiga
perubahan di "Varian repo nyata" sesudah blok-blok ini, dan kedua blok `HalamanKesiapan.php` serta kedua blok
`HalamanPersiapan.php` dilewati karena pemanggilnya sudah memakai `PreparationService::butirIzin()`. Blok lain
diterapkan seperti tertulis.

Buat `database/migrations/2026_10_09_000800_add_kind_to_trip_preparation_items_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci stabil butir persiapan sistem (titipan W8, ruling W4-C7b; ruling W8-R24). Butir izin
 * pendakian dikenali lewat kolom jenis, bukan lewat teks labelnya, supaya labelnya dapat berganti
 * tanpa memutus Persiapan, Cek kesiapan, Beranda, dan pengingat yang membacanya. Kosong untuk
 * butir templat dan butir perlengkapan pribadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->string('kind', 20)->nullable()->after('user_owned_item_id');
            $table->index(['trip_plan_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('trip_preparation_items', function (Blueprint $table) {
            $table->dropIndex(['trip_plan_id', 'kind']);
            $table->dropColumn('kind');
        });
    }
};
```

Buat `database/migrations/2026_10_09_000810_mark_permit_preparation_items.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi data kunci butir izin (ruling W4-C7b; ruling W8-R24): butir tanpa templat berlabel lama
 * "Booking izin pendakian (SIMAKSI)" ditandai jenis PERMIT dan labelnya menjadi "Pesan izin
 * pendakian (SIMAKSI)". Status, catatan, dan waktu konfirmasinya tidak berubah. Dibalik dengan
 * mengembalikan label lama dan mengosongkan jenisnya.
 */
return new class extends Migration
{
    private const LABEL_LAMA = 'Booking izin pendakian (SIMAKSI)';

    private const LABEL_BARU = 'Pesan izin pendakian (SIMAKSI)';

    public function up(): void
    {
        DB::table('trip_preparation_items')
            ->whereNull('preparation_item_id')
            ->where('label', self::LABEL_LAMA)
            ->update(['kind' => 'PERMIT', 'label' => self::LABEL_BARU]);
    }

    public function down(): void
    {
        DB::table('trip_preparation_items')
            ->where('kind', 'PERMIT')
            ->update(['kind' => null, 'label' => self::LABEL_LAMA]);
    }
};
```

`app/Models/TripPreparationItem.php`:

1. Ganti:

```php
    use HasFactory;

    protected function casts(): array
```

dengan:

```php
    use HasFactory;

    /**
     * Jenis butir sistem "izin pendakian" (kolom kind; ruling W4-C7b dan W8-R24). Ditulis
     * PreparationService lewat forceFill, tidak pernah dari isian.
     */
    public const JENIS_IZIN = 'PERMIT';

    protected function casts(): array
```

`app/Services/PreparationService.php`:

1. Ganti:

```php
{
    /** Publik: rincian jalur menyebut butir izin dengan nama yang sama dengan daftar persiapan trip. */
    public const PERMIT_LABEL = 'Booking izin pendakian (SIMAKSI)';

    public function __construct(
```

dengan:

```php
{
    /**
     * Publik: rincian jalur menyebut butir izin dengan nama yang sama dengan daftar persiapan trip.
     * Hanya teks tampilan; butirnya dikenali lewat kolom jenis (butirIzin(), ruling W8-R24).
     */
    public const PERMIT_LABEL = 'Pesan izin pendakian (SIMAKSI)';

    public function __construct(
```

2. Ganti:

```php
     * Butir izin di daftar persiapan trip sudah dikonfirmasi atau ditandai tidak berlaku.
     * Butir itu dikenali lewat PERMIT_LABEL sampai kunci stabil butir sistem tersedia
     * (titipan W8, ruling W4-C7b). Membutuhkan relasi preparationItems sudah dimuat.
     */
```

dengan:

```php
     * Butir izin di daftar persiapan trip sudah dikonfirmasi atau ditandai tidak berlaku.
     * Membutuhkan relasi preparationItems sudah dimuat.
     */
```

3. Ganti:

```php
    {
        return $trip->preparationItems->contains(fn (TripPreparationItem $butir) => $butir->preparation_item_id === null
            && $butir->label === self::PERMIT_LABEL
            && $butir->status !== PreparationStatus::NOT_CONFIRMED);
    }
```

dengan:

```php
    {
        return $trip->preparationItems->contains(fn (TripPreparationItem $butir) => self::butirIzin($butir)
            && $butir->status !== PreparationStatus::NOT_CONFIRMED);
    }

    /**
     * Butir izin trip, dikenali lewat kunci stabilnya (kolom kind; ruling W4-C7b dan W8-R24),
     * bukan lewat teks label: butir perlengkapan pribadi atau butir lain yang kebetulan berlabel
     * sama bukan butir izin. Satu predikat untuk Persiapan, Cek kesiapan, Beranda, dan pengingat.
     */
    public static function butirIzin(TripPreparationItem $item): bool
    {
        return $item->kind === TripPreparationItem::JENIS_IZIN;
    }
```

4. Ganti:

```php
        }

        $existing = $trip->preparationItems()
            ->whereNull('preparation_item_id')
            ->where('label', self::PERMIT_LABEL)
            ->first();

        TripPreparationItem::updateOrCreate(
            ['trip_plan_id' => $trip->id, 'preparation_item_id' => null, 'label' => self::PERMIT_LABEL],
            [
                'category' => PreparationCategory::LOGISTICS->value,
                'description' => $description,
                'is_critical' => true,
                'status' => $existing?->status?->value ?? PreparationStatus::NOT_CONFIRMED->value,
            ]
        );
    }
```

dengan:

```php
        }

        // Dicari lewat jenisnya, jadi label lama yang sudah dimigrasikan tetap butir yang sama dan
        // statusnya tidak hilang ketika daftar dibangkitkan ulang.
        $butir = $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->first()
            ?? new TripPreparationItem(['trip_plan_id' => $trip->id, 'status' => PreparationStatus::NOT_CONFIRMED->value]);

        $butir->fill([
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => self::PERMIT_LABEL,
            'description' => $description,
            'is_critical' => true,
        ]);
        $butir->forceFill(['kind' => TripPreparationItem::JENIS_IZIN])->save();
    }
```

`app/Services/Trips/HalamanKesiapan.php`:

1. Ganti:

```php
            ->values();
        $aturan = $belum->contains(fn (TripPreparationItem $item) => $item->preparation_item_id === null && $item->label === PreparationService::PERMIT_LABEL)
            ? $this->permits->requirementFor($trip->trail)
```

dengan:

```php
            ->values();
        $aturan = $belum->contains(fn (TripPreparationItem $item) => PreparationService::butirIzin($item))
            ? $this->permits->requirementFor($trip->trail)
```

2. Ganti:

```php
        $butir = $belum->map(function (TripPreparationItem $item) use ($trip, $terbit, $aturan) {
            $izin = $item->preparation_item_id === null && $item->label === PreparationService::PERMIT_LABEL;
            $menunggu = $item->category === PreparationCategory::WEATHER && $terbit !== null;
```

dengan:

```php
        $butir = $belum->map(function (TripPreparationItem $item) use ($trip, $terbit, $aturan) {
            $izin = PreparationService::butirIzin($item);
            $menunggu = $item->category === PreparationCategory::WEATHER && $terbit !== null;
```

`app/Services/Trips/HalamanPersiapan.php`:

1. Ganti:

```php
    {
        $butirIzin = $item->preparation_item_id === null && $item->label === PreparationService::PERMIT_LABEL;

        return [
```

dengan:

```php
    {
        $butirIzin = PreparationService::butirIzin($item);

        return [
```

2. Ganti:

```php
    {
        $punyaButirIzin = $trip->preparationItems->contains(fn (TripPreparationItem $item) => $item->preparation_item_id === null && $item->label === PreparationService::PERMIT_LABEL);

        if (! $punyaButirIzin) {
```

dengan:

```php
    {
        $punyaButirIzin = $trip->preparationItems->contains(fn (TripPreparationItem $item) => PreparationService::butirIzin($item));

        if (! $punyaButirIzin) {
```

**Varian repo nyata untuk `app/Services/PreparationService.php`** (hanya bila `butirIzin()` sudah ada; setiap teks lama
di bawah ini ditemukan tepat sekali di repo nyata dengan Grep, 8 Oktober 2026):

Varian 1, pengganti blok 3 (badan `izinDiurus()` saja, tanpa menambah `butirIzin()` kedua). Teks lama:

```php
    {
        return $trip->preparationItems->contains(fn (TripPreparationItem $butir) => $butir->preparation_item_id === null
            && $butir->label === self::PERMIT_LABEL
            && $butir->status !== PreparationStatus::NOT_CONFIRMED);
    }
```

menjadi:

```php
    {
        return $trip->preparationItems->contains(fn (TripPreparationItem $butir) => self::butirIzin($butir)
            && $butir->status !== PreparationStatus::NOT_CONFIRMED);
    }
```

Varian 2, pengganti blok 4 (repo nyata punya komentar dan `$description = $requirement->summary();` sebelum
`$existing`, jadi jangkarnya dimulai dari `$existing`). Teks lama:

```php
        $existing = $trip->preparationItems()
            ->whereNull('preparation_item_id')
            ->where('label', self::PERMIT_LABEL)
            ->first();

        TripPreparationItem::updateOrCreate(
            ['trip_plan_id' => $trip->id, 'preparation_item_id' => null, 'label' => self::PERMIT_LABEL],
            [
                'category' => PreparationCategory::LOGISTICS->value,
                'description' => $description,
                'is_critical' => true,
                'status' => $existing?->status?->value ?? PreparationStatus::NOT_CONFIRMED->value,
            ]
        );
    }
```

menjadi:

```php
        // Dicari lewat jenisnya, jadi label lama yang sudah dimigrasikan tetap butir yang sama dan
        // statusnya tidak hilang ketika daftar dibangkitkan ulang.
        $butir = $trip->preparationItems()->where('kind', TripPreparationItem::JENIS_IZIN)->first()
            ?? new TripPreparationItem(['trip_plan_id' => $trip->id, 'status' => PreparationStatus::NOT_CONFIRMED->value]);

        $butir->fill([
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => self::PERMIT_LABEL,
            'description' => $description,
            'is_critical' => true,
        ]);
        $butir->forceFill(['kind' => TripPreparationItem::JENIS_IZIN])->save();
    }
```

Varian 3, `butirIzin()` yang sudah ada dibaca dari kunci stabil. Teks lama:

```php
    /**
     * Butir izin trip: baris tanpa templat berlabel PERMIT_LABEL (dibuat addPermitItem). Satu
     * predikat untuk Persiapan dan Cek kesiapan.
     */
    public static function butirIzin(TripPreparationItem $item): bool
    {
        return $item->preparation_item_id === null && $item->label === self::PERMIT_LABEL;
    }
```

menjadi:

```php
    /**
     * Butir izin trip, dikenali lewat kunci stabilnya (kolom kind; ruling W4-C7b dan W8-R24),
     * bukan lewat teks label: butir perlengkapan pribadi atau butir lain yang kebetulan berlabel
     * sama bukan butir izin. Satu predikat untuk Persiapan, Cek kesiapan, Beranda, dan pengingat.
     */
    public static function butirIzin(TripPreparationItem $item): bool
    {
        return $item->kind === TripPreparationItem::JENIS_IZIN;
    }
```

Di repo nyata, tiga test perbaikan akhir W4 yang tidak ada di dasar simulasi membuat butir izin dengan label tanpa
`kind`, sehingga sesudah tugas ini butir itu bukan butir izin lagi. Ubah dengan pola yang sama seperti
`DashboardDeadlineTest` di Step 1 (isi `kind` lewat `forceFill`, karena `kind` tidak dapat diisi massal), dan tulis
alasannya di komentar test:

- `tests/Feature/PreparationUndoTest.php` (baris 667 pada 8 Oktober): `TripPreparationItem::create([...])` menjadi
  `(new TripPreparationItem)->forceFill([..., 'kind' => TripPreparationItem::JENIS_IZIN, ...])->save()`;
- `tests/Feature/TripStepGuidanceTest.php` (baris 481): sama;
- `tests/Feature/ReadinessDashboardTest.php` (baris 638): `$trip->preparationItems()->create([...])` menjadi
  `$trip->preparationItems()->make([...])->forceFill(['kind' => TripPreparationItem::JENIS_IZIN])->save()`, dengan
  `use App\Models\TripPreparationItem;` bila belum ada.

Lalu jalankan juga `php artisan test --compact --filter='PreparationUndo|TripStepGuidance|ReadinessDashboard'` dan
catat angkanya; angka ini tidak ada pada simulasi rencana.

- [ ] **Step 4: Build dan tipe.** Tidak ada TypeScript, rute, atau daftar rute bukan halaman React yang berubah; build
tidak perlu.

Run: `node_modules/.bin/tsc --noEmit`
Expected: tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Models/TripPreparationItem.php app/Services/PreparationService.php app/Services/Trips/HalamanKesiapan.php app/Services/Trips/HalamanPersiapan.php database/migrations/2026_10_09_000800_add_kind_to_trip_preparation_items_table.php database/migrations/2026_10_09_000810_mark_permit_preparation_items.php tests/Feature/DashboardDeadlineTest.php tests/Feature/DashboardPhaseTest.php tests/Feature/PermitItemKeyTest.php tests/Feature/PermitVisibilityTest.php tests/Feature/PermitWindowReminderTest.php tests/Spatial/TripPreparationKindSchemaTest.php`, lalu `php artisan test --compact --filter='PermitItemKey|PermitVisibility|DashboardDeadline|DashboardPhase|PermitWindowReminder|PreparationUndo|ReadinessDashboard|TrailDetailPage|OwnedEquipment'`
Expected: PASS. Pada simulasi rencana: 143 test, 143 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.250 test, 2.248 lulus, 2 dilewati (sebelum tugas: 2.246).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 42 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 15: Kalimat izin ruang kerja trip dan kesegaran prakiraan Cek kesiapan

Titipan W4 (M1 tinjauan akhir W4: ruang kerja menyusul di W8; tinjauan akhir W4 butir 8); ruling W8-R25 dan W8-R26;
R-013. Prop `izin` ruang kerja trip kini keluaran `PermitService::pemesananDariJendela()`, penentu yang sama dengan
Persiapan, Rencanakan, dan Cek kesiapan: kalimat keadaan dalam satu baris (`judul`: "Pemesanan izin belum dibuka",
"Pemesanan izin sedang dibuka", "Tanggal buka pemesanan izin belum tercatat", "Pemesanan izin sudah tutup"),
keterangan, dan tautan yang hanya mengajak memesan saat jendela terbuka, tanpa alamat pemesanan mentah. Di Cek kesiapan,
waktu ambil dan kesegaran prakiraan dihitung dari baris tanggal trip yang tampil, bukan konteks 72 jam
`WeatherService`, sehingga prakiraan yang diambil lebih dari sehari lalu tidak terbaca "Terbaru" hanya karena baris lain
baru diambil.

**Files:**
- Modify: `app/Http/Controllers/Trips/TripController.php`, `app/Services/PermitService.php`, `app/Services/Trips/HalamanKesiapan.php`, `resources/js/pages/Trips/Show.tsx`
- Test: `tests/Feature/ReadinessForecastFreshnessTest.php` (baru), `tests/Feature/TripPermitWindowTest.php`

**Interfaces:**
- Consumes: `PermitService::bookingWindowFor()`, `pemesananDariJendela(?array, PermitRequirement, bool)`, dan keadaan `BOOKING_*` (perbaikan akhir W4 M1, W8-R1); `TripStatus::isActive()`; baris `WeatherSnapshot` tanggal trip di `HalamanKesiapan` (W4).
- Produces:
  - kunci `judul` di keluaran `PermitService::pemesanan()` dan `pemesananDariJendela()` (`array{keadaan: string|null, judul: string, tautan: array{href: string, teks: string, label: string}|null, keterangan: string}`);
  - prop `izin` ruang kerja trip dalam bentuk itu, dibaca `Trips/Show.tsx`;
  - `kondisi.prakiraan.diperbarui` dan `kesegaran` di Cek kesiapan dari `fetched_at` baris tanggal trip.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction ReadinessForecastFreshnessTest`, lalu isinya diganti persis. `TripPermitWindowTest` membaca kalimat dan tautan dari
penentu satu sumber.

Buat `tests/Feature/ReadinessForecastFreshnessTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\PreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kesegaran prakiraan di Cek kesiapan (titipan W4, tinjauan akhir W4 butir 8; ruling W8-R26):
 * waktu ambil dan kesegarannya menyebut baris tanggal trip yang tampil, bukan konteks 72 jam
 * WeatherService. Area yang sama dapat menyimpan baris dari jalan refresh yang berbeda; prakiraan
 * yang diambil lebih dari sehari lalu tidak boleh terbaca "Terbaru" hanya karena baris lain di
 * jendela 72 jam baru diambil (R-013).
 */
class ReadinessForecastFreshnessTest extends TestCase
{
    use RefreshDatabase;

    private function trip(): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::ADVANCED->value, 'completed_at' => now()]);
        $trail = Trail::factory()->published()->for(Mountain::factory()->create(['timezone' => 'Asia/Jakarta']))->easy()->create();

        $trip = TripPlan::create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Uji kesegaran prakiraan',
            'planned_date' => '2026-10-10',
            'trip_type' => TripType::CAMPING->value,
            'status' => TripStatus::PLANNED->value,
        ]);
        app(PreparationService::class)->generateFor($trip);

        return $trip->fresh();
    }

    public function test_the_forecast_freshness_describes_the_trip_date_rows(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $trip = $this->trip();
        $kode = $trip->trail->weather_adm4_code;

        // Baris tanggal trip dari jalan refresh 30 jam lalu.
        WeatherSnapshot::factory()->create(['adm4_code' => $kode, 'forecast_at' => Carbon::parse('2026-10-10 00:00:00', 'UTC'), 'fetched_at' => now()->subHours(30), 'weather_description' => 'Hujan ringan']);
        // Baris lain di jendela 72 jam, baru diambil sejam lalu.
        WeatherSnapshot::factory()->create(['adm4_code' => $kode, 'forecast_at' => now()->addHours(3), 'fetched_at' => now()->subHour()]);

        $prakiraan = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $this->assertSame('tersedia', $prakiraan['keadaan']);
        $this->assertSame('Sudah lama', $prakiraan['kesegaran']);
        $this->assertSame('7 Okt 02.00 WIB', $prakiraan['diperbarui']);
    }

    public function test_fresh_trip_date_rows_read_fresh(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 01:00:00', 'UTC'));
        $trip = $this->trip();

        WeatherSnapshot::factory()->create(['adm4_code' => $trip->trail->weather_adm4_code, 'forecast_at' => Carbon::parse('2026-10-10 00:00:00', 'UTC'), 'fetched_at' => now()->subHours(2)]);

        $prakiraan = $this->actingAs($trip->user)->get(route('trips.readiness', $trip))->viewData('page')['props']['kondisi']['prakiraan'];

        $this->assertSame('Terbaru', $prakiraan['kesegaran']);
        $this->assertSame('8 Okt 06.00 WIB', $prakiraan['diperbarui']);
    }
}
```

`tests/Feature/TripPermitWindowTest.php`:

1. Ganti:

```php
    /**
     * Kalimat izin yang dikirim server ke halaman trip (React), atau string kosong bila
     * halaman tidak menyebut izin sama sekali.
     */
```

dengan:

```php
    /**
     * Kalimat izin yang dikirim server ke halaman trip (React): judul keadaan dan keterangannya
     * dari PermitService::pemesananDariJendela() (ruling W8-R25), atau string kosong bila halaman
     * tidak menyebut izin sama sekali.
     */
```

2. Ganti:

```php
        $props = $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props'];

        return $props['izin']['pesan'] ?? '';
    }
```

dengan:

```php
        $props = $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props'];

        return trim(($props['izin']['judul'] ?? '').' '.($props['izin']['keterangan'] ?? ''));
    }

    /**
     * @return array{href: string, teks: string, label: string}|null
     */
    private function tautan(TripPlan $trip): ?array
    {
        return $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props']['izin']['tautan'] ?? null;
    }
```

3. Ganti:

```php
        $pesan = $this->izin($this->tripBerjarak(1));
        $this->assertStringContainsString('sudah ditutup', $pesan);
    }
```

dengan:

```php
        $pesan = $this->izin($this->tripBerjarak(1));
        $this->assertStringContainsString('sudah tutup', $pesan);
    }

    /**
     * Satu penentu tautan dengan Persiapan dan Cek kesiapan (W4 M1, ruling W8-R25): ajakan
     * memesan hanya saat jendela terbuka, dengan hari terakhirnya, dan alamat pemesanan tidak
     * pernah ditulis mentah di kalimatnya.
     */
    public function test_the_booking_link_follows_the_window(): void
    {
        $terbuka = $this->tripBerjarak(10);
        PermitRequirement::query()->update(['booking_url' => 'https://bookingsemeru.bromotenggersemeru.org']);

        $this->assertStringStartsWith('Pesan di sistem resmi, paling lambat', $this->tautan($terbuka)['teks']);
        $this->assertStringNotContainsString('bookingsemeru', $this->izin($terbuka));
        $this->assertNull($this->tautan($this->tripBerjarak(40)), 'Belum dibuka: tanpa ajakan memesan.');
        $this->assertNull($this->tautan($this->tripBerjarak(1)), 'Sudah tutup: tanpa ajakan memesan.');
    }

    /**
     * Tanggal buka yang belum tercatat tidak pernah ditulis "sedang dibuka" (R-007, W4-FE):
     * tautannya netral, "Periksa di sistem resmi".
     */
    public function test_an_unrecorded_opening_day_offers_only_a_neutral_link(): void
    {
        $trip = $this->tripBerjarak(10);
        PermitRequirement::query()->update(['booking_opens_days_before' => null, 'booking_url' => 'https://bookingsemeru.bromotenggersemeru.org']);

        $this->assertStringContainsString('Tanggal buka pemesanan izin belum tercatat', $this->izin($trip));
        $this->assertSame('Periksa di sistem resmi', $this->tautan($trip)['teks']);
    }
```

4. Ganti:

```php
        $pesan = $this->izin($trip);
        $this->assertStringContainsString('sudah ditutup', $pesan);
        $this->assertStringNotContainsString('sedang dibuka', $pesan);
```

dengan:

```php
        $pesan = $this->izin($trip);
        $this->assertStringContainsString('sudah tutup', $pesan);
        $this->assertStringNotContainsString('sedang dibuka', $pesan);
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TripPermitWindow|ReadinessForecastFreshness|ReadinessDashboard'`
Expected: FAIL. Pada simulasi rencana: 60 test, 52 lulus, 7 gagal, 1 error. Yang merah: tujuh test `TripPermitWindowTest` (prop `izin` belum membawa `judul`,
`keterangan`, dan `tautan` dari penentu satu sumber) dan
`ReadinessForecastFreshnessTest::test_the_forecast_freshness_describes_the_trip_date_rows` (prakiraan 30 jam lalu masih
terbaca "Terbaru").

- [ ] **Step 3: Tulis implementasinya.**

`app/Http/Controllers/Trips/TripController.php`:

1. Ganti:

```php
            // Nadanya mengikuti §43: sistem menyebut aturan dan penyelenggaranya, tidak
            // pernah menyatakan izinnya sudah aman atau belum.
            'izin' => $jendela === null ? null : [
                'ditutup' => $jendela['state'] === PermitService::BOOKING_DITUTUP,
                'pesan' => $jendela['message'],
                'tautanPemesanan' => $jendela['requirement']->booking_url,
            ],
            'hasilPendakian' => $trip->history !== null ? route('history.summary', $trip) : null,
```

dengan:

```php
            // Nadanya mengikuti §43: sistem menyebut aturan dan penyelenggaranya, tidak
            // pernah menyatakan izinnya sudah aman atau belum. Kalimat dan tautannya dari
            // penentu satu sumber PermitService (W4 M1, ruling W8-R25): ajakan memesan hanya
            // saat jendela terbuka, tanpa alamat pemesanan mentah di kalimatnya.
            'izin' => $jendela === null ? null : PermitService::pemesananDariJendela($jendela, $jendela['requirement'], $trip->status->isActive()),
            'hasilPendakian' => $trip->history !== null ? route('history.summary', $trip) : null,
```

`app/Services/PermitService.php`:

1. Ganti:

```php
     * Keterangan dan ajakan pemesanan izin per keadaan jendela, satu penentu untuk Persiapan,
     * Rencanakan, dan Cek kesiapan (M1 tinjauan akhir W4; ruang kerja menyusul di W8).
     *
```

dengan:

```php
     * Keterangan dan ajakan pemesanan izin per keadaan jendela, satu penentu untuk Persiapan,
     * Rencanakan, Cek kesiapan, dan ruang kerja trip (M1 tinjauan akhir W4; ruang kerja sejak W8,
     * ruling W8-R25). `judul` adalah kalimat keadaannya dalam satu baris.
     *
```

2. Ganti:

```php
     *
     * @return array{keadaan: string|null, tautan: array{href: string, teks: string, label: string}|null, keterangan: string}
     */
```

dengan:

```php
     *
     * @return array{keadaan: string|null, judul: string, tautan: array{href: string, teks: string, label: string}|null, keterangan: string}
     */
```

3. Ganti:

```php
     * @param  array{state: string, message: string, requirement: PermitRequirement, buka: Carbon|null, tutup: Carbon|null}|null  $jendela  hasil bookingWindowFor()
     * @return array{keadaan: string|null, tautan: array{href: string, teks: string, label: string}|null, keterangan: string}
     */
```

dengan:

```php
     * @param  array{state: string, message: string, requirement: PermitRequirement, buka: Carbon|null, tutup: Carbon|null}|null  $jendela  hasil bookingWindowFor()
     * @return array{keadaan: string|null, judul: string, tautan: array{href: string, teks: string, label: string}|null, keterangan: string}
     */
```

4. Ganti:

```php
                'keadaan' => self::BOOKING_TERBUKA,
                'tautan' => $dapatDitautkan ? [
```

dengan:

```php
                'keadaan' => self::BOOKING_TERBUKA,
                'judul' => 'Pemesanan izin sedang dibuka',
                'tautan' => $dapatDitautkan ? [
```

5. Ganti:

```php
                'keadaan' => self::BOOKING_BUKA_BELUM_TERCATAT,
                'tautan' => $bolehMemesan ? self::tautanPeriksa($aturan) : null,
```

dengan:

```php
                'keadaan' => self::BOOKING_BUKA_BELUM_TERCATAT,
                'judul' => 'Tanggal buka pemesanan izin belum tercatat',
                'tautan' => $bolehMemesan ? self::tautanPeriksa($aturan) : null,
```

6. Ganti:

```php
                'keadaan' => self::BOOKING_DITUTUP,
                'tautan' => null,
```

dengan:

```php
                'keadaan' => self::BOOKING_DITUTUP,
                'judul' => 'Pemesanan izin sudah tutup',
                'tautan' => null,
```

7. Ganti:

```php
                'keadaan' => self::BOOKING_BELUM_DIBUKA,
                'tautan' => null,
```

dengan:

```php
                'keadaan' => self::BOOKING_BELUM_DIBUKA,
                'judul' => 'Pemesanan izin belum dibuka',
                'tautan' => null,
```

8. Ganti:

```php
            ],
            default => ['keadaan' => null, 'tautan' => null, 'keterangan' => $ringkas],
        };
```

dengan:

```php
            ],
            default => ['keadaan' => null, 'judul' => 'Jalur ini mewajibkan izin', 'tautan' => null, 'keterangan' => $ringkas],
        };
```

`app/Services/Trips/HalamanKesiapan.php`:

1. Ganti:

```php
use App\Services\Trails\StatusJalurRingkas;
use App\Support\Timezone;
```

dengan:

```php
use App\Services\Trails\StatusJalurRingkas;
use App\Services\WeatherService;
use App\Support\Timezone;
```

2. Ganti:

```php
        private readonly StatusGunungApi $gunungApi,
    ) {}
```

dengan:

```php
        private readonly StatusGunungApi $gunungApi,
        private readonly WeatherService $cuaca,
    ) {}
```

3. Ganti:

```php
        // dari sekarang memotong baris sore/malamnya meskipun datanya sudah tersimpan (P12).
        $baris = WeatherSnapshot::query()
            ->where('adm4_code', $trip->trail->weather_adm4_code)
```

dengan:

```php
        // dari sekarang memotong baris sore/malamnya meskipun datanya sudah tersimpan (P12).
        $snapshot = WeatherSnapshot::query()
            ->where('adm4_code', $trip->trail->weather_adm4_code)
```

4. Ganti:

```php
            ->orderBy('forecast_at')
            ->get()
            ->map(fn (WeatherSnapshot $s) => [
```

dengan:

```php
            ->orderBy('forecast_at')
            ->get();
        $baris = $snapshot
            ->map(fn (WeatherSnapshot $s) => [
```

5. Ganti:

```php
            : null;

        return $dasar + ['keadaan' => 'tersedia', 'pesan' => null, 'baris' => $baris, 'sebagian' => $sebagian];
    }
```

dengan:

```php
            : null;

        // Waktu ambil dan kesegaran dari baris tanggal trip yang tampil, bukan dari konteks 72 jam
        // WeatherService (titipan W4, ruling W8-R26): pada area yang sama keduanya bisa diambil
        // pada jalan refresh yang berbeda, dan kesegaran harus menyebut data yang dibaca pendaki.
        $diambil = $snapshot->max('fetched_at');
        $kesegaranBaris = $this->cuaca->freshness($diambil);

        return [
            ...$dasar,
            'diperbarui' => Timezone::display($diambil, $zona, 'j M H.i'),
            'kesegaran' => $kesegaranBaris !== FreshnessState::UNKNOWN ? $kesegaranBaris->label() : null,
            'keadaan' => 'tersedia',
            'pesan' => null,
            'baris' => $baris,
            'sebagian' => $sebagian,
        ];
    }
```

`resources/js/pages/Trips/Show.tsx`:

1. Ganti:

```tsx
    penilaian: string | null;
    izin: { ditutup: boolean; pesan: string; tautanPemesanan: string | null } | null;
    hasilPendakian: string | null;
```

dengan:

```tsx
    penilaian: string | null;
    /** PermitService::pemesananDariJendela(): kalimat keadaan, keterangan, dan tautan per keadaan jendela. */
    izin: { keadaan: string | null; judul: string; keterangan: string; tautan: { href: string; teks: string; label: string } | null } | null;
    hasilPendakian: string | null;
```

2. Ganti:

```tsx
                            <Card title="Izin pendakian">
                                <p className={izin.ditutup ? 'text-isi font-bold text-warn-900' : 'text-isi text-secondary'}>{izin.pesan}</p>
                                {izin.tautanPemesanan ? (
                                    <a href={izin.tautanPemesanan} rel="noopener noreferrer" target="_blank" className={tautanTeks}>
                                        Pesan di sistem resmi
                                    </a>
```

dengan:

```tsx
                            <Card title="Izin pendakian">
                                <p className={izin.keadaan === 'DITUTUP' ? 'text-isi font-bold text-warn-900' : 'text-isi font-bold text-primary'}>{izin.judul}</p>
                                <p className="mt-1 text-kecil text-secondary">{izin.keterangan}</p>
                                {izin.tautan ? (
                                    <a href={izin.tautan.href} rel="noopener noreferrer" target="_blank" className={tautanTeks}>
                                        {izin.tautan.teks}
                                        <span className="sr-only"> (membuka tab baru)</span>
                                    </a>
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Trips/TripController.php app/Services/PermitService.php app/Services/Trips/HalamanKesiapan.php tests/Feature/ReadinessForecastFreshnessTest.php tests/Feature/TripPermitWindowTest.php`, lalu `php artisan test --compact --filter='TripPermitWindow|ReadinessForecastFreshness|ReadinessDashboard'`
Expected: PASS. Pada simulasi rencana: 60 test, 60 lulus.

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.254 test, 2.252 lulus, 2 dilewati (sebelum tugas: 2.250).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 42 test lulus.

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 16: Halaman Kabar

Titipan W5-C9 (redesain `/kabar` dan perbaikan `docs/fitur/05-kabar-kondisi.md`); ruling W8-R27; R-009, R-010, R-012.
`/kabar` dibuka dengan pintu ke setiap gunung yang diikuti, lalu kelompok menurut asalnya dengan garis yang sama seperti
Beranda dan halaman gunung: status resmi (dengan kalimat R-012), status gunung api PVMBG hanya untuk gunung yang diikuti
dan tercatat, prakiraan cuaca, dan laporan komunitas bergaris putus-putus yang tidak pernah tampil sebagai pengganti
status resmi. Tanpa gunung yang diikuti, setiap kelompok kosong dan halaman mengajak membuka gunung. Dokumen fitur 05
dikoreksi: kriteria 12 (gunung api) dan 13 (laporkan kesalahan data), sumber luar meja kurator, dan baris status fitur.

**Files:**
- Modify: `app/Services/Community/KabarJalur.php`, `docs/fitur/05-kabar-kondisi.md`, `resources/js/components/trails/KartuGunungApi.tsx`, `resources/js/pages/Community/TrailNews.tsx` (ditulis ulang utuh)
- Test: `tests/Feature/KabarPageTest.php` (baru), `tests/Feature/TrailNewsTest.php`, `tests/Pratinjau/HalamanPratinjauTest.php`

**Interfaces:**
- Consumes: `KabarJalur::untuk(User)` dan `TrailNewsService` (W2 sampai W5, Tugas 2), `StatusGunungApi::terkiniUntuk()` dan `bentuk()` (Tugas 10), `KartuGunungApi` (Tugas 10), `LabelKomunitas` (W5), `Timezone`.
- Produces:
  - `KabarJalur::untuk(User): array{mengikuti: ?string, gunung: array<int, array{id: int, nama: string, url: string}>, resmi: array, gunungApi: array, cuaca: array, komunitas: array}`;
  - halaman `Community/TrailNews.tsx` ditulis ulang; `KartuGunungApi.tsx` mengekspor `TautanLaporan` (tautan laporan PVMBG ke tab baru) untuk dipakai ulang Kabar; halaman pratinjau `kabar`.

- [ ] **Step 1: Tulis test yang gagal.** Buat berkas dengan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan make:test --phpunit --no-interaction KabarPageTest`, lalu isinya diganti persis. `TrailNewsTest` memeriksa garis komunitas (label dan batas
putus-putus), bukan chip lama.

Buat `tests/Feature/KabarPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\VolcanoLevel;
use App\Models\Mountain;
use App\Models\MountainFollow;
use App\Models\User;
use App\Models\VolcanoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman Kabar sesudah redesain W8 (W5-C9; ruling W8-R27): pintu ke setiap gunung yang diikuti,
 * dan kelompok status gunung api PVMBG yang hanya tampil untuk gunung yang diikuti dan sudah punya
 * catatan (DK-43), terpisah dari status akses.
 */
class KabarPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_followed_mountains_link_to_their_pages(): void
    {
        $user = User::factory()->create();
        $gede = Mountain::factory()->create(['name' => 'Gunung Gede']);
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gede->id]);

        $this->actingAs($user)->get(route('news'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('gunung', 1)
            ->where('gunung.0.nama', 'Gunung Gede')
            ->where('gunung.0.url', route('mountains.show', $gede))
            ->where('gunungApi', []));
    }

    public function test_the_volcano_group_lists_only_followed_mountains_with_a_record(): void
    {
        $user = User::factory()->create();
        $merapi = Mountain::factory()->create(['name' => 'Gunung Merapi']);
        $gede = Mountain::factory()->create(['name' => 'Gunung Gede']);
        $lain = Mountain::factory()->create(['name' => 'Gunung Lain']);
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $merapi->id]);
        MountainFollow::create(['user_id' => $user->id, 'mountain_id' => $gede->id]);
        VolcanoStatus::factory()->for($merapi)->create(['level' => VolcanoLevel::SIAGA->value]);
        VolcanoStatus::factory()->for($lain)->create(['level' => VolcanoLevel::AWAS->value]);

        $this->actingAs($user)->get(route('news'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('gunungApi', 1)
            ->where('gunungApi.0.gunung', 'Gunung Merapi')
            ->where('gunungApi.0.label', 'Level III (Siaga)')
            ->where('gunungApi.0.atribusi', VolcanoLevel::ATRIBUSI));
    }

    public function test_following_nothing_sends_empty_groups(): void
    {
        $this->actingAs(User::factory()->create())->get(route('news'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('gunung', [])
            ->where('gunungApi', [])
            ->where('mengikuti', null));
    }
}
```

`tests/Feature/TrailNewsTest.php`:

1. Ganti:

```php
        // resmi memakai LabelSumber (jenis sumber catatan, bukan chip "Resmi" tetap;
        // kepekaannya diuji tersendiri di bawah), sementara laporan komunitas tetap
        // berlatar token community (pasangan yang dihitung ColourContrastTest di kedua
        // tema).
        $this->assertStringContainsString('<LabelSumber jenis={butir.jenisSumber} />', $sumber);
        $this->assertStringContainsString('bg-community-50 px-2 py-0.5 text-xs font-medium text-community-900">Laporan komunitas</span>', $sumber);
        $this->assertStringContainsString('Laporan komunitas tidak mengubah status resmi jalur.', $sumber);
```

dengan:

```php
        // resmi memakai LabelSumber (jenis sumber catatan, bukan chip "Resmi" tetap;
        // kepekaannya diuji tersendiri di bawah). Sejak redesain W8 laporan komunitas
        // memakai label dan garis putus-putus yang sama dengan halaman gunung dan Beranda
        // (LabelKomunitas, KondisiPendaki), di atas token community yang pasangannya
        // dihitung ColourContrastTest di kedua tema.
        $this->assertStringContainsString('<LabelSumber jenis={butir.jenisSumber} />', $sumber);
        $this->assertStringContainsString('<LabelKomunitas />', $sumber);
        $this->assertStringContainsString('border-dashed border-community-500 bg-community-50', $sumber);
        $this->assertStringContainsString('Laporan komunitas tidak mengubah status resmi jalur.', $sumber);
```

- [ ] **Step 2: Jalankan test dan pastikan gagal.**

Run: `php artisan test --compact --filter='TrailNews|KabarPage|EmptyStateCoverage|OfficialStatusSafetySentence|PrdReferenceIntegrity'`
Expected: FAIL. Pada simulasi rencana: 38 test, 34 lulus, 4 gagal. Yang merah: ketiga test `KabarPageTest` (kunci `gunung` dan `gunungApi` belum ada) dan
`TrailNewsTest::test_official_and_community_entries_stay_in_separate_groups` (halaman masih memakai chip lama).

- [ ] **Step 3: Tulis implementasinya.** `TrailNews.tsx` ditulis ulang utuh (Pra-cek butir 1a).

`app/Services/Community/KabarJalur.php`:

1. Ganti:

```php
use App\Services\ConditionAggregatorService;
use App\Services\TrailNewsService;
```

dengan:

```php
use App\Services\ConditionAggregatorService;
use App\Services\DataResmi\StatusGunungApi;
use App\Services\TrailNewsService;
```

2. Ganti:

```php
/**
 * Halaman Kabar dalam tiga kelompok menurut asalnya: status resmi, prakiraan cuaca,
 * dan laporan komunitas (PRD §92). Urutannya mengikuti hierarki otoritas (R-009):
 * yang resmi dibaca lebih dulu, dan laporan komunitas tidak pernah tampil sebagai
 * pengganti status resmi (R-010).
 *
```

dengan:

```php
/**
 * Halaman Kabar dalam kelompok menurut asalnya: status resmi, status gunung api PVMBG (W8,
 * DK-43, hanya bila ada), prakiraan cuaca, dan laporan komunitas (PRD §92). Urutannya mengikuti
 * hierarki otoritas (R-009): yang resmi dibaca lebih dulu, dan laporan komunitas tidak pernah
 * tampil sebagai pengganti status resmi (R-010).
 *
```

3. Ganti:

```php
        private readonly ConditionAggregatorService $kondisi,
    ) {}
```

dengan:

```php
        private readonly ConditionAggregatorService $kondisi,
        private readonly StatusGunungApi $gunungApi,
    ) {}
```

4. Ganti:

```php
    /**
     * @return array{mengikuti: ?string, resmi: array<int, array<string, mixed>>, cuaca: array<int, array<string, mixed>>, komunitas: array<int, array<string, mixed>>}
     */
```

dengan:

```php
    /**
     * @return array{mengikuti: ?string, gunung: array<int, array{id: int, nama: string, url: string}>, resmi: array<int, array<string, mixed>>, gunungApi: array<int, array<string, mixed>>, cuaca: array<int, array<string, mixed>>, komunitas: array<int, array<string, mixed>>}
     */
```

5. Ganti:

```php
        if ($gunung->isEmpty()) {
            return ['mengikuti' => null, 'resmi' => [], 'cuaca' => [], 'komunitas' => []];
        }
```

dengan:

```php
        if ($gunung->isEmpty()) {
            return ['mengikuti' => null, 'gunung' => [], 'resmi' => [], 'gunungApi' => [], 'cuaca' => [], 'komunitas' => []];
        }
```

6. Ganti:

```php
        $butir = $this->kabar->forUser($user);

        return [
```

dengan:

```php
        $butir = $this->kabar->forUser($user);
        $gunungApi = $this->gunungApi->terkiniUntuk($gunung->pluck('id'));

        return [
```

7. Ganti:

```php
            'mengikuti' => $gunung->pluck('name')->join(', ', ' dan '),
            'resmi' => $butir->where('jenis', TrailNewsService::STATUS)
                ->map(fn (array $b) => $this->resmi($b))
                ->values()
```

dengan:

```php
            'mengikuti' => $gunung->pluck('name')->join(', ', ' dan '),
            // Pintu ke halaman setiap gunung yang diikuti, tempat mengikuti dan berhenti mengikuti.
            'gunung' => $gunung->map(fn (Mountain $m) => ['id' => $m->id, 'nama' => $m->name, 'url' => route('mountains.show', $m)])->values()->all(),
            'resmi' => $butir->where('jenis', TrailNewsService::STATUS)
                ->map(fn (array $b) => $this->resmi($b))
                ->values()
                ->all(),
            // Level PVMBG per gunung yang diikuti, hanya yang tercatat (DK-43, ruling W8-R18).
            'gunungApi' => $gunung->filter(fn (Mountain $m) => isset($gunungApi[$m->id]))
                ->map(fn (Mountain $m) => [
                    'gunung' => $m->name,
                    ...$this->gunungApi->bentuk($gunungApi[$m->id], Timezone::isSupported($m->timezone) ? $m->timezone : Timezone::DEFAULT),
                ])
                ->values()
```

`docs/fitur/05-kabar-kondisi.md`:

1. Ganti:

```markdown
11. **Tanpa feed sosial.** Given halaman kabar, When tampil, Then tidak ada jenis "cerita", "tips", maupun reaksi jempol (DK-11, K9).

## Aturan yang berlaku
```

dengan:

```markdown
11. **Tanpa feed sosial.** Given halaman kabar, When tampil, Then tidak ada jenis "cerita", "tips", maupun reaksi jempol (DK-11, K9).
12. **Status gunung api.** Given level PVMBG tercatat untuk gunung yang diikuti atau dibuka, When tampil, Then level, rekomendasi radius, atribusi "PVMBG, Badan Geologi, KESDM (MAGMA Indonesia)", waktu terbit, kesegaran, dan tautan laporannya tampil terpisah dari status akses, tidak pernah dipetakan ke Buka atau Tutup, dan hanya bila ada (DK-43, W8).
13. **Laporkan kesalahan data.** Given pendaki melihat status atau data yang keliru, When ia melapor dari kartu status jalur atau gunung, Then laporannya masuk antrean cek ulang kurator kawasan itu tanpa mengubah status yang tampil (R-010, spec v3 bagian 10.7 langkah 7, W8).

## Aturan yang berlaku
```

2. Ganti:

```markdown
| `data_sources`, `authorities` | sumber dan penerbit | `create_data_sources_table`, `buat_tabel_otoritas_dan_kredensial_ahli` |

Sumber luar: prakiraan BMKG (kode wilayah adm4) lewat `weather:refresh`; status buka dan tutup terbit sebagai surat Kemenhut atau Balai dan dicatat admin dengan sumbernya (spec v3 bagian 10.1); level PVMBG belum diinput (DK-43, W8). `config/hiking.php`: `rate_limits.report_submissions_per_hour` (5).

## State
```

dengan:

```markdown
| `data_sources`, `authorities` | sumber dan penerbit | `create_data_sources_table`, `buat_tabel_otoritas_dan_kredensial_ahli` |

Sumber luar: prakiraan BMKG (kode wilayah adm4) lewat `weather:refresh`; status buka dan tutup terbit sebagai surat Kemenhut atau Balai dan dicatat kurator di meja kurator beserta arsip bukti, verifikasi orang kedua untuk pembukaan, dan jadwal tinjau (spec v3 bagian 10.7, W8); level PVMBG diinput kurator dengan atribusi dan tautan laporan, tanpa scraping (DK-43, W8). `config/hiking.php`: `rate_limits.report_submissions_per_hour` (5), `rate_limits.data_error_reports_per_hour` (5), `freshness.volcano_review_hours` (168).

## State
```

3. Ganti:

```markdown
|---|---|---|
| Kabar resmi | Ada | `app/Services/OfficialStatusService.php`, `resources/js/components/trails/StatusAkses.tsx`, `app/Http/Controllers/Admin/OfficialStatusController.php`, `app/Console/Commands/CheckDataFreshness.php`, `tests/Feature/OfficialStatusTargetTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Feature/DataFreshnessReviewTest.php` |
| Prakiraan cuaca | Sebagian | `app/Services/WeatherService.php`, `app/Console/Commands/RefreshWeatherSnapshots.php`, `tests/Feature/WeatherRefreshTest.php`; tampil waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin, curah hujan, dan penanda jam berisiko belum tampil |
```

dengan:

```markdown
|---|---|---|
| Kabar resmi | Ada | `app/Services/OfficialStatusService.php`, `resources/js/components/trails/StatusAkses.tsx`, `app/Http/Controllers/Curator/CuratorStatusController.php`, `app/Services/DataResmi/TerbitStatus.php`, `app/Console/Commands/CheckDataFreshness.php`, `tests/Feature/OfficialStatusTargetTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Feature/DataFreshnessReviewTest.php`, `tests/Feature/CuratorDeskTest.php` |
| Prakiraan cuaca | Sebagian | `app/Services/WeatherService.php`, `app/Console/Commands/RefreshWeatherSnapshots.php`, `tests/Feature/WeatherRefreshTest.php`; tampil waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin, curah hujan, dan penanda jam berisiko belum tampil |
```

4. Ganti:

```markdown
| Sumber dan kesegaran | Ada | `app/Services/DataFreshnessService.php`, `resources/js/components/community/LabelKesegaran.tsx`, `resources/js/components/trails/Kesegaran.tsx`, `resources/js/components/trails/LabelSumber.tsx`, `tests/Feature/TrustGrammarTest.php` |
| Kabar gunung yang diikuti | Ada | `app/Http/Controllers/Community/TrailNewsController.php`, `app/Services/TrailNewsService.php`, `app/Services/Community/KabarJalur.php`, `resources/js/pages/Community/TrailNews.tsx`, `app/Http/Controllers/Trails/MountainFollowController.php`, `tests/Feature/TrailNewsTest.php` |
| Terima kasih | Ada | `app/Http/Controllers/Trails/ReportThanksController.php`, `app/Http/Requests/Trails/ThankReportRequest.php`, `tests/Feature/ReportThanksTest.php` |
| Status gunung api PVMBG | Belum ada | DK-43, W8 |

## Task NgodingPakeAI
```

dengan:

```markdown
| Sumber dan kesegaran | Ada | `app/Services/DataFreshnessService.php`, `resources/js/components/community/LabelKesegaran.tsx`, `resources/js/components/trails/Kesegaran.tsx`, `resources/js/components/trails/LabelSumber.tsx`, `tests/Feature/TrustGrammarTest.php` |
| Kabar gunung yang diikuti | Ada | `app/Http/Controllers/Community/TrailNewsController.php`, `app/Services/TrailNewsService.php`, `app/Services/Community/KabarJalur.php`, `resources/js/pages/Community/TrailNews.tsx`, `app/Http/Controllers/Trails/MountainFollowController.php`, `tests/Feature/TrailNewsTest.php`, `tests/Feature/KabarPageTest.php` |
| Laporkan kesalahan data | Ada | `app/Http/Controllers/Community/DataErrorReportController.php`, `resources/js/components/trails/LaporkanKesalahan.tsx`, `app/Http/Controllers/Curator/DataErrorCheckController.php`, `tests/Feature/DataErrorReportTest.php` |
| Terima kasih | Ada | `app/Http/Controllers/Trails/ReportThanksController.php`, `app/Http/Requests/Trails/ThankReportRequest.php`, `tests/Feature/ReportThanksTest.php` |
| Status gunung api PVMBG | Ada | `app/Services/DataResmi/StatusGunungApi.php`, `app/Http/Controllers/Curator/VolcanoStatusController.php`, `resources/js/components/trails/KartuGunungApi.tsx`, `tests/Feature/VolcanoStatusTest.php` |

## Task NgodingPakeAI
```

5. Ganti:

```markdown
| 7 | Buat empty state keterangan info tidak tersedia | Status Belum diketahui, cuaca `available: false` | Ada |
| 8 | Rapikan tampilan responsif halaman kabar | `TrailNews.tsx` | Ada; redesain W5 |
| 9 | Buat skema dan migrasi tabel pengumuman resmi | `create_official_statuses_table` | Ada |
```

dengan:

```markdown
| 7 | Buat empty state keterangan info tidak tersedia | Status Belum diketahui, cuaca `available: false` | Ada |
| 8 | Rapikan tampilan responsif halaman kabar | `TrailNews.tsx` | Ada; redesain W8 (W5-C9): kelompok menurut asal dengan garis yang sama seperti Beranda dan halaman gunung, gunung yang diikuti, dan status gunung api |
| 9 | Buat skema dan migrasi tabel pengumuman resmi | `create_official_statuses_table` | Ada |
```

`resources/js/components/trails/KartuGunungApi.tsx`:

1. Ganti:

```tsx
export const KALIMAT_TERPISAH = 'Disimpan terpisah dari status akses, dan tidak pernah diterjemahkan menjadi Buka atau Tutup.';

function TautanLaporan({ href }: { href: string }) {
    return (
```

dengan:

```tsx
export const KALIMAT_TERPISAH = 'Disimpan terpisah dari status akses, dan tidak pernah diterjemahkan menjadi Buka atau Tutup.';

/** Tautan laporan PVMBG yang dikutip, ke tab baru. */
export function TautanLaporan({ href }: { href: string }) {
    return (
```

Tulis ulang `resources/js/pages/Community/TrailNews.tsx` utuh:

```tsx
import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { ButirKomunitas, ButirResmi, CuacaGunung } from '@/components/community/types';
import { KALIMAT_TERPISAH, TautanLaporan } from '@/components/trails/KartuGunungApi';
import { nadaKesegaran } from '@/components/trails/Kesegaran';
import { LabelKomunitas, LabelSumber } from '@/components/trails/LabelSumber';
import { StatusAkses } from '@/components/trails/StatusAkses';
import type { GunungApi } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { dashboard } from '@/routes';
import { photo } from '@/routes/reports';
import { index as trailsIndex, show as trailShow } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    mengikuti: string | null;
    gunung: Array<{ id: number; nama: string; url: string }>;
    resmi: ButirResmi[];
    gunungApi: Array<GunungApi & { gunung: string }>;
    cuaca: CuacaGunung[];
    komunitas: ButirKomunitas[];
}

const judulBagian = 'scroll-mt-4 text-bagian text-primary';
const pengantar = 'mt-1 max-w-prose text-kecil text-secondary';
const kosong = 'mt-3 max-w-prose text-kecil text-secondary';
const tautanTeks = kelas('inline-flex min-h-11 items-center rounded-control text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat);

/**
 * Kabar dari gunung yang diikuti (spec v3 bagian 5 dan 7; PRD §92; redesain W8, W5-C9), dalam
 * kelompok menurut asalnya: status resmi lebih dulu karena otoritasnya paling tinggi (R-009),
 * status gunung api PVMBG bila ada (DK-43), prakiraan BMKG, lalu laporan komunitas yang tidak
 * pernah menggantikan status resmi (R-010). Asal setiap butir terbaca dari label dan garisnya:
 * padat merek untuk catatan bersumber resmi, rambut untuk catatan lain, putus-putus untuk
 * komunitas, bukan dari warna saja. Setiap butir membawa sumber dan waktunya sendiri.
 */
export default function TrailNews() {
    const { gunung, resmi, gunungApi, cuaca, komunitas } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: dashboard.url(), label: 'Beranda' }} title="Kabar Jalur" description="Yang berubah di gunung yang Anda ikuti. Berisi keadaan gunung, bukan aktivitas pendaki lain." />

                {gunung.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada gunung yang Anda ikuti"
                        description="Ikuti gunung dari halamannya, lalu perubahan status resmi dan laporan kondisi dari kawasan itu muncul di sini. Berguna justru ketika Anda sedang tidak merencanakan apa pun: penutupan jalur diumumkan tanpa aba-aba."
                    >
                        <Button variant="secondary" href={trailsIndex.url()}>
                            Telusuri jalur
                        </Button>
                    </EmptyState>
                ) : (
                    <>
                        <section aria-labelledby="kabar-diikuti" className="space-y-2">
                            <h2 id="kabar-diikuti" className="text-meta font-bold text-secondary">
                                Gunung yang Anda ikuti
                            </h2>
                            <ul className="flex flex-wrap gap-2">
                                {gunung.map((g) => (
                                    <li key={g.id}>
                                        <Tautan
                                            href={g.url}
                                            className={kelas('inline-flex min-h-11 items-center rounded-full border border-subtle bg-surface px-4 text-kecil font-bold text-primary hover:bg-surface-sunken', fokusTerlihat)}
                                        >
                                            {g.nama}
                                        </Tautan>
                                    </li>
                                ))}
                            </ul>
                            <nav aria-label="Bagian kabar" className="flex flex-wrap gap-x-4">
                                <a href="#kabar-resmi" className={tautanTeks}>
                                    Status resmi ({resmi.length})
                                </a>
                                {gunungApi.length > 0 ? (
                                    <a href="#kabar-gunung-api" className={tautanTeks}>
                                        Gunung api ({gunungApi.length})
                                    </a>
                                ) : null}
                                <a href="#kabar-cuaca" className={tautanTeks}>
                                    Prakiraan cuaca
                                </a>
                                <a href="#kabar-komunitas" className={tautanTeks}>
                                    Laporan pendaki ({komunitas.length})
                                </a>
                            </nav>
                        </section>

                        <section aria-labelledby="kabar-resmi">
                            <h2 id="kabar-resmi" className={judulBagian}>
                                Status resmi
                            </h2>
                            <p className={pengantar}>
                                Dicatat beserta sumber dan tanggalnya. Selama belum ada keterangan resmi, status ditulis belum diketahui, bukan ditebak terbuka.
                            </p>
                            {resmi.length === 0 ? (
                                <p className={kosong}>Belum ada kabar status dari gunung yang Anda ikuti. Tidak adanya kabar bukan pernyataan bahwa jalurnya terbuka; periksa halaman jalurnya.</p>
                            ) : (
                                <>
                                    <ul className="mt-3 space-y-3">
                                        {resmi.map((b) => (
                                            <ButirStatus key={b.id} butir={b} />
                                        ))}
                                    </ul>
                                    <p className="mt-3 text-kecil text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
                                </>
                            )}
                        </section>

                        {gunungApi.length > 0 ? (
                            <section aria-labelledby="kabar-gunung-api">
                                <h2 id="kabar-gunung-api" className={judulBagian}>
                                    Status gunung api
                                </h2>
                                <p className={pengantar}>{KALIMAT_TERPISAH}</p>
                                <ul className="mt-3 space-y-3">
                                    {gunungApi.map((g) => (
                                        <li key={g.gunung} className="rounded-lg border-[1.5px] border-brand-ink bg-surface p-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <LabelSumber jenis="OFFICIAL" />
                                                <span className="text-meta font-bold text-secondary">{g.gunung}</span>
                                                <Lencana nada={nadaKesegaran(g.kesegaran)}>{g.kesegaran}</Lencana>
                                            </div>
                                            <p className="mt-2 text-kecil font-bold text-primary">{g.label}</p>
                                            <p className="mt-1 break-words text-kecil text-secondary">{g.rekomendasi}</p>
                                            <p className="mt-2 break-words text-meta text-muted">
                                                Sumber: {g.atribusi}, diterbitkan {g.diterbitkan}; dicocokkan kurator {g.diverifikasi}.
                                            </p>
                                            <TautanLaporan href={g.tautan} />
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ) : null}

                        <section aria-labelledby="kabar-cuaca">
                            <h2 id="kabar-cuaca" className={judulBagian}>
                                Prakiraan cuaca
                            </h2>
                            <p className={pengantar}>Prakiraan BMKG untuk wilayah administrasi di sekitar jalur, bukan untuk puncaknya. Cuaca gunung dapat berbeda jauh dari area di bawahnya.</p>
                            <ul className="mt-3 space-y-3">
                                {cuaca.map((g) => (
                                    <li key={g.gunung} className="rounded-lg border border-subtle bg-surface p-4">
                                        <p className="text-kecil font-bold text-primary">{g.gunung}</p>
                                        {g.pesan ? (
                                            <p className="mt-1 text-kecil text-secondary">{g.pesan}</p>
                                        ) : (
                                            <ul className="mt-2 divide-y divide-subtle">
                                                {g.wilayah.map((w) => (
                                                    <li key={w.kode} className="py-3 first:pt-0 last:pb-0">
                                                        <p className="text-kecil text-secondary">
                                                            Wilayah acuan <span className="text-primary">{w.area}</span>, untuk {w.jalur}
                                                        </p>
                                                        {w.ringkasan ? (
                                                            <p className="mt-1 text-kecil text-primary">
                                                                <span className="text-secondary">{w.waktuPrakiraan}:</span> {w.ringkasan}
                                                            </p>
                                                        ) : null}
                                                        {w.pesan ? <p className="mt-1 text-kecil text-secondary">{w.pesan}</p> : null}
                                                        <p className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-meta text-muted">
                                                            <span>
                                                                Sumber: {w.sumber}
                                                                {w.diperbarui ? `, diperbarui ${w.diperbarui}` : ''}
                                                            </span>
                                                            {w.diperbarui ? <Lencana nada={w.kesegaranLama ? 'peringatan' : 'merek'}>{w.kesegaran}</Lencana> : null}
                                                        </p>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>

                        <section aria-labelledby="kabar-komunitas">
                            <h2 id="kabar-komunitas" className={judulBagian}>
                                Laporan pendaki
                            </h2>
                            <p className={pengantar}>Laporan komunitas tidak mengubah status resmi jalur. Yang tampil sudah lolos moderasi, dan kesegarannya dihitung dari tanggal pendakian.</p>
                            {komunitas.length === 0 ? (
                                <p className={kosong}>Belum ada laporan kondisi terbaru dari komunitas.</p>
                            ) : (
                                <ul className="mt-3 space-y-3">
                                    {komunitas.map((b) => (
                                        <ButirLaporan key={b.id} butir={b} />
                                    ))}
                                </ul>
                            )}
                        </section>
                    </>
                )}
            </div>
        </div>
    );
}

/** Garis padat merek hanya untuk catatan bersumber OFFICIAL; label sumber mengikuti jenisnya (R-009). */
function ButirStatus({ butir }: { butir: ButirResmi }) {
    return (
        <li className={kelas('rounded-lg bg-surface p-4', butir.jenisSumber === 'OFFICIAL' ? 'border-[1.5px] border-brand-ink' : 'border border-subtle')}>
            <div className="flex flex-wrap items-center gap-2">
                <LabelSumber jenis={butir.jenisSumber} />
                <span className="break-words text-meta text-secondary">{butir.sumber}</span>
            </div>
            <p className="mt-2 flex flex-wrap items-center gap-x-2">
                <StatusAkses nilai={butir.status} label={butir.statusLabel} cakupan={butir.cakupan} />
                <span className="break-words text-kecil text-primary">{butir.judul}</span>
            </p>
            {butir.alasan ? <p className="mt-1 break-words text-kecil text-secondary">{butir.alasan}</p> : null}
            <p className="mt-2 text-meta text-muted">
                Dicatat <time dateTime={butir.waktuIso}>{butir.waktu}</time>
            </p>
        </li>
    );
}

/** Masukan pendaki bergaris putus-putus komunitas; tidak pernah mengubah status resmi. Foto tidak pernah menjadi kepala halaman (R-017). */
function ButirLaporan({ butir }: { butir: ButirKomunitas }) {
    return (
        <li className="rounded-lg border-[1.5px] border-dashed border-community-500 bg-community-50 p-4">
            <div className="flex flex-wrap items-center gap-2">
                <LabelKomunitas />
                <span className="break-words text-meta font-bold text-community-900">{butir.judul}</span>
                <Lencana nada={butir.kesegaranLama ? 'peringatan' : 'netral'}>{butir.kesegaran}</Lencana>
            </div>
            {butir.tag.length > 0 ? (
                <ul aria-label="Kondisi yang dilaporkan" className="mt-2 flex flex-wrap gap-1.5">
                    {butir.tag.map((t) => (
                        <li key={t}>
                            <Lencana nada="komunitas" bentuk="pil">
                                {t}
                            </Lencana>
                        </li>
                    ))}
                </ul>
            ) : null}
            {butir.catatan ? <p className="mt-2 break-words text-kecil text-primary">{butir.catatan}</p> : null}
            {butir.foto ? (
                <a href={photo.url(butir.id)} className={kelas('mt-3 inline-block rounded-control', fokusTerlihat)}>
                    <img src={photo.url(butir.id)} alt={`Foto dari laporan kondisi ${butir.judul}`} loading="lazy" className="h-24 w-32 rounded-control border border-subtle object-cover" />
                </a>
            ) : null}
            <p className="mt-2 text-meta text-community-900">
                Dilaporkan {butir.pelapor}, <time dateTime={butir.waktuIso}>{butir.waktu}</time>
                {butir.tanggalPendakian ? `, dari pendakian ${butir.tanggalPendakian}` : ''}
                {butir.terimaKasih > 0 ? `. ${butir.terimaKasih} pendaki terbantu.` : '.'}
            </p>
            {butir.jalurSlug ? (
                <Tautan href={trailShow.url({ trail: butir.jalurSlug })} className={tautanTeks}>
                    Buka halaman jalur
                </Tautan>
            ) : null}
        </li>
    );
}

TrailNews.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

`tests/Pratinjau/HalamanPratinjauTest.php`:

1. Ganti:

```php
    /**
     * Halaman gunung dengan foto berlisensi (W8, DK-37): kepala halaman foto dengan atribusi di
```

dengan:

```php
    /**
     * Halaman Kabar sesudah redesain W8 (W5-C9): Budi mengikuti Gunung Gede, jadi status resmi,
     * status gunung api contoh, dan laporan pendaki contoh ikut terpotret.
     */
    public function test_kabar(): void
    {
        $budi = $this->sebagaiBudi();
        MountainFollow::create(['user_id' => $budi->id, 'mountain_id' => Mountain::where('slug', 'gunung-gede')->value('id')]);

        $this->tulisPratinjau('kabar', $this->get(self::ASAL.'/kabar'), 'Community/TrailNews');
    }

    /**
     * Halaman gunung dengan foto berlisensi (W8, DK-37): kepala halaman foto dengan atribusi di
```

- [ ] **Step 4: Build dan tipe.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`, lalu `node_modules/.bin/tsc --noEmit`
Expected: `✓ built`; tsc tanpa keluaran galat.

- [ ] **Step 5: Jalankan test dan pastikan lulus.**

Run: `vendor/bin/pint --format agent app/Services/Community/KabarJalur.php tests/Feature/KabarPageTest.php tests/Feature/TrailNewsTest.php tests/Pratinjau/HalamanPratinjauTest.php`, lalu `php artisan test --compact --filter='TrailNews|KabarPage|EmptyStateCoverage|OfficialStatusSafetySentence|PrdReferenceIntegrity'`
Expected: PASS. Pada simulasi rencana: 38 test, 38 lulus.

Run: `LC_ALL=C grep -nP '\xE2\x80\x94' docs/fitur/05-kabar-kondisi.md resources/js/pages/Community/TrailNews.tsx`
Expected: tanpa keluaran (tanpa em dash).

- [ ] **Step 6: Suite, format, dan pratinjau.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.257 test, 2.255 lulus, 2 dilewati (sebelum tugas: 2.254).

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 43 test lulus (sebelum tugas: 42; halaman baru `kabar`).

- [ ] **Step 7: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 17: Dokumen

`DESIGN.md` (pola layar meja kurator, kartu gunung api, foto gunung, dan laporkan kesalahan data), `docs/ARCHITECTURE.md`
(layanan `DataResmi`, satu jalan tulis status, disk bukti dan foto, kunci `.env`), dan dokumen fitur 02, 05, dan 08
(status FAKTA dengan jalur berkas dan test, ruling W8). `docs/PRD.md` dan `docs/KEBIJAKAN-DATA-PRIBADI.md` tidak
disunting (Konflik 8), dan `HANDOVER.md` diperbarui koordinator di Tugas 18. Setiap jangkar dokumen satu baris atau
sepenggal baris, karena dokumen di repo nyata dapat berbeda dengan dasar sesudah W5 sampai W7 (Pra-cek butir 1).

**Files:**
- Modify: `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur/02-detail-rute.md`, `docs/fitur/05-kabar-kondisi.md`, `docs/fitur/08-konsol-pengelola.md`

**Interfaces:**
- Consumes: nama kelas, rute, kolom, config, dan test Tugas 1 sampai 16.
- Produces: dokumen yang menyebut keadaan W8 sebagai FAKTA dengan bukti berkas (R-023).

- [ ] **Step 1: Catat garis dasar.** Tidak ada test baru; test penjaga dokumen dijalankan sebelum dan sesudah suntingan.

- [ ] **Step 2: Jalankan test penjaga dokumen.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrity|DesignDirection|TeamDocsIndex|RepoHygiene|RepoPolicyFiles'`
Expected: PASS. Pada simulasi rencana: 37 test, 37 lulus sebelum dokumen disunting.

- [ ] **Step 3: Sunting dokumen.**

`DESIGN.md`:

1. Ganti:

```markdown
Foto gunung diunggah admin dengan kolom wajib pembuat, lisensi, URL sumber, teks atribusi, dan teks alternatif, lalu
tampil dengan atribusinya. Tanpa foto, hero memakai visual data (bentuk rute di atas pola kontur). Foto komunitas tidak
pernah menjadi hero (R-017). Avatar berupa unggahan pengguna atau inisial (`Avatar`); data contoh memakai inisial,
tidak pernah wajah stok (antislop R-23).
```

dengan:

```markdown
Foto gunung diunggah admin dengan kolom wajib pembuat, lisensi, URL sumber, teks atribusi, dan teks alternatif, lalu
tampil dengan atribusinya. Atribusi duduk di pojok kanan bawah foto pada keping permukaan buram (bukan teks di atas foto),
jadi kontrasnya tidak bergantung pada isi foto, dan keping itu menautkan sumber bila tercatat (W8,
`components/trails/gunung/FotoGunung.tsx`). Tanpa foto, hero memakai visual data (bentuk rute di atas pola kontur).
Foto komunitas tidak pernah menjadi hero (R-017): kepala halaman hanya membaca tabel foto gunung. Avatar berupa unggahan pengguna atau inisial (`Avatar`); data contoh memakai inisial,
tidak pernah wajah stok (antislop R-23).
```

2. Ganti:

```markdown
menulis punya wilayah `role="status"` sendiri yang selalu terpasang.

**Fokus tidak pernah jatuh ke body.** Chip saringan yang dilepas menyerahkan fokus ke chip tetangga atau wadah grupnya;
```

dengan:

```markdown
menulis punya wilayah `role="status"` sendiri yang selalu terpasang.

**Data resmi** (W8, spec v3 bagian 10.7). Meja kurator adalah halaman kerja, dipakai di layar lebar tetapi utuh di
papan ketik dan di 320 px: antrean bertajuk dengan hitungan, setiap baris menyebut sasaran, status dengan bentuk dan kata,
keadaan verifikasinya sebagai lencana, dan satu aksi; langkah alur catatan ditulis sebagai daftar bernomor dengan kata
"Selesai" atau "Belum", bukan warna saja. Status gunung api PVMBG tampil sebagai kartu bergaris padat merek dengan
kalimat tetap "Disimpan terpisah dari status akses, dan tidak pernah diterjemahkan menjadi Buka atau Tutup", atribusi
PVMBG, dan tautan laporannya; di Cek kesiapan ia satu baris di "Kondisi terkini". "Laporkan kesalahan data" adalah
tautan teks di bawah kartu status yang membuka dialog, dan kalimatnya menyebut bahwa laporan tidak mengubah status.

**Fokus tidak pernah jatuh ke body.** Chip saringan yang dilepas menyerahkan fokus ke chip tetangga atau wadah grupnya;
```

`docs/ARCHITECTURE.md`:

1. Ganti:

```markdown
| Mencatat aturan izin sebuah jalur | `/admin/permits` |
| Menambah halaman admin baru | Daftarkan route-nya, lalu tambahkan ke `resources/views/components/ui/admin-nav.blade.php` |
```

dengan:

```markdown
| Mencatat aturan izin sebuah jalur | `/admin/permits` |
| Mencatat status resmi | Meja kurator `/kurator` (W8): kurator anggota aktif badan yang berwenang atas kawasan itu, dengan aturan terbit asimetris di `App\Services\DataResmi\TerbitStatus`. Halaman admin `/admin/official-statuses` hanya membaca |
| Memberi peran kurator | Peran `curator` pada `users.role` diberikan pemilik (Q11); keanggotaan badan di `/admin/authorities` (`authority_members`). Tidak ada akun kurator yang dibuat otomatis |
| Jadwal tinjau status resmi per jenis | `config/hiking.php` → `freshness.status_review_hours` (Dibatasi, Tutup, Belum diketahui) dan `field_pack.open_status_stale_after_hours` (Buka, 24 jam); level gunung api `freshness.volcano_review_hours` |
| Disk dan batas arsip bukti serta foto gunung | `config/filesystems.php` → `evidence_disk` (`EVIDENCE_DISK`) dan `mountain_photos_disk` (`MOUNTAIN_PHOTOS_DISK`); `config/hiking.php` → `uploads.evidence_*` dan `uploads.mountain_photo_*`. Arsip bukti hanya diunduh lewat `curator.evidence.show`, foto gunung lewat `mountains.photo` |
| Batas jarak pos dari garis rute untuk km sepanjang jalur | `config/hiking.php` → `map.batas_jarak_pos_m` |
| Menambah halaman admin baru | Daftarkan route-nya, lalu tambahkan ke `resources/views/components/ui/admin-nav.blade.php` |
```

2. Ganti:

```markdown
| `Trails\TautanBagikan` | Tautan bagikan perbandingan (FR-23): token acak 192 bit yang hanya disimpan hash SHA-256-nya, masa berlaku, batas tautan aktif, dan cabut oleh pemiliknya |
| `Trails\ProfilElevasiJalur`, `Trails\PosSepanjangJalur` | Profil elevasi dengan ruas tercuram, dan km pos di sepanjang garis rute (PostGIS); tanpa geometri tidak ada km yang ditebak |
| `Community\HubProgres`, `Community\PencapaianPribadi` | Kelas halaman Progres (W7): ringkasan tahun, grafik per pendakian, target bulanan, pencapaian dari riwayat sendiri, riwayat dua sumber (trip dan catatan manual), dan peta gunung; angka satu pendakian dari satu aturan (`HubProgres::angka`) |
```

dengan:

```markdown
| `Trails\TautanBagikan` | Tautan bagikan perbandingan (FR-23): token acak 192 bit yang hanya disimpan hash SHA-256-nya, masa berlaku, batas tautan aktif, dan cabut oleh pemiliknya |
| `Trails\ProfilElevasiJalur`, `Trails\PosSepanjangJalur` | Profil elevasi dengan ruas tercuram, dan km pos di sepanjang garis rute (PostGIS); tanpa geometri tidak ada km yang ditebak, dan pos yang jauh dari garis atau urutan km yang mundur tidak diberi km (W8) |
| `DataResmi\TerbitStatus`, `DataResmi\ArahPerubahan`, `DataResmi\PemeriksaanKonsistensi` | Layanan terbit asimetris meja kurator (W8): draf, kirim, verifikasi orang kedua, tinjau ulang, persoalkan, dan rantai penggantian; pembatasan berbukti primer terbit seketika, pembukaan menunggu orang kedua, berita saja tidak pernah membuka; pemeriksaan konsistensi otomatis sebelum kirim dan verifikasi |
| `DataResmi\Yurisdiksi`, `DataResmi\MejaKurator`, `DataResmi\HalamanCatatanStatus`, `DataResmi\ArsipBukti` | Kawasan kurator dari keanggotaan aktif (satu-satunya tempat aturan yurisdiksi), kelas halaman meja dan catatan, dan arsip bukti di disk privat dengan EXIF dibuang dan SHA-256 |
| `DataResmi\StatusGunungApi`, `Mountains\FotoGunung` | Level gunung api PVMBG terakhir per gunung (DK-43, tidak pernah dibaca status akses atau Route Fit) dan foto gunung berlisensi (DK-37) |
| `Community\HubProgres`, `Community\PencapaianPribadi` | Kelas halaman Progres (W7): ringkasan tahun, grafik per pendakian, target bulanan, pencapaian dari riwayat sendiri, riwayat dua sumber (trip dan catatan manual), dan peta gunung; angka satu pendakian dari satu aturan (`HubProgres::angka`) |
```

3. Ganti:

```markdown
- **Kurasi data lapangan.** Per 20 September 2026: 7 jalur, semuanya tayang, **0 punya geometri**; 34 checkpoint, **0 punya koordinat**; 7 status resmi tercatat, semuanya `UNKNOWN`. Artinya peta tidak dapat menggambar jalur, mode pendakian tidak dapat mengenali checkpoint terdekat, dan ketujuh jalur yang tayang tidak lolos gerbang §110. Koordinat gunung sungguhan tidak boleh dikarang: angka yang salah pada fitur navigasi lapangan lebih berbahaya daripada tidak ada angka. Ini pekerjaan kurator, bukan pekerjaan kode.
- **Pengambilan sumber resmi otomatis.** §98 menyebutnya pekerjaan background. Belum ada, dan memang belum ada API resmi yang dapat dipanggil; status tetap dicatat manual oleh kurator. Pemeriksaan data basi sendiri sudah ada (`data:freshness-check` dan panel "Perlu ditinjau"), sehingga status yang kedaluwarsa tidak lagi mundur ke `UNKNOWN` tanpa suara.

## Jalur kegagalan
```

dengan:

```markdown
- **Kurasi data lapangan.** Per 20 September 2026: 7 jalur, semuanya tayang, **0 punya geometri**; 34 checkpoint, **0 punya koordinat**; 7 status resmi tercatat, semuanya `UNKNOWN`. Artinya peta tidak dapat menggambar jalur, mode pendakian tidak dapat mengenali checkpoint terdekat, dan ketujuh jalur yang tayang tidak lolos gerbang §110. Koordinat gunung sungguhan tidak boleh dikarang: angka yang salah pada fitur navigasi lapangan lebih berbahaya daripada tidak ada angka. Ini pekerjaan kurator, bukan pekerjaan kode.
- **Pengambilan sumber resmi otomatis.** §98 menyebutnya pekerjaan background. Belum ada, dan memang belum ada API resmi yang dapat dipanggil; status tetap dicatat kurator di meja kurator (W8), level PVMBG juga (Q13).
- **Kurator yang bertugas.** Meja kurator sudah ada, tetapi peran `curator` dan keanggotaan badan belum diberikan kepada siapa pun (Q11). Sampai pemilik memberikannya, status lama dari seeder kedaluwarsa menjadi Belum diketahui dan menunggu di antrean tinjau. Pemeriksaan data basi sendiri sudah ada (`data:freshness-check` dan panel "Perlu ditinjau"), sehingga status yang kedaluwarsa tidak lagi mundur ke `UNKNOWN` tanpa suara.

## Jalur kegagalan
```

`docs/fitur/02-detail-rute.md`:

1. Ganti:

```markdown
**Bahan:** features/02-detail-rute.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.4.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna
```

dengan:

```markdown
**Bahan:** features/02-detail-rute.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.4.
**Diperiksa terhadap kode:** 8 Oktober 2026 untuk baris yang disentuh W8; selebihnya 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna
```

2. Ganti:

```markdown
|---|---|---|
| `trails` | angka kunci, karakter medan, `elevation_profile`, `geometry` (PostgreSQL), `data_source_id`, `is_published` | `create_trails_table`, `add_elevation_profile_to_trails_table` |
| `trail_segments` | `sequence`, `name`, `description`, `distance_km`, `elevation_gain_m`, `technical_demand`, `terrain_character`, `geometry` | `create_trail_segments_table` |
```

dengan:

```markdown
|---|---|---|
| `trails` | angka kunci, karakter medan, `elevation_profile`, `geometry` (PostgreSQL), `data_source_id`, `is_published`; `technical_demand`, `navigation_complexity`, dan `camping_available` boleh kosong (belum diketahui, W8) dan `elevation_loss_m` hanya turunan yang diukur | `create_trails_table`, `add_elevation_profile_to_trails_table`, `make_trail_characteristics_nullable`, `clear_copied_elevation_loss_on_trails` |
| `trail_segments` | `sequence`, `name`, `description`, `distance_km`, `elevation_gain_m`, `technical_demand`, `terrain_character`, `geometry` | `create_trail_segments_table` |
```

3. Ganti:

```markdown
| Halaman publik SEO | Ada, rute lama | `app/Http/Controllers/PublicTrailController.php`, `resources/views/public/trail.blade.php` |
| Halaman gunung | Ada (W3) | `/mountains/{slug}` lewat `app/Http/Controllers/Trails/MountainPageController.php`, `app/Services/Mountains/HalamanGunung.php`, `resources/js/pages/Mountains/Show.tsx`, `tests/Feature/MountainPageTest.php` (DK-44); foto berlisensi dan status gunung api menunggu W8 (DK-37, DK-43) |
| Status gunung api PVMBG | Belum ada | DK-43, W8 |

## Task NgodingPakeAI
```

dengan:

```markdown
| Halaman publik SEO | Ada, rute lama | `app/Http/Controllers/PublicTrailController.php`, `resources/views/public/trail.blade.php` |
| Halaman gunung | Ada (W3) | `/mountains/{slug}` lewat `app/Http/Controllers/Trails/MountainPageController.php`, `app/Services/Mountains/HalamanGunung.php`, `resources/js/pages/Mountains/Show.tsx`, `tests/Feature/MountainPageTest.php` (DK-44); foto berlisensi dengan atribusi di pojok sejak W8 (`resources/js/components/trails/gunung/FotoGunung.tsx`, `tests/Feature/MountainPhotoLicenseTest.php`, DK-37) |
| Status gunung api PVMBG | Ada (W8) | `app/Services/DataResmi/StatusGunungApi.php`, `resources/js/components/trails/KartuGunungApi.tsx` di halaman gunung, rincian jalur, dan Cek kesiapan, `tests/Feature/VolcanoStatusTest.php` (DK-43) |
| Laporkan kesalahan data | Ada (W8) | `resources/js/components/trails/LaporkanKesalahan.tsx` di kartu status jalur dan gunung, `app/Http/Controllers/Community/DataErrorReportController.php`, `tests/Feature/DataErrorReportTest.php` |

## Task NgodingPakeAI
```

`docs/fitur/05-kabar-kondisi.md`:

1. Ganti:

```markdown
**Bahan:** features/05-kabar-kondisi.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.4, 7, dan 10.3.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna
```

dengan:

```markdown
**Bahan:** features/05-kabar-kondisi.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.4, 7, dan 10.3.
**Diperiksa terhadap kode:** 8 Oktober 2026 untuk baris yang disentuh W8; selebihnya 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna
```

`docs/fitur/08-konsol-pengelola.md`:

1. Ganti:

```markdown
**Bahan:** features/08-konsol-pengelola.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 10.7.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna
```

dengan:

```markdown
**Bahan:** features/08-konsol-pengelola.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 10.7.
**Diperiksa terhadap kode:** 8 Oktober 2026 untuk baris yang disentuh W8; selebihnya 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna
```

2. Ganti:

```markdown
- **Pintu:** dari menu akun sesuai peran (§145); otorisasi tetap di server (§76).
- **Rute:** `/admin/*` (peran admin), `/moderation` (peran moderator), `/jalur/{trail}/geometri` dan `/jalur/{trail}/pos` (admin atau ahli berkredensial untuk kawasannya, lewat Policy), `/kontribusi` (ahli).

## Kriteria penerimaan
```

dengan:

```markdown
- **Pintu:** dari menu akun sesuai peran (§145); otorisasi tetap di server (§76).
- **Rute:** `/admin/*` (peran admin), `/moderation` (peran moderator), `/jalur/{trail}/geometri` dan `/jalur/{trail}/pos` (admin atau ahli berkredensial untuk kawasannya, lewat Policy), `/kontribusi` (ahli), `/kurator` (kurator anggota aktif badan resmi, lewat `OfficialStatusPolicy`; W8).

## Kriteria penerimaan
```

3. Ganti:

```markdown
4. **Pos.** Given pengelola berhak, When menambah, mengubah, memindah urutan, atau menghapus pos, Then pos selalu milik jalur di alamatnya (scopeBindings) dan penghapusan yang merusak perjalanan dijaga.
5. **Status resmi.** Given admin mencatat status, When tersimpan, Then sumber, cakupan, dan waktunya tercatat, dan pencatatnya diambil dari sesi, bukan formulir.
6. **Izin dan sumber data.** Given admin, When mencatat aturan izin atau sumber data, Then sumber dan waktu verifikasi tercatat; sumber dapat diverifikasi atau diperdebatkan.
```

dengan:

```markdown
4. **Pos.** Given pengelola berhak, When menambah, mengubah, memindah urutan, atau menghapus pos, Then pos selalu milik jalur di alamatnya (scopeBindings) dan penghapusan yang merusak perjalanan dijaga.
5. **Status resmi.** Given kurator mencatat status di meja kurator, When dikirim, Then penerbit, nomor dan tanggal surat, arsip bukti dengan SHA-256, cakupan, mulai berlaku, cara berakhir, dan ringkasannya tercatat; pencatat, pemeriksa, dan badan diambil dari sesi dan keanggotaan, bukan formulir; pembatasan berbukti primer terbit seketika dan menunggu kurator kedua, pembukaan baru terbaca sesudah diverifikasi orang kedua, dan berita saja tidak pernah membuka (spec v3 bagian 10.7, W8). Halaman admin status resmi hanya membaca.
6. **Izin dan sumber data.** Given admin, When mencatat aturan izin atau sumber data, Then sumber dan waktu verifikasi tercatat; sumber dapat diverifikasi atau diperdebatkan.
```

4. Ganti:

```markdown
11. **Kelola peran.** Given admin perlu mengubah peran pengguna, When fitur ini dibangun, Then perubahan tercatat di audit dan langsung berlaku (belum ada; lihat status).

## Aturan yang berlaku
```

dengan:

```markdown
11. **Kelola peran.** Given admin perlu mengubah peran pengguna, When fitur ini dibangun, Then perubahan tercatat di audit dan langsung berlaku (belum ada; lihat status).
12. **Laporan kesalahan data.** Given pendaki melaporkan kesalahan data, When kurator kawasannya membuka meja, Then laporan itu ada di antrean cek ulang tanpa identitas pelapor, dan menandainya sudah dicek wajib menyebut hasilnya dan tidak mengubah status (W8).
13. **Gunung api dan foto.** Given kurator mencatat level PVMBG atau admin mengunggah foto gunung, When tersimpan, Then level membawa atribusi dan tautan laporannya, foto membawa lisensi, pembuat, sumber, atribusi, dan teks alternatif, keduanya diaudit (DK-43, DK-37, W8).

## Aturan yang berlaku
```

5. Ganti:

```markdown
| `analytics_events`, `usability_sessions`, `scheduled_task_runs` | corong, sesi uji kegunaan dan SUS, riwayat tugas terjadwal | `create_analytics_events_table`, `create_usability_sessions_table`, `create_scheduled_task_runs_table` |
| `users.role` | `hiker`, `moderator`, `admin` | `add_role_to_users_table` |

## State
```

dengan:

```markdown
| `analytics_events`, `usability_sessions`, `scheduled_task_runs` | corong, sesi uji kegunaan dan SUS, riwayat tugas terjadwal | `create_analytics_events_table`, `create_usability_sessions_table`, `create_scheduled_task_runs_table` |
| `users.role` | `hiker`, `moderator`, `admin`, `curator` (W8; diberikan pemilik, Q11) | `add_role_to_users_table` |
| `authority_members`, `official_statuses` (provenans), `data_error_reports`, `volcano_statuses`, `mountain_photos` | anggota badan untuk meja kurator; provenans, keadaan verifikasi, dan jadwal tinjau status; laporan kesalahan data; level PVMBG; foto gunung berlisensi | `create_authority_members_table`, `add_provenance_to_official_statuses_table`, `create_data_error_reports_table`, `create_volcano_statuses_table`, `create_mountain_photos_table` |

## State
```

6. Ganti:

```markdown
| Kelola peran pengguna | Belum ada | Tidak ada rute atau halaman; `users.role` diubah langsung di basis data. Cakupan wilayah ahli sudah ada lewat `credential_mountain` |
| Meja kurator data resmi | Belum ada | DK-36, spec v3 bagian 10.7, W8 |

## Task NgodingPakeAI
```

dengan:

```markdown
| Kelola peran pengguna | Belum ada | Tidak ada rute atau halaman; `users.role` diubah langsung di basis data. Cakupan wilayah ahli sudah ada lewat `credential_mountain` |
| Meja kurator data resmi | Ada (W8) | `app/Http/Controllers/Curator/`, `app/Services/DataResmi/`, `app/Policies/OfficialStatusPolicy.php`, `resources/js/pages/Curator/`, `tests/Feature/CuratorDeskTest.php`, `tests/Feature/AsymmetricPublishRuleTest.php`, `tests/Feature/OfficialStatusConsistencyTest.php`, `tests/Feature/EvidenceArchiveTest.php`, `tests/Feature/AuthorityMemberPolicyTest.php` |
| Status gunung api dan foto gunung | Ada (W8) | `app/Http/Controllers/Curator/VolcanoStatusController.php`, `app/Http/Controllers/Admin/MountainPhotoManagerController.php`, `tests/Feature/VolcanoStatusTest.php`, `tests/Feature/MountainPhotoLicenseTest.php` |

## Task NgodingPakeAI
```

- [ ] **Step 4: Jalankan test penjaga dokumen lagi.**

Run: `php artisan test --compact --filter='PrdReferenceIntegrity|DesignDirection|TeamDocsIndex|RepoHygiene|RepoPolicyFiles'`
Expected: PASS. Pada simulasi rencana: 37 test, 37 lulus.

Run: `LC_ALL=C grep -nP '\xE2\x80\x94' DESIGN.md docs/ARCHITECTURE.md docs/fitur/02-detail-rute.md docs/fitur/05-kabar-kondisi.md docs/fitur/08-konsol-pengelola.md`
Expected: tanpa keluaran (tanpa em dash).

- [ ] **Step 5: Suite.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.257 test, 2.255 lulus, 2 dilewati (sebelum tugas: 2.257).

- [ ] **Step 6: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

### Task 18: Verifikasi gelombang (koordinator)

Induk W8 "Selesai bila" (aturan penutup gelombang; test paritas PostgreSQL untuk setiap migrasi hijau di CI); ruling
W8-R1 sampai W8-R28. Dikerjakan koordinator sesudah Tugas 1 sampai 17 selesai dan lolos tinjauan per tugas. Setiap
klaim di laporan disertai keluaran perintah, jalur berkas, atau tangkapan (R-023). Angka "pada simulasi rencana" adalah
hasil menjalankan kode rencana ini di salinan keadaan akhir simulasi W7, bukan hasil di repo; selisih dari W5 sampai W7
di repo nyata ditambahkan koordinator. Harness pratinjau memakai port 8100 milik koordinator.

**Files:**
- Create: `docs/desain/tangkapan/w8/*.webp` (tangkapan), `docs/desain/tangkapan/w8/README.md` (perbandingan dengan acuan)
- Modify: `HANDOVER.md` (bagian 0: baris "Terakhir diperbarui" dan subbagian W8), `.superpowers/sdd/2026-09-26-memount-v3-program.md` (baris W8; berkas ini diabaikan git)

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 17; harness pratinjau (diperluas Tugas 8 sampai 11 dan 16); gambar acuan `docs/desain/prototype/acuan/03-gunung.png`, `04-detail-jalur.png`, dan `10-cek-kesiapan.png` (meja kurator dan Kabar tidak punya acuan prototype).
- Produces: bukti akhir W8, daftar izin akhir yang diperbarui (sembilan migrasi W8, pemberian kurator pertama, kurasi status sebelum 31 Oktober 2026, disk produksi bukti dan foto), dan titik lanjut W9.

- [ ] **Step 1: Catat keadaan kerja.**

Run: `git status --short | wc -l; git diff --stat | tail -1`
Expected: selisih terhadap catatan akhir W7 cocok dengan gabungan daftar Files Tugas 1 sampai 17. Pada simulasi rencana,
Tugas 1 sampai 17 menambah 92 berkas, mengubah 96, dan menghapus 1
(`app/Http/Requests/Admin/StoreOfficialStatusRequest.php`) terhadap keadaan akhir simulasi W7.

Run: `ls database/migrations | grep -c '^2026_10_09_'`
Expected: 9 (`000100` sampai `000810`, W8-R2).

- [ ] **Step 2: Suite penuh, tipe, format, dan paritas PostgreSQL.**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 0 gagal, 0 error. Pada simulasi rencana: 2.257 test, 2.255 lulus, 2 dilewati; di repo nyata angkanya bergeser oleh test W5 sampai W7 dan perbaikan putaran yang tidak ada di dasar simulasi.

Run: `php artisan test --compact`
Expected: 0 gagal. Pada simulasi rencana: 2.334 test, 2.255 lulus, 79 dilewati (77 test Spatial tanpa `SPATIAL_TEST_DSN` dan 2 yang sudah dilewati di dasar).

Run: `node_modules/.bin/tsc --noEmit`
Expected: keluar tanpa keluaran galat.

Run: `vendor/bin/pint --test --format agent`
Expected: `{"tool":"pint","result":"passed"}`

Paritas PostgreSQL (R-024). Butir di bawah ini hanya terbukti di PostgreSQL; suite SQLite tidak membuktikan satu pun.
Bila `SPATIAL_TEST_DSN` menunjuk PostGIS lokal (`docker-compose.yml`, DK-55; tidak pernah Supabase), jalankan
`php artisan test --compact --testsuite=Spatial` dan catat hasil setiap kelas; tanpa itu, catat "belum dijalankan;
menunggu CI PostGIS sesudah push yang diizinkan" per butir. Pada simulasi rencana: suite Spatial 77 test (dasar 45, 32 baru), semuanya dilewati tanpa `SPATIAL_TEST_DSN`; belum pernah dijalankan di PostgreSQL.

| Butir PostgreSQL | Test Spatial |
|---|---|
| Tiga CHECK `official_statuses` (keadaan verifikasi sah, pemeriksa bukan pencatat, SHA-256 heksadesimal), kunci asing `nullOnDelete`, indeks antrean, dan draf yang tidak terbaca pembaca mana pun | `OfficialStatusProvenanceSchemaTest` (6) |
| Indeks unik sebagian keanggotaan aktif, CHECK peran dan masa berlaku, cascade, RLS | `AuthorityMemberSchemaTest` (5) |
| Urutan `COALESCE(effective_at, created_at)` dengan NULL eksplisit sama dengan SQLite | `OfficialStatusNullOrderTest` (4, satu baru) |
| Status berakhir terbaca Belum diketahui tanpa menghidupkan catatan lama, dan antrean tinjau per jenis status (syarat "lewat jadwal tinjau" dalam SQL) | `OfficialStatusReviewScheduleTest` (2) |
| CHECK keadaan dan bagian laporan kesalahan, cascade saat akun dihapus, RLS | `DataErrorReportSchemaTest` (3) |
| CHECK level gunung api 1 sampai 4, indeks terbaru, RLS, dan catatan terbit terakhir per gunung sama dengan SQLite | `VolcanoStatusSchemaTest` (3) |
| Satu foto per gunung, CHECK lisensi dan SHA-256, RLS | `MountainPhotoSchemaTest` (4) |
| Daftar kolom kandidat sama dengan skema PostgreSQL tanpa `geometry`; kandidat membawa `has_geometry` tanpa garisnya | `TrailCandidateColumnsTest` (2), `RecommendationGeometryExclusionTest` |
| Penjaga km pos dengan `ST_LineLocatePoint` dan `ST_Distance` sungguhan | `PosSepanjangJalurTest` (5, dua baru) |
| `->nullable()->default(null)->change()` menjadi `DROP NOT NULL` dan `DROP DEFAULT` | `TrailCharacteristicsSchemaTest` (2) |
| Kolom dan indeks `kind`, dan migrasi data `000810` | `TripPreparationKindSchemaTest` (2) |

Di luar test: status migrasi Supabase (`migrate:status`) sesudah izin, kebijakan RLS yang sesungguhnya di Supabase, dan
disk produksi bukti dan foto (Konflik 7) hanya dapat diperiksa di lingkungan pemilik, sesudah izin akhir.

- [ ] **Step 3: Build dan ukuran bundel.**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build`
Expected: `✓ built`. Pada simulasi rencana: entri `app` 11,70 kB (gzip 3,44; dasar 10,75 dan 3,22), potongan bersama `dist` 346,41 kB (dasar 346,41); CSS 85,09 kB (gzip 16,15; dasar 84,47 dan 16,04). Potongan halaman baru: meja kurator 9,35 kB (gzip 2,86), rincian catatan 8,57 kB (gzip 2,78), formulir catatan 7,61 kB (gzip 2,77), formulir gunung api 2,70 kB (gzip 1,18), foto gunung admin 5,04 kB (gzip 2,00); Kabar 8,87 kB (gzip 2,64; dasar 8,00 dan 2,34). Peringatan ukuran potongan maplibre-gl sama seperti W7.

- [ ] **Step 4: Suite pratinjau dan server statis.**

Run: `php artisan test --compact --testsuite=Pratinjau`
Expected: PASS. Pada simulasi rencana: 43 test lulus (dasar 36).

Run (di latar belakang): `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php -S 127.0.0.1:8100 -t public tests/Pratinjau/statis.php`
Expected: server statis tanpa basis data. Buka `http://127.0.0.1:8100/_pratinjau/<nama>.html`.

- [ ] **Step 5: Tangkapan.** Chrome lewat MCP chrome-devtools. 390 x 844 dengan emulasi seluler, sentuh, rasio
piksel 1; 1440 x 900 rasio 1; terang dan gelap. Simpan WebP di `docs/desain/tangkapan/w8/`:

1. `meja-kurator`: 390 dan 1440, terang dan gelap.
2. `catatan-kurator` (menunggu verifikasi, dengan temuan) dan `catatan-kurator-draf`: 1440 terang; `formulir-kurator`
   dan `formulir-gunung-api`: 1440 terang dan 390 terang.
3. `kabar`: 390 dan 1440, terang dan gelap.
4. `gunung-berfoto` (foto dengan atribusi di pojok): 390 dan 1440, terang dan gelap.
5. Kartu gunung api di `gunung`, `detail-jalur`, dan `cek-kesiapan` (acuan 03, 04, 10): 390 terang dan gelap, 1440 terang.
6. Dialog "Laporkan kesalahan data" terbuka di `detail-jalur`: 390 terang.

- [ ] **Step 6: Perbandingan tertulis.** Tulis `docs/desain/tangkapan/w8/README.md` dengan susunan README tangkapan W7:
paragraf cara pengambilan, satu paragraf cakupan W8, dan tabel
`| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |`. Pemeriksaan
perencanaan menemukan hal-hal di bawah ini. Pastikan masing-masing pada tangkapan, jangan disalin tanpa melihat.

- **Sudah sesuai (gunung 390 px, acuan 03).** Foto berlisensi dengan atribusi di pojok bila tercatat, visual data bila
  tidak; kartu gunung api terpisah dari kartu status akses, dengan atribusi PVMBG, waktu terbit, kesegaran, tautan
  laporan, dan kalimat bahwa level bukan status akses; "Laporkan kesalahan data" di kartu status gunung.
- **Sudah sesuai (detail jalur dan Cek kesiapan, acuan 04 dan 10).** Kartu gunung api tampil hanya bila tercatat; Cek
  kesiapan menyebut waktu ambil prakiraan dari baris tanggal trip.
- **Sudah sesuai (meja kurator, tanpa acuan).** Satu `h1` "Meja kurator", keanggotaan, antrean berkelompok dengan
  jumlahnya, draf hanya milik pencatat, laporan kesalahan tanpa identitas pelapor, dan daftar gunung api.
- **Belum sesuai.**
  - Prototype tidak menggambar meja kurator dan Kabar; keduanya memakai kosakata komponen konsol W3 dan garis kabar
    Beranda (W8-R15, W8-R27).
  - Arsip otomatis dari URL tidak dibangun (Konflik 7).
  - Tautan remah "Gunung Gede" di rincian jalur di bawah 44 px (W3; W9).

- [ ] **Step 7: Ukur reflow, target, dan kontras.** Dengan `evaluate_script` di pratinjau:

1. `meja-kurator`, `catatan-kurator`, `catatan-kurator-draf`, `formulir-kurator`, `formulir-gunung-api`, `kabar`,
   `gunung-berfoto`, `gunung`, `detail-jalur`, `cek-kesiapan`, dan `ruang-kerja` di 320 x 568, 390 x 844, dan 768 x 1024:
   `document.documentElement.scrollWidth` sama dengan `clientWidth`. Pada simulasi rencana: sama di kesebelas halaman pada 320, 390, dan 768 px (33 dari 33), diukur ulang sesudah perbaikan terakhir.
2. Tinggi dan lebar kendali baru paling sedikit 44 px: tautan antrean, "Catat status resmi", "Catat status gunung api",
   aksi catatan, "Tandai sudah dicek", "Laporkan kesalahan data", pintu gunung di Kabar, tautan sumber, tautan laporan
   PVMBG, dan kendali foto admin. Pada simulasi rencana: semua kendali di `main` kesebelas halaman paling sedikit 44 x 44 px, kecuali tautan remah "Gunung Gede" di rincian jalur (94 x 18 px, W3; tidak disentuh W8).
3. Kontras teks atribusi foto di atas foto, teks muted di kartu gunung api, dan batas putus-putus komunitas terhadap
   permukaannya di kedua tema (4,5:1 untuk teks, 3:1 untuk batas kendali).

- [ ] **Step 8: Jalur papan ketik** (e2e menunggu Playwright, Q17). Tanpa tetikus, 1440 px untuk meja dan 390 px untuk
layar pendaki:

1. **Meja kurator.** Tab urut mengikuti tampilan: keanggotaan, "Catat status resmi", setiap antrean; Enter pada satu
   baris membuka rinciannya (butuh server; tanpa izin Q1 periksa dari kode `DaftarAntrean.tsx`). Di laporan kesalahan,
   "Tandai sudah dicek" dengan Enter membuka dialog dengan fokus di isian hasil, Escape menutup dan fokus kembali ke
   pemicunya (dapat di pratinjau statis).
2. **Catatan kurator.** "Persoalkan" membuka dialog dengan fokus di isian alasan; Escape mengembalikan fokus (pratinjau
   `catatan-kurator`).
3. **Rincian jalur.** "Laporkan kesalahan data" dengan Enter membuka dialog dengan fokus di pilihan bagian; Escape
   menutup dan fokus kembali ke tautan (pratinjau `detail-jalur`).

Simpan satu tangkapan fokus di dialog laporan sebagai `fokus-laporkan-390.webp`. Hentikan server port 8100.

- [ ] **Step 9: Delivery Gate antislop, empat blok.** Tulis laporan PASS/FAIL, satu baris per butir, dan setiap PASS
disertai bukti:

- **Salinan:** R-02 tanpa em dash. Run: `LC_ALL=C grep -rlP '\xE2\x80\x94' app resources tests database config DESIGN.md docs/ARCHITECTURE.md docs/fitur`. Expected: tanpa keluaran. Teks berbahasa Indonesia dengan "Anda"; tanpa "aman", "sehat", "dijamin", "terjamin", atau "pasti" sebagai klaim; kalimat R-012 di setiap layar pembaca status (`OfficialStatusSafetySentenceTest`).
- **Desain:** satu aksi utama per bagian; hierarki lewat bobot; resmi bergaris penuh, komunitas putus-putus, gunung api sebagai keluarga status sendiri; status tidak lewat warna saja; foto komunitas tidak pernah kepala halaman.
- **Interaksi:** R-26 setiap kendali berfungsi (daftar di tabel DK-57 baris antislop); R-27 keadaan kosong, memproses, dan galat di setiap bagian yang menulis; R-32 jalur papan ketik Step 8.
- **Kebenaran:** status berakhir terbaca Belum diketahui; draf tidak pernah terlihat pendaki; berita tidak pernah membuka; level PVMBG tidak pernah Buka atau Tutup; data contoh berpenanda; R-35 build dijalankan dan kendali diklik.

- [ ] **Step 10: HANDOVER, ledger, dan daftar izin akhir.** Di `HANDOVER.md` bagian 0, perbarui baris "Terakhir
diperbarui" dan tambahkan subbagian "W8 Data resmi (selesai <tanggal>, menunggu izin commit)" dengan susunan subbagian W7:

- rujukan rencana ini, jumlah tugas, dan letak ledger;
- tabel `| Butir | Nilai | Bukti |` berisi suite Unit dan Feature, suite bawaan, Spatial (dijalankan atau menunggu CI,
  per kelas di Step 2), tsc, Pint, build, Pratinjau, tangkapan, reflow, target, dan jalur papan ketik;
- ruling koordinator atas Konflik 1 sampai 11;
- OPEN ISSUE dari Bukti simulasi;
- daftar izin akhir: sembilan migrasi W8 (`2026_10_09_000100` sampai `000810`, dipasang sesudah enam migrasi W7, satu
  batch urut nama berkas, didahului kueri pemeriksaan Konflik 1 dan 11), pemberian keanggotaan kurator pertama
  (Konflik 2), kurasi status resmi lewat meja sebelum 31 Oktober 2026 (Konflik 6, butir izin akhir 14), disk produksi
  `EVIDENCE_DISK` dan `MOUNTAIN_PHOTOS_DISK` (Konflik 7), konfirmasi pemilik atas dua penangan unggahan baru (Konflik 7),
  pembaruan `docs/KEBIJAKAN-DATA-PRIBADI.md` dan baris status PRD dengan GR (Konflik 8), dan kurasi nilai karakteristik
  jalur lama (Konflik 9, butir izin akhir 6);
- titik lanjut W9 (tautan remah 44 px, "sumber lingkungan", entitas kawasan bila Q12 diputus).

Perbarui baris W8 di `.superpowers/sdd/2026-09-26-memount-v3-program.md`.

- [ ] **Step 11: Commit.** Lewati: tanpa commit sampai permintaan izin akhir pemilik; catat berkas yang berubah.

## Tinjauan diri

### Cakupan induk W8 dan titipan

| Butir | Tugas | Bukti di rencana |
|---|---|---|
| Butir 1: provenans status resmi (kolom spec v3 bagian 10.7, enum pendukung, enum lama dipakai ulang hanya bila artinya sama; migrasi di SQLite dan PostgreSQL; `OfficialStatusPageTest` tetap lulus) | Tugas 2 | Migrasi `000100`; `OfficialStatusVisibilityTest` (7 test), `OfficialStatusProvenanceSchemaTest` (6, PostgreSQL); `OfficialStatusPageTest` diperbarui di Tugas 8 dengan alasan (W8-R16) |
| Butir 2: `authority_members` dan peran `curator` terpisah dari moderator dan admin, dengan Policy yurisdiksi | Tugas 3 | `AuthorityMemberPolicyTest` (11 test), `AuthorityMemberSchemaTest` (5, PostgreSQL) |
| Butir 3: layanan terbit asimetris, rantai penggantian, pembersihan cache, audit sebelum-sesudah, pelaku dari sesi, mass assignment | Tugas 5, 1 | `AsymmetricPublishRuleTest` (12 test), `OfficialStatusSupersedeTest` (4), `StatusCacheInvalidationTest` (2 menjadi 6), `AuditLogChangeTest` (2 menjadi 3), `OfficialStatusVisibilityTest::test_provenance_controls_are_not_mass_assignable` |
| Butir 4: pemeriksaan konsistensi (tanggal terbalik, di luar yurisdiksi, dua status bertentangan, jalur Buka di kawasan tertutup, bukti hanya berita) | Tugas 6 | `OfficialStatusConsistencyTest` (8 test, termasuk `test_an_open_trail_inside_a_closed_area_only_warns`) |
| Butir 5: tinjau berkala (berakhir menjadi Belum diketahui dan masuk antrean, tidak pernah otomatis Buka; jadwal per jenis menggantikan 90 hari; tugas terjadwal di `SchedulerHealthService::TUGAS`) | Tugas 7 | `DataFreshnessReviewTest` (5 menjadi 10), `OfficialStatusReviewScheduleTest` (2, PostgreSQL), `SchedulerHealthTest` tetap lulus (`data:freshness-check` sudah terdaftar) |
| Butir 6: arsip bukti (validasi, EXIF dibuang, SHA-256, disk privat) | Tugas 4 | `EvidenceArchiveTest` (7 test) |
| Butir 7: status gunung api DK-43 | Tugas 10 | `VolcanoStatusTest` (9 test), `VolcanoStatusSchemaTest` (3, PostgreSQL); kartu di halaman gunung, rincian jalur, Kabar, dan Cek kesiapan |
| Butir 8: foto gunung berlisensi DK-37 (unggahan admin, atribusi di pojok, visual data tanpa foto, foto komunitas tidak pernah hero) | Tugas 11 | `MountainPhotoLicenseTest` (10 test, termasuk `test_a_community_photo_never_becomes_the_hero`), `MountainPhotoSchemaTest` (4, PostgreSQL) |
| Butir 9: UI meja kurator dengan alur tujuh langkah dan "Laporkan kesalahan data" ke antrean kurator (`CuratorDeskTest`: akses per peran, draf tidak pernah terlihat pendaki) | Tugas 8, 9 | `CuratorDeskTest` (9 test, termasuk `test_drafts_and_unverified_openings_never_reach_hiker_pages`), `DataErrorReportTest` (12), `DataErrorReportSchemaTest` (3, PostgreSQL); pratinjau `meja-kurator`, `catatan-kurator`, `catatan-kurator-draf`, `formulir-kurator` |
| Titipan W3 dan W4 "Ke W8", W5-C9, dan butir izin akhir 14 | Tugas 1, 10, 12 sampai 16, 18 | Peta titipan di Pra-cek |
| Selesai bila: aturan penutup gelombang; test paritas PostgreSQL setiap migrasi hijau di CI | Tugas 18 | Step 1 sampai 10; tabel paritas Step 2 |

### Pemindaian placeholder

Tidak ada "TBD", "TODO", "implement later", atau "similar to Task N". Setiap langkah kode memuat kodenya: berkas baru
ditulis utuh, tulis ulang memuat isi utuh, dan perubahan kecil memakai blok "Ganti ... dengan ...". Blok-blok itu
dirakit dari rantai simulasi dan diperiksa dua hal: teks lamanya muncul tepat sekali pada keadaan sebelum blok itu, dan
menerapkan semua blok berurutan menghasilkan berkas akhir tugas persis. Tidak ada blok yang diawali atau diakhiri baris
kosong. Sesudah dirakit, operasi rencana ini diputar ulang dari awal pada salinan dasar seperti implementer membacanya,
dan setiap berkas sama dengan keadaan akhir tugasnya (Bukti simulasi).

### Konsistensi nama dan tipe

- **Keterlihatan dan urutan (Tugas 1, 2, 7):** `OfficialStatus::scopeTerbaruDulu()`, `scopeTerlihatPendaki()`, `terlihatPendaki()`, `berlakuPada()`, `Trail::statusTerlihat()`, `OfficialStatusService::catatanPenentu()`; enum `StatusVerificationState`.
- **Kurator (Tugas 3 sampai 10):** `UserRole::CURATOR`, `User::isCurator()`, `AuthorityMemberRole`, `Yurisdiksi` (`kuratorAktif()`, `gunung()`, `otoritasUntuk()`, `berwenang()`), aksi `OfficialStatusPolicy` (`viewDesk`, `record`, `view`, `updateDraft`, `verify`, `dispute`, `review`, `viewEvidence`), prop bersama `auth.user.isKurator`.
- **Terbit (Tugas 5 sampai 8):** `TerbitStatus` (`buatDraf`, `ubahDraf`, `hapusDraf`, `kirim`, `verifikasi`, `persoalkan`, `tinjauUlang`), `ArahPerubahan` (`membuka`, `tingkat`), `PemeriksaanKonsistensi` (`periksa`, `blokir`, kode temuan), `ArsipBukti` (`MAP`, `aturan`, `simpan`, `utuh`, `sah`, `disk`).
- **Meja (Tugas 8 sampai 10):** `MejaKurator::props()` (kunci `badan`, `antrean` dengan `verifikasi`, `tinjau`, `laporan`, dan kelompok lain di dalamnya, dan `gunungApi`), `HalamanCatatanStatus`, rute `curator.*`, tipe di `components/kurator/types.ts` dengan nama yang sama dengan kunci server.
- **Pembaca (Tugas 9 sampai 11, 16):** prop `laporKesalahan`, `gunungApi`, dan `foto`; `StatusGunungApi::untukGunung()`; `FotoGunung::bentuk()`; `KabarJalur::untuk()` (kunci `gunung` dan `gunungApi`).
- **Jalur dan persiapan (Tugas 12 sampai 15):** `Trail::KOLOM_TANPA_GEOMETRI`, `scopeTanpaGeometri()`, `hasGeometry(): ?bool`, `PosSepanjangJalur::saring()`, `TripPreparationItem::JENIS_IZIN`, `PreparationService::butirIzin()`, kunci `judul` penentu izin.

tsc lulus sesudah setiap tugas pada simulasi dan putaran ulang.

### Review Focus

Kelima baris Review Focus punya test di tugas pemilik kodenya: status yang terbaca lebih longgar (Tugas 1, 2, 5, 7),
kurator di luar kawasan atau peran dari klien (Tugas 3, 5, 8, 9, 10), berkas bukti dan foto (Tugas 4, 11, termasuk jalur
berkas yang tidak ditulis layanan), data hilang yang terbaca sebagai nilai (Tugas 10, 13, 15), dan skema PostgreSQL (32
test Spatial baru). Semua test itu lulus pada simulasi dan putaran ulang, kecuali test Spatial yang menunggu PostGIS.

### Pemeriksaan teks rencana

- Rencana ini tanpa em dash. Tanda "§" hanya muncul saat mengutip ID PRD; dokumen lain dirujuk "bagian N".
- Teks antarmuka baru berbahasa Indonesia dengan sapaan "Anda".
- Tidak ada klaim "aman", "sehat", "dijamin", "terjamin", atau "pasti". Di luar aturan rencana ini, kata "aman" hanya
  muncul di dua komentar kode yang menjelaskan larangan klaim itu sendiri (`TripController.php`, yang sudah ada sebelum
  W8, dan komentar jadwal tinjau di `config/hiking.php`).
- Label kecocokan publik dan bobot Route Fit tidak disentuh; yang berubah di Route Fit hanya cara membaca kolom kosong
  (tidak diketahui, bukan Sedang), sesuai R-006.

### Pemeriksaan test (testing-best-practices, `rules/review.md`)

- Test baru menguji perilaku lewat HTTP atau layanan publik dan memakai pabrik atau trait penyiapan bersama
  (`Tests\Concerns\MejaKurator`); waktu dibekukan dengan `travelTo()` bila tanggal menentukan hasil (berakhir, jadwal
  tinjau, kesegaran gunung api dan prakiraan); unggahan memakai `Storage::fake`.
- Setiap rute tulis baru diuji untuk tamu, peran yang salah (admin, moderator, pendaki), kurator kawasan lain (403,
  konvensi proyek sampai Q5), isian yang menyelundupkan kolom pelaku, kalimat galat, dan batas laju dengan kunci sendiri.
- Matriks wewenang ada di `AuthorityMemberPolicyTest`; test HTTP lain memegang satu kasus penolakan per rute.
- Test yang diperbarui membawa alasannya: `OfficialStatusOrderTest`, `OfficialStatusNullOrderTest`,
  `StatusCacheInvalidationTest`, `OfficialStatusPageTest`, `OfficialStatusTargetTest`, `OfficialStatusSafetySentenceTest`,
  `EmptyStateCoverageTest`, `DataFreshnessReviewTest`, `TrustGrammarTest`, `OfficialStatusSeederTest`,
  `DataFreshnessServiceTest`, `FitAssessmentPageTest`, `PosSepanjangJalurTest`, `RecommendationGeometryExclusionTest`,
  `TrailDetailPageTest`, `DashboardDeadlineTest`, `DashboardPhaseTest`, `PermitVisibilityTest`,
  `PermitWindowReminderTest`, `TripPermitWindowTest`, `TrailNewsTest`, dan `DemoSeederTest`.

### Paritas PostgreSQL (R-024)

Suite di rencana ini memakai SQLite dalam memori, jadi hijaunya tidak membuktikan perilaku PostgreSQL. W8 menambah
sembilan migrasi (dua migrasi data), sepuluh CHECK, satu indeks unik sebagian, satu indeks unik, delapan belas indeks
biasa, enam belas kunci asing, empat RLS, dan empat perubahan kolom `->change()`. Yang dijaga test Spatial (lewat
`SPATIAL_TEST_DSN` di PostGIS CI atau lokal, tidak pernah Supabase) tercantum per butir di tabel Tugas 18 Step 2.
Yang diperiksa dengan membaca kueri: urutan antrean meja memakai kolom tak-NULL atau urutan NULL eksplisit; pencarian
gunung dan jalur di formulir meja memakai daftar dari server, bukan `LIKE`; `terkiniUntuk()` gunung api memakai satu
kueri dengan subkueri waktu terbit terbesar per gunung, yang bentuknya sama di kedua pengemudi. Rencana ini tidak
menjalankan apa pun terhadap PostgreSQL. Tanpa PostGIS lokal, laporan Tugas 18 mencatat paritas W8 sebagai "test
Spatial tertulis, menunggu CI".

## Lampiran A: Aturan eksekusi W8

Salin lampiran ini ke `.superpowers/sdd/<folder ledger W8>/aturan-eksekusi.md` sebelum Tugas 1. Isinya aturan
eksekusi W7 dengan nama gelombang, rencana, port, dan larangan baru W8.

### Aturan eksekusi W8 (dibaca setiap implementer sebelum mulai)

Proyek: Memount, Laravel 13.31 + Inertia 3 + React 19.3 + TypeScript 5.9, PHP 8.5.10, di Windows dengan Git Bash.
Root repo: `E:/praktik_aplikasi_web`. Branch `hardening`. Commit terakhir `ddc27e8`; pohon kerja memuat perubahan yang
belum di-commit dari W1 sampai W7 dan gelombang ini. Folder `.agents/skills/` yang belum dilacak (keputusan Q19)
jangan disentuh.

#### Larangan mutlak (arahan pemilik dan kontrak proyek)

1. **Jangan commit, push, `git stash`, `git checkout -- <berkas>`, `git reset`, atau `git clean`.** Pemilik
   memutuskan tidak ada commit sampai seluruh program W1 sampai W9 selesai. Lewati setiap langkah "Commit"; catat
   berkas yang berubah di laporan.
2. **Jangan menyentuh Supabase dan jangan membaca atau mencetak isi `.env`.** `.env` menunjuk basis data produksi.
3. **Jangan menjalankan `php artisan migrate` dalam bentuk apa pun, `db:seed`, `db:wipe`, tinker (termasuk
   `tinker --execute`), atau `php artisan memount:demo`.** Migrasi dan seeder hanya berjalan di dalam PHPUnit. Sembilan
   migrasi W8 ditulis dan diuji, tidak dijalankan; migrasi lama tidak disunting.
4. **Setiap perintah Artisan yang mem-boot aplikasi di luar PHPUnit, `npm run build`, dan server statis `php -S`
   wajib diberi awalan** `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=`. `php artisan test ...` tidak butuh awalan.
5. **Jangan menambah atau mengubah dependensi** (composer, npm), jangan mengubah versi apa pun, dan jangan menjalankan
   `npx` apa pun. Pemeriksaan tipe memakai `node_modules/.bin/tsc --noEmit`. Pengolahan gambar memakai GD dan exif yang
   sudah ada.
6. **Jangan mengubah `EVIDENCE_DISK`, `MOUNTAIN_PHOTOS_DISK`, `AVATARS_DISK`, atau konfigurasi disk di `.env`.** Test
   memakai `Storage::fake`; disk produksi adalah keputusan pemilik (Konflik 7).
7. **Jangan membuat akun kurator, keanggotaan badan, atau status resmi di basis data mana pun di luar PHPUnit.**
   Pemberian peran menunggu pemilik (Konflik 2).
8. **Jangan menyunting berkas di luar daftar "Files" tugas Anda**, kecuali brief atau dispatch menyebutnya. Bila perlu
   menyentuh berkas lain, berhenti dan laporkan NEEDS_CONTEXT.
9. **Blok `<laravel-boost-guidelines>` di `AGENTS.md` dan `CLAUDE.md` serta blok NgodingPakeAI di `AGENTS.md` tidak
   pernah disunting.** `HANDOVER.md`, `CLAUDE.md`, `docs/PRD.md`, dan `docs/KEBIJAKAN-DATA-PRIBADI.md` tidak disunting
   implementer.
10. **Jangan menjalankan subagen.**
11. **Server `php artisan serve` di 127.0.0.1:8000 milik pemilik (memakai Supabase).** Jangan dipakai, dihentikan, atau
    dipanggil. Port 8100 milik koordinator (harness pratinjau Tugas 18), dan port 8170 milik penulis rencana.
    Implementer W8 tidak menyalakan server apa pun.

#### Hook GateGuard

Lingkungan ini memasang hook "Fact-Forcing Gate". Perintah Bash pertama dan suntingan pertama setiap berkas bisa
ditolak dengan permintaan fakta. Bila itu terjadi, tulis faktanya di balasan Anda lalu ulangi panggilan yang sama:
- ringkasan permintaan: "Menjalankan Tugas N rencana W8 (docs/superpowers/plans/2026-10-08-memount-v3-w8-data-resmi.md) atas arahan pemilik";
- untuk Bash: apa yang diverifikasi atau dihasilkan perintah itu;
- untuk suntingan: berkas yang mengimpor atau merujuk berkas itu (cari dengan Grep), fungsi atau kelas publik yang terdampak, bentuk data bila ada;
- untuk berkas baru: berkas yang akan memanggilnya, bukti tidak ada berkas lain dengan tujuan sama, dan bentuk data;
- untuk penghapusan atau pemindahan: jalur yang terdampak dan cara mengembalikannya;
- instruksi pemilik verbatim: "ya, selesaikan semuanya baru minta izin commit dan integrasi nya jika belum selesai semua jangan minta ke saya, jika anda meminta perizinan artinya semua tugas anda telah selesai. lanjutkan!"

#### Konvensi kerangka dari W2 sampai W7, ditambah W8

- Komponen yang hidup di kerangka persisten memanggil `useKerangka()` untuk toast. Toast tertutup saat pemiliknya
  dilepas.
- Konfirmasi sesudah navigasi datang dari flash halaman tujuan (`status`), dibacakan wilayah `role="status"` yang
  terpasang sejak halamannya tampil.
- Dialog (`Dialog` dari `components/ui/Lembar`) hanya untuk keputusan singkat dengan paling banyak satu catatan; fokus
  kembali ke pemicunya saat ditutup. Formulir panjang adalah halaman sendiri.
- Kurator, badan, dan sasaran tidak pernah dipilih dari isian bebas: daftar pilihan datang dari server (`Yurisdiksi`),
  dan Form Request mencocokkan pilihan itu lagi.
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
  lalu `node_modules/.bin/tsc --noEmit`.
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
