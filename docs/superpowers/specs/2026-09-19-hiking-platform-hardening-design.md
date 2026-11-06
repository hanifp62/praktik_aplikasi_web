# Desain: Pengerasan Platform Perencanaan Pendakian

- Tanggal: 2026-09-19
- Status: menunggu review
- Basis kode: Laravel 13.31, Livewire 3.6, PostgreSQL + PostGIS (Supabase), PHP 8.5.10
- Baseline terverifikasi: 86 test lolos, Pint bersih, 109 file PHP, 31 migrasi

## 1. Tujuan

Menutup celah antara implementasi dan PRD, memperbaiki cacat yang dapat direproduksi, dan menyelaraskan alur kerja dengan realita pendakian di Indonesia — tanpa mengubah ruang lingkup produk yang sudah ditetapkan PRD.

Kriteria selesai: seluruh temuan pada register di bawah tertutup, setiap temuan punya test yang gagal sebelum perbaikan dan lolos sesudahnya, dan suite tetap hijau di tiap batas fase.

## 2. Keputusan yang sudah diambil

| Topik | Keputusan |
|---|---|
| Zona waktu | Simpan UTC, tampilkan WIB/WITA/WIT per gunung |
| Sumber peta | OpenTopoMap sebagai default, URL tile dibaca dari config agar bisa diganti |
| SIMAKSI | Model data + peringatan jendela booking + checklist. Tanpa integrasi API resmi, tanpa pelacakan kuota internal |
| Hapus akun | Laporan komunitas dianonimkan, bukan dihapus |
| Data jalur tidak lengkap | Gerbang publikasi §110 **dan** penanganan jujur untuk data parsial |

## 3. Koreksi dari crosscheck

Lima dugaan awal terbukti keliru dan **tidak** dikerjakan:

1. Proteksi mass assignment — semua model memakai atribut `#[Fillable]` Laravel 13. Aman.
2. Filter region — Laravel sudah mengurung closure `whereHas` dengan benar. Hanya filter pencarian yang bocor.
3. `READY_FOR_DEPARTURE` — sudah dipakai oleh `ReadinessDashboard.php:52`.
4. `technical_demand` / `navigation_complexity` — kolom `NOT NULL` dengan default. Tidak ada risiko fatal.
5. Readiness menuntut 100% item — sesuai PRD §39, yang memasukkan "preparation gap" ke `NEEDS PREPARATION`.

## 4. Register temuan

Kode E = terverifikasi empiris, K = terverifikasi lewat pembacaan kode.

