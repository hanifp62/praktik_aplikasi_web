# Dokumentasi Pengumpulan — Praktik Aplikasi Web Pertemuan 04

**Project:** MEMOUNT — aplikasi perencanaan pendakian

## Tujuan

Folder ini mengumpulkan dokumentasi design tokens, component inventory, hasil pengujian, dan keputusan implementasi untuk project MEMOUNT. Dokumentasi disusun dari source code pada arsip project yang diberikan serta prototype utama Job 3.

## Daftar deliverable

| File | Isi |
|---|---|
| [`01-design-tokens.md`](01-design-tokens.md) | Token warna, typography, spacing, border/radius, iconography, responsive rules, dan gap prototype–implementasi. |
| [`02-component-inventory.md`](02-component-inventory.md) | Komponen Blade, route, view, API, model, data/session, serta catatan interaksi yang teramati. |
| `03-ui-screenshots.pdf` | **Dikerjakan manual oleh pemilik tugas. File PDF belum disertakan dalam paket ini.** Tambahkan ke folder ini setelah screenshot aplikasi aktual selesai diambil dan diperiksa. |
| [`04-test-checklist.md`](04-test-checklist.md) | Daftar test otomatis dan checklist manual, beserta batasan validasi dan hasil yang dilaporkan. |
| [`05-keputusan-implementasi.md`](05-keputusan-implementasi.md) | Alasan dan konsekuensi keputusan arsitektur, data, session, UI, auth demo, serta keterbatasan implementasi. |

## Sumber acuan

- `docs/job-3/06-prototype.html` — source of truth prototype yang digunakan untuk pemetaan layar dan token visual.
- Source Laravel: `routes/`, `app/Http/Controllers/`, `app/Http/Requests/`, `app/Models/`, `resources/css/`, `resources/views/`, `database/`, dan `tests/`.
- Log pengujian Windows yang diberikan bersama konteks project: `view:cache` PASS, `npm run build` PASS, dan gabungan feature test 14 passed / 62 assertions. Rincian dan batasan reproduksi ada di `04-test-checklist.md`.

## Cakupan layar

Dokumentasi memetakan Opening/autentikasi (00A), Onboarding (00), Beranda (01), Jelajah (02), Halaman Gunung (03), Detail Jalur (04), Route Fit (05), Simpanan (06), Bandingkan (07), Rencanakan Trip (08), Persiapan (09), Cek Kesiapan (10), Mode Mendaki (11), Ringkasan (12), Progres (13), dan Profil (14). Prototype juga memuat frame Jelajah desktop (15), yang dicatat sebagai cakupan tambahan.

## Cara melengkapi PDF screenshot manual

1. Jalankan aplikasi dari root repository sesuai konfigurasi lokal.
2. Ambil screenshot dari halaman aplikasi yang benar-benar berjalan, bukan dari prototype HTML.
3. Gunakan viewport yang sesuai: umumnya 390 × 844 px untuk frame mobile; frame desktop diuji terpisah bila diperlukan.
4. Untuk halaman panjang, gunakan full-page screenshot atau beberapa tangkapan yang tetap terbaca.
5. Beri setiap kelompok screenshot nomor/nama frame serta keterangan URL atau state.
6. Ekspor menjadi `03-ui-screenshots.pdf`, buka kembali PDF, lalu pastikan halaman tidak kosong, gambar tidak terpotong dan teks masih terbaca.
7. Simpan PDF di folder `Pertemuan04/` ini.

## Batasan yang perlu dibaca

- Sebagian data trip, profil, simpanan, checklist dan laporan disimpan pada session; ini belum setara dengan penyimpanan database permanen.
- Alur Login/Sign In pada snapshot ini adalah demo berbasis session, bukan autentikasi produksi.
- Beberapa kontrol seperti unggah foto, pemilihan segmen dan perubahan angka statistik belum terbukti melakukan operasi penuh.
- Ada perbedaan token global CSS dan nilai warna per-frame prototype. Kesamaan visual akhir harus diperiksa dari screenshot aktual.
- `03-ui-screenshots.pdf` belum ada dalam paket ini karena memang disiapkan manual.

## Identitas pengumpulan

Isi jika diminta oleh dosen:

- Nama: `[isi nama mahasiswa]`
- NIM: `[isi NIM]`
- Kelas: `[isi kelas]`
- Mata kuliah: Praktik Aplikasi Web
- Pertemuan: 04

## Status

**Dokumentasi Markdown selesai disusun dari snapshot source. Paket final pengumpulan belum lengkap sampai `03-ui-screenshots.pdf` dibuat manual dan ditambahkan ke folder ini.**
