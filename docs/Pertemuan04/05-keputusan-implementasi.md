# 05 — Keputusan Implementasi MEMOUNT

## 1. Pendahuluan

Dokumen ini menjelaskan keputusan teknis yang dapat ditelusuri pada source code MEMOUNT, perbedaan yang terlihat antara prototype dan aplikasi, serta batasan yang belum boleh dianggap sebagai fitur penuh. Acuan visual adalah `docs/job-3/06-prototype.html` versi tanpa akhiran `(1)`.

## 2. Ringkasan arsitektur

| Aspek | Keputusan/implementasi yang ditemukan | Alasan atau konsekuensi |
|---|---|---|
| Framework backend | Laravel; constraint `php` `^8.3` dan `laravel/framework` `^13.17` pada `composer.json` | Routing, controller, validation, Blade dan session dikelola lewat Laravel |
| Rendering UI | Blade server-rendered | View menggabungkan markup, data controller/session, dan komponen Blade |
| CSS/build | `resources/css/app.css` + Tailwind CSS 4 + Vite | Utility CSS, custom layer komponen dan pipeline build frontend |
| Komponen | Reusable components untuk layout, navigasi, field, flow step, status badge, trail card | Mengurangi duplikasi pada bagian UI yang benar-benar digunakan berulang |
| Data domain | Eloquent `Mountain`, `Trail`, `Checkpoint` dan relasinya | Data gunung/jalur/checkpoint memiliki struktur database |
| Penyimpanan beberapa alur | Session untuk goal, rekomendasi, trip, profil, simpanan, bandingkan, checklist dan laporan | Memungkinkan demo interaksi tanpa membuat seluruh domain persistence; konsekuensinya data bukan rekam permanen lintas session |
| Status resmi jalur | Default `UNKNOWN` ketika nilai status tidak tersedia | Menghindari klaim jalur pasti terbuka tanpa sumber status |

## 3. Keputusan routing dan alur pengguna

### 3.1 Onboarding dan rekomendasi

`GET /hiking-goal` menampilkan form; `POST /recommendations` divalidasi oleh `StoreHikingGoalRequest` dan disimpan ke session `hiking_goal`; pengguna diarahkan ke `GET /recommendations`. Controller membentuk rekomendasi dari jalur database atau menggunakan `demoFallbackTrails()` apabila data jalur tidak tersedia.

**Konsekuensi:** alur dapat tetap memperlihatkan empty state atau data demo ketika database tidak tersedia. Data demo perlu dilabeli dan tidak boleh ditafsirkan sebagai verifikasi data lapangan.

### 3.2 Route Fit dan detail jalur

Halaman “kenapa cocok” dan detail jalur mengambil objek rekomendasi yang disimpan pada session. Ini membuat konteks rekomendasi dipakai lintas halaman, tetapi akses langsung tanpa memulai alur dapat mengarahkan pengguna kembali ke halaman rekomendasi.

### 3.3 Perencanaan trip

`StoreTripRequest` memvalidasi input rencana. Controller menghasilkan UUID, memasukkan informasi jalur ke data trip, memberi status awal `PLANNED`, lalu menyimpan trip ke `session('trips.{id}')`.

**Keputusan/kompromi:** dalam snapshot ini tidak ditemukan model Trip atau migration khusus trip. Trip karena itu bersifat session-backed, bukan penyimpanan database permanen.

### 3.4 Persiapan, checklist, ringkasan dan laporan

Controller memeriksa trip berdasarkan session. Checklist disimpan pada `checklist.{tripId}` dan laporan disimpan pada `laporan.{tripId}`. Jika trip yang diminta tidak ditemukan dalam session, beberapa endpoint mengalihkan pengguna ke onboarding.

## 4. Keputusan data dan fallback

| Area | Keputusan | Bukti/konsekuensi |
|---|---|---|
| Data gunung/jalur | Tabel `mountains`, `trails`, `checkpoints` dan Eloquent relationships tersedia | Seeder contoh berisi Gunung Merapi, Jalur Selo dan Basecamp/Pos 1/Puncak |
| Geometri | Migration menambah geometry Point/LineString dan membuat extension PostGIS | Infrastruktur database membutuhkan dukungan PostGIS saat migration dijalankan |
| Kegagalan sumber data rekomendasi | Controller menggunakan fallback demo terstruktur bila koleksi jalur kosong/gagal dibaca | Test mengharapkan label data demo dan status `UNKNOWN` |
| Status resmi | Nilai di luar `OPEN`, `CLOSED`, `RESTRICTED`, `UNKNOWN` atau nilai kosong dinormalisasi ke `UNKNOWN` oleh helper controller | Mengurangi risiko menyatakan status resmi yang tidak diketahui |
| Riwayat/progres | View memakai data session dan data presentasi yang disiapkan di view | Visual progres bukan hasil sensor GPS otomatis atau sumber pelacakan lapangan |

## 5. Keputusan UI dan komponen

1. **Shell mobile:** CSS global membatasi `.mm-shell` hingga 390 px untuk meniru frame mobile prototype. Frame 15 prototype adalah desain desktop yang terpisah; keduanya perlu diperiksa dengan viewport masing-masing.
2. **Navigasi:** bottom navigation global berisi lima item — Beranda, Jelajah, Trip, Mendaki, Progres. Profil diakses lewat avatar/tautan halaman, bukan item keenam.
3. **Status:** `x-status-badge` mengubah warna berdasarkan string status, sementara keputusan status berada di controller/data. Warna badge tidak membuktikan sumber data status resmi.
4. **Form:** `x-field` menyediakan label, hint dan error accessible; `x-flow-step` menunjukkan langkah workflow. Form yang tidak memakai component tersebut tetap mempunyai markup/validasinya sendiri.
5. **Design tokens:** CSS mengumpulkan sejumlah token global, tetapi beberapa view menggunakan style inline dan nilai warna berbeda dari token per-frame prototype. Penyamaan visual menyeluruh belum dapat dinyatakan hanya dari keberadaan token.

