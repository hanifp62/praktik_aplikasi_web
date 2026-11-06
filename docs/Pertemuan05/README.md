# Dokumentasi Pengumpulan: Praktik Aplikasi Web Pertemuan 05

**Project:** MEMOUNT, aplikasi perencanaan pendakian

## Tujuan

Pertemuan 05 membahas data model, migration dan model, seeder, serta CRUD. Templat tugas
memakai contoh sistem pemesanan ruangan (Room dan Booking). Folder ini memetakan templat
itu ke entitas yang benar-benar ada di kode MEMOUNT per 15 Oktober 2026 (Laravel 13,
React 19 lewat Inertia 3), bukan ke snapshot Blade yang dipakai Pertemuan 04.

## Padanan templat dan sistem

| Templat tugas | Di folder ini | Entitas MEMOUNT |
|---|---|---|
| `04-crud-room/` | [`04-crud-jalur/`](04-crud-jalur/) | Jalur pendakian (`trails`, `Trail`), dikelola admin |
| `05-crud-booking/` | [`05-crud-rencana-trip/`](05-crud-rencana-trip/) | Rencana trip (`trip_plans`, `TripPlan`), dibuat pendaki |

Dua perbedaan dari contoh pemesanan ruangan:

1. **Jalur tidak dihapus permanen.** Menghapus jalur akan ikut menghapus rencana trip dan
   riwayat pendakian orang lain lewat foreign key cascade, jadi "Delete" pada CRUD jalur
   berbentuk arsip.
2. **Rencana trip bukan pemesanan kuota.** Tidak ada pengecekan bentrok jadwal antar-pendaki.
   Izin dan kuota tetap diurus di sistem resmi pengelola gunung; MEMOUNT hanya menautkannya.

## Daftar deliverable

| Berkas | Isi |
|---|---|
| [`01-data-model.md`](01-data-model.md) | Padanan entitas, diagram relasi (Mermaid), perilaku hapus, enum, transisi status trip, aturan data |
| [`02-migration-model/`](02-migration-model/) | Konvensi migrasi dan model, daftar migrasi terkait, status migrasi, satu halaman per tabel: [`mountain.md`](02-migration-model/mountain.md), [`trail.md`](02-migration-model/trail.md), [`trip-plan.md`](02-migration-model/trip-plan.md) |
| [`03-seeder.md`](03-seeder.md) | `DatabaseSeeder` dan delapan seeder turunannya, `DemoSeeder`, perintah, test, satu catatan terbuka |
| [`04-crud-jalur/`](04-crud-jalur/README.md) | Route, alur Create/Read/Update/Delete(arsip), validasi, otorisasi, test |
| [`05-crud-rencana-trip/`](05-crud-rencana-trip/README.md) | Route, alur Create/Read/Update/Delete, validasi, otorisasi, test |
| [`06-test-checklist.md`](06-test-checklist.md) | Hasil test otomatis 15 Okt 2026, batasannya, dan checklist manual peramban |
| [`07-screenshots.pdf`](07-screenshots.pdf) | 25 halaman: 13 tangkapan CRUD jalur dan rencana trip, diagram relasi, keluaran test, dan catatan temuan |

## Ringkasan bukti

| Pemeriksaan | Hasil 15 Okt 2026 |
|---|---|
| Sebelas berkas test terkait CRUD, seeder, dan gerbang publikasi | 147 test lulus, 755 assertion |
| Suite penuh | 2.717 test: 2.660 lulus, 56 dilewati (butuh PostGIS), 1 gagal karena folder ini belum terdaftar di `docs/README.md`; sesudah indeks diperbarui, test itu lulus (10 test, 96 assertion) |
| Status migrasi basis data contoh lokal | 64 dari 65 `Ran`; migrasi RLS khusus PostgreSQL masih `Pending` |
| Tangkapan layar (`07-screenshots.pdf`) | 13 layar dari aksi CRUD sungguhan lewat HTTP; 9 dari 20 butir checklist manual terpotret, satu di antaranya memunculkan temuan |
| Uji manual di peramban pada aplikasi berjalan | belum dilakukan |
| Basis data Supabase produksi | tidak diperiksa (butuh izin pemilik) |

