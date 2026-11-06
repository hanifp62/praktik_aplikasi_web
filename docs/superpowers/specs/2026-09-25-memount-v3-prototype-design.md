# Desain Memount v3: prototype pemilik sebagai patokan desain dan arsitektur

**Tanggal:** 25 September 2026
**Status:** arah, pendekatan, dan empat keputusan disetujui pemilik dalam percakapan 25 September 2026
(jawaban verbatim di §1). Spec tertulis ini menunggu tinjauan pemilik sebelum rencana implementasi ditulis.
**Klasifikasi:** arsitektural (bahasa desain, kerangka navigasi, beberapa subsistem baru).
**Hubungan:** melanjutkan `2026-09-25-memount-v2-design.md` (DK-25 sampai DK-29 tetap berlaku). Bagian §6 spec v2
("Arah desain v2") digantikan spec ini bila keduanya berbeda. Aturan kanonis R-001 sampai R-024 dan BR-01 sampai
BR-16 tidak diubah.

---

## 1. Arahan pemilik (verbatim)

Prompt 25 September 2026, disertai berkas `E:\praktik_aplikasi_web\Memount Prototype.html`:

> "dan disini saya telah membuat sebuah prototype untuk app memount nya jadikan patokan untuk desaign.md dan ui/ux
> nya riset detail dan mendalam analisis imk nya kembangkan desain tersebut sesuai kan pula dengan logic, workflow,
> konsep, dan realita nya, dll. kembangkan desain dan architecture nya."

Arahan sebelumnya tetap berlaku (verbatim di spec v2 §1): lima tab Home, Explore, Trips, Track, Progress plus
Profile, Community sebagai lapisan lintas-halaman, repo rapi untuk tim, satu visi dan kesepakatan bersama, konteks
tahan-sesi.

Jawaban pemilik atas pertanyaan keputusan (25 September 2026):

| Pertanyaan | Jawaban pemilik |
|---|---|
| Pendekatan pengerjaan | "A. Irisan vertikal" |
| Data contoh lokal berlabel DATA CONTOH | "Ya, lokal saja" |
| Paket lapangan luring (mengubah kebijakan service worker) | "ya, riset mengenai fitur ini cari refrensi yang sudah ada yang menggunakan fitur yang serupa jika ada data resmi input buat fitur untuk menginput data resmi." |
| Sumber foto | "Foto terkurasi berlisensi" |
| Ikon (Lucide di prototype, ditandai antislop R-04) | "riset, compare dan combine. analisis kembangkan secara branding ui/ux desain imk dll pertimbangkan itu semua." |
| Huruf (Geist di prototype, ditandai antislop R-06) | "riset, analisis kembangkan secara branding ui/ux desain imk dll pertimbangkan itu semua." |

Dua jawaban terakhir mendelegasikan keputusan kepada riset (sesuai kesepakatan kerja: jawaban "riset" berarti
keputusan diambil dari bukti, lalu dicatat alasannya).

## 2. Pemahaman dan ukuran berhasil

**Hasil yang dituju.** Pendaki merasakan perjalanan utuh yang hidup dan jujur, seperti di prototype: setiap layar
tahu posisinya dalam siklus trip, menjelaskan alasannya, dan menyebut sumbernya. Tim dapat mengubah desain dari satu
sumber token dan satu pustaka komponen.

**Asumsi agen** (pemilik dapat mengoreksi):
- A7. Prototype menggantikan bagian Turunan `DESIGN.md` (palet, bentuk, komposisi, navigasi). Bagian Arah milik
  pemilik dan R-015 sampai R-017 tetap.
- A8. "Patokan" berarti pola, hierarki, bahasa visual, dan alur. Angka dan teks tidak disalin per piksel; semuanya
  berasal dari sistem, dan bila datanya belum ada tampil keadaan kosong bernama.
- A9. Hal di prototype yang gagal ukuran terhitung (kontras, isi terpotong, presisi palsu) dikoreksi tanpa mengubah
  rasanya, dan koreksinya dicatat (DK-31).

**Ukuran berhasil** (setiap butir dibuktikan, R-023):

| # | Ukuran | Bukti |
|---|---|---|
| S1 | Ke-16 layar prototype ada di aplikasi, ditambah keadaan yang dibutuhkan kenyataan (§6), dan setiap kontrol yang tampil berfungsi | Daftar klik-lewat per halaman (antislop R-35), test HTTP per aksi |
| S2 | Setiap angka, status, dan label berasal dari sistem; data yang tidak ada tampil sebagai keadaan kosong yang menyebut siapa yang ditunggu | Test props (`assertInertia`), peninjauan kode |
| S3 | Suite Unit dan Feature hijau; `npx tsc --noEmit` nol galat; build sukses; Pint bersih | Keluaran perintah |
| S4 | Kontras semua token dihitung dan dijaga test di tema terang, gelap, dan lapangan | `ColourContrastTest` |
| S5 | Tangkapan 390 px dan 1440 px tiap layar, terang dan gelap, disandingkan dengan gambar acuan prototype | `docs/desain/prototype/` dan folder tangkapan per gelombang |
| S6 | Anggaran kueri tiap halaman baru tidak tumbuh terhadap jumlah data | Test anggaran kueri |
| S7 | Delivery Gate antislop PASS dengan bukti per butir | Laporan gate di rekap akhir gelombang |
| S8 | `HANDOVER.md` §0 memuat keadaan nyata dan prompt lanjut | Isi berkas |

## 3. Temuan (FAKTA, dengan bukti)

### 3.1 Prototype

Berkas 5,3 MB, belum dilacak git, berupa bundel artifact bersarang: manifest aset gzip dan base64 dengan 16 halaman
iframe. Tiap halaman memakai runtime `dc-runtime` dengan React dan kelas `DCLogic`. Isi yang dibongkar: markup,
logika keadaan, font woff2, dan tiga foto JPG.

| Layar | Isi utama |
|---|---|
| 00 Onboarding | Tiga langkah: tingkat pengalaman; jalur terberat yang pernah didaki dan durasi terlama; jenis trip dan wilayah minat |
| 01 Beranda | Sapaan dan ringkasan; kartu perjalanan berikutnya (H-15, empat langkah, kesiapan); tenggat; kabar gunung yang diikuti (resmi dan komunitas terpisah); rekomendasi geser horizontal; tangga tanjakan |
| 02 Jelajah | Kalimat pencarian ditafsirkan menjadi chip yang dapat dilepas; lembar saringan; hitungan; Daftar/Peta; kartu dengan profil elevasi berskala sama dan "Kenapa?"; kartu "Belum bisa dinilai"; pemberitahuan jalur yang tersembunyi saringan; peta dengan pola garis per label |
| 03 Gunung | Status akses tingkat gunung dan status gunung api PVMBG terpisah; pilih jalur; izin; kondisi komunitas; provenans |
| 04 Detail jalur | Peta dan pos; empat angka; tantangan; kecocokan untuk rencana ini; profil elevasi dengan keterangan kalimat; daftar pos; kondisi resmi dan prakiraan BMKG area; laporan pendaki; yang perlu disiapkan; sumber di halaman ini; bar aksi |
| 05 Penilaian kecocokan | Label dan definisi; dinilai dari; lapis 1 syarat dasar; tujuh faktor berbobot kata; kenapa bukan Cocok; yang bisa dilakukan; versi aturan |
| 06 Simpanan | Catatan pribadi; pilih sampai 3 untuk banding; tautan bagikan hanya-baca 7 hari |
| 07 Bandingkan | Tanpa pemenang; sorot yang berbeda; profil skala sama; jadikan dasar rencana |
| 08 Rencanakan trip | Kecocokan berubah langsung menurut jenis trip; kalender rentang; jendela izin H-30 sampai H-3; terbitnya prakiraan BMKG; jam mulai; peserta; kuota belum diketahui; pengingat email H-3 dan H-1 |
| 09 Persiapan | Hitungan butir kritis; kelompok; tanda KRITIS; butir menunggu BMKG; daftar pengingat |
| 10 Cek kesiapan | Tiga hal yang diperiksa; sejak cek terakhir; pesan hari keberangkatan; tandai sudah ditinjau |
| 11 Mendaki | Pratinjau hari-H (tersimpan di perangkat, izin lokasi saat Mulai, rekam jejak mati); mode lapangan gelap: peta, pos berikutnya, sisa jarak, naik lagi, perkiraan, laporan segmen, Jeda dan Selesai |
| 12 Ringkasan dan laporan | Angka pendakian; dibanding diri sendiri; pencapaian; formulir laporan (tag, segmen, catatan, foto tanpa GPS); apa yang terbit |
| 13 Progres | Ringkasan tahun; grafik per pendakian (tanjakan atau jarak); target pribadi; pencapaian; riwayat termasuk yang batal; catat pendakian di luar trip |
| 14 Profil | Pengalaman yang diisi sendiri; preferensi; perlengkapan pribadi; tema dan bahasa; privasi; keluar |
| 15 Jelajah 1440 px | Header dengan navigasi segmen di tengah; tiga panel: saringan, daftar, peta; rentang tanjakan diketik |

### 3.2 Kecocokan prototype dengan logika nyata

- Tujuh faktor di layar 05 sama dengan `App\Enums\CompatibilityFactor`, termasuk urutannya. Bobot yang ditulis
  dengan kata sama dengan `defaultWeight()`: pengalaman 0,30 (besar); durasi, medan, teknis 0,15 (sedang);
  tanjakan dan navigasi 0,10, jenis trip 0,05 (kecil). Tidak ada skor yang tampil (BR-09, R-005).
- Definisi satu kalimat ketiga label sudah ada di `App\Enums\RouteFitLabel`; kalimat PERLU PERSIAPAN di prototype
  sama dengan kode.
- Skema sudah menampung sebagian besar data prototype: `official_statuses` (cakupan, waktu terbit, verifikasi,
  berlaku, kedaluwarsa), `permit_requirements` (tautan pemesanan, kuota, jendela pesan), `weather_snapshots` (kode
  adm4, area acuan, per tiga jam), `trail_condition_reports` (segmen, tag, foto, moderasi), `report_thanks`,
  `readiness_checks` (snapshot per cek, konfirmasi pra-berangkat), `recommendation_rules.engine_version`,
  `trails.elevation_loss_m`, `trip_plans.end_date` dan `participants_count`, `trip_reminders`,
  `trail_considerations.note`.
- **Koreksi 26 September 2026:** versi pertama butir di atas juga menyebut `personal_targets` dan
  `user_owned_items`. Itu keliru: kedua migrasinya hanya berisi `id` dan `timestamps`, dan
  `2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table` kosong (ditemukan saat menyusun rencana
  rinci W0). Skemanya diisi di W7 sebelum fiturnya dibangun (rencana induk, W7).
- Sebelas tag kondisi prototype sama dengan `App\Enums\ConditionTag`; hanya kata-katanya lebih pendek.
- Jalur yang menunggu data sudah punya penanganan sendiri (`TrailIndexController` mengirim daftar `menunggu`,
  `TrailDetailController` merender `Trails/Awaiting`).

### 3.3 Yang belum ada di sistem

Rute halaman gunung (hanya ada POST ikuti); halaman penilaian kecocokan per jalur; tautan bagikan (FR-23); rute
catatan simpanan (FR-22, test sudah ditulis); fitur target pribadi, catat pendakian manual, dan perlengkapan (skema
dan fitur belum ada; migrasinya baru rintisan, lihat koreksi §3.2); wilayah minat di preferensi; alasan pembatalan trip; tabel status gunung api; foto gunung
berlisensi; paket lapangan luring; indeks tab Trip sebagai ruang kerja; strip pendakian aktif lintas tab.

### 3.4 Analisis IMK prototype

**Kekuatan yang terbukti:**
- Aturan kanonis dijaga di hampir setiap layar: RESMI bergaris padat dan KOMUNITAS bergaris putus-putus biru
  (R-009, R-010); kartu "Belum bisa dinilai" dan "Status resmi: belum diketahui. Tidak dianggap buka." (R-006,
  R-007); "Status ini tidak berarti jalur aman" (R-012); "kami tidak menebaknya" untuk prakiraan (R-013); kesiapan
  sebagai hitungan "5 dari 8" (R-014); banding "tidak memilih pemenang" (R-016); Progres "tidak ada papan peringkat
  dan tidak ada catatan kecepatan" (R-015); status PVMBG terpisah dari status akses (keputusan D10).
