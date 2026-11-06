# Basis data

Ringkasan skema Memount per domain, catatan PostGIS, dan kebijakan migrasi. Skema kanonis
adalah isi `database/migrations/`; ringkasan ini menunjuk ke sana, dan `RepoPolicyFilesTest`
memastikan setiap tabel yang dibuat migrasi tercantum di sini. Model domain dan data ada di
`docs/PRD.md` (§14, §64, §65); lapisan kode dan aturan arsitektur di `docs/ARCHITECTURE.md`.

## Tabel per domain

| Domain | Tabel | Catatan |
|---|---|---|
| Akun dan profil pendaki | `users`, `password_reset_tokens`, `sessions`, `profiles`, `user_experience`, `user_preferences` | Peran di `users.role`; pengalaman yang diisi sendiri dipakai Route Fit |
| Gunung dan jalur | `mountains`, `trails`, `trail_segments`, `checkpoints`, `restricted_areas` | Mountain dan Trail entitas terpisah (R-001); jalur adalah unit keputusan (R-002) |
| Otoritas dan sumber data | `data_sources`, `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain` | Hierarki otoritas (R-009) |
| Kondisi dan kepercayaan data | `official_statuses`, `weather_snapshots`, `trail_condition_reports`, `report_thanks`, `permit_requirements`, `mountain_follows` | Laporan komunitas tidak menimpa status resmi (R-010); cuaca bersumber dan berkesegaran (R-013) |
| Route Fit dan rekomendasi | `recommendation_rules`, `recommendation_runs`, `recommendation_results` | Skor internal tidak pernah keluar dari server (BR-09) |
| Pertimbangkan | `trail_considerations`, `comparison_shares` | Simpanan pribadi dan catatannya (FR-22); tautan bagikan perbandingan, hanya hash tokennya yang disimpan (FR-23) |
| Rencana dan persiapan | `hiking_goals`, `trip_plans`, `trip_reminders`, `preparation_templates`, `preparation_items`, `trip_preparation_items`, `readiness_checks`, `user_owned_items` | Kesiapan adalah pendukung keputusan (R-014) |
| Pendakian dan progres | `hiking_sessions`, `hike_track_points`, `hiking_history`, `personal_targets` | Lokasi presisi privat secara bawaan (BR-13) |
| Tata kelola dan operasi | `moderation_actions`, `audit_logs`, `analytics_events`, `usability_sessions`, `scheduled_task_runs` | Jejak audit mencatat nilai lama dan nilai baru |
| Kerangka Laravel | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Bawaan kerangka |

## PostGIS

- Ekstensi `postgis` dipasang migrasi `2026_09_19_000001_enable_postgis_extension`.
- Kolom geografi ditambahkan dengan SQL mentah lewat `App\Support\PostGis::addGeography()`: tipe `geography(TYPE,4326)` beserta indeks GIST bernama menurut pola tabel, kolom, dan akhiran `_gix`. Pada koneksi selain PostgreSQL pemanggilannya tidak melakukan apa-apa, sehingga suite SQLite menjalankan migrasi yang sama tanpa kolom spasial.
- Enam kolom geografi: `mountains.location` (POINT), `trails.geometry` (LINESTRING), `trail_segments.geometry` (LINESTRING), `checkpoints.location` (POINT), `restricted_areas.geometry` (POLYGON), `hiking_sessions.last_known_location` (POINT).
- `mountains` dan `checkpoints` juga menyimpan `latitude` dan `longitude` biasa untuk dibaca di luar PostGIS; kueri spasial tetap memakai kolom geografi.
- Kode spasial diuji suite `tests/Spatial` di PostgreSQL + PostGIS (CI, atau lokal lewat `docker-compose.yml`). Suite itu belum pernah hijau di CI (`docs/ARCHITECTURE.md` bagian "Yang belum ada"). Suite SQLite yang hijau tidak membuktikan perilaku PostgreSQL (R-024).

## Akses dari luar aplikasi

Aplikasi terhubung langsung sebagai pemilik tabel dan tidak memakai REST API Supabase.
Migrasi `2026_09_20_000100_tutup_akses_postgrest_ke_tabel_aplikasi` mencabut hak peran
`anon` dan `authenticated` (termasuk hak bawaan untuk tabel yang dibuat kemudian, lewat
`ALTER DEFAULT PRIVILEGES`) dan menyalakan RLS tanpa policy pada tabel yang ada saat migrasi
itu berjalan; alasannya di `docs/ARCHITECTURE.md` bagian "Catatan operasional".

Keadaan per 26 September 2026: dari tabel yang dibuat sesudah migrasi itu, hanya
`trip_reminders` yang menyalakan RLS di migrasinya sendiri, dan `trail_considerations` lewat
`2026_09_25_002314_add_note_to_trail_considerations_table`. Dua belas tabel lainnya
(`permit_requirements`, `authorities`, `authority_mountain`, `expert_credentials`,
`credential_mountain`, `usability_sessions`, `scheduled_task_runs`, `hike_track_points`,
`report_thanks`, `mountain_follows`, `personal_targets`, `user_owned_items`) hanya dilindungi
pencabutan hak itu; lapis kedua untuk mereka menunggu keputusan pemilik (spec v3 bagian 15, Q22).
Migrasi baru yang membuat tabel wajib menyalakan RLS pada PostgreSQL mengikuti pola
`2026_09_25_000918_create_trip_reminders_table`; `comparison_shares` (W3) mengikutinya.

