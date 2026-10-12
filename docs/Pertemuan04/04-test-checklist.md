# 04 — Test Checklist MEMOUNT

## 1. Tujuan

Checklist ini memisahkan hasil pengujian otomatis, pemeriksaan build/cache, dan validasi manual. Status di bawah tidak menyamakan “route/view tersedia” dengan “semua perilaku teruji”.

## 2. Rekap pengujian yang dilaporkan dari lingkungan Windows

Log Agent yang sebelumnya diberikan bersama project mencatat hasil berikut:

| Pemeriksaan | Hasil yang dilaporkan | Bukti/catatan |
|---|---|---|
| `php artisan view:cache --no-interaction` | **PASS** | Log Agent Windows: Blade templates berhasil dicache |
| `npm run build` | **PASS** | Log Agent Windows: Vite build selesai |
| `php artisan test --compact tests/Feature/HikingFlowTest.php` | **9 passed, 53 assertions** | Log Agent Windows yang diberikan pengguna |
| `php artisan test --compact tests/Feature/FrameSmokTest.php` | **5 passed, 9 assertions** | Log Agent Windows yang diberikan pengguna |
| Gabungan dua file test | **14 passed, 62 assertions** | Hasil gabungan yang dilaporkan Agent |

Angka di atas dicatat sebagai hasil dari log Windows yang diberikan pengguna, bukan klaim bahwa command tersebut berhasil dijalankan ulang di lingkungan penyusunan dokumen ini.

### Reproduksibilitas pada lingkungan penyusunan dokumen

Percobaan menjalankan ulang test/build dari arsip ZIP pada lingkungan ini terhambat oleh dependency lingkungan:

- PHPUnit tidak dapat berjalan karena extension PHP `dom`, `mbstring`, dan `xmlwriter` tidak tersedia.
- `php artisan view:cache` berhenti dengan `Class "DOMDocument" not found` pada renderer CLI.
- `npm run build` berhenti dengan `vite: Permission denied`; pemanggilan Vite melalui Node kemudian gagal karena native optional binding Rolldown untuk Linux tidak ada dalam `node_modules` arsip.

Hasil blokir tersebut tidak digunakan untuk menyatakan kode aplikasi gagal; itu berarti pengulangan test di lingkungan ini tidak berhasil diselesaikan. Untuk laporan eksekusi lokal terbaru, gunakan kembali command pada mesin Windows project dan simpan output terminalnya.

## 3. Checklist test otomatis yang tersedia

| ID | Kasus test | Tujuan/assertion pokok | Hasil pada log Windows | Status bukti |
|---|---|---|---|---|
| T01 | `test_hiking_goal_displays_beginner_focused_fields` | Halaman hiking goal tampil dengan judul/field yang diharapkan | PASS | Tercatat dalam hasil 9 test |
| T02 | `test_incomplete_hiking_goal_returns_inline_validation_errors` | Pengiriman form kosong mengembalikan error inline | PASS | Tercatat dalam hasil 9 test |
| T03 | `test_valid_hiking_goal_is_saved_before_recommendations_load` | Goal valid disimpan ke session dan redirect ke rekomendasi | PASS | Tercatat dalam hasil 9 test |
| T04 | `test_root_opens_the_hiking_goal` | `/` menampilkan halaman hiking goal | PASS | Tercatat dalam hasil 9 test |
| T05 | `test_database_failure_uses_structured_demo_recommendation` | Data demo terstruktur dipakai saat sumber DB gagal; status `UNKNOWN` | PASS | Tercatat dalam hasil 9 test |
| T06 | `test_no_match_shows_empty_state_instead_of_recommending_a_poor_match` | Hasil kosong ditampilkan saat tidak ada kecocokan; jalur buruk tidak direkomendasikan | PASS | Tercatat dalam hasil 9 test |
| T07 | `test_recommendation_can_be_reviewed_and_saved_through_the_full_flow` | Rekomendasi → alasan → detail → form rencana → simpan trip | PASS | Tercatat dalam hasil 9 test |
| T08 | `test_trip_plan_requires_all_fields_and_returns_inline_errors` | Form rencana trip kosong memiliki error inline | PASS | Tercatat dalam hasil 9 test |
| T09 | `test_trip_plan_is_saved_to_session_and_shows_success_state` | Trip tersimpan pada session, status `PLANNED`, status resmi `UNKNOWN` | PASS | Tercatat dalam hasil 9 test |
| T10 | `test_progres_renders_without_trips` | `/progres` dapat dirender tanpa trip | PASS | Tercatat dalam hasil 5 smoke test |
| T11 | `test_profil_renders` | `/profil` dapat dirender | PASS | Tercatat dalam hasil 5 smoke test |
| T12 | `test_ringkasan_redirects_without_trip` | Ringkasan mengalihkan ketika trip tidak ada dalam session | PASS | Tercatat dalam hasil 5 smoke test |
| T13 | `test_ringkasan_renders_with_trip` | Ringkasan merender data trip di session dan form laporan | PASS | Tercatat dalam hasil 5 smoke test |
| T14 | `test_profil_shows_session_name` | Nama dari session ditampilkan di Profil | PASS | Tercatat dalam hasil 5 smoke test |

