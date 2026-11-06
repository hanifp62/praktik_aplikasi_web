# Desain: Konsolidasi Dokumen Kanonis (Sub-proyek 1)

**Tanggal:** 23 September 2026
**Status:** DK-01–DK-12 disetujui pemilik produk 23 September 2026. Amandemen DK-13–DK-24 (§2.1, arahan merek dan platform) menunggu tinjauan pemilik; slot nama merek terisi: MEMOUNT (24 September 2026). Belum ada berkas kanonis yang diubah.
**Asal:** sesi brainstorming 23 September 2026. Keputusan tercatat di §2 beserta siapa yang memutuskan.

---

## 1. Tujuan dan batas

**Tujuan.** Satu set dokumen dengan satu pemilik untuk setiap jenis isi, hasil penggabungan:

- PRD lama (`git show HEAD:PRD.md`, v2.0 FINAL, 3.670 baris, 146 bagian);
- PRD NgodingPakeAI (`PRD.md` di working tree, 412 baris, identik dengan `jelajah-gunung.zip` dan `ngodingpakeai plan get`) beserta 8 spesifikasi `features/*.md` (140 task);
- `ourplan.md` (riset strategi 862 baris, belum terlacak git);
- `PRODUCT.md` (240 baris);

dengan standar terkini yang diriset dan dikutip sumbernya (§7).

**Batas.** Struktur kode `app/` tidak dirombak (DK-01). Sub-proyek ini hanya menulis dan menata dokumen. Fitur baru yang disetujui dibangun di sub-proyek 4 lewat backlog NgodingPakeAI.

**Sub-proyek lain** (masing-masing mendapat desain sendiri):

| # | Sub-proyek | Keadaan |
|---|---|---|
| 1 | Konsolidasi dokumen kanonis | Dokumen ini |
| 2 | Kerapian repo untuk tim: README proyek, `docs/CONTRIBUTING.md`, tata letak `docs/`, berkas sisa di root, config agen/editor, `.gitignore` | Berikutnya |
| 3 | Riset UI/UX dan standar berkelanjutan | Hasil pertama ada di §7 |
| 4 | Pengembangan fitur: loop NgodingPakeAI di bawah PRD gabungan | Dijeda sampai PRD gabungan disetujui |
| 5 | Kesiapan deploy Vercel (DK-12): container FrankenPHP atau runtime `vercel-php`, endpoint cron, object storage, pooler basis data, log stderr, uji header Livewire | Setelah sub-proyek 2 |
| 6 | Program platform (DK-13–DK-24), bertahap menurut DK-23: fase nol tanpa skema (perbaikan F8, F9, F11–F13, mode gelap, adaptasi perangkat, hapus `@tailwindcss/vite`, logo setelah gerbangnya lulus), lalu peluncuran `/en/`, lalu program data gunung per fase | Setelah sub-proyek 1; tiap tahap mendapat desain sendiri |

---

## 2. Keputusan

| ID | Keputusan | Diputuskan oleh | Dasar |
|---|---|---|---|
| DK-01 | Cakupan "rombak structure" = dokumen dan kerapian repo; struktur `app/` tidak diubah | Pemilik | Dialog cakupan |
| DK-02 | Pendekatan A: satu pemilik per jenis isi; PRD inti ditulis ulang dari tiga sumber; PRODUCT.md dilebur; tabel paritas; register konflik | Pemilik | Dialog pendekatan |
| DK-03 | Peta kepemilikan §3 | Pemilik (disetujui) | Desain bagian 1 |
| DK-04 | Nomor § adalah ID permanen, bukan posisi. BR-01–BR-16 dan FR-01–FR-21 dipertahankan. Bagian baru mulai §147, fitur baru mulai FR-22 | Pemilik (disetujui) | 357 rujukan `§N` di 173 berkas kode dan test, ±244 di dokumen |
| DK-05 | Simpanan longgar (batas teknis ditetapkan di spesifikasi fitur 03), perbandingan memilih maksimal 3 | **Delegasi pemilik** kepada riset | §7.1 |
| DK-06 | Peta digerbang data: peta menjadi target antarmuka utama tetapi hanya menggambar jalur bergeometri; titik gunung sebagai pengganti jalur dihapus | **Delegasi pemilik** kepada riset | R-001, R-002, HANDOVER §8, §7.3 |
| DK-07 | Masuk MVP: catatan pribadi + tautan bagikan; pengingat + detail rencana; masuk dengan Google dan passkey; target pribadi + catat pendakian manual | Pemilik | Dialog fitur baru |
| DK-08 | Verifikasi kode lewat email, WhatsApp, dan SMS | Pemilik | Dialog fitur baru; desain berbasis riset §7.4 |
| DK-09 | Amandemen teks R-009: menyebut ACCREDITED EXPERT ("Ahli bersertifikat") di antara ADMIN VERIFIED dan COMMUNITY, sesuai kode | **Delegasi pemilik** kepada riset | `app/Enums/SourceType.php` |
| DK-10 | Teks PRD NgodingPakeAI yang melanggar aturan kanonis diselaraskan (K2–K5, K8, K16–K19 di §8) | Aturan kanonis yang berlaku | CLAUDE.md §2, `.claude/rules/mountain-core.md` |
| DK-11 | Laporan jenis "cerita/tips" dan reaksi "jempol" tidak masuk MVP | Tidak dipilih pemilik | §145.1: Kabar bukan feed sosial |
| DK-12 | Target deploy produksi: **Vercel**. Jalur yang direkomendasikan: container-backed Function dengan FrankenPHP (panduan resmi Vercel, 3 September 2026); alternatif: runtime komunitas `vercel-php@0.9.0` (PHP 8.5) | Pemilik (platform); jalur teknis berbasis riset | Dialog tinjauan spec; §7.7 |

Keputusan berlabel **delegasi** diambil atas landasan kerja pemilik: *"selalu riset detail dan mendalam ... informasi terkini ... sesuai/kompatibel dengan user sekarang"*. Ketiganya ditandai di GR-02 dan dapat dibatalkan pemilik kapan saja tanpa biaya dokumen.

### 2.1 Arahan merek dan platform (amandemen, menunggu tinjauan)

Arahan pemilik 23 September 2026: tetapkan branding sebagai landasan tambahan, kompatibel dengan semua perangkat, mode gelap, bahasa tambahan, dan informasi gunung seluruh Indonesia yang dapat diinput di aplikasi. Riset lima jalur dan sintesisnya ada di `docs/riset/2026-09-23-konsolidasi/` (§7.8). Pemilik memutuskan D1–D12 dari laporan sintesis; DK-(12+n) mencatat keputusan Dn.

**Pernyataan merek pemilik, verbatim, dengan slot nama:**

> [NAMA] adalah platform perjalanan gunung yang dipersonalisasi, yang menghubungkan discovery, Route Fit, preparation, readiness, field experience, trust, community, dan progression dalam satu perjalanan utuh.