Rinciannya ada di [`06-test-checklist.md`](06-test-checklist.md).

## Isi `07-screenshots.pdf`

| Bagian | Isi |
|---|---|
| Sampul | Identitas (isian kosong), cara tangkapan dibuat, daftar tangkapan |
| A1 sampai A5 | CRUD jalur sebagai admin, lebar 1440 px: daftar, formulir tambah, sesudah simpan, publikasi ditolak, arsip |
| B1 sampai B8 | CRUD rencana trip sebagai pendaki contoh, lebar 390 px: daftar, formulir, sesudah simpan, formulir ubah, sesudah ubah, daftar sebelum hapus, trip selesai yang tidak dapat dihapus, daftar sesudah hapus |
| C1 | Diagram relasi inti, dirender dari sumber Mermaid |
| C2 | Hasil test otomatis 15 Oktober 2026 |
| Catatan | Satu temuan dan batasan tangkapan |

Cara pembuatannya, 15 Oktober 2026:

- Sebuah test sementara di atas harness `tests/Pratinjau` menjalankan setiap aksi CRUD lewat
  HTTP di SQLite memori berisi data contoh `DemoSeeder`, lalu menyimpan halaman sesudahnya
  sebagai HTML. Test sementara itu dihapus sesudah tangkapan selesai.
- HTML itu disajikan `php -S` dengan router `tests/Pratinjau/statis.php`, lalu dipotret
  Chrome headless.
- Tidak ada basis data lokal, Supabase, maupun kata sandi yang dipakai.

Yang tidak dipotret, beserta alasannya:

| Tidak dipotret | Alasan | Buktinya ada di |
|---|---|---|
| Dialog konfirmasi bawaan peramban | Aksi dikirim test, jadi dialog tidak muncul | Teks dialog di 04 dan 05 |
| Galat validasi | Pratinjau statis tidak menerima kiriman formulir | Test otomatis di C2 |
| Akses ditolak | Sama seperti di atas | `AdminAccessTest`, `TripOwnershipTest` |

**Temuan dari tangkapan B8:** sesudah rencana dihapus, server mengirim pesan
"Rencana trip dihapus.", tetapi halaman Trip Saya (`resources/js/pages/Trips/Index.tsx`) tidak
menampilkannya. Akibatnya pendaki tidak melihat konfirmasi bahwa penghapusan berhasil. Belum
diperbaiki; perlu keputusan tim.

## Sumber acuan

- Kode: `app/Models/`, `app/Http/Controllers/Admin/TrailController.php`,
  `app/Http/Controllers/Trips/TripController.php`, `app/Http/Requests/`, `app/Policies/`,
  `app/Services/Trips/TripLifecycle.php`, `database/migrations/`, `database/seeders/`,
  `resources/js/pages/Admin/Trails.tsx`, `resources/js/pages/Trips/`, `tests/Feature/`.
- Aturan domain: [`.claude/rules/mountain-core.md`](../../.claude/rules/mountain-core.md)
  (R-001 Gunung bukan Jalur, R-007 data kosong bukan nilai rendah, R-022 gerbang publikasi).
- Skema per domain: [`database/README.md`](../../database/README.md).

## Identitas pengumpulan

Isi bila diminta dosen:

- Nama: `[isi nama mahasiswa]`
- NIM: `[isi NIM]`
- Kelas: `[isi kelas]`
- Mata kuliah: Praktik Aplikasi Web
- Pertemuan: 05

## Status

Kedelapan deliverable ada, dan setiap angka di dalamnya berasal dari perintah yang dijalankan
15 Oktober 2026. Yang tersisa adalah butir checklist manual di `06-test-checklist.md` yang
belum terpotret dan belum diuji di aplikasi berjalan.
