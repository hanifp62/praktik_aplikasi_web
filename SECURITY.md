# Kebijakan Keamanan

## Melaporkan kerentanan

Jangan membuka isu, pull request, atau diskusi publik untuk kerentanan. Laporkan secara
privat lewat fitur GitHub private vulnerability reporting: buka tab **Security** di
repositori ini, pilih **Advisories**, lalu **Report a vulnerability**. Laporan hanya
terlihat oleh pengelola repositori.

Bila tombol itu belum muncul, fitur belum diaktifkan pemilik. Jangan menulis detail kerentanan
di tempat publik mana pun. Anggota tim menghubungi pemilik lewat saluran tim yang sudah ada;
pelapor dari luar tim membuka isu dengan formulir "Laporan bug" berjudul "[Keamanan] minta
kontak privat", mengisi setiap kolom teks dengan "-" dan memilih pilihan mana pun di kolom
pilihan, lalu pemilik membalas lewat saluran privat.

Sertakan bagian yang terdampak (rute, berkas, atau fitur), langkah memunculkannya, dampak
yang Anda amati, dan commit yang Anda uji. Jangan menyertakan data pribadi pengguna nyata,
dan jangan menguji terhadap basis data produksi atau akun orang lain; pakai lingkungan
lokal sesuai `README.md`.

## Cakupan

Termasuk: kode aplikasi di repositori ini, yaitu autentikasi dan otorisasi (termasuk akses
ke data milik pengguna lain), validasi input, unggahan foto, data lokasi, tautan bagikan,
konsol pengelola, service worker, dan konfigurasi CI.

Tidak termasuk: layanan pihak ketiga (Supabase, BMKG, penyedia ubin peta). Laporkan ke
penyedianya.

## Rahasia

- Kredensial hanya tinggal di `.env`, yang diabaikan git. Kredensial tidak pernah ditulis ke kode, skrip, test, dokumen, atau pesan commit, dan tidak dicetak ke terminal (`CLAUDE.md` R-019).
- `SecretsNotInRepoTest` memindai setiap berkas teks yang dilacak dan setiap berkas baru yang tidak diabaikan git; berkas biner dan berkas di atas 2 MB dilewati. Polanya: nilai `DB_PASSWORD` yang terisi, kunci JWT Supabase (awalan `eyJhbGciOi`), kunci rahasia dan kunci publishable Supabase (`sb_secret_`, `sb_publishable_`), URL `postgres://` atau `postgresql://` yang memuat kata sandi untuk host apa pun, URL `pgsql://` yang memuat kata sandi untuk host selain loopback (`127.0.0.1`, `localhost`, `[::1]`), dan token NgodingPakeAI (`ngpk_`). Kunci atau token bentuk lain tidak dikenali pemindai ini. Workflow `.github/workflows/security.yml` menjalankannya bersama `composer audit` dan `npm audit` pada push ke `main`, `develop`, dan `hardening`, pada pull request ke `main` dan `develop`, dan setiap Senin setelah workflow ini ada di cabang bawaan `main` (GitHub hanya menjalankan jadwal dari cabang bawaan).
- Kata sandi yang sengaja tertulis di repo hanya untuk lingkungan lokal dan uji: kontainer PostGIS sekali pakai di `docker-compose.yml` (hanya terikat ke 127.0.0.1) dan di `.github/workflows/test.yml` (runner CI sekali pakai), contoh DSN ke 127.0.0.1 di dokumentasi, dan akun seeder lokal yang disebut `README.md`. Seeder hanya boleh dijalankan ke basis data lokal, karena kata sandi akunnya tertulis di repo publik ini.
- Bila rahasia terlanjur ter-commit, anggap bocor. Pemilik memutar kredensialnya lebih dulu di dasbor penyedia; menghapus barisnya saja tidak cukup karena riwayat git menyimpannya. Menulis ulang riwayat butuh izin pemilik (`CLAUDE.md` R-018).

## Versi yang didukung

Belum ada rilis bernomor. Perbaikan keamanan dikerjakan di cabang kerja aktif (saat ini
`hardening`) dan masuk ke `main` setelah disetujui pemilik.