| ID | Keputusan | Diputuskan oleh | Dasar |
|---|---|---|---|
| DK-13 | Nama merek: **MEMOUNT**, ditetapkan pemilik 24 September 2026 (verbatim: "tetapkan Memount sebagai nama brand app web ini"). Dasar: `riset-nama-mendaki-memount.md` (v1 9/12, v3 18/26, lolos semua gerbang meja; PDKI bersih; `memount.com` dan `memount.id` tersedia saat dicek 24 Sep 2026 12.55 UTC). Nama sebelumnya yang ditolak: VELORA (D1), VERTIKA, GOURVA, EMOUNT, MENDAKI. Slot [NAMA] terisi lewat `APP_NAME=Memount` (satu sumber: `config('app.name')`). Syarat tersisa: uji dengar-lalu-eja K1b sebelum aset logo final; pemilik mengamankan domain dan handle `@memount.id`; permohonan DJKI kelas 9 dan 42 | Pemilik (24 Sep 2026) | `riset-nama-mendaki-memount.md`, `riset-nama-emount.md`, `riset-nama-internasional.md` |
| DK-14 | Kontrak merek: pernyataan pemilik tetap verbatim sebagai arah (DESIGN.md "Arah", PRD §1). Teks publik memakai paket draf yang diuji dengan pengguna lalu dikunci lewat governance record: tiga kata kerja dan satu fondasi (*Memilih*, *Bersiap*, *Mendaki dan bertumbuh*; fondasi *sumber yang jujur*), pernyataan publik, proposisi satu baris "Jalur yang sesuai dengan pengalaman Anda, dengan alasan dan sumber yang jelas.", tagline **"Pilih jalur yang sesuai. Pahami alasannya."**, suara tetap "Anda". Perbaikan teks: "layak berangkat" diganti "apa yang masih kurang sebelum berangkat" (F9, R-014) | Pemilik (D2) | Laporan sintesis §Merek, R-004, R-005, R-012, R-014 |
| DK-15 | Branded house: Route Fit adalah fitur bermerek di bawah nama merek, bukan sub-merek. Nama publiknya dipilih lewat uji kegunaan dari "Route Fit", "Kecocokan Jalur", "Cek Kecocokan"; sampai uji selesai, istilah yang sudah tampil tidak diubah. Label keputusan tetap tiga (R-004) | Pemilik (D3) | `docs/PROTOKOL-UJI-KEGUNAAN.md` |
| DK-16 | Logo konsep A "Jalur Berpos": garis rute zig-zag berujung cincin pos, dalam lockup horizontal dengan wordmark dari Plus Jakarta Sans Bold yang di-outline dan disesuaikan. Urutan sebelum aset final: sketsa monokrom dan uji 16 px, audit kemiripan, penelusuran PDKI tanda gambar, uji pemahaman 5–8 pendaki, persetujuan pemilik (antislop R-23), baru warna, lalu dicatat di DESIGN.md | Pemilik (D4) | `identitas_visual_logo.md` |
| DK-17 | Mode gelap Desain A: token CSS didefinisikan ulang di bawah `prefers-color-scheme` dan `data-theme` yang dirender server dari cookie `tema` (nilai dibatasi daftar putih, tanpa enkripsi); tahan terhadap penggantian atribut `<html>` oleh `wire:navigate`. Default **"Ikuti sistem"**, tombol cepat Terang/Gelap di mode pendakian. Tetap Tailwind 3.4.19. Token gelap mulai dari token yang ada; token baru hanya bila uji layar nyata menuntutnya (butuh persetujuan dan pasangan test kontras). `theme-color` memakai warna permukaan. F13 diperbaiki lebih dulu supaya token gelap ikut diuji. Menutup `DESIGN.md` "Mode gelap belum diputuskan" | Pemilik (D5) | `mode_gelap_perangkat.md` |
| DK-18 | Peta di tema gelap: basemap topo tetap, diredupkan lewat `raster-brightness-max` (M1). Peta gelap tanpa kontur (M2) hanya atas permintaan pemilik; self-host PMTiles (M3) jalur masa depan. Penyedia cadangan OpenTopoMap diputuskan terpisah (OPEN ISSUE; kandidat openmaps.fr lewat `MAP_TILE_URL`, kebijakan pemakaiannya diverifikasi dulu) | Pemilik (D6) | `mode_gelap_perangkat.md`, §55 |
| DK-19 | Adaptasi perangkat: orientasi manifest `any` (F12, WCAG 1.3.4); CSS `@container` murni di `app.css` untuk komponen terbatas, tanpa plugin; `min-h-dvh` di kedua layout; safe area untuk bar aksi mode pendakian; latar token pada `html` dan `body` | Pemilik (D7) | `mode_gelap_perangkat.md`, §88 |
| DK-20 | Bahasa: Indonesia tetap bahasa utama dan sumber kebenaran; **Inggris** bahasa tambahan pertama; bahasa kedua diputuskan dari analitik aplikasi sendiri. `fallback_locale` diubah dari `en` ke `id` setelah F8 diperbaiki. Istilah keselamatan diuji per bahasa. Komitmen "Seluruh antarmuka berbahasa Indonesia" (`PRODUCT.md:101`) diubah lewat governance record (GR-03). Klarifikasi hukum UU 24/2009 Pasal 38 menjadi OPEN ISSUE | Pemilik (D8) | `multibahasa.md` |
| DK-21 | URL multibahasa: Indonesia di root tanpa prefiks; Inggris `/en/mountains/{gunung}/via/{jalur}` dan `/en/guides/{topik}`. `/pendakian/{slug}` dimigrasikan ke `/gunung/{slug}/via/{trail}` dengan 301 **bersamaan** dengan peluncuran `/en/` (menutup D-B tanpa rantai redirect). Halaman `en` yang belum diterjemahkan diberi `noindex` dan canonical ke `id`; `hreflang` timbal balik; tanpa redirect berbasis IP, geolokasi, atau `Accept-Language` | Pemilik (D9) | `multibahasa.md`, §145.2 |
| DK-22 | Program data gunung nasional: katalog **Tier A terkurasi** (69 unit gunung api MAGMA, 403 gunung ber-idwiki, 13 lokasi SE Kemenhut; ratusan setelah deduplikasi, angka pasti belum dihitung); Tier B GNS hanya per kasus. Setiap field bersumber; elevasi hanya dari sumber bernama ("Elevasi: belum ada sumber terverifikasi" bila kosong, R-006, R-007). Status bahaya PVMBG dan status akses Kemenhut **disimpan terpisah** dan Level PVMBG tidak pernah dipetakan ke OPEN/RESTRICTED/CLOSED (R-012). OSM hanya peta dasar, tanpa impor ke katalog (menghindari *share-alike* ODbL); tanpa data dari AllTrails, Strava, atau Wikiloc; teks Wikipedia tidak disalin. Input admin di aplikasi dengan `source_url` wajib. Empat fase: katalog, status, satu sampai tiga golden trail, kontribusi termoderasi | Pemilik (D10) | `data_gunung_indonesia.md`, R-007, R-009, R-010, R-022 |
| DK-23 | Skema bertahap: fase nol tanpa skema; `users.locale` dan deskripsi cuaca Inggris bersama peluncuran `/en/`; tabel terjemahan konten saat terjemahan konten dimulai; preferensi tema di `users` opsional; nilai kandidat per field, status bahaya, registri lisensi, dan wilayah administratif bersama program data. Setiap migrasi disetujui, diuji di PostgreSQL, dan status migrasinya diverifikasi di Supabase atas izin | Pemilik (D11) | R-019, R-021, R-024 |
| DK-24 | Nol dependensi runtime baru di fase 1. `@tailwindcss/vite` 4.3.3 yang tidak terdaftar di `vite.config.js` dihapus. Ditunda: Tailwind v4, `@tailwindcss/container-queries`. Dihindari: `laravel-lang/lang` (insiden rantai pasok Mei 2026). Paket routing i18n tidak diperlukan. Alat visual regression terang dan gelap diputuskan terpisah | Pemilik (D12) | `laporan-sintesis.md` D12, R-020 |

**Masih terbuka dari arahan "go internasional":** arahan ini diperlakukan sebagai kriteria nama. Perluasan cakupan produk ke luar Indonesia (katalog gunung negara lain, pasar sasaran, mata uang, zona waktu) belum diputuskan dan tidak diterapkan diam-diam (R-021); keputusannya diajukan terpisah bila pemilik menghendakinya.

