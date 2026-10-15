# 06. Test Checklist

Checklist ini memisahkan tiga hal: test otomatis yang benar-benar dijalankan untuk dokumen
ini, batasan test tersebut, dan pemeriksaan manual di peramban yang belum dikerjakan. Status
PASS hanya diberikan bila ada keluaran perintah yang mendukungnya.

## 1. Lingkungan

| Komponen | Versi terverifikasi 15 Okt 2026 | Perintah |
|---|---|---|
| PHP | 8.5.10 | `php -v` |
| Composer | 2.10.3 | `composer -V` |
| Laravel | 13.31.0 | `php artisan --version` |
| Frontend | React 19, Inertia 3, Tailwind CSS 4, Vite 8 (rentang di `package.json`) | `package.json` |
| Basis data test | SQLite dalam memori | `phpunit.xml` |

## 2. Hasil test otomatis

### 2.1 Test yang berkaitan langsung dengan Pertemuan 05

Dijalankan satu per satu dengan `php artisan test --compact tests/Feature/<Berkas>.php`.

| ID | Berkas test | Cakupan | Test | Assertion | Hasil |
|---|---|---|---|---|---|
| T01 | `AdminDataManagementTest` | CRUD gunung dan jalur dari panel admin, audit, ringkasan kesehatan data | 19 | 125 | PASS |
| T02 | `TrailPublishGateTest` | menerbitkan dan menarik jalur, daftar syarat yang kurang | 10 | 61 | PASS |
| T03 | `PublicationInvariantTest` | penjaga publikasi di model, seeder hanya membuat draf | 18 | 28 | PASS |
| T04 | `AdminAccessTest` | hanya admin yang dapat membuka halaman admin | 16 | 16 | PASS |
| T05 | `ArchivedTrailWithActiveTripTest` | trip tetap terbuka saat jalurnya diarsipkan | 5 | 16 | PASS |
| T06 | `TripPlanDetailTest` | buat, ubah, hapus rencana trip beserta validasinya | 19 | 132 | PASS |
| T07 | `TripLifecycleTest` | jalur harus terbit, pemilik dan status dari server, transisi status, batas laju, tab ganda | 25 | 242 | PASS |
| T08 | `TripOwnershipTest` | trip orang lain ditolak, tamu dialihkan ke login | 8 | 21 | PASS |
| T09 | `IndonesianDateFloorTest` | batas tanggal menurut waktu Indonesia | 5 | 8 | PASS |
| T10 | `DemoSeederTest` | data contoh berlabel, hanya SQLite, menolak produksi | 12 | 30 | PASS |
| T11 | `MountainSeederTest` | 16 gunung, idempoten, koordinat dan ketinggian masuk akal | 10 | 76 | PASS |
| | **Jumlah** | | **147** | **755** | **PASS** |

Gabungan sebelas berkas yang sama juga dijalankan dalam satu perintah: 147 lulus,
755 assertion.

