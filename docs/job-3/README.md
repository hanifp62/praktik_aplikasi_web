# README --- Memount Prototype Job 3

## Personalized Mountain Planning & Readiness Platform

**Praktik Aplikasi Web --- Pertemuan 3 (Job 3)**

Memount merupakan aplikasi perencanaan pendakian yang membantu pengguna
menemukan jalur pendakian sesuai profil, tujuan perjalanan, dan
karakteristik trail. Prototype Job 3 berfokus pada proses utama pengguna
dalam memilih jalur hingga membuat rencana perjalanan.

------------------------------------------------------------------------

# 1. Tujuan Prototype

Prototype ini dibuat untuk menguji alur utama pengguna dalam:

1.  Menentukan kebutuhan pendakian melalui **Hiking Goal**.
2.  Mendapatkan rekomendasi jalur berdasarkan profil dan kebutuhan
    pengguna.
3.  Memahami alasan rekomendasi melalui fitur **Why This Route?**.
4.  Melihat detail karakteristik trail.
5.  Membuat dan menyimpan rencana perjalanan.

------------------------------------------------------------------------

# 2. Scope Prototype

Prototype Job 3 memiliki batasan alur:

**P01 Hiking Goal → P02 Recommendation → P03 Why This Route? → P04 Trail
Detail → P05 Create Trip → P06 Trip Detail / Success State**

Fitur yang tidak termasuk dalam prototype Job 3 tetapi masih menjadi
bagian pengembangan produk:

-   Preparation Plan
-   Readiness Check
-   Weather Detail
-   Trail Condition Report
-   Moderasi Admin
-   Hiking History
-   Hike Mode

------------------------------------------------------------------------

# 3. Struktur Repository

    Memount-Job3/
    │
    ├── 01-scope-canvas.pdf
    ├── 02-sitemap.pdf
    ├── 03-user-flow-pengguna.pdf
    ├── 04-user-flow-admin.pdf
    ├── 05-wireframe-desktop.pdf
    ├── 06-wireframe-mobile.pdf
    ├── 07-usability-walkthrough.pdf
    ├── 08-keputusan-desain.md
    ├── prototype/
    │   └── memount-prototype.html
    │
    └── README.md

------------------------------------------------------------------------

# 4. Dokumentasi Desain

## Scope Canvas

Dokumen ini menjelaskan tujuan utama pengguna, user story yang masuk
scope, batas fitur prototype, serta asumsi yang diuji.

Scope dipilih berdasarkan kebutuhan utama pengguna yaitu menemukan jalur
pendakian yang sesuai dengan kemampuan dan rencana perjalanan.

------------------------------------------------------------------------

## Sitemap

Sitemap digunakan untuk menggambarkan struktur informasi aplikasi
Memount.

Halaman utama prototype:

-   Hiking Goal
-   Recommendation
-   Why This Route?
-   Trail Detail
-   Create Trip
-   Trip Detail / Success State

------------------------------------------------------------------------

## User Flow

### User Flow Pengguna

User flow pengguna menggambarkan perjalanan pengguna mulai dari:

Login / profil tersimpan\
↓\
Mengisi Hiking Goal\
↓\
Mendapat Recommendation\
↓\
Melihat alasan rekomendasi\
↓\
Melihat detail trail\
↓\
Membuat Trip Plan\
↓\
Trip berhasil tersimpan

Flow juga mencakup beberapa kondisi alternatif:

-   Form belum lengkap
-   Tidak ada trail yang cocok
-   Rekomendasi gagal
-   Data trip belum lengkap

------------------------------------------------------------------------

### User Flow Admin

User Flow Admin menggambarkan rancangan alur pengelolaan laporan
komunitas sebagai pengembangan lanjutan produk.

Flow ini berada di luar implementasi prototype Job 3 dan tidak termasuk
dalam halaman yang diuji.

------------------------------------------------------------------------

# 5. Keputusan Desain

## Recommendation dan Route Fit

Fitur Recommendation dibuat agar pengguna tidak hanya mendapatkan daftar
jalur, tetapi juga memahami tingkat kecocokan jalur melalui label Route
Fit.

Kategori yang digunakan:

-   Cocok
-   Perlu Persiapan
-   Kurang Cocok

------------------------------------------------------------------------

## Why This Route?

Halaman ini dibuat untuk meningkatkan transparansi rekomendasi dengan
memberikan informasi:

-   Why It Fits
-   What to Watch
-   Preparation Gap

Dengan informasi tersebut, pengguna dapat memahami alasan suatu jalur
direkomendasikan.

------------------------------------------------------------------------

## Error State

Prototype menyediakan beberapa kondisi ketika proses tidak berjalan
sesuai harapan:

-   Data Hiking Goal belum lengkap
-   Tidak terdapat trail yang sesuai
-   Rekomendasi gagal
-   Form pembuatan trip belum lengkap

Tujuannya agar pengguna mengetahui tindakan yang harus dilakukan.

------------------------------------------------------------------------

# 6. Wireframe dan Prototype

Wireframe dibuat dalam dua versi:

-   Desktop
-   Mobile

Perancangan dilakukan untuk memastikan tampilan dapat digunakan pada
berbagai ukuran layar.

Prototype dibuat dalam bentuk file HTML interaktif yang mensimulasikan
alur klik dan perpindahan halaman dari awal hingga akhir proses
pembuatan trip.

Link atau file prototype dapat ditambahkan pada bagian berikut:

**Prototype Link / File:**

(Tambahkan link HTML atau repository prototype di sini)

------------------------------------------------------------------------

# 7. Usability Walkthrough

Usability walkthrough dilakukan untuk mengevaluasi kemudahan penggunaan
prototype.

Pengujian berfokus pada:

-   pencarian rekomendasi jalur
-   pemahaman informasi trail
-   pembuatan trip
-   persiapan pendakian

Hasil evaluasi menghasilkan beberapa perbaikan:

1.  Memperjelas alasan rekomendasi.
2.  Mengelompokkan informasi penting.
3.  Menambahkan indikator progress persiapan.

------------------------------------------------------------------------

# 8. Kesimpulan

Prototype Memount berhasil menggambarkan alur utama pengguna dalam
memilih jalur pendakian hingga membuat rencana perjalanan.

Perancangan dilakukan dengan mempertimbangkan kebutuhan pengguna,
struktur informasi, kondisi normal maupun kondisi gagal, serta hasil
evaluasi usability.
