# Runbook Operasi Memount

Panduan insiden operasional untuk tim dan pemilik produk. Dokumen ini mendampingi
`docs/ARCHITECTURE.md` (peta sistem), `CLAUDE.md` bagian 3 dan bagian 5 (tata kelola dan paritas
basis data), serta `docs/PRD.md` (§93, §98, §99, §154).

Semua operasi produksi pada Supabase atau variabel lingkungan Vercel memerlukan izin eksplisit
pemilik sebelum dijalankan (R-019).

## 1. Gangguan prakiraan cuaca BMKG (`weather:refresh`)

**Gejala:** tugas `weather:refresh` di halaman `/admin/health` berstatus `GAGAL` atau `TERLAMBAT`,
atau halaman Cek kesiapan menampilkan lencana prakiraan menua maupun usang.

**Langkah penanganan:**

1. Buka `/admin/health` sebagai admin untuk membaca waktu jalan terakhir, kode keluar, dan ringkasan
   galat dari tabel `scheduled_task_runs`.
2. Periksa kode wilayah administrasi (`adm4`) gunung terkait di `/admin/mountains`. Kode yang kosong
   atau keliru membuat permintaan ke API publik BMKG dilewati atau ditolak.
3. Jangan mengisi prakiraan secara manual atau menyembunyikan umur data. Sesuai R-013, prakiraan
   yang melebihi ambang `hiking.conditions.weather_aging_hours` memang harus tampil sebagai usang
   agar pendaki mengetahui keterbatasan datanya.
4. Setelah gangguan jaringan atau kode `adm4` pulih, jalankan penyegaran terjadwal melalui endpoint
   cron resmi atau tunggu jadwal berikutnya; pastikan baris baru di `/admin/health` kembali `SEHAT`.

## 2. Pembaruan status gunung api PVMBG dan status resmi kawasan

**Gejala:** tugas `data:freshness-check` melaporkan status `kedaluwarsa` atau `lama_tidak_diverifikasi`,
atau ada pengumuman baru dari PVMBG maupun balai pengelola kawasan.

**Langkah penanganan:**

1. Kurator yang memiliki keanggotaan aktif pada yurisdiksi terkait membuka `/kurator` (Meja kurator).
2. Untuk perubahan tingkat aktivitas gunung api (Level I sampai Level IV), catat pembaruan melalui
   formulir status gunung api di `/kurator/gunung-api/baru` beserta rujukan laporan resmi PVMBG dan
   tautan atau arsip buktinya.
3. Untuk penutupan atau pembatasan jalur dan gunung oleh balai taman nasional atau pengelola resmi,
   catat status di `/kurator/status/baru`. Aturan terbit asimetris berlaku: penutupan atau pembatasan
   yang lebih ketat langsung terbit demi keselamatan, sedangkan pelonggaran ke `OPEN` membutuhkan
   verifikasi kurator kedua.
4. Bila masa berlaku pengumuman sudah habis dan belum ada surat perpanjangan resmi, biarkan sistem
   menurunkannya ke `UNKNOWN` (belum diketahui), bukan menebaknya sebagai terbuka (R-006, R-012).

## 3. Tugas penjadwal `TERLAMBAT` atau `GAGAL` dan webhook peringatan

**Gejala:** halaman `/admin/health` menampilkan status `TERLAMBAT` atau `GAGAL` pada salah satu tugas
di `SchedulerHealthService::TUGAS` (`weather:refresh`, `data:freshness-check`, `trips:kirim-pengingat`,
`model:prune`), atau kanal webhook menerima muatan `tugas_gagal` / `status_kedaluwarsa`.

**Langkah penanganan:**

1. Periksa pesan galat pada muatan webhook (`OPERATIONAL_ALERT_WEBHOOK_URL` bila diatur pemilik) atau
   log `stderr` aplikasi. Layanan `PeringatanOperasi` membungkus pengiriman webhook dalam batas waktu
   5 detik dan menangkap setiap galat jaringan agar gangguan kanal peringatan tidak pernah menjatuhkan
   tugas utamanya.
2. Pastikan variabel `PENJADWAL_HANYA_HARIAN` selaras dengan lingkungan: bernilai `true` pada Vercel
   Hobby (cron harian) dan `false` pada peladen dengan proses `schedule:work` per jam.
3. Untuk `model:prune`, pastikan `SHARE_LINK_PRUNE_AFTER_DAYS` hanya diisi bilangan hari positif
   setelah pemilik menetapkan kebijakan retensi tautan bagikan; selama kosong, perintah berjalan aman
   tanpa menghapus baris apa pun.

## 4. Vercel Cron dan `CRON_SECRET`

**Gejala:** endpoint `/api/cron/{tugas}` mengembalikan `401` atau tidak pernah tercatat di
`scheduled_task_runs`.

**Langkah penanganan:**

1. Pastikan nilai `CRON_SECRET` diatur pada Environment Variables proyek Vercel (tanpa menyalinnya ke
   kode, skrip, atau dokumen, sesuai `SECURITY.md`). Vercel Cron mengirimkan header
   `Authorization: Bearer <CRON_SECRET>` secara otomatis ke rute yang terdaftar di `vercel.json`.
2. Cocokkan daftar jadwal di `vercel.json` dengan `SchedulerHealthService::TUGAS`: setiap kunci
   tugas memakai slug bertanda hubung (`/api/cron/weather-refresh`, `/api/cron/data-freshness-check`,
   `/api/cron/trips-kirim-pengingat`, `/api/cron/model-prune`).
3. Bila satu jadwal sedang berjalan, kunci cache `cron:<tugas>` menahan panggilan ganda selama hingga
   300 detik dan mengembalikan status `dilewati`. Tunggu kunci selesai sebelum memicu ulang.

## 5. Verifikasi paritas migrasi PostgreSQL dan PostGIS

**Gejala:** rute yang bergantung pada kolom baru, RLS, atau fungsi spasial PostGIS mengembalikan `500`
di produksi meskipun suite SQLite lokal hijau (R-024).

**Langkah penanganan:**

1. Jangan menyimpulkan basis data produksi sinkron hanya dari hasil PHPUnit SQLite. Periksa status
   workflow GitHub Actions untuk job PostgreSQL (`Spatial`) dan riwayat migrasi lingkungan target.
2. Setelah mendapat izin eksplisit pemilik per migrasi (R-019, `database/README.md`), jalankan
   `php artisan migrate:status` pada lingkungan target untuk memastikan seluruh berkas di
   `database/migrations/` berstatus `Ran`.
3. Pastikan setiap tabel baru memiliki migrasi Row Level Security (`ENABLE ROW LEVEL SECURITY`) dan
   suite `tests/Spatial/` tetap lulus pada PostgreSQL ber-PostGIS sebelum menyatakan insiden selesai.

