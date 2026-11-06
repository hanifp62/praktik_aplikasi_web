# Dokumentasi Pengumpulan: Praktik Aplikasi Web Pertemuan 06

**Project:** MEMOUNT, aplikasi perencanaan pendakian

## Tujuan

Pertemuan 06 membahas autentikasi, otorisasi, role, middleware, policy, persetujuan, dan
pengujian akses. Contoh pada brief memakai peminjam, admin, ruangan, dan booking. Dokumen ini
memetakan capaian tersebut ke domain Memount yang benar-benar ada di kode, bukan menambahkan
fitur peminjaman ruang.

## Padanan brief dengan Memount

| Konsep pada brief | Padanan Memount | Bukti utama |
|---|---|---|
| Tamu | Pengguna yang belum masuk | Route publik dan route `auth` di `routes/web.php` serta `routes/auth.php` |
| Peminjam | Pendaki dengan `UserRole::HIKER` | `app/Enums/UserRole.php`, `app/Models/User.php` |
| Admin | Pengelola data gunung dan jalur | Grup route `admin.*` dengan middleware `role:admin` |
| Pemilik booking | Pemilik rencana trip (`TripPlan.user_id`) | `app/Policies/TripPlanPolicy.php` |
| Persetujuan booking | Moderasi laporan kondisi komunitas | Grup `moderation.*`, `TrailConditionReportPolicy`, `ModerationQueueController` |
| CRUD ruangan | Kelola gunung dan jalur | `AdminAccessTest`, `TrailPolicy`, `MountainPolicy` |

Moderator adalah peran terpisah dari admin. Admin juga memenuhi pemeriksaan moderator melalui
`User::isModerator()`, tetapi tidak mendapat izin mengelola trip milik pengguna lain. Aturan
otorisasi yang berjalan, bukan nama pada contoh tugas, menentukan matriks akses.

## Deliverable

| Berkas | Isi |
|---|---|
| [`01-matriks-akses.md`](01-matriks-akses.md) | Matriks tamu, pendaki, moderator, dan admin beserta batas akses tingkat objek |
| [`02-implementasi.md`](02-implementasi.md) | Alur masuk dan daftar, middleware, policy, moderasi, seeder, dan perilaku UI |
| [`03-test-checklist.md`](03-test-checklist.md) | Test otomatis yang relevan, checklist manual, dan hasil verifikasi sesi ini |

## Ringkasan status

- **Implementasi:** fitur autentikasi, role, middleware, policy, dan moderasi telah ada di
  aplikasi. Dokumen ini mencatat padanannya dengan kriteria tugas.
- **Test pada sesi ini:** 88 test lulus dengan 402 assertion pada empat kelompok test feature
  yang relevan. Detail perintah dan cakupan ada di
  [`03-test-checklist.md`](03-test-checklist.md).
- **Indeks dokumentasi:** `TeamDocsIndexTest` lulus, 10 test dan 96 assertion.
- **Kebersihan rahasia:** `SecretsNotInRepoTest` lulus, 20 test dan 35 assertion.
- **Build frontend:** `npm run build` lulus dengan PHP 8.5.12 tersedia untuk Wayfinder.
- **Uji peramban dan tangkapan layar:** belum dilakukan pada sesi ini. Checklist di
  [`03-test-checklist.md`](03-test-checklist.md) memisahkan hal yang dibuktikan kode dari yang
  masih perlu diperiksa manusia.
- **Database:** tidak ada migrasi atau akses Supabase yang dilakukan.

Angka di atas berasal dari sesi penyusunan dokumen ini. Hasil yang pernah dicatat pada dokumen
Pertemuan 05 bukan hasil test untuk Pertemuan 06 dan tidak dipakai sebagai bukti.

## Pemetaan rubrik

| Capaian pada brief | Lokasi bukti dalam project | Dokumen |
|---|---|---|
| Matriks akses | `EnsureUserHasRole`, route groups, `TripPlanPolicy`, `TrailConditionReportPolicy` | [`01-matriks-akses.md`](01-matriks-akses.md) |
| Autentikasi | Controller dan route auth, registrasi, login, reset sandi, verifikasi surel | [`02-implementasi.md`](02-implementasi.md) |
| Role dan relasi pemilik | `UserRole`, `User`, `trip_plans.user_id`, `DatabaseSeeder` | [`02-implementasi.md`](02-implementasi.md) |
| Middleware dan route | Grup `auth`, `role:moderator`, dan `role:admin` | [`02-implementasi.md`](02-implementasi.md) |
| Policy dan persetujuan | `TripPlanPolicy`, `TrailConditionReportPolicy`, antrean moderasi | [`01-matriks-akses.md`](01-matriks-akses.md) |
| UI dan pengujian | Prop Inertia untuk tampilan navigasi, test feature, checklist peramban | [`03-test-checklist.md`](03-test-checklist.md) |
| Dokumentasi dan Git | Folder ini dan indeks dokumen tim | [`../README.md`](../README.md) |

## Identitas pengumpulan

Isi bila diminta dosen:

- Nama: `[isi nama mahasiswa]`
- NIM: `[isi NIM]`
- Kelas: `[isi kelas]`
- Mata kuliah: Praktik Aplikasi Web
- Pertemuan: 06