**Interpretasi:** hasil ini menguji perilaku yang tercantum dalam assertion masing-masing test. Ini tidak mencakup seluruh interaksi visual, seluruh route, autentikasi produksi, unggah foto, GPS, atau kesamaan pixel dengan prototype.

## 4. Checklist pemeriksaan manual sebelum pengumpulan

Gunakan kolom hasil aktual setelah pengujian dilakukan di browser. Jangan mengisi PASS hanya berdasarkan keberadaan source.

| ID | Area | Langkah pemeriksaan | Hasil yang diharapkan | Status saat dokumentasi dibuat |
|---|---|---|---|---|
| M01 | Opening/auth | Buka `/opening`, `/start`, `/signin`, `/login` | Halaman terbuka dan tautan navigasi bekerja | NOT VERIFIED di browser pada proses penyusunan dokumen |
| M02 | Onboarding | Buka `/hiking-goal`; kirim form kosong lalu form valid | Error dan redirect/hasil sesuai alur | Ada test otomatis untuk kasus utama; visual manual belum dicatat di sini |
| M03 | Rekomendasi | Isi goal lalu buka hasil rekomendasi | Item, alasan kecocokan dan status resmi ditampilkan; fallback jelas diberi label | Ada cakupan test otomatis; visual manual perlu screenshot aktual |
| M04 | Detail dan Route Fit | Buka detail jalur dan halaman “kenapa cocok” dari rekomendasi | Informasi jalur dan tiga kelompok alasan terlihat | Sebagian diuji otomatis; belum berarti seluruh variasi UI teruji |
| M05 | Simpanan | Tambah/hapus jalur dan simpan catatan | Perubahan terlihat dalam session browser yang sama | NOT VERIFIED pada browser di proses ini |
| M06 | Bandingkan | Tambah jalur hingga batas tiga lalu hapus | Batas maksimum dan update daftar terlihat | NOT VERIFIED pada browser di proses ini |
| M07 | Create trip | Kirim form valid/tidak valid | Validasi, redirect dan detail trip sesuai | Ada test otomatis untuk alur utama |
| M08 | Persiapan & kesiapan | Akses trip valid; ubah checklist; refresh | Data checklist tersedia selama session masih berlaku | NOT VERIFIED dalam browser pada proses ini |
| M09 | Mode Mendaki | Buka mode mendaki dari trip | Aksi navigasi bekerja; tautan darurat berlabel jelas | NOT VERIFIED dalam browser pada proses ini |
| M10 | Ringkasan | Buka dengan trip valid; coba form laporan dan action Foto/Segmen/Ubah angka | Hanya aksi yang sungguh didukung yang dilaporkan berhasil | Smoke test merender ringkasan; beberapa action UI di source berupa placeholder |
| M11 | Progres | Buka kondisi kosong dan data contoh; periksa 12 bulan serta chart | Layout tetap dapat dibaca, state kosong jelas, chart tidak meluber | Smoke test merender halaman; pemeriksaan visual terbaru belum dicatat |
| M12 | Profil | Periksa tampilan, nama session, tema/bahasa, privasi, logout | Nilai default jelas; aksi lokal dibedakan dari backend | Ada smoke test render/nama; perilaku lainnya perlu verifikasi manual |
| M13 | Responsive | Uji viewport sekitar 390 × 844 px dan lebar desktop | Tidak ada overflow/elemen terpotong; frame desktop diperiksa terpisah | NOT VERIFIED pada browser dalam proses ini |
| M14 | Screenshot PDF | Ambil screenshot aplikasi aktual dan periksa hasil export PDF | Gambar terbaca, tidak terpotong, diberi label frame/state | **Dikerjakan manual oleh pemilik tugas; PDF belum disertakan** |

## 5. Pemeriksaan source dan risiko

- Test otomatis memakai SQLite in-memory sesuai `phpunit.xml`, sedangkan migration domain menggunakan PostGIS melalui raw SQL. Test otomatis tidak membuktikan migration PostGIS dapat berjalan di semua lingkungan.
- Trip, checklist, laporan, profil, simpanan dan bandingkan dikelola dengan session pada controller; session bukan penyimpanan database permanen.
- Sign In/Login adalah alur demo session pada `AuthController`, bukan verifikasi password pada model `User`.
- Di view Ringkasan, tombol Foto/Segmen/Ubah angka terlihat sebagai placeholder/alert dan nilai rating disiapkan sebagai hidden input. Jangan mencatatnya sebagai fitur upload/penilaian penuh tanpa test tambahan.
- Build/cache/test pada mesin Windows sebelumnya dilaporkan berhasil. Untuk bukti final pengumpulan, jalankan lagi perintah terkait pada environment yang digunakan dan simpan log terminal/screenshot bila dosen meminta bukti.

## 6. Perintah verifikasi yang disarankan

Jalankan dari root project pada mesin Windows yang sudah dikonfigurasi:

```powershell
php artisan view:cache --no-interaction
npm run build
php artisan test --compact tests/Feature/HikingFlowTest.php tests/Feature/FrameSmokTest.php
```

Jika executable PHP khusus diperlukan, gunakan path PHP 8.4 yang dikonfigurasi di mesin tersebut. Jangan mengganti status test menjadi PASS jika command gagal atau belum dijalankan.