- Heuristik Nielsen: status sistem terlihat (hitungan, kesegaran, "menunggu BMKG"); kecocokan dengan dunia nyata
  (pos, via, basecamp, WIB); kendali pengguna (chip tafsiran dapat dilepas, "Kembalikan tafsiran kalimat",
  "Tampilkan juga"); pencegahan galat (batas banding 3 dengan alasan, tombol kirim nonaktif sampai tag dipilih);
  pengenalan alih-alih ingatan (alasan merujuk riwayat pengguna sendiri: "Tanjakannya dekat dengan Prau via Patak
  Banteng (700 m) yang sudah Anda daki").
- Aksesibilitas sejak rancangan: peta `role="region"` dengan padanan teks; grafik dengan `figcaption`; rentang
  tanjakan diketik, bukan diseret (WCAG 2.5.7); garis jalur dibedakan pola dan legenda (1.4.1); status dibedakan
  bentuk; `aria-pressed`, `aria-expanded`, `aria-live` pada hitungan.

**Kontras dihitung** (skrip rumus WCAG 2.x, 50 pasangan warna prototype): 43 lolos, 7 gagal.

| Pasangan | Rasio | Syarat | Tindakan |
|---|---|---|---|
| Batas isian `#CFC8BA` di atas putih | 1,66:1 | 3:1 (1.4.11) | Ganti `#8C8579` (3,65:1 di putih, 3,30:1 di kanvas) |
| Kotak centang dan sakelar mati `#B9B2A4` | 2,11:1 | 3:1 | Pakai batas kontrol yang sama |
| Garis putus-putus komunitas `#7C9CCB` di `#F2F6FC` | 2,59:1 | 3:1 | Ganti `#5F82B8` (3,60:1) |
| Teks redup `#666D69` di sumur `#EEEAE1` | 4,42:1 | 4,5:1 | Ganti `#626965` (4,69:1) |
| Batas kartu `#E2DDD2` di kanvas | 1,22:1 | dekoratif | Tetap; kartu dikenali dari isinya, bukan garisnya |
| Garis kartu mode lapangan `#2D3731` di `#0F1311` | 1,52:1 | dekoratif | Tetap untuk kartu; tombol mendapat batas ≥3:1 |

Semua teks utama, label kecocokan, status, tombol, dan mode lapangan gelap lolos (tinta lapangan 16,31:1, tombol
lapangan 9,33:1).

**Cacat yang terukur di peramban** (prototype dirender di Chrome headless, 390 px):
- Kartu "Perjalanan berikutnya" di Beranda terpotong: tinggi tampil 305 px dari isi 468 px. Kotak kesiapan dan dua
  tombol aksinya ("Lanjutkan persiapan", "Cek") tidak terlihat. Penyebab: kanvas prototype bertinggi tetap, sehingga
  kartu flex menciut.
- Petak "Perkiraan ±1 j 20 m" di mode lapangan patah menjadi dua baris; sekaligus presisi palsu (riset 03 §4.5:
  waktu tempuh sebagai rentang bersumber).
- Tombol "Jadikan dasar rencana" di Bandingkan membuat jalur pertama tombol terisi dan dua lainnya tombol garis.
  Walau urutannya urutan pilihan pengguna, penekanan visual itu terbaca sebagai rekomendasi (R-016).
- Label tab 11 px; riset 01 D11 meminta minimal 12 px untuk pemakaian luar ruang.

**Yang tidak ada di prototype tetapi dibutuhkan kenyataan:** strip pendakian aktif lintas tab (riset D4); akurasi
posisi ± m dan umur posisi, sakelar layar tetap menyala (D5); urungkan (D19); skeleton, galat, luring; status Tutup;
Beranda untuk fase lain; indeks tab Trip; keadaan pertama kali; izin lokasi ditolak.

**Celah logika:** onboarding prototype tidak menanyakan pengalaman medan dan navigasi, padahal `CompatibilityScorer`
memakai `terrain_experience` dan `navigation_experience`. Tanpa keduanya faktor medan dan navigasi menilai rendah
setiap jalur yang menuntut.

### 3.5 Keadaan repo (runtime 25 September 2026)

- PHP 8.5.10, Composer 2.10.3, Laravel 13.31.0, React 19.3, `@inertiajs/react` 3.7.1, Tailwind 3, Vite 8, MapLibre
  4.7.1, TypeScript 5.9.
- `php artisan test --compact --testsuite=Unit,Feature`: **1.252 test, 1.231 lulus, 5 gagal, 15 error, 1 dilewati**
  (53,7 detik). `HANDOVER.md` §0 masih menulis 1.159 dan 1.158. Penyebab: pekerjaan paralel sesi sebelumnya yang
  terhenti.
  - `SavedTrailNoteTest`: rute `trails.saved.note.update` dan `trails.saved.note.destroy` tidak ada (12 error, 1
    gagal; versi pertama spec menulis 13 error, dikoreksi 26 September 2026 dari keluaran ulang).
  - `SavedRoutesTest`: komponen `components/trails/banding/KartuSimpanan.tsx` dan `AksiPilihan.tsx` tidak ada (3
    error).
  - `PrdReferenceIntegrityTest`: folder `docs/fitur/` tidak ada, padahal PRD §145 merujuk delapan spesifikasi.
  - `AuthenticationTest::test_navigation_menu_can_be_rendered`: masih mengharapkan label lima permukaan lama.
  - `NoDeadViewDataTest`: props tidak terpakai (`jumlahTersimpan`, `pesan` ke Compare; `batasBanding` ke Saved;
    `pengingat` ke Trips/Show).
  - `SchedulerHealthTest`: `trips:kirim-pengingat` tidak terdaftar di `SchedulerHealthService::TUGAS`.
- `PersonalTargetTest`, `OwnedEquipmentTest`, `ManualHikeRecordTest`, dan `PersonalDataWriteGuardTest` masing-masing
  berisi satu test yang membuka `/`, jadi lulusnya tidak membuktikan apa pun (diganti test nyata di W7).
- Cacat runtime yang tidak tertangkap suite (ditemukan saat menyusun rencana rinci W0, 26 September 2026): tombol
  "Hapus" di Simpanan mengimpor rute Wayfinder basi dan mengirim DELETE ke `/trails/compare/{id}` yang tidak ada;
  "Hapus dari perbandingan" memanggil `router.delete` tanpa alamat karena bentuk prop `hapus` di server dan klien
  berbeda. `npx tsc --noEmit` lulus hanya karena keluaran Wayfinder yang diabaikan git sudah basi.
- Token warna di `resources/css/app.css` masih skala bawaan Tailwind (emerald `#047857`, rose `#E11D48`, sky
  `#0EA5E9`). Navigasi `AppLayout.tsx` masih pola Breeze (bilah atas bergaris bawah, bilah bawah datar berlabel
  sekitar 10,7 px). Huruf dimuat dari `fonts.bunny.net`.
- Fondasi v2 yang sudah ada dan dipakai ulang: radius konsentris, durasi dan kurva pegas terhitung, kelas `.chrome`
  (solid bawaan, kaca bersyarat), `data-gerak="kurangi"`, penanganan `prefers-contrast`.
- `.env` memakai koneksi `pgsql` (Supabase). Aturan proyek menolak `php artisan migrate` dan `migrate -*`.
- Data lapangan di basis data pemilik hampir kosong (per 20 September 2026: 0 jalur bergeometri, 0 pos
  berkoordinat, status resmi UNKNOWN; `docs/ARCHITECTURE.md` "Yang belum ada").

### 3.6 Drift dokumen

`docs/ARCHITECTURE.md` masih memetakan komponen Livewire; `PRODUCT.md` masih memuat "layak berangkat" (bertentangan
R-014) dan "perbandingan maksimum lima" (bertentangan DK-05); `DESIGN.md` masih menyebut lima permukaan lama; label
keadaan kesiapan `ReadinessState::READY` adalah "Siap", padahal prototype sengaja memakai "Persiapan kritis
lengkap".

## 4. Keputusan

| ID | Keputusan | Oleh | Dasar |
|---|---|---|---|
| DK-30 | Prototype pemilik menjadi patokan desain. Bagian Turunan `DESIGN.md` ditulis ulang darinya; Arah pemilik dan R-015 sampai R-017 tetap | Pemilik | §1 |
| DK-31 | Koreksi terukur atas prototype: token kontras (§3.4), label tab 12 px, tinggi selalu mengikuti isi, waktu tempuh sebagai rentang bersumber, tombol "Jadikan dasar rencana" berbobot setara | Agen | Ukuran §3.4; R-005, R-016, WCAG 1.4.11 |
| DK-32 | Status akses memakai warna, bentuk, dan kata sekaligus: Buka (lingkaran hijau), Dibatasi (belah ketupat kuning), Tutup (persegi bata), Belum diketahui (lingkaran putus-putus abu), disertai kalimat R-012 di setiap tampilan utama. Usul riset D14 (Buka netral) tidak diambil karena bentuk sudah memenuhi 1.4.1 dan kalimatnya menjaga R-012 | Agen, mengikuti prototype | Prototype; R-012; WCAG 1.4.1 |
| DK-33 | Layar lebar (≥1024 px): header 68 px dengan navigasi segmen di tengah dan menu avatar. Usul rail kiri (riset D12) tidak diambil | Agen, mengikuti prototype | Prototype 15; spec v2 §5 |
| DK-34 | Pengerjaan irisan vertikal: stabilisasi, fondasi, kerangka, lalu klaster tab dikerjakan tuntas | Pemilik | §1 |
| DK-35 | Data contoh hanya di basis data SQLite lokal, berlabel DATA CONTOH di setiap halaman, ditolak otomatis bila koneksinya bukan SQLite lokal (§11) | Pemilik | §1; R-007 |
| DK-36 | Paket lapangan luring berisi data stabil saja; data dinamis hanya "terakhir diketahui per waktu"; ditambah fitur input data resmi untuk kurator (§10) | Pemilik | §1; riset `docs/riset/2026-09-25-luring/` |
| DK-37 | Foto gunung terkurasi berlisensi dengan atribusi wajib; tanpa foto tampil visual yang digambar dari data; foto komunitas tidak pernah menjadi hero (§8.7) | Pemilik | §1; R-017 |
| DK-38 | Tipografi: satu keluarga Plus Jakarta Sans 400 sampai 800 dengan angka tabular; Geist, Geist Mono, dan Newsreader dilepas; isi 16 px; lantai 12 px; label kecil huruf kalimat (§8.2) | Riset atas delegasi pemilik | `docs/riset/2026-09-25-merek/reports/` |
| DK-39 | Ikonografi hibrida: Phosphor untuk kendali umum dengan bobot per ukuran, ditambah 8 sampai 12 glyph domain dari tanda "Jalur Berpos"; tombol tanpa panah hiasan (§8.3, §8.8) | Riset atas delegasi pemilik | `docs/riset/2026-09-25-merek/reports/` |
| DK-40 | Kontrol yang belum berfungsi tidak ditampilkan (antislop R-26, C-2): pilihan bahasa disembunyikan sampai terjemahan Inggris ada; lonceng membuka daftar pengingat yang benar-benar ada; tombol bagikan hanya tampil setelah FR-23 dibangun | Agen | Keluhan pemilik "tidak semua fitur berfungsi" |
| DK-41 | Onboarding menurunkan pengalaman medan dan navigasi dari jalur terberat yang dipilih, lalu meminta konfirmasi. Jalur yang tidak ada di katalog diisi manual. Dilewati berarti label tampil "Belum dinilai" | Agen | §3.4 celah logika; R-006, BR-11 |
| DK-42 | Wilayah minat masuk preferensi pendaki (kolom baru) | Pemilik lewat prototype 00 dan 14 | Prototype |
| DK-43 | Status gunung api PVMBG disimpan di tabel sendiri, diisi kurator dari sumber resmi, tidak pernah dipetakan ke Buka atau Tutup, dan tampil hanya bila datanya ada | Keputusan D10 (23 Sep 2026) | Prototype 03, 04, 10 |
| DK-44 | Halaman gunung terautentikasi di `/mountains/{mountain:slug}` sebagai hub jalur (riset 02 E6). Halaman publik SEO `/gunung/{slug}` tetap mengikuti rencana D-B (bersamaan dengan `/en/`) | Agen | R-001; PRD §145.2 |
| DK-45 | Beranda berfase: `RingkasanDasbor` diperluas dari empat keadaan menjadi fase trip dengan satu aksi utama per fase (§6.1) | Agen | Riset 02 H1; prototype 01 |
| DK-46 | Gelombang 0 menutup 20 test merah sebelum redesain dimulai | Agen | §3.5; R-023 |
| DK-47 | Prototype dan gambar acuannya pindah dari root ke `docs/desain/prototype/` dan dilacak git sebagai rujukan tim | Agen | Kerapian repo (arahan pemilik) |
| DK-48 | Label keadaan kesiapan mengikuti prototype: "Persiapan kritis lengkap" menggantikan "Siap"; "Masih perlu persiapan"; "Tidak disarankan dengan rencana ini" menggantikan "Tidak direkomendasikan" | Agen, mengikuti prototype | R-014; prototype 09 dan 10 |
| DK-49 | Mode lapangan mengikuti tema pengguna (terang, gelap, ikuti sistem) dengan sakelar sekali ketuk di header. Tampilan gelap prototype menjadi tema gelap lapangan. Info kritis ≥7:1 di kedua tema | Agen | Riset 03 §4.2 (terang tidak kalah di bawah matahari); prototype 11 |
| DK-50 | Alasan pembatalan trip (cuaca, status resmi, kondisi tubuh, logistik, lainnya) dicatat opsional supaya Progres dapat menulis "Dibatalkan · cuaca" | Agen, mengikuti prototype 13 | PRODUCT.md "tidak pernah menghukum keputusan yang benar" |

Label tab berbahasa Indonesia tetap asumsi A1 spec v2: Beranda, Jelajah, Trip, Mendaki, Progres; Profil dari
avatar.

## 5. Kerangka aplikasi dan navigasi

### 5.1 Ponsel (<1024 px)

- **Tanpa bilah atas global.** Setiap halaman punya header sendiri:
  - akar tab: judul halaman (h1) dan aksi kontekstual di kanan, ditambah tombol avatar 44 px ke Profil yang sama di
    setiap akar tab (konsistensi; prototype meletakkannya di Beranda dan Progres);
  - halaman turunan: tombol kembali bulat 44 px ke induk logisnya (bukan hanya riwayat peramban), judul atau konteks,
    dan aksi seperti Bagikan.
- **Bilah tab mengambang** berbentuk pil: lima tab, tinggi minimal 52 px, label 12 px, ikon 22 px. Tab aktif: latar
  lembut merek, teks merek, tebal 700, dan `aria-current="page"`. Material `.chrome` (solid bawaan, kaca bersyarat).
  Memperhitungkan `env(safe-area-inset-bottom)`.
- **Halaman tugas dengan bar aksi tetap menyembunyikan bilah tab**, mengikuti prototype: detail jalur (Simpan,
  Banding, Rencanakan trip), penilaian kecocokan (Ke jalur, Lanjutkan persiapan), rencanakan trip (ringkasan dan
  Simpan rencana). Tombol kembali selalu ada.
- **Strip pendakian aktif**: bila ada trip berstatus berlangsung, strip 48 px tampil di atas bilah tab di semua tab
  kecuali mode lapangan: nama jalur, pos berikutnya, waktu berjalan, "Buka". Mengetuknya kembali ke mode lapangan.
  Tombol kembali di mode lapangan mengecilkan pendakian ke strip ini, tidak mengakhirinya.
- **Lonceng** di header Beranda membuka lembar "Pengingat" berisi pengingat trip terjadwal dan tenggat izin. Tidak
  ada notifikasi dorong sampai keputusan H2 (kanal notifikasi dan privasi) diambil pemilik.

### 5.2 Layar lebar (≥1024 px)

Header 68 px: lockup di kiri; navigasi segmen lima tab di tengah (ikon dan label 14 px); di kanan tautan Simpanan
dengan hitungan dan menu avatar (Profil; Moderasi dan Admin sesuai peran; Keluar). Isi memakai panel sesuai
kebutuhan: Jelajah tiga panel (saringan 300 px, daftar 520 px, peta sisa); detail jalur dua kolom (isi utama dan
kolom kanan lengket berisi kecocokan dan aksi); ruang kerja trip dua kolom (langkah dan isi).

### 5.3 Peta rute

| Tab | Halaman | Rute | Keadaan |
|---|---|---|---|
| Beranda | Beranda berfase | `/dashboard` | Ada, diperluas |
| Beranda | Kabar lengkap | `/kabar` | Ada |
| Jelajah | Daftar dan peta | `/trails` | Ada |
| Jelajah | Detail jalur | `/trails/{trail:slug}` | Ada |
| Jelajah | Penilaian kecocokan | `/trails/{trail:slug}/kecocokan` (opsional `?trip=`) | Baru |
| Jelajah | Gunung | `/mountains/{mountain:slug}` | Baru |
| Jelajah | Simpanan | `/trails/saved` | Ada; catatan FR-22 dilengkapi |
| Trip | Indeks trip | `/trips` | Ada, dirancang ulang |
| Trip | Bandingkan | `/trails/compare` | Ada |
| Trip | Rencanakan | `/trips/create` | Ada |
| Trip | Ruang kerja dan persiapan | `/trips/{trip}`, `/trips/{trip}/preparation` | Ada |
| Trip | Cek kesiapan | `/trips/{trip}/readiness` | Ada |
| Mendaki | Pintu tab dan pratinjau hari-H | `/mendaki` | Ada |
| Mendaki | Mode lapangan | `/trips/{trip}/hike` | Ada |
| Progres | Hub progres | `/progres` | Ada |
| Progres | Ringkasan dan laporan | `/history/{trip}` | Ada; formulir laporan masuk ke sini |
| Profil | Profil | `/profile`, `/onboarding` | Ada |
| Publik | Tautan bagikan banding | `/b/{token}` | Baru (FR-23) |

## 6. Layar

Setiap layar dirancang untuk keadaan ideal, pertama kali, kosong karena saringan, data belum ada, memuat, galat, dan
luring (antislop R-27). Yang tidak berlaku ditandai.

### 6.1 Beranda (prototype 01)

Fase dihitung server (`RingkasanDasbor`) dari profil, trip, dan riwayat:

| Fase | Syarat | Isi utama | Satu aksi utama |
|---|---|---|---|
| B0 | Profil belum diisi | Kartu "Kenali pengalaman Anda" dan alasannya; Jelajah tetap dapat dibuka dengan label "Belum dinilai" | "Isi profil pendaki" |
| B1 | Profil ada, tanpa trip mendatang | Tiga rekomendasi dan alasannya; kabar gunung yang diikuti; tangga bila ada riwayat | "Rencanakan trip" |
| B2 | Trip mendatang | Kartu perjalanan berikutnya: H-n, jenis trip, tanggal, peserta, empat langkah, kotak kesiapan (hitungan dan butir yang belum dikonfirmasi), tenggat | Menurut fase trip, lihat di bawah |
| B3 | Trip sedang berlangsung | "Anda sedang mendaki" dengan pos berikutnya | "Buka mode lapangan" |
| B4 | Trip selesai ≤7 hari, belum melapor | Ringkasan singkat dan ajakan melapor | "Bagikan laporan kondisi" |

Aksi utama fase B2: izin wajib belum dikonfirmasi dan jendela terbuka → "Pesan di sistem resmi" (tautan keluar);
jendela belum dibuka → "Ingatkan saat dibuka" (pengingat); persiapan kritis belum lengkap → "Lanjutkan persiapan";
H-3 sampai H-1 → "Cek kesiapan"; hari-H → "Mulai pendakian".

Tenggat terdekat: jendela izin tutup (dari `permit_requirements`), tanggal prakiraan BMKG untuk tanggal trip mulai
terbit (tanggal trip dikurangi tiga hari), dan pengingat terjadwal. Waktu memakai zona gunung dan penandanya.

Rekomendasi "Jalur lain untuk Anda": paling banyak tiga dari `TrailFitService`, tanpa jalur yang sedang direncanakan
dan tanpa jalur yang datanya belum lengkap (R-008), masing-masing dengan label, profil elevasi, dan satu alasan dari
`RecommendationExplanationService`. Sapaan waktu ("Selamat sore") dihitung di klien dari jam perangkat.

Koreksi: tinggi kartu mengikuti isinya (§3.4).

### 6.2 Jelajah (prototype 02 dan 15)

- **Pencarian kalimat** (FR-27): tafsiran tampil sebagai chip yang dapat dilepas di bawah "Kalimat Anda dibaca
  sebagai:"; bila semua dilepas muncul "Kembalikan tafsiran kalimat".
- **Saringan** (lembar bawah di ponsel, panel kiri di layar lebar): kecocokan dasar (tiga label dengan hitungan),
  jenis trip, tuntutan teknis "Hanya rendah" dengan catatan "yang belum diketahui tidak dianggap rendah", status resmi
  dengan catatan "jalur berstatus Tutup tidak pernah muncul sebagai rekomendasi", rentang tanjakan diketik. Tombol
  "Tampilkan N jalur" menghitung langsung.
- **Kartu jalur**: label kecocokan (ikon dan kata), tombol simpan 44 px dengan urungkan, nama ke detail, gunung dan
  wilayah ke halaman gunung, tiga angka (jarak, tanjakan, naik), profil elevasi dengan skala vertikal yang sama di
  semua kartu, satu alasan, status resmi dengan sumber, "Kenapa?" yang membuka "Mengapa sesuai" dan "Yang perlu
  diperhatikan" serta tautan "Lihat penilaian lengkap".
- **Jalur menunggu data**: kartu bergaris putus-putus "Belum bisa dinilai" tanpa angka, tidak dihitung, tidak pernah
  direkomendasikan, menyebut yang ditunggu ("Menunggu kurator Memount").
- **Tersembunyi saringan**: "N jalur disembunyikan saringan ...: rinciannya" dengan "Tampilkan juga".
- **Peta** (MapLibre, jalur bergeometri saja): garis per label dibedakan pola (utuh, putus panjang, titik-titik) dan
  legenda; jalur terpilih lebih tebal; chip pemilih jalur; kartu pratinjau "Lihat detail jalur"; kalimat berapa
  jalur tidak digambar karena belum bergeometri; atribusi peta.
- **Keadaan**: pertama kali tanpa profil (label "Belum dinilai" dan ajakan mengisi profil); tersaring habis ("Tidak
  ada jalur dengan saringan ini" dan saringan mana yang dapat dilepas); memuat (skeleton kartu); galat peta (kalimat
  jujur, daftar tetap tersedia).

### 6.3 Gunung (prototype 03, baru)

Foto berlisensi dengan atribusi (DK-37) atau visual data; tombol kembali dan bagikan; ikuti kabar (rute yang ada);
tinggi puncak dengan kalimat "Ketinggian hanya keterangan..." (BR-02); status akses tingkat gunung dan kalimat "Buka
di tingkat gunung tidak berarti semua jalur buka" (R-012, kaskade §42); kartu status gunung api PVMBG bila ada
(DK-43); daftar jalur dengan label dan status; izin (pengelola, wajib, jendela pesan, kuota atau "belum ada sumber
terverifikasi", tautan sistem resmi, "Memount menautkan sistem resmi dan tidak menjual izin"); kondisi terbaru dari
pendaki (lapisan komunitas); catatan provenans (sumber koordinat, elevasi, foto).

### 6.4 Detail jalur (prototype 04)

Urutan pengungkapan PRD §89. Isi: peta jalur dan pos (bila tanpa geometri: kalimat menunggu data dan daftar pos
tetap tampil); remah ke gunung; empat angka sekali jalan naik; "Apa tantangannya?" (fisik, teknis, navigasi dengan
tiga ruas dan kata, medan, lingkungan); "Cocokkah untuk saya?" (kecocokan dasar, atau "untuk rencana ini" bila ada
trip; tiga daftar: mengapa sesuai, yang perlu diperhatikan, celah persiapan; tautan ketujuh faktor; kalimat "Dinilai
dengan aturan tertulis, bukan AI, dan tanpa skor angka..."); profil elevasi dengan ruas tercuram diarsir dan
keterangan kalimat; pos (elevasi, km, perkiraan waktu dari awal, jenis pos); kondisi sekarang (status resmi dan
kesegaran, PVMBG, prakiraan area BMKG dengan area acuan dan waktu ambil, catatan tanggal terbit prakiraan untuk trip
pengguna); laporan pendaki termoderasi dengan terima kasih dan catatan "Kesegaran dihitung dari tanggal pendakian";
yang perlu disiapkan; sumber di halaman ini dengan urutan kepercayaan; bar aksi tetap.

### 6.5 Penilaian kecocokan (prototype 05, baru)

Label dan definisi satu kalimat dari `RouteFitLabel`; alasan tanpa skor; "Dinilai dari" (profil yang diisi sendiri
dan rencana trip, masing-masing dengan "Ubah"); lapis 1 syarat dasar dan kalimat "Bila satu saja gagal..."; lapis 2
dan 3: tujuh faktor diurut menurut bobot, setiap faktor dengan keadaan (Sesuai, Perlu perhatian, Belum diketahui),
bobot dalam kata (besar ≥0,25; sedang ≥0,12; kecil <0,12, dibaca dari `recommendation_rules`), dan rincian "Jalur
ini" lawan "Anda"; "Kenapa bukan Cocok?" dari penjelasan server; "Yang bisa Anda lakukan" (butir persiapan,
pendamping yang pernah lewat, banding); catatan versi aturan dan waktu penilaian; BR-11.

### 6.6 Simpanan (prototype 06)

Simpan tanpa batas; pilih sampai 3 untuk banding (DK-05); catatan pribadi ≤500 karakter "Hanya Anda" (FR-22, test
sudah ada); baki banding mengambang ("Dipilih N dari 3", pesan batas, "Bandingkan N jalur" atau "Pilih minimal 2
jalur"); bagikan (FR-23) di §9.3. Pertama kali: "Belum ada jalur tersimpan..." dan tautan ke Jelajah.

### 6.7 Bandingkan (prototype 07)

Kolom menurut urutan pilihan pengguna; baris: kecocokan dasar dan alasan singkat, jarak, tanjakan (bilah skala sama),
durasi, medan, teknis, navigasi, area kemah, air, status resmi dan waktunya, laporan terbaru (warna komunitas), izin;
sakelar "Sorot yang berbeda"; profil elevasi skala sama dengan keterangan kalimat; "Jadikan dasar rencana" berbobot
setara untuk setiap jalur (DK-31); bagikan.

### 6.8 Rencanakan trip (prototype 08)

Jalur terpilih dan "Ganti"; kecocokan untuk rencana ini dihitung ulang server setiap jenis trip atau tanggal berubah
(muat ulang parsial Inertia, bukan skor di klien); jenis trip (Tektok 1 hari, Camping 2 hari, Multi-hari 3 hari);
kalender rentang; jendela izin (belum dibuka, terbuka sampai, sudah tutup) dari `permit_requirements`; kalimat
terbitnya prakiraan BMKG; jam mulai dalam zona gunung; peserta 1 sampai 12; kuota belum terverifikasi; nama; catatan;
sakelar pengingat email H-3 dan H-1; ringkasan tetap dan "Simpan rencana". Jalur berstatus Tutup tetap dapat
direncanakan untuk tanggal mendatang, dengan peringatan status saat ini.

### 6.9 Indeks trip dan ruang kerja (tidak ada di prototype)

`/trips`: kartu utama trip terdekat (fase dan aksi berikutnya), lalu kelompok Berlangsung, Mendatang, Selesai (ke
Progres). Kosong: "Belum ada trip. Mulai dari jalur yang Anda simpan atau dari Jelajah." dan "Rencanakan trip".
Ruang kerja trip memakai empat langkah prototype (Jalur, Rencana, Persiapan, Cek) dari `TripSteps`.

### 6.10 Persiapan (prototype 09)

Header trip (jenis, H-n, nama, tanggal, jam, peserta, "Ubah"); langkah; keadaan kesiapan (N dari M butir kritis,
ruas per butir, butir yang belum dikonfirmasi, "Butir yang belum dicentang bukan berarti tidak Anda miliki");
kelompok `PreparationCategory` dengan tanda KRITIS, keterangan, tautan resmi; butir menunggu ("Menunggu BMKG: terbit
mulai ..."); centang optimis dengan urungkan; daftar pengingat; "Lanjut ke cek kesiapan". Perlengkapan pribadi
mengisi awal daftar tetapi tetap dikonfirmasi per trip.

### 6.11 Cek kesiapan (prototype 10)

Keadaan (DK-48) dan kalimat R-014; tiga hal: kecocokan untuk rencana, persiapan (hitungan dan butir yang kurang
dengan aksinya), kondisi terkini (status resmi dan kesegaran, gunung api, prakiraan untuk tanggal trip atau "Belum
terbit", laporan komunitas terbaru); "Sejak cek terakhir" dari perbandingan snapshot `readiness_checks`; pesan hari
keberangkatan (pastikan status di pos registrasi; status lama tidak ditampilkan); aksi "Selesaikan N butir" atau
"Saya sudah meninjau semuanya" (`pre_departure_confirmed`) dengan waktu tercatat.

### 6.12 Mendaki (prototype 11)

**Pintu tab** `/mendaki`: pendakian berlangsung langsung membuka mode lapangan (perilaku yang ada). Tanpa itu:
pratinjau hari-H untuk trip terdekat yang dapat dimulai (keadaan paket luring §10, penjelasan izin lokasi, rekam
jejak mati sebagai bawaan) dan daftar trip lain; "Mulai pendakian" 66 px.

**Mode lapangan** `/trips/{trip}/hike`: header (kecilkan ke strip, nama jalur, waktu berjalan atau dijeda, sakelar
tema); peta (garis yang sudah dilalui, sisa jalur, pos, posisi dengan lingkaran akurasi, tombol 48 px); pos
berikutnya (nama 30 px); tiga petak: sisa jarak (menyusuri jalur bila geometri ada, "garis lurus" bila tidak;
dibulatkan ke 50 m di bawah 1 km dan 0,1 km di atasnya), naik lagi (dari profil elevasi, bukan ketinggian GPS),
perkiraan (rentang bersumber atau "belum ada data waktu tempuh"); akurasi dan umur posisi ("±15 m · 30 detik lalu",
peringatan bila >50 m); laporan komunitas untuk segmen ini; sakelar layar tetap menyala (mati bawaan); penanda luring
dan antrean sinkron; Jeda/Lanjut dan Selesai 60 px. Tiba di pos: otomatis bila jarak ditambah akurasi ≤75 m, selain
itu bertanya "Sudah tiba di Pos 3?". Pos berikutnya dihitung di perangkat dari data yang sudah dimuat.

### 6.13 Ringkasan dan laporan (prototype 12)

Varian selesai dan varian dibatalkan (DK-50). Angka (jarak pulang-pergi, tanjakan, waktu bergerak, titik tertinggi)
dari rekaman bila ada, dari data jalur bila tidak, dengan "Ubah angka"; dibanding pendakian sendiri (tangga
tanjakan, selisih dari tertinggi sebelumnya, "tidak ada pembanding dengan pendaki lain"); pencapaian pribadi yang
diturunkan dari riwayat; BR-11; formulir laporan (tag, segmen, catatan, foto dengan GPS dibuang, apa yang terbit dan
tidak terbit, tombol kirim aktif setelah tag dipilih); keadaan terkirim menunggu moderasi dengan "Ubah sebelum
dimoderasi"; "Lihat progres".

### 6.14 Progres (prototype 13)

Ringkasan tahun (jumlah pendakian selesai dan batal, total tanjakan, total jarak); grafik per pendakian dengan
pilihan Tanjakan atau Jarak, batal digambar putus-putus, keterangan kalimat; target pribadi (FR-25) tanpa streak dan
tanpa teguran; pencapaian; riwayat dengan status dan status laporan; "Catat pendakian di luar trip" (FR-25).

### 6.15 Profil (prototype 14)

Identitas (foto unggahan atau inisial, nama, tingkat "diisi sendiri", bergabung); pengalaman dan "Ubah"; preferensi
(jenis trip, durasi, wilayah); perlengkapan pribadi; tampilan (tema, gerak, efek transparan; bahasa disembunyikan
sampai DK-40 terpenuhi); privasi (rekam jejak mati bawaan, lokasi hanya di Mendaki, hapus semua jejak, kebijakan data
pribadi, hapus akun); pintu konsol sesuai peran; keluar.

### 6.16 Onboarding (prototype 00)

Langkah 1 tingkat pengalaman dengan deskripsi; langkah 2 jalur terberat (cari di katalog; pilihan menampilkan nilai
turunan untuk dikonfirmasi: tanjakan, durasi, medan, navigasi; "Tidak ada di daftar" membuka isian manual; "Belum
pernah mendaki") dan durasi terlama; langkah 3 jenis trip dan wilayah minat. Akhir: akibat langsung ("Dengan jawaban
ini, N jalur berlabel Cocok") lalu Jelajah. "Nanti saja" menuju Jelajah dengan label "Belum dinilai".

## 7. Tata bahasa kepercayaan dan data

| Unsur | Aturan |
|---|---|
| Label kecocokan | Hanya COCOK, PERLU PERSIAPAN, KURANG COCOK (R-004), selalu ikon dan kata. "Belum dinilai" adalah keadaan UNKNOWN, bukan label keempat |
| Status akses | DK-32, selalu dengan sumber, cakupan, dan waktu verifikasi; lencana kesegaran (Terkini, Perlu ditinjau, Kedaluwarsa) dari `DataFreshnessService` |
| Sumber | RESMI: garis padat 1,5 px merek, ikon perisai, "RESMI · nama lembaga". KOMUNITAS: garis putus-putus 1,5 px komunitas, ikon orang, "KOMUNITAS · lolos moderasi". Ahli bersertifikat dan interpretasi sistem disebut di buku sumber halaman |
| Waktu | Zona gunung dengan penanda WIB, WITA, WIT; relatif dan mutlak; kesegaran laporan dari tanggal pendakian |
| Angka | Format Indonesia (9,7 km; 1.650 m); durasi sebagai rentang (7–8 j); tanpa persen untuk kecocokan dan kesiapan; hitungan ("5 dari 8") |
| Grafik | Skala sama saat membandingkan; keterangan kalimat; ruas tercuram diarsir; tanpa animasi saat muat |
| Data contoh | Lencana DATA CONTOH di setiap halaman bila mode demo aktif (§11) |

## 8. Bahasa desain v3 (isi `DESIGN.md` bagian Turunan)

### 8.1 Palet dan token

Token menyebut peran; nilai tinggal di `resources/css/app.css`; tidak ada warna mentah di komponen
(`ColourContrastTest` sudah menegakkan).

| Token (peran) | Terang | Catatan |
|---|---|---|
| `canvas` | `#F6F3EC` | Kertas hangat |
| `surface` | `#FFFFFF` | Kartu, lembar, bilah |
| `surface-sunken` | `#EEEAE1` | Sumur, kontrol segmen |
| `border-subtle` | `#E2DDD2` | Garis kartu (dekoratif) |
| `control-border` | `#8C8579` | Batas kontrol, kotak centang, sakelar mati (koreksi DK-31) |
| `text-primary` | `#1D2320` | 14,43:1 di kanvas |
| `text-secondary` | `#4E5551` | 6,91:1 di kanvas |
| `text-muted` | `#626965` | Koreksi DK-31; 4,69:1 di sumur |
| `brand-700` (isian tombol) | `#234F32` | Putih di atasnya 9,39:1 |
| `brand-800` (tekan) | `#16361F` | |
| `brand-ink` (tautan, garis grafik) | `#234F32` | |
| `brand-100` (lembut) | `#E4EEE6` | Tab aktif, chip aktif |
| `fit-cocok` | `#DDEEE2` / `#1E5E35` | 6,43:1 |
| `fit-perlu` | `#FBEBCF` / `#7A4E0B` | 6,12:1 |
| `fit-kurang` | `#F7DFDA` / `#8E2F22` | 6,40:1 |
| `fit-belum` | `#ECE9E3` / `#4E5551` | 6,32:1 |
| `status-buka` / `dibatasi` / `tutup` / `belum` | `#2F7A48` / `#B7791F` / `#8E2F22` / `#8A8F8B` | Non-teks ≥3:1 di putih |
| `warn-50` (kotak peringatan) | `#FBF1DE` | |
| `community-50` / `community-500` / `community-900` | `#F2F6FC` / `#5F82B8` / `#24508C` | Garis 3,60:1 (koreksi); teks 7,44:1 |
| `accent-pos` | `#E8B04B` | Satu aksen: cincin logo, pos berkemah, tujuan; fokus di tema lapangan gelap. 1,76:1 di kanvas, jadi tidak pernah membawa arti sendirian: penanda pos berkemah bergaris gelap dan berlabel jenis |

**Kanvas.** `#F6F3EC` hanya berselisih 2/255 dari jangkar klaster "krem hangat" pada skill `frontend-design`
Anthropic. Ia dipertahankan karena pilihan pemilik dan alasannya tertulis: kertas peta dan buku catatan lapangan,
permukaan yang menahan garis rute dan kontur. Risiko keseragaman ditangani dengan tidak memasangkannya dengan huruf
serif tampilan (Newsreader dilepas) dan dengan memikulkan keunikan pada motif dan tata bahasa data (§8.9).

Tema gelap aplikasi diturunkan dari palet lapangan prototype (kanvas `#0F1311`, permukaan `#1B221E`, tinta
`#F2EFE8`, tinta kedua `#B7BFB9`, merek sebagai latar depan `#8FD3A0`, isian tombol `#8FD3A0` dengan teks `#0F2418`,
komunitas `#9DBBE6` di `#18222F`, peringatan `#F0C27A`); nilai lengkap dihitung di gelombang 1 dan dikunci
`ColourContrastTest` untuk kedua blok gelap.

### 8.2 Tipografi (DK-38)

Dasar: laporan `docs/riset/2026-09-25-merek/reports/Tipografi dan ikon merek Memount.md`. Prototype memakai Geist
dan Geist Mono; pengukuran atas bundelnya menemukan 163 simpul teks monospace, 182 untaian bertitik tengah, 24 label
kapital 11 px, panah pada lima label aksi, dan 45,6% deklarasi ukuran huruf bernilai 12 px atau kurang. Empat ciri
itu tercantum langsung di klaster "tampilan AI" skill `frontend-design` Anthropic (September 2026), dan Geist adalah
huruf bawaan `create-next-app`.

- **Satu keluarga: Plus Jakarta Sans variable, bobot 400 sampai 800**, untuk antarmuka, judul, dan angka. Alasan
  tertulis: dirancang Tokotype (Indonesia) untuk identitas +Jakarta, memiliki angka tabular, glyph ↑, dan bobot 800
  yang dibutuhkan skala judul prototype. Kelangkaan bukan alasannya (peringkat 39 Google Fonts, dipakai Gaia GPS);
  keunikan merek dipikul motif dan tata bahasa data (§8.9). Geist, Geist Mono, dan Newsreader dilepas. Huruf mono
  hanya tumpukan sistem untuk kode alfanumerik (misalnya nomor dokumen resmi), tidak untuk ukuran.
- **Angka:** `font-variant-numeric: tabular-nums` dari keluarga yang sama untuk setiap jarak, tanjakan, durasi,
  tanggal, jam, dan hitungan. Lebar digitnya 0,600 em, sama dengan sel Geist Mono, sehingga kolom tetap rata seperti
  di prototype, sedangkan baris "9,7 km · ↑1.650 m · 7–8 j" menyusut dari 68,6% ke 49,9% lebar isi 360 px.
  Pemformatan lewat `Intl` dengan lokal `id-ID` (koma desimal, titik ribuan, jam "15.06").
- **Skala** (prototype, dengan lantai keterbacaan): tampilan 30/800/-0,025em; judul halaman 26 sampai 28/800; judul
  kartu utama 23/800; judul bagian 18/700; subjudul 17/700; **isi 16** dengan tinggi baris 1,5 (naik dari 15 karena
  sudut visual tinggi-x Plus Jakarta Sans di 14 sampai 15 px berada di bawah rentang baca lancar); isi kecil 14; meta
  13; **lantai 12 px** untuk teks apa pun, termasuk label tab dan label peta.
- **Label kecil** memakai huruf kalimat dengan tebal 600 sampai 700 dan ukuran 12 sampai 13 px, bukan kapital
  bertracking lebar. Label kecocokan tetap huruf kalimat seperti di kode dan prototype ("Perlu persiapan"); R-004
  mengunci katanya.
- **Hosting:** berkas variable dihosting sendiri di `public/fonts/` dengan teks OFL, subset Latin 27,3 KB ditambah
  Latin Extended yang diunduh hanya bila dipakai; preload subset Latin; `font-display: swap` dengan metrik fallback
  terhitung (terhadap Roboto: `size-adjust` 104,6%, `ascent-override` 99,2%, `descent-override` 21,2%,
  `line-gap-override` 0%) supaya tata letak tidak bergeser. `fonts.bunny.net` dilepas; `head.blade.php` saat ini
  hanya memuat bobot 400 sampai 700.

### 8.3 Ikonografi (DK-39)

- **Sistem hibrida.** Phosphor (sudah tertanam sebagai path, lisensi MIT) untuk kendali umum: kembali, tutup, cari,
  saring, bagikan, simpan, lonceng, lapisan, perbesar, posisi saya, kalender, jam, kamera, tautan keluar. Cakupan
  bukan pembeda (Phosphor, Lucide, Tabler, dan Material masing-masing menutup 36 dari 36 ikon prototype), jadi
  identitas datang dari glyph domain.
- **Kebijakan bobot per ukuran:** Regular pada 20 px ke atas; Bold pada 16 sampai 19 px; tidak ada ikon informatif
  di bawah 16 px (panah naik-turun 12 px di `CheckpointJourney.tsx` menjadi 16 px Bold).
- **8 sampai 12 glyph domain** digambar dari tata bahasa tanda "Jalur Berpos" (garis rute monoline, belokan, cincin
  pos, goresan setebal tanda): pos bernomor, tanjakan, turunan, jalur bertanda, area kemah, sumber air, puncak,
  basecamp, titik rawan, resmi (cap atau segel, pengganti perisai bercentang), laporan pendaki, serta ikon tab Jelajah
  dan Mendaki. Dimulai dari pilot tiga glyph yang jam kerjanya diukur, lalu diuji pengenalannya dengan 5 sampai 8
  pendaki (§15 Q15) sebelum set lengkap.
- **Tab aktif:** varian isi, garis, tebal label, dan `aria-current`; glyph rute yang bergaris tunggal mendapat bentuk
  aktif yang dirancang khusus.
- **Simbol POI peta** berupa glyph padat di dalam wadah, dibedakan bentuk glyph dan bukan warna baru; kendali peta
  tetap ikon garis.
- **Aksesibilitas:** ikon dekoratif di samping label `aria-hidden`; tombol ikon punya nama aksesibel; status dibawa
  bentuk karena mode `forced-colors` menimpa `fill` dan `stroke`; kuning pos (1,76:1 di kanvas) tidak pernah membawa
  arti sendirian.
- **Kewajiban lisensi:** teks lisensi MIT Phosphor ditambahkan ke repo (saat ini tidak ada, padahal path-nya ditanam).

### 8.4 Bentuk, ruang, dan target

Radius: kontrol kecil 10 px, kontrol 12 px, tombol dan isian 15 sampai 16 px, kartu 18 px, kartu utama dan peta 22
px, lembar bawah 26 px sudut atas, pil untuk chip dan bilah tab; radius dalam sama dengan radius luar dikurangi
padding. Ruang: kelipatan 2 dari skala 4, 6, 8, 10, 12, 14, 16, 20, 24, 28; gutter halaman 20 px (16 px di layar
padat seperti Jelajah). Target: kontrol utama ≥44 px; chip ≥36 px dengan jarak (WCAG 2.5.8); kontrol mode lapangan 48
sampai 66 px.

### 8.5 Material dan elevasi

Kartu datar bergaris rambut; bayangan hanya untuk lapisan yang melayang (bilah tab, baki banding, kartu pratinjau
peta, lembar bawah) dengan satu nilai `shadow-overlay`; kaca paling banyak dua elemen per layar lewat `.chrome`,
tidak pernah di atas peta, kartu, status resmi, atau laporan komunitas.

### 8.6 Gerak (dial 2, daftar tertutup)

Umpan balik tekan (skala 0,98, 120 ms); buka dan tutup lembar (280 ms, kurva `gerak`); kartu jalur ke detail lewat
View Transitions Inertia bila didukung; centang persiapan (yang sudah ada); tiba di pos (satu denyut, getar opsional);
toast urungkan (200 ms); pembuka "Kenapa?" (200 ms). Tidak ada animasi masuk dan tidak ada gerak berulang.
`prefers-reduced-motion` dan pengaturan Gerak mengganti semuanya dengan pudar singkat atau tanpa transisi. Mode
lapangan tanpa gerak selain status.

### 8.7 Media dan foto

Foto gunung diunggah admin dengan kolom wajib: pembuat, lisensi (CC BY, CC BY-SA, domain publik, milik sendiri),
URL sumber, teks atribusi, teks alternatif. Tampil dengan atribusi di pojok foto. Tanpa foto, hero memakai visual
data (bentuk rute di atas pola kontur). Foto komunitas tidak pernah menjadi hero (R-017). Avatar: unggahan pengguna
(EXIF dibuang, ukuran dibatasi) atau inisial; data contoh memakai inisial (antislop R-23).

### 8.8 Nada dan teks

"Anda"; tanpa em dash; tanpa "aman" dan "layak berangkat" (R-012, R-014); menyebut siapa yang ditunggu ("Menunggu
BMKG", "Menunggu kurator Memount", "Belum ada sumber terverifikasi"); aksi spesifik ("Lanjutkan persiapan", "Pesan
di sistem resmi", "Rencanakan trip"). Tombol aksi tanpa panah hiasan (ciri klaster "tampilan AI"); chevron hanya
pada baris yang membuka halaman lain dan pada pembuka yang dapat dilipat; ikon tautan keluar hanya pada tautan ke
situs lain. Rentang angka di data memakai en dash ("7–8 j"); di dalam kalimat memakai kata "sampai" (EYD V memakai
tanda pisah untuk rentang, sedangkan antislop R-02 melarang em dash). Titik tengah hanya pada baris ukuran yang
padat ("9,7 km · ↑1.650 m · 7–8 j"), tidak sebagai pemisah meta di setiap kartu.

### 8.9 Dial dan pembacaan desain

Reading this as: aplikasi perencanaan pendakian untuk pendaki pemula Indonesia di ponsel, bahasa visual editorial
utilitarian yang hangat (kertas, hijau hutan, data sebagai visual), dial ENERGY 2 / RHYTHM 2 / MOTION 2; mode
lapangan ENERGY 1 / MOTION 1. Motif identitas: garis rute berujung cincin pos (tanda "Jalur Berpos"), berulang
sebagai profil elevasi, garis jalur, bilah langkah, dan penanda pos bernomor. Satu aksen: kuning pos.

## 9. Arsitektur kode

### 9.1 Frontend

- `resources/js/components/ds/`: primitif desain tanpa pengetahuan domain (tombol, tombol ikon, chip, kontrol segmen,
  sakelar, kotak centang, stepper, lembar bawah dan dialog, toast urungkan, bar aksi, bilah tab, strip pendakian,
  header halaman, keadaan kosong tiga jenis, skeleton, avatar, lencana).
- Komponen domain di `components/{trails,trips,community,akun,peta}` menyusun primitif: label kecocokan, status
  akses, kartu resmi, kartu komunitas, buku sumber, profil elevasi, garis profil kartu, tangga tanjakan, hitungan
  ruas.
- Halaman tipis: memilih keadaan dari props, tidak memuat aturan domain.
- Satu komponen ikon dengan registri path (§8.3); huruf dihosting sendiri di `public/fonts/` dengan `@font-face` di
  `app.css` dan preload subset utama di `inertia.blade.php`; `fonts.bunny.net` dilepas.
- Optimistic UI dengan keadaan lokal, `router.post` dengan `preserveScroll`, pengembalian saat galat, dan toast
  urungkan yang mengirim aksi kebalikannya.

### 9.2 Server

- Satu kelas halaman per layar, mengikuti pola `RingkasanDasbor`, `TrailDetailPage`, `RouteComparisonTable`:
  `HalamanGunung`, `PenilaianKecocokan`, `IndeksTrip`, perluasan `RingkasanDasbor` (fase), `HubProgres`. Resource
  membentuk props yang sudah terformat (angka Indonesia, zona waktu).
- Test anggaran kueri untuk setiap halaman baru.
- Skor internal tidak pernah masuk props (BR-09); kecocokan yang berubah di Rencanakan dihitung server.

### 9.3 Backend baru

| Fitur | Isi | Keamanan |
|---|---|---|
| Catatan simpanan (FR-22) | `PUT` dan `DELETE trails/saved/{trail}/catatan`, ≤500 karakter, kosong berarti hapus | Hanya pemilik; batas laju; field pemilik dan jalur dari rute, bukan payload |
| Tautan bagikan (FR-23) | Tabel token (hash token, pemilik, daftar jalur, `expires_at` 7 hari, `revoked_at`); `POST` buat, `DELETE` cabut, `GET /b/{token}` publik | Token acak ≥128 bit disimpan sebagai hash; halaman publik tanpa catatan dan label kecocokan; `noindex`; batas laju |
| Target pribadi dan catat manual (FR-25) | Rute di Progres; `hiking_history.source` = manual | Hanya pemilik; validasi tanggal dan angka |
| Perlengkapan pribadi | Rute di Profil; mengisi awal persiapan | Hanya pemilik |
| Wilayah minat (DK-42) | Kolom JSON di preferensi berisi kode provinsi atau kelompok wilayah | Daftar putih nilai |
| Alasan pembatalan (DK-50) | Kolom enum opsional di `trip_plans` | Daftar putih |
| Halaman gunung (DK-44) | `GET /mountains/{mountain:slug}` | Terautentikasi; hanya jalur terbit |
| Penilaian kecocokan | `GET /trails/{trail:slug}/kecocokan` | Trip opsional hanya milik pengguna |
| Status gunung api (DK-43) | Tabel terpisah per gunung: level I sampai IV, rekomendasi radius, sumber, waktu terbit, waktu verifikasi | Diisi kurator; audit |
| Foto gunung (DK-37) | Tabel media gunung dengan kolom lisensi | Unggahan divalidasi, EXIF dibuang, admin saja |
| Input data resmi dan paket luring | §10 | §10 |

Setiap migrasi diuji di SQLite dan PostgreSQL CI dan tidak dijalankan di Supabase sebelum izin pemilik (R-019,
DK-23).

### 9.4 Keandalan, performa, observability

- `SchedulerHealthService::TUGAS` memuat `trips:kirim-pengingat`.
- Anggaran: huruf ≤100 KB untuk semua bobot yang dipakai; MapLibre tetap dimuat sesuai kebutuhan; foto responsif
  dan lambat-muat. Angka performa diukur di emulasi Android kelas menengah, tidak diperkirakan.
- Peristiwa corong yang dicatat `AnalyticsRecorder`: onboarding selesai, jalur disimpan, banding dibuka, trip dibuat,
  cek kesiapan dikonfirmasi, pendakian dimulai dan selesai, laporan dikirim, paket luring diunduh.

## 10. Paket lapangan luring dan input data resmi (DK-36)

Dasar: laporan `docs/riset/2026-09-25-luring/reports/Paket luring dan input data resmi.md` (lima catatan riset,
diakses 25 September 2026). Rinciannya, termasuk sumber per klaim, ada di laporan itu; bagian ini memuat keputusan
desainnya.

### 10.1 Temuan yang menentukan

- Aplikasi jalur (AllTrails, Outdooractive, FarOut) dan aplikasi resmi (NPS, Parks Canada, NSW) menyimpan objek,
  bukan wilayah. Tidak satu pun mendokumentasikan pemeriksaan kelengkapan paket; kegagalan terbanyaknya senyap
  (paket dikira ada ternyata hilang, terkunci langganan, atau tidak lengkap).
- Aplikasi resmi tidak membawa peringatan ke mode luring (NPS mengecualikan alert; Plan My Walk tidak dapat menerima
  alert baru). Aturan pemilik "terakhir diketahui per waktu" lebih ketat dari praktik mana pun yang terdokumentasi.
- Di Indonesia hanya BMKG yang punya API dengan ketentuan tertulis. Status buka-tutup terbit sebagai surat bernomor
  dari Kemenhut (tingkat kawasan, misalnya SE.4/KSDAE/... 31 Agustus 2026) dan dari Balai atau Perhutani (tingkat
  jalur, sering sampai segmen, sering "sampai waktu yang belum dapat ditentukan"). Level PVMBG hanya halaman web
  berlabel "All Rights Reserved". Tidak ada geometri jalur resmi yang dipublikasikan.
- Peramban sanggup menyimpan paket (kuota besar) tetapi berhak menggusur seluruh origin; iOS memisahkan penyimpanan
  tab Safari dari aplikasi Layar Utama. Background Sync dan Background Fetch hanya Chromium.
- `resources/views/sw.blade.php:27-33` menghapus setiap cache selain shell saat versi baru aktif. Paket berbasis
  Cache Storage akan hilang diam-diam pada deploy berikutnya bila aturan itu tidak diubah.
- Prefetch ubin dari server publik OpenTopoMap perlu izin tertulis; `tile.openstreetmap.org` melarang pemakaian
  luring. Koridor ±1 km jalur 20 km sampai z16 sekitar 7,6 MiB (model sintetis, harus diukur ulang).
- Tidak ada bukti bahwa fitur keselamatan aplikasi mana pun menurunkan insiden. Pola kegagalan berulang: satu ponsel
  sebagai satu-satunya navigasi, rute dari sumber daring, dan tidak ada yang memegang rencana perjalanan.

### 10.2 Paket lapangan

| Bagian | Isi | Tampilan saat luring |
|---|---|---|
| Jalur, pos, profil elevasi | Geometri, segmen, pos berurutan dengan jarak menyusuri jalur, elevasi | Normal, dengan sumber dan lisensi geometri |
| Waktu tempuh | Rentang bersumber | Rentang, atau "belum ada data waktu tempuh" |
| Aturan resmi | Izin, pemandu, kuota, jendela pesan, area terlarang | Dengan tanggal verifikasi; "Belum diketahui" bila kosong |
| Rencana | Tanggal, peserta, daftar persiapan dan keadaannya | Normal; centang saat luring masuk antrean |
| Kartu darurat | 112 dan 115 sebagai tombol telepon, kontak pengelola bila resmi diketahui, "tetap di tempat setelah menelepon", blok lokasi untuk dibacakan (lintang-bujur, pos terdekat, elevasi dari profil, waktu dan akurasi) | Satu ketukan dari mode lapangan |
| Status resmi, gunung api, cuaca, laporan komunitas | Potret dengan waktu pengetahuan dan sumbernya; laporan paling banyak 10 dalam 30 hari | Aturan §10.3 |
| Ubin koridor | Tahap 1 tanpa peta dasar (§10.5) | Kanvas polos berlabel "Peta dasar tidak tersedia luring" |

Hanya jalur terbit (R-008, R-022). Tidak memuat GPX komunitas, keluaran AI (R-011), skor, nama peserta lain, atau
jejak orang lain. Server menerbitkan manifest per paket (versi berupa hash isi stabil, daftar bagian dengan ukuran
dan SHA-256, potret dinamis dengan waktu pengetahuan, dan semua atribusi).

**Unduh dan verifikasi.** Pintu unduh di ruang kerja trip dan pratinjau hari-H. Sebelum mengunduh: isi, ukuran
menurut manifest, dan sisa ruang (`navigator.storage.estimate()`); ditolak bila ruang kurang. Di iPhone yang membuka
dari tab Safari, pemasangan ke Layar Utama diminta lebih dulu dengan alasannya. `persist()` dipanggil pada ketukan
unduh; bila ditolak, kalimat jujur tentang kemungkinan dihapus peramban. Unduhan dijalankan halaman (bukan service
worker), dapat dilanjutkan per bagian, dan progres dihitung dari bagian yang sudah terverifikasi. "Siap luring"
hanya muncul setelah setiap bagian dibaca ulang dari penyimpanan dan cocok dengan manifest, termasuk shell luring
di cache service worker; keadaan ini dihitung di perangkat. Gagal disebut spesifik ("Belum lengkap: ...") dengan
"Coba lagi". Versi lama tidak dihapus sebelum versi baru lolos; tidak ada penghapusan otomatis di dalam jendela
trip. Sakelar "Pakai paket saja" membaca hanya dari paket walau sinyal lemah, sekaligus menjadi uji luring yang benar.

### 10.3 Data dinamis

Waktu pada label adalah waktu MEMOUNT terakhir mencocokkan data dengan sumbernya (status: `verified_at`; gunung api:
tanggal level dan waktu cek; cuaca: tanggal analisis BMKG; laporan: tanggal mendaki), bukan waktu unduh. Label
memakai kata "terakhir diketahui", ikon jam, dan gaya yang berbeda dari status terkini (bukan warna saja).

| Data | Perilaku luring |
|---|---|
| Tutup atau Dibatasi | Tetap tampil dengan penerbit, nomor dokumen, masa berlaku, dan waktu cek; umurnya makin menonjol |
| Buka | "Terakhir diketahui: Buka, dicek ..." dan kalimat R-012; setelah ambang (usul 24 jam, butuh keputusan) turun menjadi "Status belum dapat dipastikan sejak ... Pastikan di pos registrasi." |
| Belum diketahui | Tetap Belum diketahui |
| Gunung api | Terpisah dari status akses, tidak pernah dipetakan ke Buka atau Tutup |
| Cuaca | "Prakiraan area ... (BMKG), analisis ..."; tanggal di luar horizon: "belum terbit saat paket dibuat" |

Saat daring kembali, potret diperbarui lebih dulu sebelum apa pun tampil sebagai terkini.

### 10.4 Service worker dan penyimpanan

Kalimat kebijakan baru: shell boleh disimpan; isi dari server tidak pernah disajikan ulang dari cache; isi paket
hanya berasal dari unduhan yang dipicu pendaki, disimpan terpisah, dan selalu tampil dengan umurnya. Perubahan:
(1) `/offline` menjadi shell luring statis dengan entri Vite kecil yang merender paket dari IndexedDB, di-precache
bersama asetnya; (2) `activate` hanya menghapus cache `shell-` yang usang dan tidak pernah menyentuh `pack-*`, dijaga
test regresi; (3) navigasi dokumen tetap jaringan lebih dulu, dan bila gagal shell luring dibuka; (4) endpoint
dinamis tetap hanya dari jaringan. Service worker tetap ditulis tangan (tanpa Workbox). IndexedDB per pengguna
(`memount-luring-<hash id>`) untuk manifest, data stabil, rencana, potret, dan antrean; ubin di Cache Storage per
paket dibaca lewat `addProtocol` MapLibre 4.7 (tetap 4.7.x). Di aplikasi Inertia, `router.on('networkError')`
menawarkan "Buka paket luring"; service worker tidak pernah memalsukan respons Inertia. Mode lapangan: satu pohon
komponen dengan dua sumber data (props saat daring, IndexedDB saat luring). Antrean kiriman diputar ulang halaman
dengan token XSRF segar dan kunci idempoten; memperluas pola `resources/js/jejak.js`.

### 10.5 Ubin dan lisensi

Tahap 1: paket tanpa peta dasar (garis jalur, pos, profil, posisi dengan lingkaran akurasi di atas kanvas polos
berlabel), sehingga paket tidak tertahan perizinan. Paralel: izin tertulis OpenTopoMap untuk prefetch koridor, atau
penyedia yang ketentuannya mengizinkan cache luring, atau ubin yang dihosting sendiri (PRD §55). Parameter usulan:
koridor ±1 km, z10 sampai z16. Manifest dan peta luring membawa semua atribusi; atribusi bawaan diperbaiki
mengikuti rumusan OpenTopoMap (termasuk SRTM). Geometri menyimpan kelas sumber, lisensi, dan tanggal perolehan; satu
gunung bersumber tunggal untuk geometri jalurnya (ODbL). RBI, inaRISK, dan SIGAP tidak masuk sebelum izinnya jelas.

### 10.6 Keamanan dan privasi

Paket minimal (tanpa token, tanpa data orang lain); pengaman nyata adalah minimisasi, CSP ketat, dan kunci layar,
bukan enkripsi dengan kunci di peramban yang sama. Toko diberi kunci hash id pengguna; berganti akun menghapus paket
akun sebelumnya. Keluar: periksa jejak belum terkirim dan minta keputusan pendaki, lalu hapus basis data paket,
cache `pack-*`, dan OPFS; respons logout mengirim `Clear-Site-Data: "storage"` sebagai lapis kedua. Celah yang ada
di `resources/js/jejak.js` (basis data tidak terikat pengguna) diperbaiki bersama. Manifest paket diakses lewat
Policy pemilik trip; bagian rencana pribadi dengan `Cache-Control: no-store`. Retensi di perangkat, batas penghapusan
akun untuk perangkat luring, dan penilaian dampak sebelum PP 33/2026 berlaku ditulis pemilik di
`docs/KEBIJAKAN-DATA-PRIBADI.md` sebelum paket dirilis.

### 10.7 Input data resmi

**Tahap 1: meja kerja kurator** yang menyalin dari sumber resmi. **Tahap 2: penerbit mitra** (petugas Balai) setelah
kemitraan. Keduanya memakai satu model data:

| Tambahan | Isi |
|---|---|
| `official_statuses.authority_id` | Penerbit sebagai rekaman `Authority` |
| `document_number`, `document_date` | Nomor dan tanggal surat |
| `evidence_type`, `evidence_archive_path`, `evidence_sha256`, `captured_at` | Jenis bukti dan arsip salinannya |
| `end_condition` | Tanggal; sampai pemberitahuan lebih lanjut; tidak disebut (memisahkan dua arti `expires_at` kosong, R-007) |
| `limit_description` | Batas pembukaan parsial ("sampai Ranu Kumbolo") |
| `reason_category` | Kebakaran, erupsi, cuaca, pemeliharaan, acara, kecelakaan, lainnya |
| `legal_basis`, `official_excerpt`, `plain_summary` | Dasar hukum, kutipan resmi singkat, ringkasan bahasa sederhana |
| `verified_by`, `verification_state` | Orang kedua yang berbeda dari pencatat; draf, menunggu verifikasi, terverifikasi, dipersoalkan |
| `superseded_by_id`, `next_review_at` | Rantai penggantian dan jadwal tinjau per jenis status |
| Tabel `authority_members` | Pengguna, otoritas, peran, surat penunjukan, masa berlaku, pencabutan |
| Tabel status gunung api | DK-43 |
| Peran `curator` | Terpisah dari moderator dan admin |

Alur tujuh langkah: masukan (tempel URL atau unggah dokumen, arsip dan hash otomatis); penstrukturan (penerbit dari
daftar otoritas yang berwenang atas wilayah itu, nomor dan tanggal, lingkup termasuk titik batas, nilai, alasan,
mulai berlaku, cara berakhir, kutipan dan ringkasan); pemeriksaan konsistensi otomatis (tanggal terbalik, lingkup di
luar yurisdiksi, dua status aktif bertentangan, jalur Buka di kawasan yang ditutup kebijakan pusat, bukti hanya
berita); verifikasi orang kedua; terbit (rekaman lama ditandai digantikan, cache dibersihkan, audit sebelum-sesudah);
tinjau berkala (kedaluwarsa menjadi Belum diketahui dan masuk antrean, tidak pernah otomatis Buka); konflik (laporan
komunitas tidak mengubah status, kurator mendapat tugas cek ulang).

**Aturan terbit asimetris:** perubahan yang membatasi boleh terbit oleh satu kurator dengan bukti primer, bertanda
"menunggu verifikasi kedua"; perubahan yang membuka wajib diverifikasi orang kedua; berita media saja tidak pernah
dapat membuka jalur. Kebijakan tingkat kawasan tampil terpisah dan tidak diwariskan otomatis ke status jalur.

**Otorisasi:** penerbit mitra hanya dalam yurisdiksinya; `recorded_by`, `verified_by`, `authority_id` dari sesi dan
Policy, tidak pernah dari formulir; aturan dua orang ditegakkan di lapisan layanan; unggahan bukti divalidasi dan
EXIF dibuang; batas laju; audit sebelum-sesudah. Level PVMBG diinput kurator dengan atribusi "PVMBG, Badan Geologi,
KESDM (MAGMA Indonesia)" dan tautan laporan, tanpa scraping. Di layar pendaki setiap status membawa chip sumber yang
dapat diketuk dan tautan "Laporkan kesalahan data" ke antrean kurator.

### 10.8 Keselamatan: paket sebagai sistem kedua

Paket dibingkai sekali saat unduh dan sekali saat mode lapangan dimulai ("Paket luring membantu mengikuti jalur
resmi. Bawa cadangan: peta cetak atau perangkat lain."), tanpa "aman", "terjamin", atau "pasti". Versi cetak (sketsa
rute, tabel pos, aturan resmi bertanggal, kartu darurat, rencana). Rencana perjalanan dibagikan sebagai teks WhatsApp
atau SMS yang memuat instruksi eskalasi bagi kontak ("Bila belum ada kabar sampai [jam kembali + 1 jam], hubungi
basecamp, lalu Basarnas 115 atau 112 ...") dan pernyataan bahwa MEMOUNT tidak memantau perjalanan; ini bukan berbagi
lokasi langsung. Mode lapangan: akurasi dan umur posisi, tiba di pos dengan konfirmasi, GPS hanya saat layar terlihat,
Wake Lock mati bawaan. Tetap di luar cakupan: peringatan keluar jalur, berbagi lokasi langsung, impor GPX komunitas
(PRD §53, BR-13, R-022).

### 10.9 Keputusan terkait yang dibutuhkan

Masuk §15 sebagai Q7 sampai Q14.

## 11. Data contoh lokal (DK-35)

- `database/seeders/DemoSeeder.php` dan perintah `memount:demo` yang membuat `database/demo.sqlite` (di-gitignore),
  menjalankan migrasi pada koneksi `demo`, lalu mengisi data contoh.
- Penjaga: perintah menolak berjalan bila driver koneksi bukan `sqlite`, bila berkasnya di luar `database/`, atau
  bila `APP_ENV` bukan `local`. Perintah ini menjalankan migrasi, jadi pertama kali dijalankan pemilik sendiri atau
  atas izin eksplisit, karena aturan proyek menolak `php artisan migrate`.
- `APP_DEMO=true` menampilkan lencana DATA CONTOH di setiap halaman; nilai bawaan `false`.
- Isi: satu pendaki contoh berinisial, enam sampai delapan gunung di Jawa Barat, Jawa Tengah, Bali, dan Nusa Tenggara,
  sekitar sepuluh jalur dengan geometri yang disederhanakan dan berlabel DATA CONTOH di peta, status, prakiraan,
  laporan, trip di berbagai fase, riwayat termasuk satu pembatalan karena cuaca, dan target.
- Data contoh tidak pernah masuk seeder produksi dan tidak pernah dijalankan terhadap `pgsql`.

## 12. Gelombang kerja

| Gelombang | Isi | Selesai bila |
|---|---|---|
| W0 Stabilisasi | 20 test merah (§3.5): FR-22 dilengkapi, komponen Simpanan, `docs/fitur/` delapan spesifikasi, label navigasi di test, props mati, tugas penjadwal | Suite hijau, `tsc` nol, build |
| WR Struktur repo dan dokumen tim | §17: `docs/PRD.md`, `docs/AGENTS.md`, `docs/README.md`, penunjuk `docs/DESIGN.md` dan `docs/PRODUCT.md`; CI dipecah; `docker-compose.yml` PostGIS; `database/README.md`; `SECURITY.md`, templat PR dan isu; inventaris dan perapian root; `tests/e2e` bila Q17 disetujui | Tonggak M1 siap push (DK-58) |
| W1 Fondasi | Token terang, gelap, lapangan; huruf (DK-38); ikon (DK-39); primitif `ds/`; `ColourContrastTest`; `DESIGN.md` v3; prototype dan gambar acuan ke `docs/desain/prototype/`; data contoh (§11) | Test kontras dan sumber komponen; tangkapan primitif |
| W2 Kerangka | `AppLayout` ponsel dan layar lebar, bilah tab, header, strip pendakian aktif, toast, lembar bawah, lembar pengingat | Test navigasi dan tangkapan 390 dan 1440 |
| W3 Klaster Jelajah | Jelajah, detail jalur, penilaian kecocokan, gunung, simpanan (catatan, bagikan), banding | S1 sampai S6 untuk layar ini |
| W4 Klaster Trip | Indeks trip, rencanakan, ruang kerja dan persiapan, cek kesiapan | Sama |
| W5 Beranda | Fase B0 sampai B4, kabar | Sama |
| W6 Mendaki | Pintu tab, pratinjau hari-H, mode lapangan, strip, paket luring (§10), ringkasan dan laporan | Sama, ditambah uji luring di peramban |
| W7 Progres dan Profil | Target, catat manual, pencapaian, alasan pembatalan, profil, perlengkapan, wilayah, onboarding (DK-41) | Sama |
| W8 Data resmi | Input data resmi, status gunung api, foto gunung berlisensi (§10) | Sama |
| W9 Penutup | Klik-lewat penuh, Delivery Gate antislop, dokumen (PRD catatan tata kelola, PRODUCT.md, ARCHITECTURE.md, HANDOVER) | S1 sampai S8 |

`HANDOVER.md` §0 diperbarui di akhir setiap gelombang.

## 13. Verifikasi

Per gelombang: suite Unit dan Feature, `npx tsc --noEmit`, build, Pint; test baru untuk perilaku yang berubah; test
anggaran kueri; `ColourContrastTest`; tangkapan 390 px dan 1440 px terang dan gelap serta tema lapangan, disandingkan
dengan gambar acuan; klik-lewat setiap kontrol dengan hasilnya (antislop R-35); jalur keyboard (Tab, Enter, Escape
menutup lembar, fokus terlihat); gerak dikurangi; `forced-colors`; paritas PostgreSQL untuk setiap migrasi (CI).
Akhir: Delivery Gate antislop dengan bukti per butir. Uji kegunaan dengan pengguna nyata tetap mengikuti
`docs/PROTOKOL-UJI-KEGUNAAN.md` dan hasilnya tidak dikarang.

## 14. Risiko

- **Cakupan besar.** Dikerjakan per gelombang dengan HANDOVER diperbarui; subagen per klaster dengan daftar berkas
  milik masing-masing; berkas bersama (layout, token, tipe bersama) dipegang koordinator.
- **Data lapangan kosong.** Keadaan kosong yang jujur di produksi; data contoh untuk peninjauan lokal; golden trail
  pertama (P6 spec v2) tetap dibutuhkan.
- **Perangkat kelas bawah.** Anggaran huruf, ikon, dan peta diukur; kaca bersyarat.
- **Luring.** Batas penyimpanan peramban dan perilaku iOS (§10).
- **Test berbasis sumber rapuh.** Diperbarui menurut niatnya, tidak dihapus.

## 15. Butuh izin atau keputusan pemilik

| # | Hal | Usulan |
|---|---|---|
| Q1 | Menjalankan `memount:demo` (migrasi ke SQLite lokal) | Pemilik menjalankan `! php artisan memount:demo` sekali, atau memberi izin eksplisit |
| Q2 | Migrasi baru di Supabase | Setelah CI PostgreSQL hijau, per migrasi |
| Q3 | Dependensi baru bila riset luring atau huruf membutuhkannya | Diajukan terpisah dengan ukuran dan lisensi |
| Q4 | Commit dan push | Setelah tinjauan diff |
| Q5 | Perilaku keamanan tertunda dari spec v2 (P9: 404 atau 403 untuk IDOR, jawaban lupa sandi, verifikasi email) | Tetap seperti sekarang sampai diputuskan |
| Q6 | Notifikasi dorong (H2) | Belum; email dan daftar pengingat saja |
| Q7 | Amandemen PRD untuk DK-36 (PRD §13, §53, §55, §121 butir 8, dan baris Mendaki §145.1 masih menyebut navigasi luring di luar MVP) | Amandemen dengan catatan tata kelola dan batas tertulis "paket lapangan, bukan navigasi luring penuh" (R-021) |
| Q8 | Sumber ubin peta luring | Tahap 1 tanpa peta dasar berlabel; pemilik meminta izin tertulis OpenTopoMap (tindakan keluar) atau memilih penyedia berbayar atau ubin sendiri |
| Q9 | Ambang turun data dinamis saat luring dan jadwal tinjau per jenis status | Buka turun setelah 24 jam (diuji dulu); cuaca 24 jam dan laporan komunitas 30 hari memakai konfigurasi yang ada; ambang tinjau 90 hari diganti per jenis status |
| Q10 | Kebijakan data di perangkat | Pemilik menulis di `docs/KEBIJAKAN-DATA-PRIBADI.md`: retensi setelah trip, hapus saat keluar, batas penghapusan akun untuk perangkat luring, penilaian dampak sebelum PP 33/2026 berlaku |
| Q11 | Peran baru dan aturan terbit | Peran kurator dan penerbit mitra per yurisdiksi; aturan terbit asimetris |
| Q12 | Kebijakan tingkat kawasan terhadap rekomendasi | Jalur di kawasan yang ditutup kebijakan pusat dikecualikan dari rekomendasi tanpa menulis ulang status jalur |
| Q13 | Tindakan keluar: surat permintaan izin atau API ke PVMBG; kemitraan satu Balai untuk jalur percontohan | Dilakukan pemilik; sampai itu, level PVMBG diinput kurator dengan atribusi dan tautan |
| Q14 | Telaah hukum sebelum paket didistribusikan publik (penerbitan ulang fakta status, geometri turunan OSM dan ODbL, RBI menurut UU 4/2011, lisensi inaRISK dan SIGAP); fitur keselamatan tambahan (versi cetak, pesan rencana perjalanan dengan eskalasi, kolom nomor registrasi dan jam kembali) | Telaah oleh ahli hukum pilihan pemilik; fitur keselamatan masuk W6 |
| Q15 | Uji pengenalan glyph domain dan uji keterbacaan huruf serta angka dengan 5 sampai 8 pendaki (render di Android nyata) | Pemilik menjalankan dengan protokol `docs/PROTOKOL-UJI-KEGUNAAN.md`; hasil tidak dikarang |
| Q16 | Huruf kustom (misalnya dengan Tokotype) dan wordmark final | Ditunda sampai setelah MVP; wordmark tetap melewati gerbang DK-16 |
| Q17 | Dependensi dev: `@playwright/test` untuk `tests/e2e`; ESLint dan Prettier | Playwright disetujui lebih dulu (alur kritis dan uji luring); ESLint dan Prettier menyusul |
| Q18 | `LICENSE` (proprietary atau terbuka) dan isi `CODEOWNERS` | Keputusan pemilik; tanpa itu `LICENSE` tidak dibuat |
| Q19 | Folder skill yang dilacak git | Skill milik proyek dan `skills-lock.json` dilacak; salinan pihak ketiga dipulihkan lewat `npx skills experimental_install` |
| Q20 | `maplibre-gl` 4.7.1 terkena CVE-2026-85061 (GHSA-jrc7-96c5-q579, kritis, CVSS 10.0, terbit 19 Agustus 2026; semua versi sampai 6.4.0; diperbaiki hanya di 6.4.1): XSS lewat HTML atribusi yang lolos `DOM.sanitize()`. FAKTA: satu-satunya `new maplibregl.Map` (`resources/js/components/peta/usePetaDasar.ts:78`) memasang kontrol atribusi bawaan, sedangkan keempat komponen peta sudah menampilkan atribusi sebagai teks React lewat `WadahPeta.tsx:41`. INFERENCE: tidak terjangkau penyerang pada konfigurasi bawaan (atribusi dari `MAP_ATTRIBUTION` milik operator), terjangkau bila `MAP_STYLE_URL` menunjuk gaya pihak ketiga. `npm audit` di `security.yml` merah sampai diputuskan | **Diputuskan pemilik 26 September 2026:** mitigasi `attributionControl: false` dengan test regresi masuk W0 (tanpa mengubah versi; atribusi tetap tampil lewat `WadahPeta`). Masih terbuka: upgrade ke MapLibre 6.x sebelum W3, karena W3 dan W6 dibangun di atas API peta (butuh izin karena mengubah kunci versi 4.7.x) |
| Q21 | Repo GitHub publik (FAKTA: API GitHub tanpa autentikasi menjawab 200). Yang sudah terbaca publik di `origin/hardening`: Workspace ID NgodingPakeAI di blok alat `AGENTS.md`, dan ref proyek Supabase di `docs/db/schema-snapshot-sebelum-hardening.md` baris 3 (keduanya pengenal, bukan kredensial). Yang akan terbaca bila di-commit: 6 foto JPG di prototype dan gambar acuannya, yang sumber dan haknya belum tercatat | Ref proyek disamarkan di commit berikutnya ("proyek Supabase pemilik"); riwayat tidak ditulis ulang kecuali pemilik memintanya (R-018). Workspace ID: pemilik memutuskan. Prototype: pemilik memastikan hak foto sebelum commit, atau prototype dan gambar acuan tetap lokal (diabaikan git) |
| Q22 | Izin baca Supabase untuk `php artisan migrate:status` dan advisor keamanan | Dibutuhkan butir DK-58. FAKTA dari kode: 13 tabel yang dibuat sesudah migrasi penutupan PostgREST `2026_09_20_000100` tidak menyalakan RLS di migrasinya (`hike_track_points` berisi jejak lokasi pribadi); lapis pertama (hak `anon` dan `authenticated` dicabut lewat `ALTER DEFAULT PRIVILEGES`) masih berlaku. Usul: migrasi lapis kedua yang menyalakan RLS pada ketiga belas tabel, diuji di SQLite dan PostgreSQL CI, dijalankan di Supabase atas izin |

## 16. Dokumen yang berubah

`DESIGN.md` (Turunan ditulis ulang dari §8; Arah dan R-015 sampai R-017 utuh); `PRD.md` (§145.1 halaman gunung,
penilaian kecocokan, indeks trip; catatan tata kelola baru di §146); `PRODUCT.md` (drift §3.6); `docs/ARCHITECTURE.md`
(peta halaman React, kelas halaman baru, paket luring, data contoh); `docs/fitur/` (delapan spesifikasi, W0);
`README.md` dan `CONTRIBUTING.md` (bagian sistem desain); `HANDOVER.md` §0.

## 17. Addendum 26 September 2026: struktur repo, dokumen tim, dan skill

### 17.1 Arahan pemilik (verbatim)

> "setuju, lanjut tulis rencana implementasi W0 sampai W9, restructure untuk persiapan push repo denga rapi dan
> profesional berikut panduannya: [dua panduan struktur] compare dan combine dengan ini [...]. tambahkan folder docs
> berupa design md, product md, prd md, dan agent md sebagai aturan untuk rekan satu team lainnya setelah di repo
> standart disini agar visi, misi, ekspektasi, dan kesepakatan nya sama serta kriteria" pengerjaan lainnya seperti
> selalu riset detail dan mendalam melalui analisis brainstorming lalu cara audit nya dan kriteria" lainnya yang
> sudah menjadi kesepakatan disini pastikan detail dan lengkap. serta install dan selalu terapkan skill berikut untuk
> menunjang pengembangan app terutama ui/ux compare dan combine skill berikut: npm install -g ui-ux-pro-max-cli, npm
> install -g @anthropic-ai/claude-code, npx skills add https://github.com/Leonxlnx/taste-skill --skill
> "design-taste-frontend". terapkan combine dengan skill" lainnya seperti impactcable dll."

Panduan pertama adalah monorepo (`apps/backend`, `apps/frontend`, `packages/`, `database/`, `tests/e2e`,
`.github/workflows/test.yml` dan `deploy.yml`, `docker-compose.yml`). Panduan kedua adalah empat repo terpisah
(layanan mikro dengan Kubernetes, klien frontend dengan Turborepo, infrastruktur Terraform dan Ansible, cetak biru
basis data per layanan).

### 17.2 Keadaan skill (FAKTA, 26 September 2026)

`ui-ux-pro-max-cli` 2.15.0 dan `@anthropic-ai/claude-code` 2.1.282 sudah terpasang global dan sama dengan versi
terbit di npm; Claude Code tidak dipasang ulang karena memasang ulang versi yang sama di tengah sesi berisiko
mengunci berkas program di Windows. `uipro init --ai claude` dan `--ai universal` memasang `ui-ux-pro-max` beserta
`design-system`, `ui-styling`, `brand`, `design`, `banner-design`, dan `slides` ke `.claude/skills/` dan
`.agents/skills/`. `npx skills add ... --skill design-taste-frontend -y --copy` lolos tiga pemindai (Gen Safe, Socket
0 peringatan, Snyk Low Risk) dan isinya identik dengan yang sudah terpasang; salinan ketiga yang dibuat CLI di
`skills/` root dihapus setelah terbukti identik.

### 17.3 Bandingkan dan gabungkan

| Unsur panduan | Tujuannya | Padanan di Memount (Laravel monolit modular) | Keputusan |
|---|---|---|---|
| `apps/backend/src/{config,controllers,middlewares,models,routes,services}` | Lapisan server terpisah | `config/`, `app/Http/Controllers`, `app/Http/Middleware`, `app/Models`, `routes/`, `app/Services`, `app/Policies` | Tetap susunan Laravel; aturan lapisan ditegakkan `docs/ARCHITECTURE.md` |
| `apps/frontend/src/{components,core,features,views}` | Lapisan antarmuka per fitur | `resources/js/components/ds` (primitif), `components/{trails,trips,community,akun,admin}` (fitur), `lib/` (inti), `pages/` (halaman) | Tetap; `ds/` dibuat di W1 |
| `packages/api-client` | Satu klien API | Rute dan aksi bertipe Wayfinder, router Inertia | Sudah ada |
| `packages/ui-design-system` | Sistem desain bersama | `resources/js/components/ds` dan token `app.css` | W1 |
| `packages/config` (ESLint, Prettier, TypeScript) | Konfigurasi mutu bersama | `tsconfig.json`, Pint | ESLint dan Prettier diajukan (Q17) |
| `database/{migrations,seeders}`, `README.md` | Cetak biru tanpa kebocoran data | `database/migrations`, `seeders`, `factories` | Tambah `database/README.md`; dump SQL diabaikan git |
| `tests/e2e` (Playwright) | Uji alur utuh | Belum ada | Diajukan (Q17) untuk alur kritis |
| `.github/workflows/test.yml`, `deploy.yml` | CI/CD | Satu `ci.yml` (PostGIS, Pint, unit, feature, spasial, tsc, build) | Dipecah: `test.yml`, `security.yml`, `deploy.yml` manual yang menunggu keputusan target (DK-12) |
| `docker-compose.yml` | Lingkungan lokal terisolasi | Tidak ada; uji spasial butuh Postgres lokal | PostgreSQL + PostGIS untuk `SPATIAL_TEST_DSN` (paritas R-024) |
| `Dockerfile`, layanan mikro, Kubernetes, Terraform, Ansible | Skala dan infrastruktur | Aturan 10 `docs/ARCHITECTURE.md`: monolit modular sebagai pilihan sadar | Tidak diambil; kriteria bukti kebutuhan dicatat di ARCHITECTURE |
| `.gitignore` "benteng keamanan" | Mencegah rahasia terdorong | `.gitignore`, `SecretsNotInRepoTest` | Diperketat; pemindai rahasia di CI |
| `README.md` panduan pasang | Masuk tim | `README.md`, `CONTRIBUTING.md` | Diperbarui; ditambah `SECURITY.md`, templat PR dan isu |

### 17.4 Keputusan

| ID | Keputusan | Dasar |
|---|---|---|
| DK-51 | Tetap monolit modular Laravel. Prinsip kedua panduan diambil lewat pemetaan §17.3, bukan dengan memindahkan `app/` ke `apps/backend/src`. Memindahkan susunan akan memutus autoload, Artisan, Laravel Boost, 1.252 test, dan konvensi Inertia; layanan mikro butuh bukti kebutuhan terukur dan persetujuan (R-021) | `docs/ARCHITECTURE.md` aturan 10; pedoman Boost "stick to existing directory structure" |
| DK-52 | Dokumen tim di `docs/`: `docs/PRD.md` (dipindah dari root, sekaligus melindunginya dari penimpaan `ngodingpakeai plan get` yang menulis `PRD.md` di root, temuan T9 spec v2), `docs/AGENTS.md` (kesepakatan kerja lengkap untuk rekan tim manusia dan agen), `docs/README.md` (indeks). `DESIGN.md` dan `PRODUCT.md` tetap di root karena skill impeccable dan antislop membacanya dari root; `docs/DESIGN.md` dan `docs/PRODUCT.md` berupa penunjuk satu paragraf, bukan salinan, sehingga folder `docs/` memuat keempatnya tanpa dua sumber kebenaran. `AGENTS.md` root tetap memuat blok Laravel Boost ditambah pintu masuk ringkas ke `docs/AGENTS.md`; `CLAUDE.md` tetap kontrak operasi dan menunjuk ke sana | Arahan pemilik; kontrak alat (Claude Code, Codex, Cursor, Zed, Boost, impeccable) |
| DK-53 | `docs/AGENTS.md` memuat: visi, misi, ekspektasi (PRD §4, §5, §160); kesepakatan kerja (PRD §161, HANDOVER §3, CLAUDE.md); alur riset lebih dulu (brainstorming, spec, rencana, TDD, verifikasi, tinjauan); tingkat bukti R-023; cara audit (`docs/AUDIT-PROTOCOL.md`, audit antislop, tinjauan keamanan, audit aksesibilitas, uji kegunaan); izin (git, Supabase, dependensi, migrasi, rahasia); definisi selesai; format rekap berpersentase; lima prinsip rekayasa; matriks skill dan urutan wewenangnya; protokol tahan-sesi | Arahan pemilik "pastikan detail dan lengkap" |
| DK-54 | CI dipecah menjadi `test.yml`, `security.yml` (composer audit, npm audit, pemindai rahasia), dan `deploy.yml` yang hanya `workflow_dispatch` dan tidak menjalankan deploy sampai pemilik memutuskan target dan rahasianya; push dan deploy tetap butuh izin (R-018) | §17.3 |
| DK-55 | `docker-compose.yml` untuk PostgreSQL + PostGIS lokal, tanpa mengubah aplikasi; Docker opsional | R-024 |
| DK-56 | Root dirapikan: setiap berkas root diinventarisasi dengan keputusan (pertahankan, pindah, abaikan git, hapus atas izin); prototype ke `docs/desain/prototype/` (DK-47); `ourplan.md` ke `docs/arsip/`; `features/` dan `.ngodingpakeai/` menjadi ruang kerja alat yang diabaikan git setelah `docs/fitur/` ada; berkas lokal (`*.disabled`, cache) diabaikan git | Arahan pemilik |
| DK-57 | Skill UI/UX digabung dengan urutan wewenang: arahan pemilik dan aturan kanonis, lalu `DESIGN.md`, lalu saringan antislop, lalu saran skill (ui-ux-pro-max, design-taste-frontend, impeccable, apple-design, emil-design-eng, dan lainnya). Skill tidak menciptakan arah baru: palet dan pasangan huruf dari basis data ui-ux-pro-max dipakai sebagai pembanding dan pemeriksa, bukan pengganti keputusan DK-38. Langkah "Persist Design System" ui-ux-pro-max diarahkan ke `DESIGN.md`, bukan folder `design-system/` baru. Skill pihak ketiga ditinjau (pemindai keamanan dan isi `SKILL.md`) sebelum dipasang atau diperbarui | Arahan pemilik; antislop "boundary" |
| DK-58 | Tonggak M1 "siap push" = W0 + WR: suite hijau, `tsc` nol, build, Pint, pemindai rahasia bersih, diff ditinjau, status migrasi diverifikasi, dokumen tim lengkap. Push tetap menunggu izin eksplisit pemilik | R-018 |

Butir izin baru di §15: Q17 dependensi dev (Playwright untuk `tests/e2e`; ESLint dan Prettier); Q18 `LICENSE`
(proprietary atau terbuka) dan isi `CODEOWNERS`; Q19 folder skill yang dilacak git (usul: skill milik proyek dan
`skills-lock.json` dilacak; salinan skill pihak ketiga dipulihkan dengan `npx skills experimental_install`).

Gelombang baru **WR "Struktur repo dan dokumen tim"** berjalan setelah W0 dan sebelum W1 (§12).