| ID | Temuan | Bukti | Fase |
|---|---|---|---|
| F-01 | Jalur tanpa data karakteristik dilabeli COCOK untuk pemula (skor 78.25) | E: evaluasi langsung | 1 |
| F-02 | Tidak ada gerbang publikasi §110 | K: `trails.is_published` bebas di-set | 1 |
| F-03 | Status resmi scope SEGMENT tidak pernah dibaca mesin | K: `OfficialStatusService.php:38` | 1 |
| F-04 | `RestrictedArea` punya geometri dan status tapi tak dikonsumsi | K | 1 |
| F-05 | Pencarian jalur menembus filter `published()` | E: dump SQL | 2 |
| F-06 | Jadwal cuaca bergeser 7 jam (WIB disimpan sebagai UTC) | E: API BMKG live + `config/app.php` | 2 |
| F-07 | Halaman readiness menulis baris baru tiap GET | E: 3 tampilan = 3 baris, 21 query | 2 |
| F-08 | Konfirmasi pre-departure hilang saat halaman dimuat ulang | K: konsekuensi F-07 | 2 |
| F-09 | Hike Mode melewati checkpoint yang sedang dituju | K: `HikeMode.php:87` | 2 |
| F-10 | Judul halaman selalu "Laravel"; 20+ `#[Title]` jadi kode mati | E: render `/trails` | 2 |
| F-11 | Trip bisa dibuat pada jalur belum terpublikasi | K: `TripForm.php:65` | 2 |
| F-12 | Tidak ada penjaga transisi status trip | K: `TripShow.php:31,46,83` | 2 |
| F-13 | `completion_state` divalidasi sebagai string biasa | K | 2 |
| F-14 | Foto tidak pernah ditampilkan; moderator tak bisa memoderasi foto | K: `moderation-queue.blade.php:56` | 3 |
| F-15 | Foto tersimpan di disk publik | K: `config/filesystems.php:89` | 3 |
| F-16 | EXIF tidak dibersihkan | K | 3 |
| F-17 | `updatePosition()` tanpa validasi rentang lat/lng | K: `HikeMode.php:38` | 3 |
| F-18 | Tidak ada rate limit pada submit laporan dan run rekomendasi | K | 3 |
| F-19 | Hapus akun menghancurkan laporan komunitas; file foto yatim | K: cascade pada migrasi | 3 |
| F-20 | N+1 pada mesin rekomendasi: 58 query untuk 12 jalur | E: `DB::listen` | 4 |
| F-21 | Konteks cuaca dihitung dua kali per agregasi | K: `ConditionAggregatorService.php:30,114` | 4 |
| F-22 | Refresh cuaca per jalur, bukan per `adm4` unik | K: `RefreshWeatherSnapshots.php:16` | 4 |
| F-23 | `weather_snapshots.trail_id` saling menimpa dan `cascadeOnDelete` | K: migrasi + `WeatherService.php:53` | 4 |
| F-24 | Tidak ada caching meski §97 mewajibkan | K | 4 |
| F-25 | Dimensi kondisi terkini tidak memengaruhi state readiness | K: `ReadinessService.php:62` | 4 |
| F-26 | Peta memakai tile demo MapLibre; layar kosong di jalur | K: `hike-mode.blade.php:121` | 5 |
| F-27 | MapLibre dimuat dari CDN, tidak di-bundle | K | 5 |
| F-28 | Tidak ada model izin/kuota pendakian | Research realita 2026 | 5 |
| F-29 | Kode spasial nol coverage (test berjalan di SQLite) | K | 6 |
| F-30 | Tidak ada E2E core journey §100 | K | 6 |
| F-31 | CI PHP 8.4 vs lokal 8.5.10; tanpa job PostGIS | K: `.github/workflows/ci.yml` | 6 |
| F-32 | `.env.example` masih default sqlite/MySQL | K | 6 |
| F-33 | Route `/awal` sisa scaffolding | K: `routes/web.php:27` | 6 |
| F-34 | Aksesibilitas tipis terhadap target WCAG 2.2 AA §87 | K: 11 atribut `aria-` | 6 |

## 5. Desain per fase

### Fase 1 — Semantik keselamatan

**Nilai unknown pada faktor kompatibilitas.** `FactorScore` mendapat properti `isUnknown`. `CompatibilityScorer` berhenti memetakan data kosong ke rank termudah; faktor yang datanya tidak ada ditandai unknown dengan detail eksplisit.

`RouteFitService::label()` mendapat aturan: bila ada faktor unknown, label dibatasi maksimal `PERLU_PERSIAPAN`; bila faktor unknown menyentuh salah satu faktor kritis (experience, technical, navigation), label menjadi `KURANG_COCOK`. Penjelasannya menyebut data mana yang belum tersedia, bukan menyembunyikannya.

Ini membalik arah bias: data yang hilang membuat jalur terlihat **lebih menuntut**, bukan lebih mudah. Sesuai PRD §95.

**Gerbang publikasi §110.** `Trail::publishabilityReport()` mengembalikan daftar syarat yang belum terpenuhi: sumber data, geometri, karakteristik dasar (jarak, elevation gain, durasi), minimal satu checkpoint, dan metadata status. `TrailManager` menolak `is_published = true` bila daftar tidak kosong dan menampilkan apa yang kurang. Jalur yang sudah terlanjur published dan tidak lolos gerbang tetap ditampilkan, tapi ditangani oleh aturan unknown di atas.

**Status SEGMENT dan RestrictedArea.** `OfficialStatusService` mendapat `segmentRestrictionsForTrail()` yang mengambil status scope SEGMENT untuk seluruh segmen jalur dalam satu query. Segmen tertutup atau dibatasi menghasilkan peringatan bernama segmen tersebut dan membatasi label maksimal `PERLU_PERSIAPAN` — bukan mengeksklusi jalur, karena PRD §26 memisahkan pembatasan dari pengecualian dan realita Semeru adalah jalur tetap dibuka sebagian.