## 6. Keputusan autentikasi demo

`AuthController@signinStore` memvalidasi nama, email, password dan confirmation lalu menyimpan profil serta flag `auth` ke session. `loginStore` memvalidasi format email dan keberadaan password, lalu mengisi session dengan nama default `Pendaki`; source yang diperiksa tidak membandingkan password dengan credential database. Model `User` Laravel tersedia, tetapi alur UI tersebut tidak menggunakan verifikasi kredensial pada model.

**Kesimpulan:** auth saat ini harus dianggap simulasi untuk demonstrasi UI, bukan autentikasi produksi. Fitur lupa password dan Login dengan Google juga belum terbukti terhubung ke reset password/OAuth sungguhan.

## 7. Keputusan penyimpanan session

Session dipakai untuk menyimpan:

- `hiking_goal` dan `recommended_trails`;
- `trips` dan `trip_success`;
- `simpanan`, `catatan`, dan `compared`;
- `checklist.{tripId}` dan `laporan.{tripId}`;
- `user_profile` dan `auth`.

Keuntungan: alur demo tidak harus menambah tabel untuk setiap interaksi. Konsekuensi: data bergantung pada konfigurasi/session browser dan tidak layak diperlakukan sebagai penyimpanan jangka panjang lintas akun/perangkat.

## 8. Penyimpangan dan batasan implementasi

| Area | Implementasi yang ditemukan | Dampak/batasan |
|---|---|---|
| Design tokens | Prototype frame memakai `#E9E5DC`, `#1D2320`, `#234F32`; global CSS utama memakai `#f4f2ed`, `#171b18`, `#1f6f47` | Perlu perbandingan screenshot untuk menyimpulkan kesesuaian aktual; token `--color-sand` tidak digunakan untuk background utama body |
| Font | Prototype menetapkan Geist/Geist Mono; CSS app menyatakan font tersebut di daftar preferensi tetapi tidak menyertakan `@font-face` lokal yang sama | Font aktual bisa jatuh ke Inter/system sesuai ketersediaan mesin |
| Upload foto laporan | Tombol Foto ditemukan di Ringkasan dan source memakai placeholder/alert | Belum merupakan upload file ke storage |
| Memilih segmen | Kontrol segmen ditemukan; source menampilkan alert untuk memilih segmen | Belum terbukti sebagai dropdown pilihan operasional yang menyimpan nilai segmen |
| Ubah angka statistik | Tombol ditemukan; source menggunakan alert placeholder | Belum mengubah data statistik yang tersimpan |
| Rating laporan | Snapshot view mengirim nilai rating via hidden input statis | Tidak sama dengan penilaian interaktif 1–5 yang dinamis |
| Privasi/profil | Beberapa tombol privacy/tema/bahasa/perlengkapan memakai aksi JavaScript lokal/alert | Tidak boleh dianggap sebagai penghapusan data backend atau preferensi persisten tanpa implementasi tambahan |
| Progres | Sejumlah histori/angka dalam view merupakan data contoh/fallback | Tidak ada bukti bahwa angka tersebut dikumpulkan otomatis dari GPS atau sensor |
| Database test vs migration | PHPUnit dikonfigurasi menggunakan SQLite in-memory; migration domain memakai PostGIS | Perlu test integrasi database terpisah untuk memastikan migration dan query berjalan pada PostGIS |

## 9. Keputusan testing

Test feature yang tersedia berfokus pada onboarding, validasi, fallback rekomendasi, Route Fit/detail, pembuatan trip dan smoke test halaman Progres/Profil/Ringkasan. Berdasarkan log Agent Windows yang diberikan pengguna, hasil terakhir yang tercatat adalah **14 test passed dan 62 assertions**, serta `view:cache` dan build frontend dilaporkan berhasil.

Pengulangan command dari lingkungan penyusunan dokumen terblokir oleh extension PHP dan native binding Node yang tidak cocok dengan lingkungan tersebut. Karena itu, log pass sebelumnya dicatat sebagai sumber laporan eksekusi; belum diklaim sebagai pass yang berhasil direproduksi ulang di lingkungan ini.

## 10. Kesimpulan

MEMOUNT telah menghubungkan rangkaian layar melalui Blade, routing, validasi Laravel, Eloquent untuk data gunung/jalur/checkpoint, dan session untuk alur demo yang lebih luas. Batasan yang paling relevan untuk laporan adalah bahwa sejumlah alur belum disimpan permanen ke database; auth bukan auth produksi; beberapa aksi UI masih placeholder; dan token global belum seluruhnya identik dengan warna spesifik per-frame prototype. Perbedaan tersebut perlu dideskripsikan jujur dan tidak dianggap selesai hanya karena view dapat dirender.

## 11. Referensi source

- `docs/job-3/06-prototype.html`
- `routes/web.php` dan `routes/api.php`
- `app/Http/Controllers/AppController.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/HikingFlowController.php`
- `app/Http/Requests/StoreHikingGoalRequest.php`
- `app/Http/Requests/StoreTripRequest.php`
- `app/Models/Mountain.php`, `Trail.php`, `Checkpoint.php`, `User.php`
- `database/migrations/2026_09_18_085405_create_mountains_table.php`, `2026_09_18_085411_create_trails_table.php`, `2026_09_18_085416_create_checkpoints_table.php`
- `database/seeders/MountainSeeder.php`
- `resources/css/app.css` dan `resources/views/components/`
- `tests/Feature/HikingFlowTest.php` dan `tests/Feature/FrameSmokTest.php`