## Kebijakan migrasi

1. Migrasi dibuat dengan `php artisan make:migration --no-interaction` dan bersifat aditif: tabel atau kolom baru. Menghapus kolom, tabel, atau data butuh izin pemilik (DK-23, R-021).
2. Setiap migrasi diuji suite di SQLite dan, bila menyentuh kolom spasial, transaksi, atau constraint, suite `Spatial` di PostgreSQL + PostGIS.
3. Migrasi tidak pernah dijalankan di Supabase tanpa izin pemilik per migrasi (R-019, DK-23). `.claude/settings.json` menolak `php artisan migrate` hanya di Claude Code; bagi agen lain dan manusia, izin itu satu-satunya pagar.
4. Setelah dijalankan atas izin, status migrasi diverifikasi di lingkungan sasaran (`php artisan migrate:status`, atau halaman Kesehatan Sistem di konsol pengelola), bukan disimpulkan dari test (R-024).
5. Kolom yang datanya bisa kosong menyimpan `null`, bukan nol (R-007).

## Data contoh, tanpa dump

- Tidak ada dump basis data di repo: berkas `.sql`, `.sql.gz`, `.dump`, dan SQLite diabaikan git, karena dump dapat memuat data pengguna sungguhan.
- Data lokal berasal dari seeder dan pabrik. `DatabaseSeeder` membuat tiga akun lokal (admin, moderator, pendaki; lihat `README.md`) lalu memanggil `RecommendationRuleSeeder`, `PreparationTemplateSeeder`, `MvpDatasetSeeder`, `MountainSeeder`, `OfficialStatusSeeder`, `PermitRequirementSeeder`, `AuthoritySeeder`, dan `AwaitingTrailSeeder`. Isian yang tidak diketahui sumbernya dibiarkan kosong, tidak ditebak.
- `db:seed` hanya ke basis data lokal. Data contoh berlabel DATA CONTOH (DK-35) ada di `DemoSeeder` dan diisi `php artisan memount:demo` ke berkas `database/demo.sqlite`, oleh pemilik atas keputusan Q1, bukan oleh agen. Perintah itu menolak berjalan di luar `APP_ENV=local`, menolak koneksi yang bukan SQLite, dan menolak berkas di luar folder `database/`, lalu mencetak cara menyajikannya tanpa menyentuh basis data, sesi, cache, antrean, berkas (termasuk foto laporan), surel, atau broadcasting yang ditunjuk `.env`. `DatabaseSeeder` tidak memanggil `DemoSeeder`.
- Baris penyaji dari Git Bash:

  ```sh
  DB_CONNECTION=demo APP_DEMO=true SESSION_DRIVER=file CACHE_STORE=file QUEUE_CONNECTION=sync FILESYSTEM_DISK=local REPORT_PHOTOS_DISK=local MAIL_MAILER=log BROADCAST_CONNECTION=null php artisan serve
  ```

  Dari PowerShell. Variabel `$env:` bertahan sepanjang jendela itu, bukan cuma satu perintah, jadi bersihkan sesudahnya atau pakai jendela terpisah:

  ```powershell
  $env:DB_CONNECTION="demo"; $env:APP_DEMO="true"; $env:SESSION_DRIVER="file"; $env:CACHE_STORE="file"; $env:QUEUE_CONNECTION="sync"; $env:FILESYSTEM_DISK="local"; $env:REPORT_PHOTOS_DISK="local"; $env:MAIL_MAILER="log"; $env:BROADCAST_CONNECTION="null"; php artisan serve
  Remove-Item Env:DB_CONNECTION, Env:APP_DEMO, Env:SESSION_DRIVER, Env:CACHE_STORE, Env:QUEUE_CONNECTION, Env:FILESYSTEM_DISK, Env:REPORT_PHOTOS_DISK, Env:MAIL_MAILER, Env:BROADCAST_CONNECTION
  ```

  Baris itu lapis pertama. Lapis kedua ada di aplikasi sendiri: selama koneksi bawaan `demo`, `AppServiceProvider` memanggil `PenjagaDataContoh::isolasiBilaMenyajikanDemo()`, yang memutus cache, antrean, surel, kedua disk, dan broadcasting dari `.env` dan memakai sesi berkas, walau baris penyaji tidak disalin lengkap. Surel lalu memakai mailer `array` (dibuang, tidak dicatat) dan cache memakai `array`. Kanal log tetap mengikuti `LOG_*` di `.env`.
- Snapshot skema Supabase sebelum hardening (tanpa data) disimpan sebagai catatan sejarah di `docs/db/schema-snapshot-sebelum-hardening.md`.
