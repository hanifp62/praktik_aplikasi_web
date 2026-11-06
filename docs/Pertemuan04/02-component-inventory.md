# 02 — Component Inventory MEMOUNT

## 1. Tujuan

Dokumen ini memetakan komponen Blade, view, route, dan interaksi yang ditemukan pada snapshot project MEMOUNT yang diberikan. Nama berkas dan endpoint ditulis berdasarkan source code; status “tersedia” tidak otomatis berarti kesesuaian visual telah tervalidasi.

## 2. Layout dan reusable Blade components

| Komponen | File | Jenis | Fungsi | Status/keterangan |
|---|---|---|---|---|
| `x-memount-layout` | `resources/views/components/memount-layout.blade.php` | Global layout | HTML document, viewport, title/description, pemuatan Vite CSS/JS, dan notifikasi session `notice` | Dipakai oleh sebagian alur hiking; beberapa screen lain memiliki layout/style page-specific |
| `x-bottom-nav` | `resources/views/components/bottom-nav.blade.php` | Global navigation | Navigasi lima item Beranda, Jelajah, Trip, Mendaki, Progres; menerima prop `active` | Mendaki diarahkan ke trip pertama yang bukan `CANCELLED`, atau ke daftar Trip bila belum ada trip |
| `x-field` | `resources/views/components/field.blade.php` | Form utility | Label, slot control, hint opsional, error dengan `role="alert"` dan `aria-live` | Komponen membungkus control yang diberikan melalui slot; bukan input lengkap dengan sendirinya |
| `x-flow-step` | `resources/views/components/flow-step.blade.php` | Progress utility | Menampilkan nomor/checkmark dan label langkah, state aktif/selesai | Digunakan dalam alur rekomendasi dan pembuatan trip |
| `x-status-badge` | `resources/views/components/status-badge.blade.php` | Status UI | Warna label untuk `OPEN`, `CLOSED`, `RESTRICTED`; status lain memakai gaya default | Tidak memvalidasi kebenaran sumber status, hanya presentasi |
| `x-trail-card` | `resources/views/components/trail-card.blade.php` | Domain card | Region, nama jalur/gunung, Route Fit, statistik jalur, medan, status resmi, alasan, tautan pilih/detail | Bergantung pada array `trail` yang disediakan controller/session |

### Komponen global CSS

`resources/css/app.css` mendefinisikan kelas reusable seperti `.mm-shell`, `.memount-input`, `.memount-button`, `.memount-button-secondary`, `.mm-bottom-nav`, `.mm-nav-item`, `.mm-page-header`, `.mm-back-btn`, `.mm-progress-bars`, `.mm-card`, serta kelas status/Route Fit. Sebagian halaman yang lebih baru (contohnya Beranda, Jelajah, Simpanan, Progres, Profil dan Ringkasan) juga memiliki kelas CSS page-specific dan style inline.

## 3. Pemetaan layar prototype ke implementasi

