# Serah-terima Konteks — Aplikasi Web Gunung

Dokumen ini ditulis supaya percakapan dapat dilanjutkan di terminal baru **tanpa mengulang
dari nol**. Isinya tujuan, kesepakatan, keadaan kode, dan semua keputusan yang sudah
diambil beserta alasannya.

Terakhir diperbarui: 21 September 2026.

---

## 1. Cara melanjutkan di terminal baru

Buka terminal baru di `E:\praktik_aplikasi_web`, jalankan `claude`, lalu tempelkan ini:

> Baca `HANDOVER.md` di root proyek ini dari awal sampai akhir sebelum melakukan apa pun.
> Itu konteks lengkap pekerjaan kita. Sesudah membacanya, konfirmasi ke saya apa yang kamu
> pahami tentang tujuan, kesepakatan, dan keadaan sekarang, lalu tunggu instruksi saya.
> Jangan mulai menulis kode sebelum saya setujui.

Dokumen ini plus `PRD.md` plus `docs/superpowers/specs/` cukup untuk memulihkan seluruh
konteks.

---

## 2. Tujuan dan ekspektasi pemilik produk

Disalin dari kata-katanya sendiri, bukan ditafsirkan:

> "aplikasi web gunung yang memadukan antara aplikasi web atau aplikasi yang popular dan
> sudah banyak user nya dengan strava dan traveloka, bagaimana user riset di masing-masing
> aplikasi nya dengan itu saya ingin mengcompare ke 3 itu di padukan dan menerapkan pada
> aplikasi gunung."

> "saya mengutamakan untuk pertama imk dan ui/ux"

> "impact dari interaksi manusia komputer, ui, dan ux di semua refrensi yang saya cantumkan
> dari hasil riset, tujuan, dan kesepakatan diatas itu sama dengan aplikasi web yang akan
> kita bangun."

Artinya: **bukan menyerupai rupa ketiga rujukan, melainkan mencapai dampak IMK/UI/UX yang
setara dengan mereka.**

Aspek sistem yang dituntut, di luar UI/UX: arsitektur dan skalabilitas, keamanan dan
kepatuhan, keandalan dan manajemen kegagalan, operasional dan observability, pengelolaan
data dan konsistensi, serta marketing.

Peran yang diminta: lead engineer 30 tahun, sekaligus UI/UX designer, product designer,
software engineer, dan manajer audit.

---

## 3. Kesepakatan yang mengikat

Ini aturan kerja yang diminta pemilik produk dan **tidak boleh dilanggar**:

1. **Git adalah penyerahan kepada klien.** Push dan merge hanya dilakukan ketika aplikasi
   sudah benar-benar sesuai tujuan. Commit lokal boleh; push tidak, sampai diizinkan.
2. **Selalu konfirmasi sebelum mengakses Supabase**, termasuk kuncinya. Kunci boleh
   dipakai, tetapi **tidak pernah ditaruh di skrip mana pun**.
3. **Riset mendalam dan brainstorming terlebih dahulu sebelum memutuskan.** Bukan menebak.
4. **Selalu testing dan crosscheck.** Buktikan dengan pengukuran sebelum melapor temuan.
5. **Terapkan semua skill yang terpasang**, bukan hanya yang relevan tetapi juga yang
   mendukung.
6. **Selalu evaluasi dan susun rencana problem-solving berikutnya.**
7. **Setiap recap wajib memuat persentase progres bermetode**, plus problem, solusi, yang
   masih kurang, dan rencana berikutnya.
8. **Belajar di setiap task.**

---

## 4. Dasar produk: PRD

`PRD.md`, 3365 baris, 128 bagian. Yang paling menentukan:

- **§7 Core Product Loop**: PROFILE → ROUTE FIT → PLAN → PREPARE → CHECK → HIKE → REPORT
  → IMPROVE. Aturannya tegas: *"Semua fitur utama harus memiliki hubungan dengan loop
  tersebut."*