**Masih terbuka dari D10** (perlu keputusan pemilik sebelum fase datanya dimulai): tingkat otoritas GVP (usulan: ACCREDITED EXPERT hanya untuk identitas dan riwayat erupsi; menyentuh R-009 dan R-021), tabel pemetaan SE ke CLOSED dan RESTRICTED, ambang selisih elevasi yang dianggap konflik, ambang kesegaran status, golden trail pertama, lisensi kontribusi pengguna di ToS, surat permintaan data atau izin ke Balai TN, PVMBG, dan BIG, serta jumlah gunung seed yang sebenarnya (16 menurut `PRODUCT.md`, 17 menurut brief; PERLU KLARIFIKASI lewat query basis data atas izin).

---

## 3. Peta kepemilikan

| Jenis isi | Pemilik tunggal | Perubahan |
|---|---|---|
| Pintu masuk tim: apa proyek ini, cara menjalankan, peta dokumen | `README.md` | Ditulis ulang di sub-proyek 2 (sekarang README bawaan Laravel) |
| Kontrak operasi agen, R-018–R-021, R-023, R-024 | `CLAUDE.md` | Isi tetap; rujukan ke PRODUCT.md dan nomor bagian diperbarui |
| Instruksi agen non-Claude | `AGENTS.md` | Kepala proyek pendek yang menunjuk CLAUDE.md; blok hasil generate (Laravel Boost, NgodingPakeAI) dibiarkan di dalam penandanya |
| Aturan domain R-001–R-014, R-022 | `.claude/rules/mountain-core.md` | Hanya amandemen R-009 (DK-09, teks di §11) |
| Arah desain, R-015–R-017 | `DESIGN.md` | R-015–R-017 tidak berubah. Bagian "Arah" ditambah pernyataan merek pemilik (verbatim) dan nama terpilih (DK-13); "Mode gelap belum diputuskan" ditutup (DK-17); konsep logo A dicatat sebagai arah, aset final menyusul gerbang DK-16. Token gelap masuk bagian "Turunan" |
| Visi, misi, north star, posisi, persona, prinsip, registri BR, cakupan dan fase, arsitektur informasi, NFR, model domain, metrik, risiko, tata kelola dan log keputusan | `PRD.md` | Ditulis ulang (§5); PRODUCT.md dilebur ke sini |
| Spesifikasi per fitur | `docs/fitur/01–08-*.md` | Baru, dari `features/` + FR lama (§6) |
| Arsitektur kode, kontrak SEO teknis | `docs/ARCHITECTURE.md` | Rujukan diperbarui |
| Kebijakan data pribadi dan penghapusan akun | `docs/KEBIJAKAN-DATA-PRIBADI.md` | Tidak diubah di sub-proyek ini; rekomendasi UU PDP dan data nomor telepon (DK-08) diajukan terpisah |
| Keadaan terkini dan serah terima | `HANDOVER.md` | Diperbarui ke keadaan pasca-konsolidasi; keputusan tertutupnya pindah ke log keputusan PRD |
| Riset asal | `docs/riset/2026-09-22-strategi-platform.md` | `ourplan.md` diarsipkan utuh sebagai bukti asal |
| Sumber NgodingPakeAI | `docs/fitur/` | `features/` dipindah dan diperkaya; `jelajah-gunung.zip` dihapus di sub-proyek 2 (duplikat identik, dapat diambil ulang lewat `plan get`) |

---

## 4. Skema ID

1. **Nomor § adalah ID, bukan halaman.** Setiap bagian PRD membawa ID-nya di judul, misalnya `## §91 Tanpa presisi palsu`. Urutan baca boleh berbeda dari urutan nomor.
2. **Tidak ada ID yang dihapus atau dipakai ulang.** Bagian yang dipadatkan ke bagian lain tetap tercatat sebagai alias, misalnya `§137 → lihat §1`.
3. **ID baru:** bagian mulai §147, fitur mulai FR-22, aturan bisnis baru (bila ada) memakai nomor berikutnya setelah BR-16, governance record mulai GR-02.
4. **Indeks ID** di lampiran PRD memetakan setiap §1–§159, FR-01–FR-27, BR-01–BR-16 ke lokasinya. Pembagian persis per ID ada di rencana implementasi (`docs/superpowers/plans/2026-09-23-konsolidasi-dokumen.md`).
5. **Rujukan di kode tidak disentuh.** Karena ID tidak berubah, 357 rujukan di kode dan test serta rujukan CLAUDE.md ke §130, §132, §145, §146 tetap benar.

---

## 5. Kerangka PRD

Urutan disusun untuk manusia yang membaca dari atas. Setiap bagian menyebut ID yang dimuatnya dan sumbernya.

| Bagian | Isi | ID yang dimuat | Sumber |
|---|---|---|---|
| Pembuka | Cara membaca, skema ID, status dokumen, baseline teknis nyata | §135 (diamandemen: Laravel 13.31.0 terpasang) | PRD lama, runtime |
| I. Produk | Ringkasan, masalah, peluang, visi, misi, north star, posisi, persona, JTBD, tujuan, non-tujuan, prinsip produk, prioritas mengikat, komitmen merek, merek, ATM dan rujukan | §1–§13, §133–§141 (alias), §144, §156 | PRD lama; PRODUCT.md; tujuan dan prinsip PRD NgodingPakeAI; DK-13–DK-16 |
| II. Aturan dan model domain | Model domain, Mountain ≠ Trail, karakteristik rute, kesulitan, MDPL, Route Fit tiga lapis, faktor, skor internal, label publik, explainability, audit, semantik keselamatan, status resmi dan cakupannya, hierarki otoritas, cuaca, laporan komunitas, kesegaran dan kepercayaan data | §14–§18, §24–§31, §38–§51, §59–§60, §95 | PRD lama; penyelarasan DK-10 |
| III. Pengalaman | Loop inti, alur pengguna (pendaki, pengelola, pakar wilayah), arsitektur informasi lima permukaan, prinsip UX, indeks fitur yang menunjuk `docs/fitur/` | §7, §19, §87–§93, §145 (diamandemen DK-05, DK-06; §145.2 oleh DK-21) | PRD lama; alur PRD NgodingPakeAI |
| IV. Fungsional ringkas | Satu paragraf per FR dengan tautan ke spesifikasi fitur | FR-01–FR-27 (§20–§23, §32–§37, §52–§58, §61–§62, §147–§152) | Kedua PRD |
| V. Non-fungsional | Keamanan (OWASP 2025), privasi (UU PDP sebagai rujukan), aksesibilitas (WCAG 2.2 lengkap), mobile-first dan adaptasi perangkat, tema tampilan, bahasa dan wilayah, keandalan, performa (Core Web Vitals), cache, jobs, observability dan kontrak telemetry, kebijakan AI | §75–§99 (§75, §84, §87, §88, §96 diamandemen), §157, §158 | PRD lama; NFR PRD NgodingPakeAI; §7.2; DK-17–DK-21 |
| VI. Data dan teknik | Model data (skema kanonis = migrasi), geospasial, indeks, foto, backup, arsitektur, service domain, testing, kesiapan produksi, versi, CI | §64–§74, §100–§104, §123–§126 | PRD lama; arsitektur per domain PRD NgodingPakeAI |
| VII. Cakupan dan peta jalan | MVP, MVP+, masa depan, dataset, kurasi, syarat terbit, program data gunung nasional, riset pengguna, tugas validasi UX dan target terukur, kriteria penerimaan, fase (fase lama + Fase 1–4 NgodingPakeAI + roadmap `ourplan.md` + tahap DK-23), DoD dan tingkat bukti L0–L4 | §105–§122, §127–§129, §159 (§108–§110 diamandemen) | Ketiga sumber; DK-22, DK-23 |
| VIII. Tata kelola | Governance, feature gate, registri BR, kunci keputusan, status PRD, governance record GR-01 dan GR-02, register konflik yang diputuskan | §130–§132, §142–§143, §146 | PRD lama; §8, §10 |
| Lampiran | Indeks ID; tabel paritas 146 bagian lama; daftar sumber riset | | |