| Frame | Layar | Route utama | View utama | Data dan perilaku yang ditemukan |
|---|---|---|---|---|
| 00A | Opening | `GET /opening` | `auth/opening.blade.php` | Layar pembuka dengan tautan menuju halaman awal auth |
| 00A | Login/sign-in landing | `GET /start` | `auth/landing.blade.php` | Tautan ke Sign In dan Login |
| 00A | Sign In | `GET /signin`, `POST /signin` | `auth/signin.blade.php` | Validasi nama, email, password minimal 8 karakter dan konfirmasi; profil/auth disimpan ke session, bukan tabel user |
| 00A | Login | `GET /login`, `POST /login` | `auth/login.blade.php` | Validasi input email/password lalu mengisi session; bukan verifikasi credential terhadap database |
| 00 | Profil pendaki/onboarding | `GET /hiking-goal`, `POST /recommendations` | `hiking/goal.blade.php` | Target tanggal, region, durasi, tipe trip dan tantangan; validasi oleh `StoreHikingGoalRequest` |
| 01 | Beranda | `GET /beranda` | `beranda.blade.php` | Dashboard pendaki, shortcut ke jelajah, simpanan, bandingkan, profil dan progres; beberapa konten berupa presentasi contoh |
| 02 | Jelajah | `GET /jelajah` | `jelajah.blade.php` | Mengambil daftar gunung melalui `Mountain`; query menerima `mode`, `q`, `region`, dan `difficulty`; ada tab/list dan panel filter |
| 03 | Halaman gunung | `GET /gunung/{id}` | `gunung.blade.php` | Memuat gunung, jalur dan checkpoint melalui Eloquent; redirect kembali ke Jelajah jika data tidak ditemukan/gagal dibaca |
| 04 | Detail jalur | `GET /trails/{trailId}` | `hiking/trail.blade.php` | Menggunakan hasil rekomendasi pada session; membutuhkan rekomendasi yang sudah dimuat |
| 05 | Route Fit: kenapa? | `GET /recommendations/{trailId}/why` | `hiking/why.blade.php` | Menjelaskan `Why It Fits`, `What to Watch`, dan `Preparation Gap` dari rekomendasi session |
| 06 | Simpanan/catatan | `GET /simpanan`; `POST /simpanan`; `DELETE /simpanan/{trailId}`; `POST /simpanan/catatan` | `simpanan.blade.php` | Menambah/menghapus jalur dan menyimpan catatan ke session; menyimpan jalur tergantung hasil rekomendasi pada session |
| 07 | Bandingkan | `GET /bandingkan`; `POST /bandingkan`; `DELETE /bandingkan/{trailId}` | `bandingkan.blade.php` | Membandingkan maksimal tiga jalur; item yang tersedia berasal dari rekomendasi di session |
| 08 | Rencanakan trip | `GET /trips/create/{trailId}`, `POST /trips` | `hiking/create-trip.blade.php` | Nama trip, jalur, tanggal, jam, tipe trip dan catatan; `StoreTripRequest` menangani validasi; trip disimpan ke session |
| 08 / P06 | Daftar/detail trip | `GET /trips`, `GET /trips/{tripId}` | `hiking/trips-index.blade.php`, `hiking/trip-detail.blade.php` | Daftar dan detail trip berbasis data session; trip memakai UUID dan status awal `PLANNED` |
| 09 | Persiapan trip | `GET /trips/{tripId}/persiapan` | `hiking/trip-persiapan.blade.php` | Membaca trip dari session; tanpa trip yang cocok diarahkan ke onboarding |
| 10 | Cek kesiapan | `GET /trips/{tripId}/kesiapan`, `POST /trips/{tripId}/kesiapan` | `hiking/cek-kesiapan.blade.php` | Checklist disimpan per trip pada session |
| 11 | Mode mendaki | `GET /trips/{tripId}/mendaki` | `hiking/mendaki.blade.php` | UI mode lapangan per trip; ada tautan darurat `tel:119` dan jalur ke Cek Kesiapan/Ringkasan |
| 12 | Ringkasan + laporan kondisi | `GET /trips/{tripId}/ringkasan`, `POST /trips/{tripId}/laporan` | `hiking/ringkasan.blade.php` | Statistik/ringkasan mengambil trip dari session; laporan kondisi tervalidasi dan disimpan ke session; beberapa affordance berupa alert/placeholder |
| 13 | Progres | `GET /progres` | `progres.blade.php` | Membaca trip dan simpanan dari session; view berisi visualisasi progres/riwayat, termasuk data presentasi contoh |
| 14 | Profil | `GET /profil`, `POST /profil` | `profil.blade.php` | Profil dibaca/disimpan ke session; sebagian aksi privasi/perlengkapan/tampilan merupakan interaksi UI lokal/alert, bukan layanan backend penuh |
| 15 | Jelajah desktop | `GET /jelajah` dengan query mode terkait | `jelajah.blade.php` | Prototype memiliki frame desktop 1440 × 900; route tetap sama dan kesetaraan visual desktop belum dibuktikan hanya dari source |

## 4. API yang tersedia