- **§8 Diferensiator**: bukan banyak gunung, bukan GPS, bukan review, bukan checklist,
  melainkan **menghubungkan karakteristik pendaki dengan karakteristik jalur, lalu
  menjelaskan alasannya**.
- **§18 / BR-02**: MDPL hanya atribut deskriptif, **tidak pernah** penentu kesulitan.
  Gunung 2.000 mdpl bertanjakan 1.400 m lebih berat daripada 3.000 mdpl bertanjakan 700 m.
- **§89 Progressive disclosure**: Apa ini → Cocokkah untuk saya → Mengapa → Apa
  tantangannya → Apa yang disiapkan → Bagaimana kondisinya.
- **§90 Explainability**: pengguna paham alasannya tanpa membuka dokumentasi teknis.
- **§91 No false precision**: dilarang persentase dan skor; **boleh** menyebut pengukuran
  nyata (8 jam, 700 m). Label hanya: Cocok / Perlu persiapan / Kurang cocok.
- **§92**: keterangan resmi dan masukan komunitas harus terbedakan secara visual.
- **§96**: anggaran query tidak boleh tumbuh mengikuti jumlah data.
- **§87 / §88**: WCAG 2.2 AA dihitung bukan ditaksir; mobile-first, tidak meluber di 400px.
- **BR-09**: skor internal tidak pernah sampai ke antarmuka dalam bentuk apa pun.
- **§60**: model kepercayaan data adalah nilai inti produk.

---

## 5. Hasil riset tiga rujukan, dan sintesisnya

Riset dilakukan dengan pencarian web, bukan dari ingatan. Sumbernya tercantum di
`docs/superpowers/specs/2026-09-21-mesin-kecocokan-design.md`.

**AllTrails** — tujuan pengguna sebenarnya adalah **memperkirakan ongkos**: berapa waktu,
tenaga, dan sumber daya. Titik sakit terbesar: bolak-balik antar halaman jalur karena tidak
ada cara melihat pilihan berdampingan.

**Traveloka** — corongnya **lihat → simpan → bandingkan → pesan**, dan riset menemukan
masalah di ketiga tahap awal, dengan kalimat yang nyaris identik dengan AllTrails. Keunggulan
Traveloka atas Tiket.com ada pada **kekayaan filternya**, bukan kecantikannya.

**Strava** — segment adalah **perbandingan yang dipersempit** ke kelompok acuan yang cukup
kecil sehingga menang terasa mungkin. 70% pengguna menyebut membandingkan diri sebagai
pendorong utama.

**Sintesis: ketiganya bukan katalog, ketiganya mesin pembanding.** Dan §8 PRD — menghubungkan
pendaki dengan jalur lalu menjelaskannya — **juga sebuah perbandingan**. Tujuan produk ini
dan mekanik ketiga rujukan ternyata satu hal yang sama.

**ATM yang sudah diterapkan:**
- Dari AllTrails: perbandingan dibingkai sebagai ongkos versus kemampuan pembaca.
- Dari Traveloka: tahap "Pertimbangkan" (simpan), yang sebelumnya tidak ada sama sekali.
- Dari Strava: tangga kemajuan — **acuannya diri sendiri, bukan pendaki lain**. Papan
  peringkat kontribusi **ditolak** karena memberi hadiah pada jumlah merusak §60.

---

## 6. SELESAI — sembilan migrasi sudah dijalankan di Supabase

**Status: beres pada 21 September 2026, atas izin eksplisit pemilik produk.**

Kesembilan migrasi dijalankan dengan `php artisan migrate --force` terhadap Supabase.
Hasilnya diverifikasi: `migrate:status` menunjukkan **nol Pending**, seluruhnya `Ran`
pada batch [7]. Tabel `trail_considerations`, `mountain_follows`, dan `hike_track_points`
ada beserta indeks, kunci unik, dan foreign key-nya. Aplikasi dijalankan terhadap skema
baru dan halaman publiknya menjawab 200 tanpa satu pun galat di log.

Sebelum ini, bagian berikut adalah masalah terbesar proyek, dan dicatat sebagai riwayat
karena penyebabnya adalah kelas kesalahan yang mudah berulang:

**Pemilik produk tidak pernah melihat satu pun hasil kerja sesi itu.** Basis datanya
tertinggal sembilan migrasi, sementara seluruh 874 test berjalan di SQLite in-memory yang
migrasinya otomatis lengkap. Jadi test hijau dan aplikasi error pada saat yang sama, di dua
basis data yang berbeda.

Lebih buruk lagi, halaman Jelajahi **sebelumnya berfungsi** dan menjadi error setelah
Tugas 5 menambahkan fitur timbang ke sana, karena fitur itu menanyakan tabel yang belum ada
di produksi. Pekerjaan yang dimaksudkan memperbaiki justru merusak, dan suite tidak dapat
melihatnya.

**Pelajaran untuk sesi berikutnya:** suite hijau tidak membuktikan aplikasi berjalan di
lingkungan pemilik produk. Setiap kali sebuah tugas menambahkan tabel atau kolom, periksa
`migrate:status` terhadap basis data yang sebenarnya dipakai, bukan hanya menjalankan test.

Halaman dan tabel yang dulu terdampak:

| Halaman | Butuh tabel | Status |
|---|---|---|
| Jelajahi | `trail_considerations` | migrasi belum jalan |
| Pertimbangkan | `trail_considerations` | migrasi belum jalan |
| Kabar | `mountain_follows` | migrasi belum jalan |
| Progres | `hike_track_points` | migrasi belum jalan |

Halaman Jelajahi **sebelumnya berfungsi** dan menjadi error setelah Tugas 5 menambahkan
fitur timbang ke sana. Jadi pekerjaan terakhir memperburuk keadaan yang dilihat pemilik
produk, meskipun seluruh 874 test hijau — karena test berjalan di SQLite yang migrasinya
otomatis lengkap.

**Perbaikannya satu perintah, dan harus dijalankan pemilik produk sendiri** karena lapisan
izin Claude Code memblokir akses Supabase dari agen:

```
! php artisan migrate --force
```

Sembilan migrasi yang tertunda, semuanya **aditif** (tabel dan kolom baru, tidak ada drop):

```
2026_09_20_000450_create_usability_sessions_table
2026_09_20_000500_create_scheduled_task_runs_table
2026_09_20_000600_add_trail_snapshot_to_recommendation_results_table
2026_09_20_000700_add_elevation_profile_to_trails_table
2026_09_20_000800_create_hike_track_points_table
2026_09_20_000810_add_track_recording_preference
2026_09_20_000900_create_report_thanks_table
2026_09_20_001000_create_mountain_follows_table
2026_09_21_000100_create_trail_considerations_table
```

**Alternatif tanpa menyentuh produksi**, untuk sekadar melihat hasilnya:

```
! php artisan migrate --force --database=sqlite --env=local
```

atau jalankan dengan basis data SQLite lokal (`database/local.sqlite`, sudah di-gitignore).

---

## 7. Keadaan kode

- Cabang: `hardening`. **Belum pernah di-push.** `origin/main` masih berisi PRD saja.
- Commit di depan `main`: sekitar 149.
- Suite: **874 test, 863 lulus, 11 dilewati, 0 gagal**. Pint bersih. Build sukses.
- 11 yang dilewati butuh `SPATIAL_TEST_DSN` (uji PostGIS).

**Yang sudah dibangun sesi terakhir** (rencana `docs/superpowers/plans/2026-09-21-mesin-kecocokan.md`):

1. `TrailFitService` — menilai kecocokan sekumpulan jalur tanpa query tambahan
2. Halaman jelajah menyebut kecocokan tiap jalur beserta alasannya
3. Penjelasan kecocokan menyebar ke detail jalur dan halaman trip
4. "Pertimbangkan" — timbangan maks 5, lintas perangkat, penolakan dinyatakan
5. Bandingkan dicapai dari jelajah, dibingkai ongkos versus kemampuan
6. Tangga kemajuan di atas tanjakan, acuannya diri sendiri
7. Menu 9 kata benda jadi 5 permukaan: Jelajah, Pertimbangkan, Perjalanan, Progres, Kabar