**Bagian dan fitur baru:**

| ID | Isi | Asal |
|---|---|---|
| §147 FR-22 | Catatan pribadi pada daftar simpanan (hanya terlihat pemilik, tidak ikut dibagikan) | Fitur 03, DK-07 |
| §148 FR-23 | Tautan bagikan perbandingan: publik, read-only, bertoken, berkedaluwarsa; tidak pernah menulis ke simpanan siapa pun | Fitur 03, DK-07, HANDOVER §8 |
| §149 FR-24 | Pengingat perjalanan (email sebagai kanal awal; kanal lain lewat abstraksi yang sama dengan FR-26) | Fitur 04, DK-07 |
| §150 FR-25 | Target pribadi (jarak, tanjakan, frekuensi) dan catatan pendakian manual; self-comparison, tidak menaikkan level | Fitur 06/07, DK-07, R-015, BR-11 |
| §151 FR-26 | Verifikasi kode multi-kanal (email, WhatsApp, SMS) | DK-08, §7.4 |
| §152 FR-27 | Pencarian kalimat sederhana berbasis aturan, dengan batas yang dinyatakan jujur | Fitur 01, §86 |
| §153 | Izin pendakian resmi: produk menautkan sistem resmi (e-Rinjani, booking Semeru), tidak mereplikasi pemesanan | §7.5, non-goal §13 |
| §154 | Arsitektur operasional di Vercel: runtime, state (session dan cache di basis data), berkas di disk `supabase`, penjadwal lewat Vercel Cron, antrean, log, migrasi di CI, koneksi lewat pooler | DK-12, §7.7 |
| §155 | Pertumbuhan dan halaman publik (bagian Growth PRODUCT.md) | DK-02 |
| §156 | Merek: nama, pernyataan pemilik verbatim, tiga kata kerja dan satu fondasi, proposisi, tagline, suara, arsitektur branded house, logo dan gerbang verifikasinya | DK-13–DK-16 |
| §157 | Tema tampilan: Terang, Gelap, Ikuti sistem (default); dirender server; kontras diuji di kedua tema; peta diredupkan di tema gelap | DK-17, DK-18 |
| §158 | Bahasa dan wilayah: Indonesia sebagai sumber kebenaran, Inggris sebagai bahasa pertama tambahan, fallback `id`, URL `/en/`, `hreflang`, `noindex` untuk halaman belum diterjemahkan, QA istilah keselamatan, terjemahan konten bergaris keturunan | DK-20, DK-21, DK-23 |
| §159 | Program data gunung nasional: Tier A terkurasi, sumber per field, registri lisensi, dua keluarga status, empat fase, input admin di aplikasi | DK-22, DK-23 |

**Amandemen pada ID lama:** §1 dan §9 (pernyataan merek pemilik dan proposisi, DK-13, DK-14), FR-01 (§20: Google dan passkey), FR-06 (§33: DK-05), FR-08 (§35: jumlah peserta dan tanggal selesai), FR-11 dan §42 (§41–§42: status bahaya dan status akses sebagai dua keluarga terpisah, DK-22), FR-14 (§52: catatan manual), FR-16 dan FR-18 (§56, §58: input data gunung dengan sumber per field dan registri lisensi, DK-22), FR-21 (§145: DK-05, DK-06, catatan, tautan publik; §145.2: DK-21), §43 (DK-09), §55 (jalur offline masa depan lewat PMTiles; peredupan di tema gelap dan penyedia cadangan, DK-18), §75, §84, §87, §96 (§7.2), §87 dan §88 (orientasi, `@container`, `dvh`, safe area, DK-19), §98 (penjadwal lewat Vercel Cron), §108–§110 (dataset Tier A dan kurasi, DK-22), §123–§126 (kesiapan produksi, Supabase, CI/CD untuk Vercel), §135 (versi nyata; dependensi menurut DK-24).

---

## 6. Spesifikasi fitur (`docs/fitur/`)

Delapan berkas, satu per fitur NgodingPakeAI, dengan templat yang sama:

1. **Tujuan dan pertanyaan pengguna.**
2. **Posisi di loop dan permukaan** (§7, §145).
3. **Kriteria penerimaan** Given/When/Then, dari "Selesai bila" ditambah kriteria lama (§114–§120).
4. **Aturan yang berlaku:** ID R, BR, dan § yang mengikat fitur ini.
5. **Data dan sumber:** tabel nyata dari migrasi, bukan skema usulan.
6. **State:** kosong, memuat, galat, data belum lengkap.
7. **Status implementasi:** ada di kode (dengan rujukan berkas), belum ada, atau berbeda.
8. **Task NgodingPakeAI:** daftar task fitur ini, dipetakan ke kode yang sudah ada bila tugasnya greenfield.
9. **Keputusan dan konflik terkait:** DK dan K dari dokumen ini.

| Berkas | Fitur | FR utama |
|---|---|---|
| `01-jelajah-rute.md` | Jelajah Rute | FR-04, FR-05, FR-21, FR-27 |
| `02-detail-rute.md` | Detail Rute | FR-07, FR-11, FR-12 |
| `03-pertimbangkan.md` | Pertimbangkan | FR-06, FR-22, FR-23 |
| `04-perjalanan-persiapan.md` | Perjalanan dan Persiapan | FR-08, FR-09, FR-10, FR-24 |
| `05-kabar-kondisi.md` | Kabar dan Kondisi | FR-11, FR-12, FR-13 |
| `06-pendakian-progres.md` | Pendakian dan Progres | FR-14, FR-15, FR-25 |
| `07-akun-masuk.md` | Akun dan Masuk | FR-01, FR-02, FR-03, FR-26 |
| `08-konsol-pengelola.md` | Konsol Pengelola | FR-16–FR-20 |

Tema (§157) dan bahasa (§158) berlaku lintas fitur, jadi keduanya tidak mendapat berkas sendiri: setiap spesifikasi menyebutnya di "Aturan yang berlaku" bila layarnya terdampak. Fitur 07 memuat preferensi tema dan bahasa di akun; fitur 08 memuat input data gunung Tier A dengan sumber per field (§159).

---

## 7. Riset yang diintegrasikan

Seluruh sumber diakses 23 September 2026.

### 7.1 Pola aplikasi rujukan (dasar DK-05)