### 2.2 Suite penuh

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test --compact` (sebelum indeks `docs/README.md` diperbarui) | 2.717 test: 2.660 lulus, 56 dilewati, 1 gagal |
| Test yang gagal | `TeamDocsIndexTest::test_every_team_document_is_listed_in_the_index`, pesan "Belum terdaftar di docs/README.md: docs/Pertemuan05/" |
| Perbaikan | baris Pertemuan05 ditambahkan ke tabel "Tugas praktikum tim" di `docs/README.md` |
| `php artisan test --compact tests/Feature/TeamDocsIndexTest.php` sesudah perbaikan | 10 lulus, 96 assertion |

Kegagalan itu disebabkan folder ini sendiri, bukan oleh kode aplikasi. Test tersebut menjaga
supaya setiap folder di `docs/` tertaut dari indeks tim. Suite penuh belum dijalankan ulang
sesudah perbaikan; yang dijalankan ulang hanya berkas test yang gagal.

### 2.3 Rincian kasus penting

| ID | Nama test | Yang dibuktikan |
|---|---|---|
| K01 | `test_a_new_trail_is_always_a_draft_whatever_the_payload_says` | formulir yang mengirim `is_published=1` tetap menghasilkan draf |
| K02 | `test_a_duplicate_trail_name_on_the_same_mountain_is_rejected_next_to_its_field` | nama jalur kembar ditolak di samping isiannya, bukan galat 500 |
| K03 | `test_a_trail_edit_is_audited` | perubahan jalur tercatat di log audit |
| K04 | `test_an_incomplete_trail_cannot_be_published` | jalur tidak lengkap tidak dapat diterbitkan |
| K05 | `test_editing_a_published_trail_into_an_incomplete_state_is_rejected` | jalur terbit tidak dapat disunting sampai kehilangan data kritis |
| K06 | `test_a_trip_cannot_be_created_on_an_unpublished_trail` | rencana trip tidak dapat dibuat untuk jalur draf |
| K07 | `test_the_owner_and_the_status_come_from_the_server_not_the_form` | `user_id` dan `status` tidak dapat diselipkan lewat formulir |
| K08 | `test_details_left_empty_are_stored_as_null_not_zero` | isian kosong disimpan `null`, bukan 0 |
| K09 | `test_the_number_of_participants_stays_within_one_and_twelve` | batas peserta 1 sampai 12 |
| K10 | `test_a_trip_that_has_started_can_no_longer_be_edited` | trip yang sudah dimulai tidak dapat diubah |
| K11 | `test_a_trip_in_progress_is_not_deleted_and_the_reason_is_given` | trip berlangsung tidak terhapus dan alasannya disebut |
| K12 | `test_a_completed_trip_is_not_deleted_because_its_history_depends_on_it` | trip selesai tidak terhapus karena riwayat bergantung padanya |
| K13 | `test_another_user_cannot_edit_or_delete_someone_elses_trip` | pengguna lain mendapat 403 untuk ubah dan hapus |
| K14 | `test_deleting_after_another_tab_started_the_hike_is_refused` | hapus dari tab basi tidak menghapus pendakian yang baru dimulai |
| K15 | `test_rerunning_the_seeder_publishes_nothing` | seeder dijalankan dua kali tetap tidak menerbitkan jalur |

## 3. Batasan test otomatis

1. **SQLite bukan PostgreSQL** (R-024). Suite di atas berjalan di SQLite dalam memori. Kolom
   spasial, indeks GiST, RLS, dan penguncian baris PostgreSQL tidak ikut teruji. Folder
   `tests/Spatial` berisi 54 test yang butuh PostGIS sungguhan, dan itulah asal sebagian
   besar dari 56 test yang dilewati. Cara menjalankannya ada di `README.md` root, bagian
   test spasial (`php artisan test --testsuite=Spatial` dengan `SPATIAL_TEST_DSN`).
2. **Status migrasi Supabase tidak diperiksa** untuk dokumen ini. Pemeriksaan basis data
   produksi memerlukan izin pemilik (R-019).
3. **Test HTTP bukan uji peramban.** Test memeriksa respons server, props Inertia, dan isi
   basis data. Tampilan, fokus keyboard, dan perilaku di layar ponsel belum terbukti oleh
   test ini.
4. **Celah seeder.** `DatabaseSeeder` mematikan event model, sehingga penjaga publikasi
   jalur tidak aktif selama `db:seed`. Belum ada test yang menjalankan `DatabaseSeeder` penuh
   lalu memeriksa jumlah jalur terbit. Lihat [`03-seeder.md`](03-seeder.md).

## 4. Checklist manual di peramban

Belum ada butir yang diuji dengan tangan di aplikasi yang berjalan. Sembilan butir sudah
**TERPOTRET** di [`07-screenshots.pdf`](07-screenshots.pdf). Artinya aksinya dijalankan
sungguhan lewat HTTP di suite pratinjau dan halaman hasilnya dipotret; itu bukti tampilan,
belum uji manual. Butir lain berstatus **BELUM DIUJI**. Isi kolom hasil sesudah mencoba
sendiri, dan jangan menulis PASS tanpa mencobanya.

Persiapan:

- **CRUD rencana trip (M10 sampai M18):** `composer inspeksi:data` mengosongkan lalu
  mengisi `database/demo.sqlite` dengan data contoh, termasuk akun pendaki
  `budi@contoh.test`. Kata sandinya diacak dan hanya dicetak di terminal. Lalu jalankan
  `composer inspeksi` dan buka alamat yang dicetak terminal.
- **CRUD jalur (M01 sampai M09):** data contoh tidak membuat akun admin. Dibutuhkan basis
  data lokal terpisah yang diisi `php artisan migrate` dan `php artisan db:seed`, yang
  membuat akun `admin@example.com`. Langkah ini belum dicoba untuk dokumen ini.

### CRUD jalur (masuk sebagai admin)

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| M01 | Buka `/admin/trails` | Ringkasan kesehatan data, tombol Tambah jalur, tabel berpaginasi | TERPOTRET (07, A1) |
| M02 | Tambah jalur dengan nama kosong | Pesan galat berbahasa Indonesia di samping isian nama | BELUM DIUJI |
| M03 | Tambah jalur dengan nama dan gunung valid, angka dikosongkan | "Data jalur tersimpan."; jalur muncul sebagai draf | TERPOTRET (07, A3) |
| M04 | Tambah jalur dengan nama yang sama di gunung yang sama | "Jalur dengan nama ini sudah ada di gunung tersebut." | BELUM DIUJI |
| M05 | Ubah jalur, ganti deskripsi | Perubahan tersimpan dan tampil di tabel | BELUM DIUJI |
| M06 | Tekan Publikasikan pada jalur yang datanya belum lengkap | Peringatan berisi daftar syarat yang kurang; jalur tetap draf | TERPOTRET (07, A4) |
| M07 | Tekan Arsipkan, lalu batalkan di dialog konfirmasi | Tidak ada perubahan | BELUM DIUJI |
| M08 | Tekan Arsipkan dan setujui, lalu tekan Aktifkan | Label berganti menjadi "(diarsipkan)", lalu kembali aktif | SEBAGIAN: arsip terpotret (07, A5); Aktifkan belum dicoba |
| M09 | Masuk sebagai pendaki, buka `/admin/trails` | Akses ditolak | BELUM DIUJI |

### CRUD rencana trip (masuk sebagai pendaki)

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| M10 | Buka `/trips` | Kartu trip terdekat, kelompok Berlangsung, Mendatang, Selesai | TERPOTRET (07, B1 dan B6) |
| M11 | Dari rincian jalur terbit, tekan rencanakan trip dan kirim formulir kosong | Pesan galat di samping isian wajib | BELUM DIUJI |
| M12 | Isi tanggal selesai sebelum tanggal mulai | "Tanggal selesai tidak boleh sebelum tanggal mulai pendakian." | BELUM DIUJI |
| M13 | Isi formulir valid dan simpan | Pindah ke halaman Persiapan dengan "Rencana trip tersimpan." | TERPOTRET (07, B3) |
| M14 | Buka halaman trip, tekan ubah, ganti nama, simpan | "Rencana trip diperbarui."; nama baru tampil | TERPOTRET (07, B4 dan B5) |
| M15 | Tekan Hapus rencana, batalkan dialog | Trip tetap ada | BELUM DIUJI |
| M16 | Tekan Hapus rencana dan setujui | Kembali ke Trip Saya dengan "Rencana trip dihapus." | TEMUAN: rencana terhapus, tetapi pesan tidak tampil di Trip Saya (07, B8) |
| M17 | Buka halaman trip yang sudah selesai | Bagian Hapus rencana menyebut alasan trip tidak dapat dihapus | TERPOTRET (07, B7) |
| M18 | Salin URL trip, buka dengan akun lain | Akses ditolak (403) | BELUM DIUJI |

### Aksesibilitas dan seluler

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| M19 | Telusuri formulir jalur dan formulir trip hanya dengan Tab dan Enter | Semua isian dan tombol terjangkau, fokus terlihat | BELUM DIUJI |
| M20 | Buka `/trips` dan `/admin/trails` di lebar 390 px | Tidak ada gulir mendatar; tombol utama mudah disentuh | BELUM DIUJI |

## 5. Perintah verifikasi

```powershell
# Test yang berkaitan dengan Pertemuan 05
php artisan test --compact tests/Feature/AdminDataManagementTest.php tests/Feature/TrailPublishGateTest.php tests/Feature/PublicationInvariantTest.php tests/Feature/AdminAccessTest.php tests/Feature/ArchivedTrailWithActiveTripTest.php tests/Feature/TripPlanDetailTest.php tests/Feature/TripLifecycleTest.php tests/Feature/TripOwnershipTest.php tests/Feature/IndonesianDateFloorTest.php tests/Feature/DemoSeederTest.php tests/Feature/MountainSeederTest.php

# Suite penuh
php artisan test --compact

# Status migrasi lokal
php artisan migrate:status
```

Simpan keluaran terminal bila dosen meminta bukti. Jangan mengganti status menjadi PASS bila
perintah gagal atau belum dijalankan.
