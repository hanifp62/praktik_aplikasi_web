# 03. Seeder

MEMOUNT punya dua jalur pengisian data yang sengaja dipisah:

| Jalur | Perintah | Basis data | Isi |
|---|---|---|---|
| Data awal | `php artisan db:seed` | koneksi bawaan `.env` | akun admin/moderator/pendaki, aturan, gunung, jalur draf, status, izin, otoritas |
| Data contoh berlabel | `composer inspeksi:data` (menjalankan `php artisan memount:demo`) | hanya SQLite `database/demo.sqlite` | satu pendaki contoh dengan trip, riwayat, simpanan, dan laporan |

Data contoh tidak pernah masuk ke basis data produksi. `DemoSeeder` menolak berjalan bila
koneksinya bukan SQLite atau lingkungannya `production`, dan `DatabaseSeeder` tidak
memanggilnya sama sekali.

## 1. `DatabaseSeeder`

Berkas: [`database/seeders/DatabaseSeeder.php`](../../database/seeders/DatabaseSeeder.php)

Membuat tiga akun lewat `UserFactory`, lalu memanggil seeder lain secara berurutan:

| Akun | Peran |
|---|---|
| `admin@example.com` | `admin` |
| `moderator@example.com` | `moderator` |
| `pendaki@example.com` | `hiker` (bawaan) |

Kata sandi akun ini mengikuti `UserFactory` dan sengaja tidak ditulis di dokumen.

Urutan pemanggilan dan isinya:

| Urutan | Seeder | Isi | Sifat data |
|---|---|---|---|
| 1 | `RecommendationRuleSeeder` | bobot kecocokan versi 1 (PRD §27) | konfigurasi, dapat dikalibrasi ulang |
| 2 | `PreparationTemplateSeeder` | daftar persiapan bawaan (PRD §36) | konfigurasi |
| 3 | `MvpDatasetSeeder` | 5 gunung, 7 jalur beserta pos, sumber "Data kurasi internal (demo)" | data kurasi pengembangan, status resmi `UNKNOWN`, semua jalur draf |
| 4 | `MountainSeeder` | 16 gunung di Jawa dan Lombok dengan koordinat dan ketinggian | dari Wikidata, dua nilai dikoreksi dengan Wikipedia Indonesia |
| 5 | `OfficialStatusSeeder` | status resmi gunung per September 2026 | setiap baris membawa sumber dan tanggal; gunung tanpa keterangan dibiarkan kosong |
| 6 | `PermitRequirementSeeder` | aturan izin gunung berkuota per September 2026 | yang tidak disebut sumbernya dibiarkan kosong |
| 7 | `AuthoritySeeder` | badan resmi (balai taman nasional, BNSP, LSP, APGI) dan gunung di bawahnya | data nyata, bukan contoh |
| 8 | `AwaitingTrailSeeder` | 29 jalur yang namanya dikenal pendaki, karakteristiknya sengaja kosong | jalur draf "menunggu data" |

Urutan 3 dan 4 penting. `MvpDatasetSeeder` dan `MountainSeeder` memakai `updateOrCreate`
dengan kunci `slug`, sehingga gunung yang sama diperbarui, bukan digandakan. Status di
urutan 5 menempel pada gunung, jadi gunungnya harus ada lebih dulu.

### Prinsip isian

- **Tidak mengarang angka.** `AwaitingTrailSeeder` hanya mengisi nama jalur dan gunungnya.
  Jarak dan tanjakan dibiarkan kosong karena angka yang salah langsung memengaruhi
  penilaian kecocokan (R-007).
- **Tidak menebak status.** Gunung yang tidak ditemukan status resminya dibiarkan tanpa
  catatan, sehingga sistem menjawab `UNKNOWN`, bukan `OPEN` (R-006, R-012).
- **Tidak menerbitkan jalur.** Seeder menyiapkan bahan; menerbitkan adalah keputusan admin
  lewat panel Kelola Jalur. `AwaitingTrailSeeder` menulis `is_published => false`.
  `MvpDatasetSeeder` sengaja tidak menyebut kunci itu: baris baru memakai nilai bawaan
  `false`, dan baris yang sudah ada tidak berubah saat seeder dijalankan ulang.
- **Idempoten.** Menjalankan seeder dua kali tidak menggandakan gunung dan tidak
  menerbitkan apa pun (`MountainSeederTest::test_running_it_twice_does_not_duplicate`,
  `PublicationInvariantTest::test_rerunning_the_seeder_publishes_nothing`).

