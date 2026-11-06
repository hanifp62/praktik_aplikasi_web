# 03. Test Checklist

Dokumen ini memisahkan bukti dari kode, hasil eksekusi pada sesi ini, dan langkah manual yang
belum dijalankan. Tidak ada status PASS tanpa keluaran perintah atau pemeriksaan yang
mendukungnya.

## 1. Lingkungan dan hasil sesi

| Pemeriksaan | Hasil sesi 6 Nov 2026 | Bukti |
|---|---|---|
| PHP default awal | 8.3.33 | `php -v` sebelum runtime pengujian disiapkan |
| PHP pengujian | 8.5.12 | PHP 8.5 dipasang melalui Winget setelah pemilik mengizinkan |
| Composer | 2.9.2 | `composer -V` |
| Laravel | 13.31.0 | `php artisan --version`, `composer show laravel/framework --no-ansi` |
| Dependensi PHP | Terpasang dari lockfile, 125 paket | `composer install --no-interaction --prefer-dist` |
| Dependensi frontend | Terpasang dari lockfile, 103 paket | `npm ci --ignore-scripts` |
| Build frontend | LULUS | `npm run build` |
| Test aplikasi pada sesi ini | 88 lulus, 402 assertion | Empat kelompok test di bawah |
| Test peramban dan tangkapan layar | BELUM DIUJI | Belum ada browser run pada sesi ini |
| Migrasi dan Supabase | Tidak dilakukan | Tidak ada koneksi atau perintah database yang dijalankan |

Runtime PHP 8.5.12 dipakai karena PHP awal 8.3.33 tidak memenuhi syarat minimum dependency lock.
Tidak ada dependency manifest atau lockfile yang diubah. Test dijalankan dengan SQLite in-memory,
PHP ini sementara berisi ekstensi yang dibutuhkan, dan `APP_KEY` acak hanya di environment proses;
nilainya tidak disimpan atau dicatat.

`npm ci --ignore-scripts` melaporkan satu temuan berkeparahan tinggi dari audit registry. Temuan
tersebut tidak dianalisis atau diperbaiki dalam tugas dokumentasi ini; dependency tidak diubah.

## 2. Test otomatis yang relevan

Test berikut telah ada di repo. Semua perintah lulus pada sesi ini.

| Perintah | Cakupan | Hasil |
|---|---|---|
| `php artisan test --compact tests\Feature\Auth` | Login, registrasi, reset dan konfirmasi sandi, verifikasi surel | 30 lulus, 154 assertion |
| `php artisan test --compact tests\Feature\RouteAccessMatrixTest.php tests\Feature\AdminAccessTest.php` | Matriks route, admin, dan moderator | 20 lulus, 20 assertion |
| `php artisan test --compact tests\Feature\TripOwnershipTest.php` | Kepemilikan trip, IDOR, dan tamu | 8 lulus, 21 assertion |
| `php artisan test --compact tests\Feature\CommunityReportModerationTest.php tests\Feature\ModerationFlowTest.php` | Moderasi laporan, otorisasi, konsistensi, dan audit | 30 lulus, 207 assertion |
| **Jumlah** |  | **88 lulus, 402 assertion** |

Indeks folder dokumen juga diverifikasi lewat
`php artisan test --compact tests\Feature\TeamDocsIndexTest.php`: 10 lulus, 96 assertion.
Pemeriksaan kebersihan secret project lewat
`php artisan test --compact tests\Feature\SecretsNotInRepoTest.php`: 20 lulus, 35 assertion.

Perintah di atas dijalankan di PowerShell yang memilih PHP 8.5.12, PHP ini pengujian dengan
ekstensi SQLite, dan kunci aplikasi acak hanya di environment proses.

Test berjalan di harness lokal SQLite. Tidak ada `.env` yang diubah, akses Supabase, atau
migrasi yang dijalankan.

## 3. Pemeriksaan manual di browser

Semua baris di bawah berstatus **BELUM DIUJI** pada sesi ini. Lakukan pada lingkungan lokal
dengan akun uji yang disiapkan secara aman. Jangan menyalin kata sandi ke dokumen atau
tangkapan layar.

| ID | Langkah | Hasil yang diharapkan | Status |
|---|---|---|---|
| M01 | Buka halaman login dan kirim formulir kosong | Galat validasi terkait tampil dekat isian | BELUM DIUJI |
| M02 | Masuk dengan sandi salah | Pesan generik tampil, sesi tetap sebagai tamu | BELUM DIUJI |
| M03 | Daftar dengan data valid | Pengguna masuk dan diarahkan ke onboarding | BELUM DIUJI |
| M04 | Coba mengirim `role=admin` bersama formulir daftar | Akun tetap berperan pendaki | BELUM DIUJI |
| M05 | Buka dashboard sebagai tamu | Dialihkan ke login | BELUM DIUJI |
| M06 | Buka panel admin sebagai pendaki | Server menolak dengan 403 | BELUM DIUJI |
| M07 | Buka panel admin sebagai moderator | Server menolak dengan 403 | BELUM DIUJI |
| M08 | Buka antrean moderasi sebagai moderator dan admin | Antrean tampil untuk keduanya | BELUM DIUJI |
| M09 | Buka atau ubah trip milik pengguna lain dengan mengganti ID URL | Server menolak, data tidak berubah | BELUM DIUJI |
| M10 | Ajukan laporan komunitas sebagai pendaki | Laporan menunggu moderasi dan belum terlihat publik | BELUM DIUJI |
| M11 | Setujui laporan sebagai moderator | Laporan disetujui, tindakan dan audit tercatat | BELUM DIUJI |
| M12 | Tolak laporan dengan alasan | Laporan ditolak dan alasan tersedia bagi pelapor | BELUM DIUJI |
| M13 | Keluar, lalu coba kembali ke halaman privat | Sesi berakhir dan halaman privat meminta login | BELUM DIUJI |
| M14 | Ulangi navigasi dengan keyboard pada layar auth dan moderasi | Fokus terlihat dan kontrol utama dapat dioperasikan | BELUM DIUJI |
| M15 | Periksa lebar ponsel dan layar lebar | Formulir dan antrean tidak terpotong atau overflow | BELUM DIUJI |

## 4. Batas bukti

1. Assertion Inertia pada test feature membuktikan respons server dan props, bukan bahwa UI
   telah diklik di browser.
2. Suite test SQLite tidak membuktikan perilaku PostgreSQL/PostGIS (R-024). Tidak ada perubahan
   skema pada tugas dokumentasi ini.
3. Test akses ini tidak menggantikan uji keamanan manual yang diwajibkan sebelum rilis.
   Checklist ini tidak menyatakan seluruh gerbang keamanan rilis telah lulus.
4. Tangkapan layar belum disertakan. Buat hanya sesudah menjalankan alur pada browser dengan
   akun lokal nonrahasia, lalu catat ukuran viewport, langkah, dan hasil yang benar-benar
   terlihat.
