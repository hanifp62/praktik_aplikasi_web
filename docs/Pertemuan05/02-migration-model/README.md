# 02. Migration dan Model

Folder ini memetakan berkas migrasi ke model Eloquent untuk tiga entitas yang dipakai CRUD
Pertemuan 05. Kode aslinya tidak disalin ke sini supaya dokumen tidak menyimpang dari kode;
setiap halaman menautkan berkas sumbernya dan hanya mengutip bagian yang dibahas.

| Berkas | Tabel | Model | Peran di tugas |
|---|---|---|---|
| [`mountain.md`](mountain.md) | `mountains` | `Mountain` | induk jalur (padanan gedung) |
| [`trail.md`](trail.md) | `trails` | `Trail` | padanan Room |
| [`trip-plan.md`](trip-plan.md) | `trip_plans` | `TripPlan` | padanan Booking |

## Konvensi migrasi di repo ini

1. **Satu migrasi pembuat tabel, lalu migrasi tambahan.** Kolom baru ditambah lewat migrasi
   baru (`add_..._to_..._table`), bukan dengan menyunting migrasi lama, karena migrasi lama
   sudah berjalan di basis data produksi.
2. **Kolom tambahan bersifat aditif.** Kolom baru dibuat `nullable` atau bernilai bawaan
   supaya baris lama tetap sah. Contoh: `end_date`, `participants_count`,
   `cancellation_reason`, dan `expected_return_at` di `trip_plans`.
3. **Migrasi tabel dan kolom punya `down()`** yang membalik `up()`. Pengecualian yang
   disengaja: `2026_09_19_000001_enable_postgis_extension.php` tidak mencabut ekstensi
   PostGIS karena skema lain di proyek Supabase yang sama dapat bergantung padanya.
4. **Kolom spasial lewat helper.** `App\Support\PostGis::addGeography()` hanya berjalan di
   PostgreSQL dengan PostGIS. Di SQLite (test dan data contoh) kolom spasial tidak dibuat.
5. **Keamanan basis data di migrasi.** `2026_09_20_000100_tutup_akses_postgrest_ke_tabel_aplikasi`
   dan `2026_10_12_020612_aktifkan_rls_di_semua_tabel_aplikasi` menutup akses API data
   Supabase (PostgREST) ke tabel aplikasi. Keduanya hanya bekerja di PostgreSQL.

## Konvensi model

- Atribut yang boleh diisi massal ditulis dengan atribut PHP `#[Fillable([...])]` di atas
  kelas, bukan properti `$fillable`. Kolom tersembunyi memakai `#[Hidden([...])]`.
- Konversi tipe ada di method `casts()`: tanggal, boolean, JSON, dan enum.
- Relasi ditulis sebagai method bertipe kembali (`BelongsTo`, `HasMany`, `HasOne`,
  `MorphMany`).
- Logika yang menyentuh lebih dari satu model (membuat trip beserta daftar persiapannya,
  misalnya) tidak ditaruh di model, melainkan di service seperti
  `App\Services\Trips\TripLifecycle`.

## Daftar migrasi yang menyentuh tiga tabel ini

| Urutan | Berkas | Isi |
|---|---|---|
| 1 | `2026_09_19_000001_enable_postgis_extension.php` | mengaktifkan ekstensi PostGIS (hanya PostgreSQL) |
| 2 | `2026_09_19_000210_create_mountains_table.php` | tabel `mountains` dan kolom `location` |
| 3 | `2026_09_19_000220_create_trails_table.php` | tabel `trails` dan kolom `geometry` |
| 4 | `2026_09_19_000410_create_trip_plans_table.php` | tabel `trip_plans` |
| 5 | `2026_09_20_000110_add_timezone_to_mountains.php` | `mountains.timezone`, bawaan `Asia/Jakarta` |
| 6 | `2026_09_20_000200_indeks_foreign_key_yang_belum_terindeks.php` | indeks untuk `mountains.data_source_id`, `trails.mountain_id`, `trails.data_source_id`, `trip_plans.user_id`, `trip_plans.trail_id`, `trip_plans.hiking_goal_id` |
| 7 | `2026_09_20_000300_tambah_koordinat_biasa_ke_gunung.php` | `mountains.latitude`, `mountains.longitude` |
| 8 | `2026_09_20_000700_add_elevation_profile_to_trails_table.php` | `trails.elevation_profile` (JSON) |
| 9 | `2026_09_25_000917_add_plan_details_to_trip_plans_table.php` | `end_date`, `participants_count`, `reminders_enabled` |
| 10 | `2026_10_07_000300_add_cancellation_reason_to_trip_plans_table.php` | `cancellation_reason` |
| 11 | `2026_10_07_000500_add_return_and_registration_to_trip_plans_table.php` | `expected_return_at`, `registration_number` |

Seluruh repo berisi 65 berkas migrasi (`ls database/migrations | wc -l`, 15 Oktober 2026).

## Status migrasi yang terverifikasi

| Lingkungan | Hasil | Bukti |
|---|---|---|
| Basis data contoh lokal (`database/demo.sqlite`, koneksi `demo`) | 64 dari 65 berstatus `Ran`; `2026_10_12_020612_aktifkan_rls_di_semua_tabel_aplikasi` masih `Pending` | `php artisan migrate:status`, 15 Oktober 2026 |
| Suite PHPUnit | SQLite dalam memori, seluruh migrasi dijalankan ulang setiap test | `phpunit.xml` |
| Supabase produksi | tidak diperiksa untuk dokumen ini | pemeriksaan ke Supabase butuh izin pemilik (R-019) |

Migrasi RLS yang masih `Pending` di SQLite contoh tidak mengubah apa pun di SQLite karena
isinya khusus PostgreSQL. Menjalankan `php artisan migrate` pada koneksi contoh akan
menandainya `Ran`.

## Perintah

```powershell
php artisan migrate:status          # daftar migrasi dan statusnya
php artisan migrate                 # jalankan migrasi yang belum berjalan
php artisan make:migration add_kolom_to_trails_table --table=trails --no-interaction
php artisan make:model NamaModel --factory --no-interaction
```

`php artisan migrate:fresh` menghapus semua tabel. Jangan pernah menjalankannya terhadap
basis data Supabase.