| Method | Endpoint | Controller | Keterangan |
|---|---|---|---|
| GET | `/api/mountains` | `MountainController@index` | Daftar gunung |
| GET | `/api/mountains/{id}` | `MountainController@show` | Detail gunung |
| GET | `/api/trails` | `TrailController@index` | Daftar jalur |
| GET | `/api/trails/{id}` | `TrailController@show` | Detail jalur |
| GET | `/api/checkpoints` | `CheckpointController@index` | Daftar checkpoint |

Keberadaan route di atas dibuktikan oleh `routes/api.php`. Keberadaan route tidak dengan sendirinya membuktikan integrasi ke setiap halaman atau kondisi deployment.

## 5. Model data dan persistensi

| Model | Tabel domain | Relasi utama | Catatan |
|---|---|---|---|
| `Mountain` | `mountains` | `hasMany(Trail)` | Field yang dapat diisi mencakup nama, elevasi, region; migration juga menambahkan kolom geometry `location` |
| `Trail` | `trails` | `belongsTo(Mountain)`, `hasMany(Checkpoint)` | Menyimpan jarak, tanjakan, estimasi durasi, tuntutan teknis dan karakter medan; migration menambahkan `route_geometry` |
| `Checkpoint` | `checkpoints` | `belongsTo(Trail)` | Nama dan urutan pos; migration menambahkan `location` geometry |
| `User` | `users` | Model autentikasi Laravel tersedia | Auth UI yang saat ini diimplementasikan memakai session demo, bukan alur autentikasi Laravel yang memverifikasi model `User` |

Migration domain menjalankan perintah `CREATE EXTENSION IF NOT EXISTS postgis` dan menambahkan kolom geometry melalui SQL mentah. Lingkungan database yang menjalankan migration harus mendukung PostGIS.

**Data session yang ditemukan:** `hiking_goal`, `recommended_trails`, `trips`, `trip_success`, `simpanan`, `catatan`, `compared`, `checklist`, `laporan`, `user_profile`, dan `auth`. Berdasarkan controller yang diperiksa, entitas Trip, laporan kondisi, profil, daftar simpanan, bandingkan dan checklist belum memiliki model/migration domain tersendiri dalam snapshot ini.

## 6. Interaksi dan tingkat verifikasi

| Area | Interaksi yang ditemukan di source | Batasan yang perlu diketahui |
|---|---|---|
| Onboarding dan rencana trip | POST form, validasi request, inline error | Status test otomatis untuk alur utama tersedia di `HikingFlowTest` |
| Rekomendasi | Skor/penyaringan dari data jalur; fallback demo jika data tidak tersedia | Fallback harus ditandai sebagai data demo; status resmi default `UNKNOWN` |
| Simpanan dan bandingkan | POST/DELETE; session state | Bukan persistensi database permanen |
| Checklist kesiapan | POST `items[]` | Disimpan dalam session per trip |
| Laporan kondisi | POST kondisi, rating dan komentar; session | Upload foto/segment selector tampak sebagai affordance, tetapi view menunjukkan alert placeholder; rating yang dikirim berupa hidden input statis pada snapshot |
| Profil | POST nama/email; session | Beberapa action privasi, tema/bahasa, perlengkapan menggunakan JavaScript lokal/alert dan belum terlihat layanan persisten penuh |
| Login | POST dengan validasi field | Tidak membandingkan password dengan credential yang tersimpan; tidak boleh dianggap autentikasi produksi |
| Lupa password/Google | Link/aksi UI ditemukan di view Login | Belum dibuktikan terhubung ke reset password/OAuth provider |
| Progres | Chart/indikator di Blade | Tampilan memuat data contoh/fallback; bukan sumber GPS otomatis |

## 7. Kesimpulan inventory

Source memuat view untuk frame 00A–14 dan route utama yang mendukung alur pendakian. Tingkat implementasi tidak seragam: beberapa alur form memiliki validasi dan session state, sedangkan sejumlah affordance pada UI masih berupa alert, state lokal, atau data contoh. Kesimpulan ini berdasarkan pemeriksaan source; kesetaraan visual final perlu ditentukan dengan screenshot aplikasi aktual.