**Enam cacat produksi yang ditemukan dan ditutup**, tidak satu pun berasal dari rencana:

1. Izin pendakian salah hitung tujuh jam setiap hari (bisa bilang izin masih terbuka
   padahal sudah tutup)
2. Dasbor berkata "Berangkat besok" untuk keberangkatan hari itu juga
3. Kesegaran laporan kondisi terlihat lebih baru daripada kenyataan
4. Kunci mesin `official_status_closed` bocor ke layar pengguna
5. Halaman perbandingan mati total (`DivisionByZeroError`) untuk jalur berjarak nol
6. Timbangan tidak dapat dicapai dari menunya sendiri

---

## 8. Keputusan yang sudah diambil dan tidak perlu dibahas ulang

- **Papan peringkat kontribusi ditolak** — memberi hadiah pada jumlah merusak §60.
- **Tangga kemajuan memakai tanjakan, bukan MDPL** — BR-02/§18.
- **Foto jalur tidak dijadikan wajah kartu** — foto berasal dari laporan komunitas yang
  membawa nama pelapornya; §82 dan §83 melarang menerbitkannya begitu saja.
- **Peta-dulu seperti AllTrails ditunda** — geometri jalur baru masuk lewat impor admin,
  jadi peta-dulu akan memamerkan kekosongan.
- **Mode gelap belum diputuskan** pemilik produk, tercatat terbuka di `DESIGN.md`.
- **Tautan perbandingan yang dibagikan bersifat read-only** — tidak pernah menulis ke
  timbangan siapa pun.
- **Halaman detail menyatakan jenis penilaiannya** ("Kecocokan dasar" / "untuk rencana
  ini") agar tidak ada label yang terbaca berbeda di dua halaman tanpa penjelasan.

Catatan lengkap seluruh 24 ruling beserta biaya bila keliru ada di
`.superpowers/sdd/2026-09-21-mesin-kecocokan/progress.md`.

---

## 9. Yang masih terbuka

**Menghalangi pemilik produk melihat hasil:**
- Sembilan migrasi belum jalan di Supabase (lihat bagian 6)

**Keterbatasan yang diketahui, dinyatakan bukan disembunyikan:**
- Kunci balapan timbangan tidak terbukti suite: `lockForUpdate()` nyata di PostgreSQL,
  no-op di SQLite
- Penjaga zona waktu masih dapat dielakkan lewat variabel perantara
- Ambang A4 spec (kecocokan hadir di ≥10 dari 12 layar) baru tercapai 6 dari 12

**Butuh manusia, alatnya sudah ada di sistem:**
- Uji pakai 5–8 pemula untuk enam UX validation task §113
- SUS ≥ 80 — `UsabilityStudyService` menuntut minimal 12 responden
- Penelusuran pembaca layar
- Scheduler produksi belum berjalan

---

## 10. Umpan balik terakhir pemilik produk, belum terjawab

> "hasilnya tetap sama. error di bagian jelajahi, pertimbangan, kabar ... saya tidak
> merasakan experience berbeda masih jauh dari tujuan dan ekspektasi saya. yang saya lihat
> masih crud sederhana."

Penyebab error sudah terdiagnosis (bagian 6). **Penilaian "masih CRUD sederhana" belum
dapat diuji secara jujur**, karena pemilik produk belum pernah melihat halaman yang
berfungsi.

Langkah berikutnya yang benar, berurutan:

1. Jalankan sembilan migrasi
2. Pemilik produk membuka aplikasinya dan menilai ulang
3. **Baru** setelah itu evaluasi UI/UX besar-besaran, dengan penilaian yang berdasar pada
   apa yang benar-benar terlihat, bukan pada halaman error

Melakukan riset UI/UX besar-besaran sebelum langkah 1 dan 2 berarti merancang untuk
sesuatu yang belum pernah dilihat siapa pun.