- **AllTrails Peak (Mei 2025):** rute kustom dengan smart routing AI, kondisi jalur prediktif dari 15 faktor lingkungan di atas peta, area offline, heatmap keramaian. Sumber: [TechCrunch](https://techcrunch.com/2025/05/12/alltrails-debuts-a-80-year-membership-that-includes-ai-powered-smart-routes), [AllTrails Press](https://www.alltrails.com/press/alltrails-expands-membership-offering-with-alltrails-peak).
- **Strava 2025–2026:** Route Saves dari berbagai permukaan, Off-Route Alerts, Route Builder mobile baru, fitur hiking Juni 2026 dengan Live Elevation, kontrol Map Visibility yang menyembunyikan titik awal dan akhir. Sumber: [Strava Press](https://press.strava.com/articles/strava-adds-new-features-for-hiking-making-the-outdoor-experience-more-discoverable-navigable-and-social), [Strava Help](https://support.strava.com/hc/en-us/articles/115000173384-Edit-Map-Visibility).
- **Simpulan untuk produk ini:** menyimpan banyak kandidat lalu membandingkan sedikit adalah pola yang dipakai rujukan; tiga kolom tetap terbaca di lebar 400 px (§88). Batas perbandingan menjadi 3; batas simpanan ditetapkan sebagai batas teknis di spesifikasi fitur 03, bukan batas produk.

### 7.2 Standar

- **WCAG 2.2:** sembilan kriteria baru, termasuk 2.4.11 Focus Not Obscured (AA), 2.5.7 Dragging Movements (AA), 2.5.8 Target Size Minimum 24×24 (AA), 3.2.6 Consistent Help (A), 3.3.7 Redundant Entry (A), 3.3.8 Accessible Authentication (AA). Proyek menargetkan 44×44 untuk kontrol utama. Sumber: [W3C WCAG 2.2](https://www.w3.org/TR/WCAG22/).
- **OWASP Top 10:2025** (final Januari 2026): kategori baru A03 Software Supply Chain Failures dan A10 Mishandling of Exceptional Conditions; SSRF dilebur ke Broken Access Control. Sumber: [OWASP](https://owasp.org/Top10/2025/0x00_2025-Introduction/).
- **Core Web Vitals:** LCP < 2,5 s, INP < 200 ms (menggantikan FID sejak Maret 2024), CLS < 0,1, dinilai pada persentil ke-75. Sumber: [corewebvitals.io](https://www.corewebvitals.io/core-web-vitals).
- **UU PDP No. 27/2022:** berlaku penuh Oktober 2024; notifikasi kegagalan pelindungan data tertulis paling lambat 3×24 jam. Sumber: [JDIH BPK](https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022).

### 7.3 Peta (dasar DK-06)

- Data nyata: jalur belum bergeometri (ARCHITECTURE.md "Yang belum ada"; PRODUCT.md "Evidence on Hand").
- PMTiles dan basemap Protomaps memungkinkan peta vektor self-hosted dan offline tanpa tile server; jalur yang sah untuk "offline di masa depan" karena §55 melarang offline memakai ubin OSM standar. Sumber: [Protomaps](https://docs.protomaps.com/pmtiles/maplibre), [maplibre-offline-pmtiles](https://github.com/makinacorpus/maplibre-offline-pmtiles).

### 7.4 Autentikasi dan verifikasi kode (dasar DK-08)

- **NIST SP 800-63B-4** (final Juli 2025): OTP lewat PSTN/SMS adalah satu-satunya restricted authenticator; email dan VoIP tidak boleh dipakai sebagai autentikator out-of-band. Sumber: [NIST](https://csrc.nist.gov/pubs/sp/800/63/b/4/final).
- **WhatsApp Business Platform:** OTP dikirim sebagai authentication template, ditagih per pesan terkirim sejak Juli 2025; Indonesia termasuk pasar authentication-international. Hanya platform resmi (Cloud API atau BSP) yang dipakai. Sumber: [Meta for Developers](https://developers.facebook.com/documentation/business-messaging/whatsapp/pricing/authentication-international-rates/).
- **Passkey first-party Laravel:** `laravel/passkeys` (Laravel 11–13) dan `@laravel/passkeys`. Proyek memakai autentikasi Breeze/Volt, bukan Fortify, sehingga integrasinya langsung lewat paket tersebut. Sumber: [Laravel News](https://laravel-news.com/laravel-introduces-first-party-passkey-authentication-support).
- **Penyedia:** Twilio Verify (SMS, WhatsApp, voice, email, TOTP; 20–40% pengguna di Indonesia memilih WhatsApp), Citcall (penyedia lokal, SMS dan verifikasi missed call), Vonage, Infobip. Sumber: [Twilio](https://www.twilio.com/docs/verify/whatsapp), [EngageLab](https://www.engagelab.com/blog/otp-service-provider).

**Desain yang diusulkan untuk FR-26:**

| Kebutuhan | Metode | Alasan |
|---|---|---|
| Masuk utama | Passkey, dan email + kata sandi | Passkey tahan phishing dan memenuhi 3.3.8 |
| Daftar cepat | Google (Laravel Socialite) | Permintaan pemilik (DK-07) |
| Bukti kepemilikan email | Kode atau tautan lewat email | Membuktikan kepemilikan alamat, bukan faktor autentikasi (NIST) |
| Verifikasi nomor dan pemulihan akun | Kode WhatsApp, dengan SMS sebagai cadangan | WhatsApp dominan di Indonesia; SMS restricted, jadi bukan satu-satunya jalan |

Aturan kode: 6 digit, kedaluwarsa paling lama 10 menit, sekali pakai, disimpan ter-hash, dibatasi laju dan jumlah percobaan, `autocomplete="one-time-code"`, tempel diizinkan. Semua kanal lewat Laravel Notifications. Nomor telepon adalah data pribadi: persetujuan, minimisasi, dan penghapusan mengikuti kebijakan data pribadi yang diperbarui secara terpisah. Kunci penyedia hanya di `.env`.

### 7.5 Konteks Indonesia

- **Izin pendakian 2026:** Rinjani wajib e-Rinjani/e-Simaksi dengan kuota per jalur dan penutupan 1 Januari–31 Maret 2026; Semeru memakai booking online dengan kuota 200 per hari. Sumber sekunder: [panduan SIMAKSI 2026](https://blog.kumbaya.id/2026/06/05/panduan-simaksi-online-pendakian-2026/). **UNVERIFIED:** URL resmi `esimaksi.rinjaninationalpark.id` belum terverifikasi dan domain `bookingsemeru.bromotenggersemeru.org` gagal di-resolve saat dicek ulang 23 September 2026 (laporan sintesis riset); alamat resmi dipastikan ke Balai TN sebelum ditautkan di §153. Status akses resmi terkini diatur SE.4/KSDAE/SKSDAE/KSA.02.02/B/8/2026 (Kemenhut, 31 Agustus 2026).
- **BMKG:** prakiraan per kelurahan/desa (adm4), 3 hari, interval 3 jam, diperbarui 2 kali sehari, 60 permintaan per menit per IP, atribusi wajib. Sesuai dengan kode (`trails.weather_adm4_code`) dan BR-15. Sumber: [Data Terbuka BMKG](https://data.bmkg.go.id/prakiraan-cuaca/).

### 7.6 Stack

- **Laravel 13** dirilis 17 Maret 2026, PHP ≥ 8.3, passkey, AI SDK, dukungan keamanan sampai Q1 2028. Terpasang 13.31.0. Sumber: [Laravel](https://laravel.com/docs/13.x/releases).
- **Livewire 4** dirilis 14 Januari 2026 (komponen single-file bersintaks sama dengan Volt). Proyek memakai 3.8.9 dan suite 933 test lulus; upgrade adalah keputusan terpisah (R-020). Klaim sumber sekunder bahwa hanya Livewire 4 yang kompatibel dengan Laravel 13 tidak dipakai karena dibantah runtime. Sumber: [Livewire](https://livewire.laravel.com/docs/4.x/upgrading).
- **ADR:** format MADR di dalam repo untuk keputusan arsitektur; dipakai di sub-proyek 2. Sumber: [MADR](https://adr.github.io/madr/).

### 7.7 Deploy di Vercel (dasar DK-12)

- **Panduan resmi Vercel** (terbit 3 September 2026): Laravel berjalan di container-backed Function dengan FrankenPHP (`Dockerfile.vercel`, `Caddyfile`, `vercel.json`). Session dan cache di penyimpanan bersama (basis data atau Redis); migrasi dijalankan di CI, bukan saat container start; unggahan di object storage; `LOG_CHANNEL=stderr`; antrean lewat Vercel Queues; penjadwal lewat Vercel Cron yang memanggil endpoint terautentikasi; jangan menjalankan `php artisan optimize` saat build (config cache membekukan variabel lingkungan); `.env` tidak ikut image; `APP_KEY` stabil per lingkungan. Contohnya memakai PHP 8.4; versi lain lewat image Dockerfile, ketersediaan image PHP 8.5 diverifikasi saat deploy. Batas waktu, memori, dan ukuran image tidak didokumentasikan di panduan. Sumber: [Vercel KB](https://vercel.com/kb/guide/laravel-php-with-docker).
- **Runtime komunitas `vercel-php`:** mendukung PHP 7.4–8.5 (`vercel-php@0.9.0` untuk PHP 8.5); bukan runtime resmi Vercel. Sumber: [vercel-community/php](https://github.com/vercel-community/php).
- **Livewire di serverless:** kasus Livewire 2 di Bref (2022) rusak karena CDN membuang header `X-Livewire` dan `X-Csrf-Token` (galat 419 dan hidrasi korup). Header ini menjadi item uji wajib setelah deploy. Sumber: [livewire/livewire #5031](https://github.com/livewire/livewire/discussions/5031).
- **Supabase dari serverless:** transaction pooler (port 6543) tidak mendukung prepared statement sisi server, sehingga koneksi memakai `PDO::ATTR_EMULATE_PREPARES => true` atau session pooler. Klaim dukungan pooling bawaan Laravel 13.17 diverifikasi di dokumentasi resmi sebelum dipakai. Sumber: [Supabase Docs](https://supabase.com/docs/guides/database/connecting-to-postgres).
- **Keadaan kode yang relevan:** disk `supabase` sudah dikonfigurasi di `config/filesystems.php` dan dipakai `ConditionReportForm`; session dan cache sudah memakai driver `database`; penjadwal punya dua tugas di `routes/console.php` (`weather:refresh` pukul 06:00 dan 18:00, `data:freshness-check` pukul 07:00, zona waktu aplikasi UTC).

### 7.8 Merek dan platform (dasar DK-13–DK-24)

Catatan bersumber di `docs/riset/2026-09-23-konsolidasi/`; setiap klaim di sana membawa URL dan tanggal akses. Ringkasan yang mengikat keputusan:

| Berkas | Isi yang dipakai |
|---|---|
| `strategi_merek.md` | Delapan pilar diringkas menjadi tiga kata kerja dan satu fondasi; tagline dan contoh yang ditolak; suara "Anda" (DK-14, DK-15) |
| `identitas_visual_logo.md` | Tiga konsep logo dengan perhitungan celah 16 px: A lolos (2,4–2,47 px), B gagal (1,75 px), C marginal (1,5 px) (DK-16) |
| `mode_gelap_perangkat.md` | `wire:navigate` di Livewire 3.8.9 mengganti atribut `<html>` yang tidak dikirim server, sehingga tema harus dirender server; OpenTopoMap berstatus "survival mode" (DK-17–DK-19) |
| `multibahasa.md` | Inggris menjangkau langsung pendaki Australia, Singapura, Inggris Raya, dan Irlandia, dan paling mungkin menjadi bahasa perantara bagi pengunjung Rinjani dari 78 negara (Balai TNGR, Juni 2023); Google menerima segmen URL terjemahan maupun tidak dan menganjurkan agar pengguna tidak dialihkan otomatis antarversi bahasa (DK-20, DK-21) |
| `data_gunung_indonesia.md` | Katalog terbuka terbesar (Wikidata 28.125 item dengan elevasi hanya 1,1%, OSM 23.568 puncak, GeoNames) berasal dari satu hulu NGA GNS; tidak ada daftar resmi terbuka yang lengkap; garis merah ODbL (DK-22) |
| `laporan-sintesis.md` | D1–D12 beserta opsi, untung-rugi, dan 78 temuan repo |
| `riset-nama-merek.md` | Tujuh kriteria nama, metode verifikasi (RDAP registri dengan domain kontrol, PDKI, WIPO, toko aplikasi, handle), daftar pendek (DK-13) |
| `riset-nama-usulan-pemilik.md` | Usulan pemilik dinilai dengan rubrik yang sama: VERTIKA 5 dari 12, gugur karena aplikasi "Vertika" di App Store Indonesia; GOURVA 7 dari 12, cadangan bersyarat; urutan PAMARGI 12, PARWATA 10, LAMPAH 9, JALURA 9 tidak berubah (DK-13) |
| `riset-nama-internasional.md` | Laporan akhir riset nama internasional (62 kandidat baru, 13 diverifikasi mendalam, rubrik v2 dan v3 berdampingan): TALAMPI memimpin tipis (v3 21 dari 26, v2 10 dari 12), LINTAMO 20, TANLIMO 19; ketiganya diusulkan masuk uji dengar-lalu-eja dengan pengguna nyata (K1b), TUMPALO cadangan; PAMARGI 14 dan tertahan untuk arah internasional, tetap terbaik bila produk hanya untuk Indonesia; langkah sebelum aset final: K1b, penutur asli, Konsultan KI Indonesia, UE, dan AS, Madrid lewat DJKI, domain dan handle @[nama]app pada hari keputusan (DK-13) |
| `nama-internasional/lanskap-dan-strategi.md` | Stem jenuh yang dihindari (trail, peak, summit, hike, route, fit, ready, yama, berg; lokal: tapak, muncak, gunung, mdpl); pendaftaran internasional lewat Madrid bergantung 5 tahun pada merek dasar di DJKI, jadi nama yang deskriptif dalam bahasa Indonesia berisiko untuk semua negara; usulan rubrik v3 berbobot. **Pesaing konsep terdekat: muncak.id** (perbandingan jalur, pencocokan jalur dengan profil, jalur luar negeri; dicek ulang 23 September 2026 15.20 UTC), jadi pembeda produk harus dinyatakan lewat penjelasan alasan, hierarki sumber resmi, dan UNKNOWN yang jujur (bahan §9) |

---

## 8. Register konflik dan diskrepansi

**Konflik antarsumber**

| # | Konflik | Resolusi | Dasar |
|---|---|---|---|
| K1 | Banding 3 (baru) vs 5 (lama, kode) | DK-05 | §7.1 |
| K2 | Skor kecocokan tampil ke pengguna | Label + alasan per faktor, tanpa angka | R-004, R-005, BR-09 |
| K3 | Label "cocok/cukup/kurang" | COCOK / PERLU PERSIAPAN / KURANG COCOK | R-004 |
| K4 | Skor kesiapan berbasis kebugaran | READY / NEEDS_PREPARATION / NOT_RECOMMENDED + faktor yang kurang; bukan penilaian medis | R-014, §39, §40 |
| K5 | Status Dibuka/Ditutup/Dibatasi/Waspada tanpa UNKNOWN | OPEN / RESTRICTED / CLOSED / UNKNOWN; "waspada" menjadi peringatan kondisi | R-006, BR-07 |
| K6 | Hierarki dengan "Pakar" | DK-09 | Kode SourceType |
| K7 | Peta sebagai antarmuka utama | DK-06 | §7.3 |
| K8 | Basi jika > 7 hari (universal) | Ambang per sumber | §59, §93 |
| K9 | Laporan "cerita/tips" + reaksi jempol | DK-11 | §145.1 |
| K10 | Masuk dengan Google | DK-07 + DK-08 | §7.4 |
| K11 | Pengingat email/notifikasi | DK-07 (FR-24); syarat operasional: driver mail dan scheduler produksi | HANDOVER §9 |
| K12 | Jumlah peserta dan tanggal selesai | DK-07 (amandemen FR-08); penambahan kolom aditif | §35 |
| K13 | Target bulanan | DK-07 (FR-25) | R-015, BR-11 |
| K14 | Catat pendakian manual | DK-07 (FR-25) | BR-11 |
| K15 | Redis wajib | Ditunda sampai beban menuntut | §99 |
| K16 | Skema greenfield (UUID, ±15 tabel baru) | Skema kanonis = migrasi yang sudah berjalan | R-021, HANDOVER §6 |
| K17 | Cuaca per gunung | Area referensi per jalur | BR-15, kode |
| K18 | Biru = informasi | Biru = komunitas | DESIGN.md, §92 |
| K19 | Matriks `role_permissions` | Policy/Gate + enum peran | §76 |
| K20 | Pencarian kalimat sederhana | FR-27 berbasis aturan dengan batas jujur; implementasi sekarang diperbaiki (F3) | §86 |
| K21 | Tautan bagikan tanpa akun | DK-07 (FR-23) | §77, §145.1 |
| K22 | Catatan pribadi | DK-07 (FR-22) | §145.1 |
| K23 | Detail jalur tanpa geometri | Pesan "data jalur belum lengkap" | §110, R-022 |
| K24 | Penjadwal lewat `schedule:work`/cron server (HANDOVER §9, ARCHITECTURE) vs Vercel tanpa proses permanen | Vercel Cron memanggil endpoint terautentikasi per tugas terjadwal | §7.7 |
| K25 | Deployment "Docker + hosting terkelola atau VPS" (PRD NgodingPakeAI §7) dan §124–§126 lama | Vercel (DK-12); §123–§126 diamandemen | DK-12 |
| K26 | Koneksi basis data dari banyak instance serverless | Supabase pooler + emulasi prepared statement | §7.7 |
| K27 | Header Livewire bisa terbuang oleh lapisan edge | Uji `X-Livewire` dan `X-Csrf-Token` setelah deploy | §7.7 |
| K28 | Config cache saat build membekukan variabel lingkungan | Tidak di-cache saat build | §7.7 |
| K29 | "Seluruh antarmuka berbahasa Indonesia" (`PRODUCT.md:101`) vs bahasa Inggris sebagai bahasa tambahan | Indonesia tetap utama dan sumber kebenaran; Inggris ditambahkan lewat GR-03 | DK-20 |
| K30 | `DESIGN.md` "Mode gelap belum diputuskan" vs arahan pemilik menambah mode gelap | Ditutup: Desain A, default "Ikuti sistem" | DK-17 |
| K31 | Tiga nama produk di runtime: `APP_NAME` "Jalur Naik", manifest "Rencana Pendakian" dan "Pendakian" (F11) | Diseragamkan ke nama terpilih setelah DK-13 | DK-13 |
| K32 | "Apakah sudah layak berangkat" (`welcome.blade.php:8`, `PRODUCT.md:25`) vs readiness sebagai decision support | "Apa yang masih kurang sebelum berangkat" | R-014, DK-14 |
| K33 | Godaan memetakan Level PVMBG ke OPEN/RESTRICTED/CLOSED demi satu tampilan | Dua keluarga status berdampingan dengan sumber dan waktu; tidak pernah dipetakan | R-012, DK-22 |
| K34 | Jumlah gunung seed: 16 (`PRODUCT.md:116-120`) vs 17 (brief) | PERLU KLARIFIKASI lewat query basis data atas izin | R-023 |

**Diskrepansi dokumen dan runtime** (CLAUDE.md §1.11)

| # | Dokumen | Runtime | Resolusi |
|---|---|---|---|
| D-A | Laravel 13.32.0 (§135) | 13.31.0 (`composer show`) | PRD mencatat versi terpasang; upgrade butuh persetujuan (R-020) |
| D-B | URL publik `/gunung/{slug}/via/{trail}` (§145.2) | `/pendakian/{trail:slug}` | §145.2 tetap target; migrasi dengan 301 bersamaan dengan peluncuran `/en/` (DK-21) |
| D-C | Seluruh antarmuka berbahasa Indonesia (`PRODUCT.md:101`) | `fallback_locale` = `en` (`config/app.php:83`) dan `lang/en/recommendation.php` berisi teks Indonesia (F8) | Teks dipindah ke `lang/id`, lalu fallback diubah ke `id` sebelum bahasa Inggris aktif (DK-20) |

**Temuan kode yang dibawa ke sub-proyek 4**

| # | Temuan | Status |
|---|---|---|
| F1 | Fokus keyboard hilang ke `<body>` setelah aksi Livewire karena `x-ui.button` menonaktifkan dirinya | Terukur di peramban; berlaku di seluruh aplikasi |
| F2 | Peta Jelajah memakai koordinat gunung sebagai titik jalur | Diputuskan DK-06 |
| F3 | Pencarian kalimat memakai 5 alias kota dan membuang teks pencarian | Masuk FR-27 |
| F4 | PRD kanonis tertimpa PRD NgodingPakeAI tanpa governance record | Diselesaikan oleh dokumen ini (GR-02) |
| F5 | Logo header bawaan Laravel | Menunggu nama (DK-13) dan aset logo yang lulus gerbang DK-16 |
| F6 | Di ponsel, daftar jalur mulai jauh di bawah layar pertama | Redesain Jelajah |
| F7 | Baki simpanan `sticky` berpotensi menutupi fokus (WCAG 2.4.11) | Hipotesis; diverifikasi di peramban sebelum diklaim |
| F8 | `lang/en/recommendation.php` berisi teks Indonesia sementara `fallback_locale` = `en` | Terverifikasi; diperbaiki di fase nol sebelum bahasa Inggris aktif (DK-20) |
| F9 | Meta description publik "apakah sudah layak berangkat" (`welcome.blade.php:8`) bertentangan dengan R-014 | Terverifikasi; K32 |
| F10 | Nama VELORA berisiko (DeFi eks-ParaSwap, merek lokal, domain terdaftar) | Selesai: pemilik menolak nama itu (DK-13) |
| F11 | Manifest PWA memakai `app-512.png` untuk `any` dan `maskable`; nama manifest berbeda dari `APP_NAME` (`ProgressiveWebAppController.php:17-18, 32-33`) | Terverifikasi; ikon maskable dengan zona aman dibuat bersama aset logo (DK-16), nama diseragamkan (K31) |
| F12 | Manifest `'orientation' => 'portrait'` (`ProgressiveWebAppController.php:22`) | Terverifikasi (penguncian); diubah ke `any` (DK-19) |
| F13 | `ColourContrastTest::token()` memakai `preg_match`, jadi hanya token pertama yang dibaca | Terverifikasi; diperbaiki sebelum token gelap ditambahkan (DK-17) |

---

## 9. Verifikasi (kriteria selesai sub-proyek 1)

| # | Pemeriksaan | Lulus bila |
|---|---|---|
| V1 | Skrip mengekstrak setiap `§N`, `BR-N`, `FR-N` dari kode, test, migrasi, config, dan dokumen | Setiap ID ada di indeks PRD; 0 hilang |
| V2 | Tabel paritas 146 bagian lama | Setiap bagian berstatus dipertahankan, dipadatkan, digabung, atau diamandemen; 0 hilang |
| V3 | Rujukan CLAUDE.md (§130, §132, §145, §146, PRODUCT.md, DESIGN.md) | Semua valid |
| V4 | `docs/fitur/` | 8 berkas, templat lengkap; 140 task NgodingPakeAI terpetakan |
| V5 | Antislop copywriting pada teks baru | 0 em dash, tanpa buzzword, bahasa Indonesia |
| V6 | GR-02 dan GR-03 | 7 butir §130 terisi di masing-masing; keputusan delegasi ditandai |
| V7 | Suite test | Tetap hijau (test memindai berkas terlacak, misalnya SecretsNotInRepoTest) |
| V8 | Tinjauan pemilik | PRD gabungan disetujui sebelum di-commit |
| V9 | Merek dan platform | DK-13–DK-24 tercermin di §156–§159, amandemen ID lama, dan DESIGN.md; slot [NAMA] terisi nama pilihan pemilik; "VELORA" hanya muncul di catatan riset dan log keputusan |

---

## 10. Governance record GR-02 (draf)

- **Requirement changed:** PRD ditulis ulang menjadi PRD inti + 8 spesifikasi fitur; PRODUCT.md dilebur; ID § dipertahankan; §147–§155 dan FR-22–FR-27 ditambahkan; FR-01, FR-06, FR-08, FR-14, FR-21, §43, §55, §75, §84, §87, §96, §98, §123–§126, §135 diamandemen; R-009 diamandemen. Perubahan merek dan platform dicatat terpisah di GR-03.
- **Reason:** PRD kanonis tertimpa PRD NgodingPakeAI tanpa governance record; pemilik meminta riset, perbandingan, dan penggabungan keduanya beserta dokumen yang bertumpuk.
- **Impact:** satu sumber kebenaran produk; batas MVP melebar dengan lima kemampuan (DK-07, DK-08); perbandingan menjadi maksimal 3.
- **Affected modules:** Pertimbangkan, Akun, Perjalanan, Progres, Jelajah, Kabar (status UNKNOWN tetap), dokumentasi.
- **Security impact:** permukaan baru: tautan bagikan publik (FR-23), OAuth Google dan passkey, verifikasi kode (FR-26). Masing-masing mendapat model ancaman di spesifikasi fiturnya.
- **Data impact:** tidak ada perubahan skema di sub-proyek ini; fitur baru kelak membutuhkan migrasi aditif yang dijalankan terhadap Supabase atas izin (R-019).
- **Scope impact:** feature gate §131 dipenuhi: catatan dan tautan meningkatkan route selection; pengingat dan detail rencana meningkatkan preparation; akun dan verifikasi meningkatkan data trust; target dan catatan manual meningkatkan continuity. Keputusan DK-05, DK-06, DK-09 diambil atas delegasi pemilik.

### 10.1 Governance record GR-03 (draf): merek dan platform

- **Requirement changed:** §156–§159 ditambahkan; §1, §9, §41, §42, §55, §56, §58, §87, §88, §108–§110, §135, §145.2 diamandemen; komitmen "Seluruh antarmuka berbahasa Indonesia" menjadi "Bahasa Indonesia sebagai bahasa utama dan sumber kebenaran, Inggris sebagai bahasa tambahan pertama"; `DESIGN.md` menutup keputusan mode gelap.
- **Reason:** arahan pemilik 23 September 2026 (branding sebagai landasan, semua perangkat, mode gelap, bahasa tambahan, data gunung seluruh Indonesia) dan keputusan D1–D12 atas laporan sintesis riset.
- **Impact:** identitas merek tunggal dengan pernyataan pemilik sebagai arah; tampilan mengikuti preferensi sistem; pendaki asing terlayani lewat `/en/`; katalog gunung tumbuh dengan sumber per field, bukan impor massal.
- **Affected modules:** layout dan navigasi, token warna dan `ColourContrastTest`, manifest PWA, peta, routing publik dan SEO (`hreflang`, canonical, sitemap), berkas `lang/`, konsol pengelola (input data gunung), model status resmi.
- **Security impact:** cookie `tema` tanpa enkripsi dengan daftar putih nilai; locale divalidasi terhadap daftar yang didukung, tidak pernah dipakai sebagai path; tidak ada redirect otomatis berbasis IP atau bahasa; impor sumber data hanya ke host tetap dengan teks kueri tersimpan.
- **Data impact:** skema bertahap menurut DK-23; setiap migrasi disetujui, diuji di PostgreSQL, dan status migrasinya diverifikasi di Supabase atas izin (R-019, R-024). Lisensi per sumber tercatat; OSM tidak masuk katalog.
- **Scope impact:** feature gate §131: mode gelap dan adaptasi perangkat meningkatkan keterpakaian di lapangan (preparation dan field experience); bahasa Inggris memperluas route selection ke pendaki asing; program data meningkatkan data trust dan route selection. Tidak ada monetisasi atau kompetisi sosial yang ditambahkan (R-021).

---

## 11. Teks amandemen R-009 yang diusulkan

**Statement.** Prioritas sumber/status adalah: OFFICIAL > ADMIN VERIFIED > ACCREDITED EXPERT > COMMUNITY > AI. ACCREDITED EXPERT adalah pemegang kredensial Ahli yang masih berlaku dari badan resmi, dan kewenangannya terbatas pada kawasan kerjanya.

**Scope** dan **Invariant** tidak berubah.

---

## 12. Risiko

| Risiko | Mitigasi |
|---|---|
| PRD tetap panjang | PRD inti ringkas; rincian di spesifikasi fitur; indeks ID |
| Nomor § tidak berurutan menurut halaman | Aturan ID dijelaskan di pembuka; indeks di lampiran |
| Keputusan delegasi tidak sesuai selera pemilik | Ditandai di GR-02; dapat dibatalkan tanpa biaya dokumen |
| PRD di server NgodingPakeAI tetap versi lama (CLI tidak punya perintah ubah plan) | PRD repo adalah kanonis; setiap task dipetakan ke spesifikasi fitur |
| Fitur baru butuh akun penyedia dan kunci (WhatsApp, SMS, Google) | Di luar sub-proyek ini; kunci hanya di `.env`; akses dan biaya diputuskan pemilik |
| Vercel bukan platform bawaan Laravel: panduan container baru terbit, runtime PHP komunitas, batas waktu/memori/cron per paket belum terdokumentasi | Sub-proyek 5 memverifikasi di lingkungan preview sebelum produksi: header Livewire, cold start, cron, unggahan, koneksi pooler |
| Nama merek belum dipilih, sehingga wordmark, logo, manifest, dan `APP_NAME` tertahan | Dokumen memakai slot [NAMA]; pekerjaan yang tidak bergantung nama (mode gelap, perangkat, F8, F13) tetap berjalan |
| Nama terpilih ternyata bentrok setelah pemeriksaan lanjutan (PDKI fonetik, pendapat Konsultan KI) | Aset final baru dibuat setelah gerbang verifikasi nama lulus; bukti pemeriksaan disimpan bertanggal |
| Setiap bahasa tambahan menggandakan biaya QA istilah keselamatan | Satu bahasa tambahan dulu; halaman belum diterjemahkan `noindex`; teks Indonesia tetap sumber kebenaran |
| Regresi visual tema gelap tidak tertangkap test PHP | Test paritas token dan kontras di kedua tema (setelah F13); alat visual regression diputuskan terpisah (DK-24) |
| Program data bergantung pada izin pihak luar (Balai TN, PVMBG, BIG) | Fase 1 memakai sumber terbuka berlisensi jelas; otomatisasi status hanya setelah izin tertulis |

---

## 13. Langkah berikut

1. Pemilik memilih nama merek (DK-13) dan meninjau amandemen §2.1.
2. Rencana implementasi diperbarui: rentang indeks §1–§159, bagian §156–§159 dan amandemennya, tugas `DESIGN.md` (arah merek, penutupan mode gelap), GR-03, verifikasi V9.
3. Rencana dieksekusi per tugas dengan subagen: indeks ID dan tabel paritas → PRD bagian I–VIII → `docs/fitur/` × 8 → amandemen R-009 → `DESIGN.md` → rujukan CLAUDE.md → HANDOVER → arsip `ourplan.md` → verifikasi V1–V9.
4. Sub-proyek 2 (kerapian repo) dan tahap pertama sub-proyek 6 (fase nol tanpa skema) mendapat desain tersendiri.