### Catatan terbuka: penjaga publikasi tidak aktif saat `db:seed`

`DatabaseSeeder` memakai trait `WithoutModelEvents`. Laravel membungkus seluruh `run()`,
termasuk seeder yang dipanggil lewat `$this->call()`, dengan `Model::withoutEvents()`
(`vendor/laravel/framework/src/Illuminate/Database/Seeder.php`, method `__invoke`). Akibatnya
penjaga `saving` di `Trail::booted()` tidak berjalan selama `php artisan db:seed`.

Saat ini hasilnya tetap aman karena tidak ada seeder di bawah `DatabaseSeeder` yang menulis
`is_published = true`. Test `PublicationInvariantTest` memanggil `MvpDatasetSeeder` secara
langsung, sehingga penjaganya aktif di test itu, dan belum ada test yang menjalankan
`DatabaseSeeder` penuh lalu memeriksa jumlah jalur terbit. Status: **OPEN ISSUE**. Usulan,
belum dikerjakan dan perlu keputusan tim: tambah test regresi "DatabaseSeeder tidak
menerbitkan jalur apa pun", atau lepas `WithoutModelEvents` dari `DatabaseSeeder`.

## 2. `DemoSeeder` (data contoh berlabel)

Berkas: [`database/seeders/DemoSeeder.php`](../../database/seeders/DemoSeeder.php)

Isinya meniru contoh di prototype supaya tangkapan layar dapat dibandingkan dengan gambar
acuan:

- satu pendaki pemula, Budi Setiawan (`budi@contoh.test`);
- delapan gunung dan sebelas jalur (sepuluh terbit, satu menunggu data);
- trip "Gede via Cibodas" lima belas hari lagi dengan pengingat H-3 dan H-1;
- tiga pendakian selesai, satu trip batal, lima jalur tersimpan;
- satu aturan izin untuk Gunung Gede dan satu laporan kondisi dari pendaki contoh kedua.

Pengamannya:

| Pengaman | Bukti |
|---|---|
| Menolak koneksi selain SQLite | `DemoSeederTest::test_it_refuses_a_database_that_is_not_sqlite` |
| Menolak lingkungan `production` | `DemoSeederTest::test_it_refuses_production` |
| Setiap teks yang terbaca sebagai fakta diawali `DATA CONTOH` | `DemoSeederTest::test_every_text_that_reads_as_fact_is_marked` |
| Kata sandi pendaki contoh diacak setiap kali dan hanya dicetak ke konsol | `DemoSeederTest::test_the_sample_password_is_random_and_never_written_down` |
| `DatabaseSeeder` tidak pernah menanam data contoh | `DemoSeederTest::test_the_regular_seeder_never_plants_sample_data` |

Jalur contoh diterbitkan lewat `$trail->refresh()->update(['is_published' => true])`
sesudah pos, sumber, dan statusnya dibuat. `DemoSeeder` tidak memakai `WithoutModelEvents`,
jadi penerbitan ini melewati penjaga model.

## 3. Perintah

```powershell
# Data awal ke koneksi bawaan .env
php artisan migrate
php artisan db:seed

# Satu seeder saja
php artisan db:seed --class=MountainSeeder

# Data contoh berlabel (SQLite database/demo.sqlite), lalu jalankan aplikasi
composer inspeksi:data
composer inspeksi
```

`php artisan migrate:fresh --seed` menghapus seluruh tabel sebelum mengisi ulang. Pakai
hanya pada basis data lokal, jangan pernah pada Supabase.

## 4. Test yang menjaga seeder

| Test | Jumlah test | Hasil 15 Okt 2026 |
|---|---|---|
| `tests/Feature/MountainSeederTest.php` | 10 | 10 lulus, 76 assertion |
| `tests/Feature/DemoSeederTest.php` | 12 | 12 lulus, 30 assertion |
| `tests/Feature/PublicationInvariantTest.php` (termasuk dua test seeder) | 18 | 18 lulus, 28 assertion |
| `tests/Feature/OfficialStatusSeederTest.php` | ikut suite penuh | lihat [`06-test-checklist.md`](06-test-checklist.md) |
| `tests/Feature/PermitRequirementSeederTest.php` | ikut suite penuh | lihat [`06-test-checklist.md`](06-test-checklist.md) |