`RestrictedArea` yang memotong geometri jalur dicari lewat `ST_Intersects` dan ditampilkan pada Trail Detail serta masuk ke peringatan agregator kondisi. Pada koneksi tanpa PostGIS, pencarian ini dilewati dengan aman.

### Fase 2 — Bug korektif

`TrailIndex` membungkus kondisi pencarian dalam grup closure.

Cuaca: migrasi menambah `forecast_at` (UTC, diturunkan dari `utc_datetime` milik BMKG yang tidak ambigu) dan memindahkan kunci unik ke `(adm4_code, forecast_at)`. Kolom `local_datetime` dipertahankan sebagai string tampilan asli BMKG. Tabel `mountains` mendapat kolom `timezone` (`Asia/Jakarta` | `Asia/Makassar` | `Asia/Jayapura`, default Jakarta). Seluruh penyaringan dan perbandingan memakai `forecast_at`; seluruh tampilan dikonversi ke zona gunung bersangkutan.

Readiness menjadi idempoten: `ReadinessService::evaluate()` dipecah menjadi `compute()` yang murni dan `record()` yang menyimpan. Membuka halaman memakai `compute()` plus pembacaan check tersimpan terakhir; baris baru hanya ditulis saat pengguna menekan recompute atau mengonfirmasi pre-departure. `pre_departure_confirmed` dibaca dari check tersimpan terakhir sehingga bertahan melewati reload.

Hike Mode: checkpoint berikutnya ditentukan oleh radius kedatangan (`config('hiking.checkpoint_arrival_radius_m')`, default 75) dan urutan sequence. Checkpoint dianggap tercapai bila pendaki pernah berada dalam radius; yang berikutnya adalah checkpoint pertama dalam urutan yang belum tercapai. Kemajuan disimpan pada `hiking_sessions`.

Judul: kedua layout memakai `{{ $title ?? config('app.name') }}`. `APP_NAME` diisi nama aplikasi yang sebenarnya. Halaman non-Livewire (`dashboard`, `profile`) mendapat judulnya lewat data view.

`TripForm` memvalidasi `trail_id` terhadap jalur terpublikasi dan tidak diarsipkan. `TripShow` mendapat penjaga transisi berbasis `TripStatus`: hanya transisi yang sah yang dijalankan, sisanya menghasilkan pesan yang jelas. `completion_state` divalidasi dengan `Enum(CompletionState::class)`.

### Fase 3 — Keamanan dan privasi

Foto pindah ke disk privat. Route bernama `reports.photo` menyajikan file setelah memeriksa policy: pemilik laporan, moderator, dan — bila laporan sudah `APPROVED` — pengguna terautentikasi. Antrean moderasi **menampilkan** fotonya agar moderasi §67 benar-benar mungkin; Trail Detail menampilkan foto laporan yang sudah disetujui.

EXIF dibersihkan saat unggah dengan re-encode GD, tanpa dependensi baru. `updatePosition()` memvalidasi lat ∈ [-90,90] dan lng ∈ [-180,180]. Rate limit dipasang pada submit laporan dan run rekomendasi.

Hapus akun: laporan komunitas dianonimkan, bukan dihapus. `user_id` menjadi `nullOnDelete`, model menyediakan label penulis "Pendaki terdahulu" saat `user_id` null. File foto milik laporan yang benar-benar dihapus ikut dibersihkan lewat event model.

### Fase 4 — Performa

`RouteFitService::recommend()` menghitung bobot sekali lalu mengopernya ke `evaluate()`; status resmi seluruh kandidat dan gunungnya di-preload dalam dua query; hasil ditulis dengan satu bulk insert. Target: **di bawah 10 query untuk 12 jalur**, dikunci test anggaran query yang akan gagal bila regresi kembali.

`ConditionAggregatorService` menghitung konteks cuaca sekali lalu mengopernya ke `warnings()`. `RefreshWeatherSnapshots` mengelompokkan jalur berdasarkan `adm4` unik sehingga satu area = satu panggilan API, menghormati batas 60 permintaan/menit BMKG. `weather_snapshots.trail_id` dilepas seluruhnya karena snapshot adalah milik area, bukan jalur — kolom itu saat ini saling menimpa antar jalur satu kelurahan dan `cascadeOnDelete`-nya ikut menghapus data yang dipakai jalur lain.

