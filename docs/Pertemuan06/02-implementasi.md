# 02. Implementasi Autentikasi dan Otorisasi

## 1. Autentikasi

Route daftar dan masuk tersedia untuk tamu di `routes/auth.php`. Setelah daftar, server
memvalidasi tiga isian, membuat pengguna dengan peran bawaan pendaki, mengirim event
`Registered`, memasukkan pengguna, lalu mengarahkannya ke onboarding. Pengguna tidak dapat
memilih role dari payload daftar.

Pengguna yang sudah masuk dapat keluar melalui POST, memverifikasi surel, meminta tautan
atur ulang sandi, mengubah sandi, dan mengonfirmasi ulang sandi untuk aksi sensitif. Batas laju
diterapkan pada login, kirim ulang verifikasi, dan konfirmasi sandi. Pesan login untuk email
yang tidak ditemukan sama dengan pesan sandi salah agar formulir tidak membocorkan keberadaan
akun.

**Bukti kode:** `app/Http/Controllers/Auth/RegisteredUserController.php`,
`app/Http/Controllers/Auth/AuthenticatedSessionController.php`,
`app/Http/Requests/Auth/RegisterRequest.php`, `routes/auth.php`, dan
`app/Models/User.php`.

## 2. Role dan relasi pemilik

`App\Enums\UserRole` membatasi role aplikasi menjadi `hiker`, `moderator`, dan `admin`.
Kolom `users.role` ditambahkan dengan nilai bawaan `hiker`. `User::isModerator()` menerima
moderator dan admin; `User::isAdmin()` hanya menerima admin.

Rencana perjalanan menggunakan relasi `User::tripPlans()` dan `trip_plans.user_id`. Policy
memeriksa ID pengguna yang sudah terautentikasi, bukan ID pemilik dari formulir. Seeder data
awal membuat contoh setiap role, tetapi kata sandinya tidak dicatat di dokumen ini. Jangan
menjalankan seeder pada database produksi sebagai bagian dari checklist tugas.

**Bukti kode:** `app/Enums/UserRole.php`, `app/Models/User.php`,
`app/Models/TripPlan.php`, `database/migrations/2026_09_19_000100_add_role_to_users_table.php`,
dan `database/seeders/DatabaseSeeder.php`.

## 3. Middleware dan route

`routes/web.php` mengelompokkan area setelah login di bawah middleware `auth`. Dashboard juga
memerlukan `verified`. Route `moderation.*` memakai `auth` bersama `role:moderator`, sedangkan
route `admin.*` memakai `auth` bersama `role:admin`.

Alias `role` didaftarkan pada `bootstrap/app.php` ke
`App\Http\Middleware\EnsureUserHasRole`. Middleware mengubah parameter role menjadi enum dan
menolak pengguna yang tidak memenuhi peran. Admin memenuhi route moderator melalui aturan
`User::isModerator()`.

Middleware bukan satu-satunya lapisan. Controller aksi juga memanggil policy ketika akses
bergantung pada objek tertentu. Penolakan role atau kepemilikan terjadi di server meskipun
tombol atau tautan tidak ditampilkan pada UI.

## 4. Policy dan alur moderasi

`TripPlanPolicy` membatasi baca, ubah, dan hapus rencana trip ke pemiliknya. Controller trip,
persiapan, readiness, mode mendaki, keselamatan, dan ringkasan meminta otorisasi sebelum
mengakses objek. Pembatasan status trip tetap dijalankan oleh siklus hidup trip, terpisah dari
policy kepemilikan.

Laporan komunitas masuk ke antrean moderasi. Moderator atau admin dapat menyetujui, menolak,
menandai, atau menghapus laporan. Setuju dan tolak memeriksa kembali status serta sidik isi
laporan yang dibaca sebelum tindakan, lalu mencatat tindakan dan audit. Laporan yang belum
disetujui tidak menjadi konten publik, dan laporan komunitas tidak menggantikan status resmi.

**Bukti kode:** `app/Policies/TripPlanPolicy.php`,
`app/Policies/TrailConditionReportPolicy.php`,
`app/Http/Controllers/Moderation/ModerationQueueController.php`,
`app/Providers/AppServiceProvider.php`, dan `app/Services/AuditLogService.php`.

## 5. UI menurut hak akses

`HandleInertiaRequests` membagikan identitas dan indikator moderator/admin untuk membentuk
navigasi. Nilai ini hanya mengatur apa yang ditampilkan. Sumber otorisasi tetap middleware,
policy, dan server; prop Inertia tidak menjadi bukti izin.

Pengguna menyelesaikan akses akun melalui halaman React di `resources/js/pages/Auth/`.
Antrean moderator berada di `resources/js/pages/Moderation/Queue.tsx`; halaman konsol admin
berada di `resources/js/pages/Admin/`. Pengujian HTTP memeriksa komponen dan props Inertia,
tetapi tidak membuktikan uji klik langsung di browser. Checklist manual dan statusnya ada di
[`03-test-checklist.md`](03-test-checklist.md).

## 6. Catatan kesesuaian domain

Brief memakai alur persetujuan booking. Memount tidak memiliki booking ruang atau persetujuan
rencana trip oleh admin. Padanan yang nyata adalah moderasi laporan kondisi komunitas.
Persetujuan laporan hanya mengubah status publikasi laporan, bukan status resmi jalur,
keamanan pendakian, atau kepemilikan trip.