Fase 2 dan fase 4 sama-sama menyentuh `ReadinessService`. Urutannya disengaja: fase 2 memisahkan `compute()` dari `record()`, fase 4 baru menambah dimensi kondisi ke dalam `compute()` yang sudah murni.

Cache dipasang untuk data publik saja — metadata gunung dan jalur, snapshot cuaca, snapshot status resmi — dengan TTL dari config. Data privat tidak pernah masuk shared cache sesuai §97.

`ReadinessService::determineState()` membaca dimensi ketiga: status UNKNOWN, peringatan komunitas ber-tag caution, cuaca basi atau tidak tersedia, dan pembatasan segmen masing-masing mencegah `READY` dan menghasilkan `NEEDS_PREPARATION` dengan alasan eksplisit. `NOT_RECOMMENDED` tetap hanya untuk penutupan resmi dan inkompatibilitas keras, sesuai §39.

### Fase 5 — Realita lapangan

MapLibre dipasang lewat npm dan di-bundle Vite, bukan dimuat dari CDN saat runtime. Sumber tile dibaca dari `config('hiking.map')` dengan OpenTopoMap sebagai default dan atribusi CC-BY-SA serta OpenStreetMap ditampilkan pada peta. `role="img"` pada kontainer peta diganti penanganan yang benar untuk wilayah interaktif.

Tabel `permit_requirements` menyimpan, untuk gunung atau jalur: penyelenggara, URL booking, kuota harian, `booking_opens_days_before`, `booking_closes_days_before`, kewajiban pemandu terdaftar, durasi maksimum hari, catatan, serta `source`, `source_url`, dan `verified_at` mengikuti model kepercayaan §60.

Mesin rekomendasi menambahkan peringatan — bukan pengecualian — bila `target_date` pada goal jatuh di luar jendela booking. `PreparationService` menghasilkan item kategori Logistik yang menautkan URL booking resmi. Trail Detail mendapat bagian Perizinan. Admin mendapat CRUD-nya. Tidak ada integrasi API ke sistem pemerintah dan tidak ada pelacakan kuota internal, karena data internal tidak akan sinkron dengan sistem resmi dan justru berisiko menyesatkan.

### Fase 6 — Pengujian dan CI

Regression test untuk setiap temuan F-01 sampai F-28, masing-masing ditulis gagal lebih dulu. E2E core journey §100 dari register sampai pre-departure check. Suite PostGIS terpisah yang berjalan bila Postgres tersedia dan di-skip bila tidak, menutup F-29. Job CI Postgres + PostGIS, versi PHP disamakan dengan lokal, `.env.example` dirapikan ke konfigurasi pgsql yang sebenarnya dipakai. Route `/awal` dihapus. Audit aksesibilitas pada alur inti: label form, focus visible, ukuran target sentuh, dan pesan error yang terbaca screen reader.

## 6. Di luar lingkup

- Integrasi API ke sistem SIMAKSI/eRinjani/e-Simaksi
- Pelacakan kuota pendakian internal
- Peta offline dan self-hosted tiles (PRD §55 menempatkannya sebagai future)
- Fitur AI apa pun (PRD §85 membatasi MVP)
- Migrasi skema ke instance Supabase yang hidup — keputusan pemilik proyek
- Malware scanning pada unggahan (PRD §81 menempatkannya sebagai tambahan produksi)

## 7. Risiko

**Perubahan skema cuaca menyentuh data yang sudah ada.** Migrasi `forecast_at` menyertakan backfill dari `local_datetime` dengan asumsi WIB. Jalur yang zona waktunya bukan WIB perlu diperiksa manual setelah migrasi.

**Aturan unknown akan menurunkan label banyak jalur sekaligus** bila dataset MVP belum lengkap. Ini disengaja dan benar, tapi perlu diketahui sebelum demo: jalur yang tadinya COCOK bisa berubah menjadi PERLU PERSIAPAN sampai datanya dilengkapi.

**OpenTopoMap tidak aktif dikembangkan sejak 2024 dan tanpa SLA.** Arsitektur pluggable adalah mitigasinya; berpindah penyedia cukup mengubah config.

**Anonimisasi laporan tidak bisa dibatalkan.** Setelah `user_id` dilepas, kaitan ke penulis hilang permanen. Ini memang yang diinginkan secara privasi.
