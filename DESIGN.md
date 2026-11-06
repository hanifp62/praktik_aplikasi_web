# Arah desain

Berkas ini memegang arah; `.claude/skills/antislop` menyaringnya. Arah adalah milik
pemilik produk, saringan tidak pernah menciptakannya.

Bagian **Arah** di bawah adalah kata-kata pemilik produk, disalin bukan ditafsirkan.
Bagian **Turunan** adalah simpulan yang ditarik agen dari arah itu, ditandai terpisah
supaya pemilik dapat membetulkannya.

---

## Arah (dari pemilik produk)

**Nama merek:** **Memount**. Kata-kata pemilik, 24 September 2026: "tetapkan Memount
sebagai nama brand app web ini", dengan asal-usul "kepanjangan dari mendaki mount (gunung)".
Pernyataan merek pemilik (23 September 2026) tetap berlaku dengan nama ini: "Memount adalah
platform perjalanan gunung yang dipersonalisasi, yang menghubungkan discovery, Route Fit,
preparation, readiness, field experience, trust, community, dan progression dalam satu
perjalanan utuh."

**Dunia visual:** editorial utilitarian.

**Maksud produk,** dalam kata-katanya sendiri: aplikasi web gunung yang memadukan
aplikasi web populer dengan Strava dan Traveloka, ditelaah dari semua aspek sistem,
UI/UX, enterprise, startup, dan profesional.

**Rujukan yang disebut:** AllTrails, Strava, Traveloka. Diambil pola perilakunya
(ATM: amati, tiru, modifikasi), bukan rupanya.

**Tuntutan mutu:** akurasi, keandalan, keamanan, dan pengalaman pengguna, dengan UX dan
HCI sebagai titik berat.

---

## Turunan (ditarik agen, silakan dibetulkan)

Bahasa desain v3 dari prototype pemilik (`docs/desain/prototype/memount-prototype.html`, gambar acuan di
`docs/desain/prototype/acuan/`), diturunkan spec v3 bagian 8 dan dibangun di gelombang 1. Nilainya tinggal di token dan
kode; bagian ini menjelaskan peran dan alasannya, beserta test yang menjaganya.

### Pembacaan desain dan tiga dial

Reading this as: aplikasi perencanaan pendakian untuk pendaki pemula Indonesia di ponsel, bahasa visual editorial
utilitarian yang hangat (kertas, hijau hutan, data sebagai visual).

| Dial | Nilai | Dasarnya |
|---|---|---|
| **ENERGY** | **2** | Mode Operate: halaman membantu merencanakan, bukan membujuk. Satu aksen dan hierarki lewat bobot huruf 800 memberi cukup suara tanpa sapaan keras. |
| **RHYTHM** | **2** | Komposisi mengikuti isinya: status, angka, dan narasi tidak berbentuk sama, tetapi variasi tidak dikejar. |
| **MOTION** | **2** | Gerak hanya dari daftar tertutup di bagian Gerak: tekan, buka lembar dan dialog, toast, dan umpan balik keadaan. Tidak ada animasi masuk dan tidak ada gerak berulang. |

Mode lapangan (Mendaki, W6): ENERGY 1, MOTION 1; gerak hanya untuk perubahan status.

ENERGY dan MOTION naik dari 1 ke 2 mengikuti spec v3 bagian 8.9: prototype pemilik memakai tekan yang menyusut, lembar
bawah yang naik, dan toast urungkan, dan ketiganya membawa umpan balik yang tidak dibawa teks.

### Motif identitas

Garis rute berujung cincin pos, dari tanda "Jalur Berpos" (konsep A, keputusan pemilik D4): satu garis monoline yang
mendaki lewat belokan dan berakhir pada cincin pos. Motif ini berulang sebagai profil elevasi, garis jalur di peta,
bilah langkah, penanda pos bernomor, dan tiga glyph domain pilot. Tidak ada gunung pada tanda: Trail adalah unit
keputusan (R-001, R-002).

Tanda dan wordmark ada di `resources/js/components/brand/` (`Mark`, `Lockup`), memakai `currentColor` dengan token
`logo-mark` dan `logo-type`. Status aset: sketsa yang sudah tampil di produk, belum aset final; gerbang DK-16 (audit
kemiripan, penelusuran PDKI, uji pemahaman 5 sampai 8 pendaki, persetujuan pemilik) belum dilewati.

### Palet dan token

Token menyebut peran, nilainya tinggal di `resources/css/app.css`, dan blok `@theme` di berkas yang sama memetakannya ke kelas
bernama. Tidak ada warna mentah, `bg-white`, `text-black`, atau `dark:` di komponen (`InertiaLayoutTest`,
`SemanticTokenTest`).

| Peran | Kelas | Terang | Gelap |
|---|---|---|---|
| Kanvas | `bg-canvas` | `#F6F3EC` | `#0F1311` |
| Permukaan (kartu, lembar, bilah) | `bg-surface` | `#FFFFFF` | `#1B221E` |
| Sumur (kontrol segmen, stepper) | `bg-surface-sunken` | `#EEEAE1` | `#151A17` |
| Permukaan kebalikan (toast) | `bg-surface-inverse`, `text-inverse` | `#1D2320` / `#FFFFFF` | `#F2EFE8` / `#0F1311` |
| Garis dekoratif | `border-subtle` | `#E2DDD2` | `#2D3731` |
| Batas kontrol, sakelar mati | `border-control`, `bg-control` | `#8C8579` | `#7F8B84` |
| Teks utama, kedua, redup | `text-primary`, `text-secondary`, `text-muted` | `#1D2320`, `#4E5551`, `#626965` | `#F2EFE8`, `#B7BFB9`, `#9AA39D` |
| Isian tombol merek, tekan | `bg-brand-700`, `hover:bg-brand-800` | `#234F32`, `#16361F` | `#8FD3A0`, `#A6DDB3` |
| Teks di atas isian merek | `text-on-brand` | `#FFFFFF` | `#0F2418` |
| Merek sebagai latar depan | `text-brand-ink` | `#234F32` | `#8FD3A0` |
| Latar lembut merek (tab aktif, chip) | `bg-brand-100`, `text-brand-900` | `#E4EEE6`, `#1E5E35` | `#1D3326`, `#B5E4C1` |
| Label Cocok | `bg-fit-cocok`, `text-fit-cocok-teks` | `#DDEEE2` / `#1E5E35` | `#1D3326` / `#B5E4C1` |
| Label Perlu persiapan | `bg-fit-perlu`, `text-fit-perlu-teks` | `#FBEBCF` / `#7A4E0B` | `#3A2C12` / `#F0C27A` |
| Label Kurang cocok | `bg-fit-kurang`, `text-fit-kurang-teks` | `#F7DFDA` / `#8E2F22` | `#3D1B16` / `#F2A597` |
| Belum dinilai (UNKNOWN) | `bg-fit-belum`, `text-fit-belum-teks` | `#ECE9E3` / `#4E5551` | `#252D28` / `#B7BFB9` |
| Status Buka, Dibatasi, Tutup, Belum diketahui | `bg-status-buka` dan seterusnya | `#2F7A48`, `#B7791F`, `#8E2F22`, `#808682` | `#6FBF84`, `#F0C27A`, `#E0806C`, `#8A958E` |
| Komunitas | `community-50`, `-500`, `-900` | `#F2F6FC`, `#5F82B8`, `#24508C` | `#18222F`, `#6F93C9`, `#9DBBE6` |
| Aksen pos | `bg-aksen-pos` | `#E8B04B` | `#E8B04B` |

Koreksi terukur atas prototype (DK-31, ditambah satu di gelombang 1): batas kontrol `#8C8579`, garis komunitas
`#5F82B8`, teks redup `#626965`, dan status belum diketahui `#808682`, karena `#8A8F8B` hanya 2,97:1 di kanvas. Rasio
dihitung dengan rumus WCAG 2.x, bukan ditaksir: teks 4,5:1 dan non-teks 3:1 di kedua tema (`ColourContrastTest`).

**Satu aksen.** Kuning pos hanya 1,76:1 di kanvas, jadi tidak pernah membawa arti sendirian: ia selalu menemani bentuk
dan label. Sejak W3 aksen ini menandai pos berkemah dan tujuan di profil elevasi, peta jalur, dan daftar pos rincian
jalur, selalu bersama lingkaran bernomor dan kata jenis posnya. Bilah langkah (W4 dan W5) dan cincin fokus lapangan
gelap (W6) menyusul. Tanda merek tetap `currentColor` dengan token `logo-mark` sampai gerbang DK-16.

**Warna semantik tidak pernah didesaturasi demi keselarasan.** Peringatan, bahaya, dan status membawa arti keselamatan.

### Tema gelap

Tiga pilihan (DK-17, keputusan pemilik D5): **Ikuti sistem** (bawaan), **Terang**, **Gelap**. Pilihan disimpan di
cookie `tema`, dirender server sebagai `data-theme` pada `<html>` saat muat penuh, dan disamakan lagi oleh
`terapkanTema()` di `resources/js/dasar.js` pada setiap kunjungan Inertia, karena kunjungan Inertia mengganti
komponen halaman, bukan `<html>`. Nilai v3 diturunkan dari palet lapangan prototype.

Skala warna dipetakan ulang menurut **peran**: `bg-brand-100 text-brand-900` tetap berarti "latar lembut, teks
pekat" di kedua tema. Isian tombol merek di tema gelap hijau muda `#8FD3A0`, jadi teks di atasnya token `on-brand`
gelap `#0F2418` (9,33:1), dan tanda centang serta titik radio bawaan formulir digelapkan di `app.css`; isian bahaya
tetap merah bata dengan teks putih (4,92:1). Peta topografi tetap terang dan hanya diredupkan: kontur adalah informasi
keselamatan. Karena itu setiap garis rute di peta berdiri di atas tepi `--surface` 7 px (garisnya 4 px), supaya garis
hijau muda tetap terbaca di atas basemap yang terang (`MapComponentTest`, `ColourContrastTest`). Dua blok gelap di
`app.css` identik dan dijaga `ColourContrastTest`.

### Tema lapangan

Mode Mendaki (W6) memasang `data-tema-lapangan="terang"` atau `"gelap"` pada akarnya (DK-49). Kedua blok di `app.css`
menulis semua token warna: token jalur bernilai sendiri, dan sisanya sama dengan tema aplikasi sejenis. Tema aplikasi di
`<html>` dapat berbeda dari tema lapangan, misalnya ponsel yang beralih gelap saat senja, dan blok yang hanya menimpa
token jalur mewarisi sisanya dari tema itu, sehingga status Tutup dan Dibatasi turun ke sekitar 2:1. Informasi kritis
(nama pos, angka jarak, tombol Jeda dan Selesai) paling sedikit 7:1: tinta `#0F1311` di atas putih pada lapangan terang,
`#F2EFE8` di atas `#0F1311` pada lapangan gelap. Di lapangan gelap cincin fokus memakai kuning pos (9,58:1),
satu-satunya tempat kuning pos membawa arti karena berdiri di atas gelap. Kontrol lapangan 48 sampai 66 px.
`ColourContrastTest` menghitung kedua tema lapangan dengan ambangnya sendiri, dan keempat kombinasi tema aplikasi dan
tema lapangan dengan ambang tema aplikasi.

Ukuran di jalur (W6): "Mulai pendakian" di tab Mendaki 66 px, Jeda, Lanjut, Selesai, dan Kartu darurat 60 px, kontrol
peta 48 px. Nama pos berikutnya memakai `text-tampilan`, tiga petak angka menulis "Belum ada" dengan huruf biasa bila
angkanya tidak diketahui. Tiba di pos satu-satunya gerak (`denyut-tiba`, `--dur-sedang`), dan ia ikut dipangkas gerak
dikurangi. Data dinamis dari paket luring memakai label "Terakhir diketahui" dengan ikon jam dan garis putus-putus,
atau "Status belum dapat dipastikan" bila Buka melewati ambangnya: kata, ikon, dan garis, bukan warna saja (R-013).

Isian nomor darurat 112 dan 115 di Kartu darurat (W6 Tugas 7) adalah pengecualian pada tema lapangan gelap: latar
`danger-900` bernilai terang di blok gelap (salem), sehingga angkanya memakai teks gelap (`text-inverse` di atas
`danger-900`, 9,46:1) alih-alih "isian bahaya merah bata dengan teks putih" di atas. `ColourContrastTest::fieldPairs`
menjaga rasio ini di kedua tema lapangan.

### Tipografi

**Satu keluarga: Plus Jakarta Sans variabel** untuk antarmuka, judul, dan angka (DK-38). Alasannya tertulis di laporan
riset merek: dirancang Tokotype (Indonesia) untuk identitas +Jakarta, memiliki angka tabular, glyph ↑, dan bobot 800
yang dibutuhkan skala judul prototype. Kelangkaan bukan alasannya; keunikan merek dipikul motif dan tata bahasa data.
Geist, Geist Mono, dan Newsreader hanya hidup di bundel prototype.

| Peran | Kelas | Ukuran, tinggi baris, bobot |
|---|---|---|
| Tampilan (sapaan Beranda) | `text-tampilan` | 30 px, 1,15, 800, tracking -0,025em |
| Judul halaman (`PageHeader`, h1) | `text-judul` | 28 px, 1,2, 800, tracking -0,02em |
| Judul kartu utama | `text-judul-kartu` | 23 px, 1,25, 800 |
| Judul bagian | `text-bagian` | 18 px, 1,35, 700 |
| Subjudul | `text-subjudul` | 17 px, 1,4, 700 |
| Isi | `text-isi` | 16 px, 1,5 |
| Isi kecil | `text-kecil` | 14 px, 1,45 |
| Meta | `text-meta` | 13 px, 1,4 |
| Label (lantai) | `text-label` | 12 px, 1,35 |

**Lantai 12 px** untuk teks apa pun, termasuk label tab dan label peta (`TypeFloorTest`). Isi 16 px, juga di isian
formulir: di bawah 16 px Safari iOS memperbesar halaman saat isian difokus.

**Angka:** `tabular-nums` dari keluarga yang sama untuk jarak, tanjakan, durasi, tanggal, jam, dan hitungan (`table`,
`dd`, `time`, `output`, `[data-angka]`). Format lewat lokal `id-ID`: koma desimal, titik ribuan, jam "15.06". Rentang
di data memakai en dash ("7–8 j"); di dalam kalimat memakai "sampai". Titik tengah hanya pada baris ukuran yang padat
("9,7 km · ↑1.650 m · 7–8 j"), tidak sebagai pemisah meta di setiap kartu.

**Label kecil** memakai huruf kalimat tebal 13 px, bukan kapital bertracking lebar: "Data contoh", "Kritis", "Perlu
persiapan" (`DesignPrimitivesTest`). Label kecocokan publik hanya "Cocok", "Perlu persiapan", "Kurang cocok", dengan
"Belum dinilai" untuk UNKNOWN (R-004, R-006).

**Hosting:** dua subset WOFF2 dihosting sendiri di `public/fonts/plus-jakarta-sans/` bersama teks OFL: Latin 27.348 bita
(dipramuat) dan Latin Extended 21.728 bita (diunduh hanya bila halaman memuat glyph-nya), jumlahnya 49.076 bita dari
anggaran 100 KB. `font-display: swap` dengan dua wajah cadangan bermetrik (Roboto dan Arial) supaya tata letak tidak
melompat. Asal berkas, hash, dan lisensinya ada di `docs/lisensi-pihak-ketiga.md`; ukuran dan hash dipaku
`TypographySystemTest`. Mengganti berkas huruf wajib memakai nama berkas baru: service worker menyimpan huruf
cache-first, dan versinya hanya berganti ketika manifest build berganti.

**Mono:** tumpukan sistem, tanpa unduhan, hanya untuk kode alfanumerik seperti nama migrasi dan kunci tugas penjadwal
di halaman Kesehatan admin; tidak untuk ukuran.

### Ikon

Ikon hanya di tempat yang membawa arti: arah naik dan turun, penanda tab, jenis pos, asal keterangan, dan aksi yang
namanya sudah dibawa label. Tidak pernah sebagai hiasan di samping judul (`IconSystemTest` menjaga `PageHeader` dan
`Card` tetap bebas darinya).

**Phosphor** (MIT), path ditanam per bobot di `resources/js/components/ui/ikon/phosphor.ts`, tanpa paket npm. Cakupan
bukan pembeda antarpustaka; identitas datang dari glyph domain. Bukan Lucide maupun Heroicons.

| Ukuran render | Bobot | Contoh |
|---|---|---|
| 16 sampai 19 px | Bold | Panah naik dan turun di daftar pos, centang chip |
| 20 px ke atas | Regular | Tombol ikon dan tab (22 px) |
| Tab aktif | Isi | Beranda, Jelajah, Trip, Mendaki, Progres |

Tidak ada ikon informatif di bawah 16 px. API: `<Icon name size isi label />` dengan `size` 16, 20, 22, 24, 28, atau
32; ukuran tidak ditulis sebagai kelas. Ikon di samping label `aria-hidden`; ikon yang berdiri sendiri membawa `label`;
tombol ikon (`TombolIkon`) wajib bernama. Status dibawa bentuk dan kata, karena mode `forced-colors` menimpa `fill` dan
`stroke`.

**Tombol aksi tanpa panah hiasan** (ciri klaster "tampilan AI"). Arah kembali memakai ikon `arrow-left`; chevron hanya
pada baris yang membuka halaman lain dan pada pembuka yang dapat dilipat; ikon tautan keluar hanya pada tautan ke situs
lain.

**Glyph domain pilot** (DK-39, ruling W1-R3): `pos`, `tanjakan`, dan `resmi`, digambar dari tata bahasa tanda "Jalur
Berpos" (garis monoline, belokan bersudut, cincin pos) sebagai goresan setebal Phosphor pada bobot yang sama, di
`resources/js/components/ui/ikon/glyphDomain.ts`. `resmi` hanya untuk keterangan bersumber OFFICIAL (R-009). Jam kerja
pilot: rancangan geometrinya dibuat di sesi perencanaan W1 tanpa pencatat waktu (belum terukur); implementasi dan
pemeriksaan visual di Tugas 4: 0 jam 3 menit (mulai 2026-09-27 03.38, selesai 03.41); putaran perbaikan bentuk pos (W1-4a) sesudahnya tidak tercatat waktunya (belum terukur). Set lengkap 8 sampai 12 glyph menunggu uji pengenalan dengan 5 sampai 8
pendaki (spec v3 bagian 15, Q15).

**Lisensi:** teks MIT di `resources/js/components/ui/LICENSE-phosphor.txt`; setiap berkas yang memuat path Phosphor
menunjuknya, dan pemberitahuannya ikut ke bundel produksi (`ThirdPartyLicenseTest`).

### Bentuk, ruang, dan target

| Kelas | Radius | Untuk |
|---|---|---|
| `rounded-kecil` | 10 px | Kotak centang, kontrol kecil |
| `rounded-control` | 12 px | Segmen di dalam sumur, tombol ikon kotak |
| `rounded-tombol` | 16 px | Tombol, isian, sumur segmen, toast |
| `rounded-lg`, `rounded-kartu` | 18 px | Kartu, panel, keadaan kosong |
| `rounded-utama` | 22 px | Kartu utama, peta, bar aksi, dialog |
| `rounded-lembar` | 26 px | Lembar bawah (sudut atas di ponsel) |
| `rounded-lencana` | 6 px | Lencana kotak |
| `rounded-full` | pil | Chip, bilah tab, tombol ikon bulat, avatar, sakelar |

Radius dalam sama dengan radius luar dikurangi padding. Nilainya tinggal di token `--radius-*` (`SemanticTokenTest`);
`rounded-md`, `rounded-xl`, dan sejenisnya tidak dipakai di view Blade (`DesignDirectionTest`); TSX dirapikan saat
halamannya dirombak. Peta (`WadahPeta`) dan panel peringatan (`Alert`) mengikuti tabel ini (`DesignDirectionTest`).

Ruang memakai kelipatan dari skala 4, 6, 8, 10, 12, 14, 16, 20, 24, 28 px; gutter halaman 20 px, 16 px di layar padat
seperti Jelajah (halaman lama tetap 16 px sampai W2). Target: kontrol utama paling sedikit 44 px di kedua sumbu, tinggi
dan lebar (`min-h-11 min-w-11`); tinggi dijaga `TapTargetTest`; lebar dijaga `DesignPrimitivesTest` untuk chip, segmen,
dan tab; chip berbentuk 36 px di dalam target 44 px (W1-K5); kontrol lapangan 48 sampai 66 px.

### Material, elevasi, dan lapisan

Kartu datar bergaris rambut (`border-subtle`), tanpa bayangan (`SurfaceSystemTest`). Satu elevasi, `shadow-overlay`
berwarna token `bayangan`, hanya untuk lapisan yang melayang: bilah tab, strip pendakian aktif, bar aksi, lembar bawah,
dialog, menu avatar, toast, pesan jaringan, dan kartu pratinjau peta. Kaca paling banyak dua elemen per layar lewat
`.chrome`, bersyarat dan bukan bawaan, tidak pernah di atas peta, kartu, status resmi, laporan komunitas, atau dialog.

Skala lapisan: bar aksi, bilah tab, strip pendakian aktif, dan header layar lebar `z-30`; toast dan
pesan jaringan `z-40`. Lembar bawah dan dialog adalah `<dialog>` asli yang dibuka `showModal()`, dan menu avatar adalah
popover asli, jadi ketiganya tinggal di top layer peramban tanpa z-index. Lembar dan dialog memakai tirai token `scrim`;
halaman di belakangnya berhenti bergulir, Escape menutup, dan fokus kembali ke pemicunya, atau ke `#konten` bila
pemicunya tidak ada (W1-K6).

Urutan tumpuk di tepi bawah layar, dari bawah ke atas: bilah tab; strip pendakian aktif selama trip berlangsung; bar
aksi 0,75rem di atasnya; lalu toast 1rem di atas navigasi ditambah tinggi bar aksi. `--nav-bawah` adalah jumlah tinggi
bilah tab dan strip, diturunkan `app.css` dari elemen yang benar-benar ada di halaman (W2). Selama terpasang atau
tampil, bar aksi dan strip mengukur tingginya sendiri dan menuliskannya ke `--bar-aksi` dan `--strip-pendakian` di
`<html>`. Pengganjal bar aksi di bawah isi halaman membaca `--bar-aksi`, dan jarak bawah toast membaca `--nav-bawah`
dan `--bar-aksi`, jadi toast tidak menutupi tombol bar aksi, juga ketika bar atau strip membungkus ke dua baris di
layar sempit (`DesignPrimitivesTest`, `ActiveHikeStripTest`, `InertiaLayoutTest`).

Anggaran tinggi lapisan bawah di 320 sampai 360 px (tinjauan akhir W2, W2-F1 dan W2-F5): bar aksi rincian jalur satu
baris (simpan berupa tombol ikon 44 px, Banding dan Rencanakan trip berukuran kecil), dan baki banding Simpanan satu
baris ringkas (hitungan, ruas, pesan pendek, satu aksi) tanpa daftar nama. Jelajah tidak punya baki; hitungan dan
pintu Simpanan ada di kepala halaman (prototype 02 dan 15). Panel yang menempel di layar lebar (peta dan saringan
Jelajah, kolom kanan rincian jalur) menyisakan header, pesan jaringan, dan lapisan bawah dari tingginya (W2-F3).

### Gerak

Daftar tertutup (spec v3 bagian 8.6); hanya transform, opacity, dan warna yang berpindah, dan hanya lewat token.

| Gerak | Durasi dan kurva | Di mana |
|---|---|---|
| Tekan | `duration-sentuh` (120 ms), `ease-gerak`, skala 0,98 | `umpanTekan` di `gaya.ts`: tombol, tombol ikon, chip |
| Geser kenop sakelar | 120 ms, `ease-gerak` | `Sakelar`, sebagai umpan balik tekannya |
| Lembar bawah naik | `--dur-sedang` (280 ms), `--ease-gerak` | `.lembar[open]` |
| Dialog tengah muncul | `--dur-cepat` (200 ms), `--ease-pudar` | `.dialog-tengah[open]` |
| Toast urungkan naik | `--dur-cepat` (200 ms), `--ease-gerak` | `.toast-masuk` |
| Centang persiapan, tiba di pos, pembuka "Kenapa?", kartu jalur ke detail | menurut spec v3 bagian 8.6 | W3, W4, W6 |

Tutup seketika (W1-K6). Tidak ada animasi masuk dan tidak ada gerak berulang. `prefers-reduced-motion` dan setelan Gerak
di Profil (`data-gerak="kurangi"`) memangkas semuanya menjadi 1 ms (`MotionTest`). Mode lapangan tanpa gerak selain
status.

### Media dan foto

Foto gunung diunggah admin dengan kolom wajib pembuat, lisensi, URL sumber, teks atribusi, dan teks alternatif, lalu
tampil dengan atribusinya. Tanpa foto, hero memakai visual data (bentuk rute di atas pola kontur). Foto komunitas tidak
pernah menjadi hero (R-017). Avatar berupa unggahan pengguna atau inisial (`Avatar`); data contoh memakai inisial,
tidak pernah wajah stok (antislop R-23).

### Nada dan teks

Sapaan "Anda". Tagline "Pilih jalur yang sesuai. Pahami alasannya." (D2). Tanpa em dash. Tidak pernah menyatakan jalur
atau gunung "aman" dan tidak pernah "layak berangkat" (R-012, R-014): kalimat publik menyebut apa yang masih kurang
sebelum berangkat. Keadaan kosong memakai tiga jenis `EmptyState`, pertama kali, tersaring, dan menunggu, dan yang
menunggu menyebut siapa yang ditunggu ("Menunggu BMKG", "Menunggu kurator Memount") (PRD §104). Aksi spesifik
("Lanjutkan persiapan", "Pesan di sistem resmi", "Rencanakan trip"). Data contoh selalu berlabel "Data contoh" (DK-35), di layout aplikasi maupun halaman publik jalur.

### Primitif

Primitif desain tinggal di `resources/js/components/ui/` (ruling W1-R1; spec v3 bagian 9.1 menyebut `components/ds/`,
dan ruling itu yang berlaku).

| Primitif | Berkas |
|---|---|
| Tombol, tombol ikon | `Button.tsx`, `TombolIkon.tsx` |
| Chip pilihan dan chip saringan | `Chip.tsx` |
| Kontrol segmen, sakelar, kotak centang, stepper | `KontrolSegmen.tsx`, `Sakelar.tsx`, `KotakCentang.tsx`, `Stepper.tsx` |
| Lembar bawah dan dialog | `Lembar.tsx` |
| Toast urungkan, bar aksi, bilah tab | `ToastUrungkan.tsx`, `BarAksi.tsx`, `BilahTab.tsx` |
| Keadaan kosong tiga jenis, lencana, avatar | `EmptyState.tsx`, `Lencana.tsx`, `Avatar.tsx` |
| Isian, kartu, judul halaman, peringatan | `Field.tsx`, `Card.tsx`, `PageHeader.tsx`, `Alert.tsx` |
| Kelas fokus dan tekan bersama | `gaya.ts` |

Setiap primitif interaktif punya fokus terlihat, target 44 px, dan nama aksesibel, dan labelnya membungkus alih-alih
terpotong (`DesignPrimitivesTest`, `LongInputLayoutTest`). Komponen domain tidak tinggal di folder itu: W3 memindah
dan menulis ulang enam yang tersisa ke `resources/js/components/trails/` (FitBadge menjadi `LabelKecocokan`,
StatusBadge menjadi `StatusAkses`, Freshness menjadi `Kesegaran`, FactorBars menjadi `FaktorKecocokan`,
ElevationProfile menjadi `ProfilElevasi`, CheckpointJourney menjadi `rinci/DaftarPos`), bersama `LabelSumber` yang
naik dari `rinci/`.

**Tata bahasa kepercayaan** (W3, spec v3 bagian 7). Label kecocokan selalu ikon dan kata, tanpa angka: tiga label
publik, ditambah "Belum dinilai" untuk UNKNOWN yang katanya datang dari server (`RouteFitLabel`). Status akses dibawa
bentuk dan kata, beserta sumber dan kesegarannya. Tanpa catatan yang berlaku, statusnya Belum diketahui, bukan Buka.
Glyph `resmi` hanya menemani sumber OFFICIAL (`TrustGrammarTest`). Sumber di rincian jalur diurutkan menurut otoritas
(R-009), dengan interpretasi sistem terakhir (`TrailDetailPageTest`).

**Konvensi prop untuk primitif baru** (ruling W1-F3): kontrol berkeadaan memakai `nilai` dan `onUbah`, nama tampak
memakai `label`, dan nama prop berbahasa Indonesia; primitif yang sudah ada tidak diganti namanya. Grup kontrol yang
dapat bergalat (`KontrolSegmen`, `Stepper`) menerima `penjelas`, id galat atau keterangan yang diikat lewat
`aria-describedby` pada grupnya (W4, `KeyboardAndScreenReaderTest`).

**Umpan balik aksi di wilayah yang selalu terpasang** (W4 Tugas 4 dan 6). Umpan balik sebuah aksi, baik kabar
tersimpan maupun penolakan server, dibacakan lewat wilayah `role="status"` untuk kabar biasa atau `role="alert"`
untuk penolakan. Wilayahnya selalu terpasang di dekat aksinya sejak halaman dimuat; hanya isinya yang berubah.
Wilayah yang baru dipasang bersamaan dengan isinya tidak andal dibacakan pembaca layar. Kabar yang datang bersama
perpindahan halaman (misalnya "Rencana trip tersimpan.") tampil di wilayah dekat kepala halaman tujuan, tempat pendaki
mendarat, dan isinya ditulis sesudah halaman terpasang; kabar aksi di halaman yang sama tetap di dekat aksinya
(flash `status` lawan `kabar-aksi`, W4-FK).

**Klaster Trip** (W4, spec v3 bagian 6.8 sampai 6.11). Ruang kerja trip menampilkan empat langkah (Jalur, Rencana,
Persiapan, Cek) sebagai garis rute berujung cincin pos: garis merek menyambung cincin bernomor, dan cincin langkah yang
selesai terisi dengan centang. Mulai 1024 px langkah menjadi kolom kiri 18rem di samping isinya. Halaman Rencanakan,
Persiapan, dan Cek membawa baris langkah ringkas (`LangkahRingkas`) dengan langkah halaman itu ditandai garis bawah
merek, tebal huruf, dan `aria-current`; langkah lain yang dapat dibuka tampak sebagai tautan saat diam (warna tautan
dan garis bawah teks), bukan hanya saat disorot. Kepala trip (`KepalaTrip`) menyebut jenis dan sisa hari sebagai
lencana, tanggal dengan kata "sampai", jam dengan zona gunungnya, dan peserta, tanpa titik tengah; status "Siap
berangkat" tidak pernah tampil di kepala (R-014). Rencana yang belum dimulai tetapi tanggalnya sudah lewat mengganti
lencana sisa hari dengan lencana peringatan berikon "Tanggal rencana sudah lewat", di kepala dan kartu indeks, tanpa langkah berikutnya; aksinya hanya "Ubah rencana". Hitungan butir kritis berupa ruas per butir ditambah kalimat "N dari M", bukan
persen. Keadaan kesiapan memakai kata DK-48 dan kalimat "Ini dukungan keputusan, bukan izin medis, izin hukum, atau
jaminan keselamatan." Hanya kartu utama indeks trip yang membawa tombol penuh; kartu kelompok memakai tautan teks. Penolakan centang di
Persiapan tampil sebagai teks di baris butirnya dan dibacakan lewat satu wilayah `role="alert"` halaman yang selalu
terpasang, sesuai konvensi umpan balik di atas.

**Beranda** (W5, spec v3 bagian 6.1, prototype 01). Server memilih satu dari lima fase (B0 profil belum diisi, B1
tanpa trip mendatang, B2 trip mendatang, B3 sedang mendaki, B4 pendakian selesai yang belum dilaporkan), dan setiap
fase membawa tepat satu aksi utama selebar kartu di ponsel. Kartu perjalanan B2 memakai garis profil jalur sebagai
visual data selama foto berlisensi belum ada (DK-37), empat langkah sebagai bilah dengan centang sesudah namanya, dan
persiapan kritis sebagai ruas per butir ditambah kalimat "N dari M". Tenggat terdekat memakai penanda kalender yang
disembunyikan dari pembaca layar, dengan zona gunung disebut sekali di samping judulnya. Kabar memisah status resmi
(garis padat merek hanya untuk sumber OFFICIAL) dari laporan komunitas (kartu bergaris putus-putus yang sama dengan
halaman gunung). "Jalur lain untuk Anda" bergulir menyamping di ponsel dan pindah ke kolom samping 22rem mulai 1024 px
bersama tangga tanjakan, yang menyebut jalur beserta gunungnya karena nama jalur tidak unik; tanpa isi samping,
halaman tetap satu kolom. Daftar yang bergulir menyamping diberi
`relative`, supaya teks sr-only di dalamnya tidak melebarkan halaman.

**Fokus tidak pernah jatuh ke body.** Chip saringan yang dilepas menyerahkan fokus ke chip tetangga atau wadah grupnya;
toast yang dipicu papan ketik mengambil fokus ke Urungkan, dan fokus kembali ke unsur sebelumnya atau ke `#konten`
ketika toast hilang; sesudah Urungkan, pemanggil memfokus unsur yang dikembalikan begitu jawaban server dirender; pesan
jaringan yang ditutup, lewat "Tutup pesan" maupun "Muat ulang halaman", memindah fokus ke `#konten` sebelum hilang
(`ShellFeedbackTest`); tombol stepper di batas rentang memakai `aria-disabled` alih-alih `disabled`.

**Pameran** `/dev/primitif` (hanya `APP_ENV=local`, dijaga dua lapis: rutenya hanya didaftarkan di local dan closure-nya menolak 404 di luar local) menampilkan setiap primitif dengan contoh isi yang berfungsi.
**Pratinjau** tanpa basis data memakai suite PHPUnit `Pratinjau` dan server statis; caranya di `CONTRIBUTING.md`, bagian
"Pratinjau visual tanpa basis data".

### Navigasi

Lima tab Beranda, Jelajah, Trip, Mendaki, Progres (DK-25), dari satu daftar (`itemTab()` di
`resources/js/layouts/NavLinks.tsx`) untuk dua bentuk kerangka yang dibangun W2 (spec v3 bagian 5):

- **Di bawah 1024 px** tidak ada bilah atas global. Setiap halaman membawa kepalanya lewat `PageHeader`: akar tab
  memberi `akar` (h1, aksi kontekstual, avatar 44 px ke Profil; Beranda menambah `merek` untuk lockup), halaman turunan
  memberi `induk` (tombol kembali 44 px ke induk logisnya, bukan riwayat peramban). Navigasi utama adalah bilah tab pil
  mengambang (`BilahTab`: item 56 px, label 12 px, ikon 22 px, varian isi dan `aria-current` untuk tab aktif). Halaman
  tugas dengan bar aksi tetap (detail jalur, penilaian kecocokan, rencanakan trip) memasang
  `<AppLayout bilahTab={false}>`. Lebar 640 sampai 1023 px sengaja tetap memakai kerangka ponsel.
- **Mulai 1024 px** (DK-33) header 68 px menempel di atas: lockup, navigasi segmen lima tab di tengah (ikon 20 px, label
  14 px), lalu lencana Data contoh, Simpanan dengan hitungannya, dan menu avatar (popover asli: Profil; Moderasi dan
  Admin sesuai peran; Keluar). Di ponsel, Moderasi, Admin, dan Keluar tinggal di kartu Akun halaman Profil. Popover menu
  akun sengaja tanpa cadangan untuk peramban lawas (ruling W2-2c): didukung Chrome 114+, Safari 17+, Firefox 128+ (lantai Tailwind 4 untuk seluruh aplikasi, PRD GR-08), dan
  peramban desktop 2023 ke bawah melihat menunya meluap statis di header.
- **Strip pendakian aktif**: selama trip berlangsung, strip isian merek di atas bilah tab membawa pendaki kembali ke
  mode lapangan dengan satu ketukan; mode lapangan sendiri tidak memasangnya.
- **Lonceng Beranda** membuka lembar Pengingat (pengingat surel terjadwal dan tenggat izin milik sendiri), tanpa
  notifikasi dorong (Q6).
- Jarak lapisan tepi layar tidak disalin ke tiap halaman. `--bilah-tab` bawaannya tinggi bilah itu sendiri dan baru
  dinolkan lewat `:not(:has(nav[data-bilah-tab]))` ketika bilahnya tidak ada, sengaja dibalik supaya peramban yang
  belum mengerti `:has()` kelebihan ruang di bawah, bukan kehilangan ruang dan tertutup bilah yang tetap tergambar
  (ruling W2-2b). Mulai 1024 px `--bilah-tab` juga dinolkan walau bilahnya ada di halaman, karena di sana bilah itu
  tersembunyi. `--kepala-atas` mengikuti arah yang sama (perbaikan akhir W2): mulai 1024 px bawaannya tinggi header
  68 px, dan baru dinolkan lewat `:not(:has([data-kepala-lebar]))` di halaman tanpa header (GuestLayout, Welcome, mode
  lapangan). Strip pendakian, bar aksi, dan pesan jaringan mengukur tingginya sendiri lewat `ResizeObserver` dan
  menuliskannya ke `--strip-pendakian`, `--bar-aksi`, dan `--pesan-jaringan` di `<html>` selama masing-masing
  terpasang atau tampil. Baki banding Jelajah beserta variabel tingginya dilepas W3.
- Fokus papan ketik dan sasaran jangkar tidak berhenti di balik lapisan tetap (WCAG 2.4.11): `scroll-padding-bottom`
  menghitung bilah tab dan strip (`--nav-bawah`) dan bar aksi (ruling W2-2a);
  `scroll-padding-top` menghitung header layar lebar (`--kepala-atas`) dan pesan jaringan (ruling W2-5a), dan `<main>`
  memberi dirinya `padding-top` setinggi pesan jaringan. Toast tidak masuk hitungan itu: ia hilang sendiri sesudah
  8 detik, hitungannya berhenti selama fokus papan ketik (`:focus-visible`) berada di dalamnya atau penunjuk berada
  di atasnya, dan fokusnya pindah ke Urungkan bila aksinya dari papan ketik. Selama tampil, toast dapat menutup unsur
  terfokus di dekat tepi bawah.

**Pemilik tab** (spec v3 bagian 5.3, DK-25). Halaman gunung menyalakan Jelajah. Banding milik Trip, jadi tombol
kembalinya ke Trip, dan Simpanan tetap terjangkau lewat "Lihat simpanan" di bawah judulnya. Tombol kembali tidak
pernah memindah tab: bila sebuah halaman menyalakan tab, induknya menyalakan tab yang sama
(`NavigationConsistencyTest`, tinjauan akhir W2, W2-F2).

**Peta Jelajah** (W3) tetap mengikuti keputusan F2: yang digambar bentuk jalur bergeometri, bukan titik gunung. Label
kecocokan dibedakan pola garis (utuh untuk Cocok, putus panjang untuk Perlu persiapan, titik-titik untuk Kurang cocok,
putus-titik untuk Belum dinilai) dan legenda bertulisan, bukan warna saja. Jalur tanpa geometri disebut jumlahnya di
pojok peta. Bila tidak satu pun jalur punya geometri, MapLibre tidak dimuat dan panelnya menyebut yang ditunggu.

### Catatan tata kelola

Test yang menyandikan keputusan lama diperbarui menurut niat baru di gelombang 1, dengan alasan di docblock-nya (spec v3
bagian 14):

| Test atau berkas | Keputusan lama | Niat baru |
|---|---|---|
| `ColourContrastTest` | Putih di atas isian merek; non-teks hanya dihitung di tema gelap | `on-brand` di atas `brand-700`; non-teks di kedua tema; label kecocokan, status, toast, dan dua tema lapangan 7:1 |
| `SemanticTokenTest` | Kata peran ada di konfigurasi | Setiap peran dipetakan `token('...')`; skala radius v3 |
| `TypographySystemTest` | Tiga suara, serif judul, Bunny Fonts | Satu keluarga dihosting sendiri, skala bernama, cadangan bermetrik, anggaran 100 KB |
| `SecurityHeadersTest`, `LandingPageTest` | CSP mengizinkan Bunny Fonts | Tanpa host huruf luar |
| `SurfaceSystemTest` | Judul `text-3xl` atau `text-4xl` | Judul `text-judul` |
| `IconSystemTest` | Ikon tab berkelas `h-6 w-6` | Ukuran bernama, bobot per ukuran, varian isi |
| Dial di berkas ini | ENERGY 1, MOTION 1 | ENERGY 2, RHYTHM 2, MOTION 2 (spec v3 bagian 8.9); mode lapangan 1/1 |

---

## Yang tidak dikerjakan, dan alasannya

- **Bento grid, kartu double-bezel, animasi masuk bertahap.** Pola mode Persuade. Seluruh
  aplikasi ini mode Operate, dan animasi masuk pada halaman perencanaan keselamatan
  menunda isi demi hiasan.
- **Tekstur, grain, bayangan berwarna.** Menambah bita tanpa menambah keterbacaan pada
  antarmuka operasional.
- **Papan peringkat kontribusi.** Memberi imbalan atas jumlah, dan yang dibutuhkan
  produk ini ketepatan. Ia merusak nilai inti produknya sendiri.
- **Animasi pergantian tema.** Transisi warna pada seluruh elemen membebani ponsel
  kelas bawah, dan pergantian tema adalah keputusan pengguna yang tidak perlu dirayakan.

---

# Aturan canonical (R-015–R-017)

Berkas ini adalah **canonical owner** untuk R-015, R-016, dan R-017.

**Provenance:** USER-PROVIDED CANONICAL SOURCE. Statement, Scope, dan Invariant disalin
dari sumber canonical pemilik produk. Ketiganya bukan bagian **Turunan** di atas dan
tidak boleh diperlakukan sebagai simpulan agen.

**Transitional duplication:** CLAUDE.md §27, §26, dan §30 masih memuat representasi lama
untuk ketiga rule ini, dan bagian *Yang tidak dikerjakan* di atas menyinggung papan
peringkat kontribusi. Representasi-representasi itu berstatus PRESENT_NONCANONICAL dan
dibiarkan utuh sampai pruning setelah reachability terbukti. Ketika berbeda, blok di
bawah ini yang berlaku.

---

## R-015 | Progress adalah self-comparison

**Statement.** Progress digunakan terutama untuk membandingkan perkembangan pengguna
dengan riwayat dirinya sendiri; bukan leaderboard kontribusi sebagai inti pengalaman.

**Scope.** Progress dashboard, history, achievement, analytics, dan personal development
UX.

**Invariant.** Progress harus berorientasi pada perkembangan individual, bukan ranking
kontribusi antar pengguna sebagai core mechanic.

---

## R-016 | Comparison tidak memiliki winner

**Statement.** Fitur comparison menyajikan trade-off dan perbedaan antar pilihan; sistem
tidak menetapkan satu pilihan sebagai pemenang.

**Scope.** Compare flow, comparison UI, shared comparison, recommendation presentation.

**Invariant.** Comparison harus membantu pengguna memahami trade-off tanpa menghasilkan
"winner", ranking paksaan, atau keputusan final atas nama pengguna.

---

## R-017 | Community photo bukan default trail-card hero

**Statement.** Foto komunitas yang belum memenuhi status/standar yang sesuai tidak boleh
menjadi hero/default visual utama pada Trail Card.

**Scope.** Trail Card, discovery, recommendation result, community media integration.

**Invariant.** Visual utama Trail Card harus mengikuti media/asset authority yang sesuai
dan tidak boleh secara default digantikan oleh foto komunitas yang tidak terverifikasi.

---

# LEGACY RULE: DISPOSISI SELESAI

Dua batasan berikut sebelumnya hidup di CLAUDE.md tanpa owner. Keduanya kini sudah
mendapat disposisi dari pemilik produk, dan catatan ini disimpan sebagai jejak keputusan.
Keduanya **bukan** aturan canonical milik berkas ini dan tidak diberi nomor R-xxx.

| Batasan | Owner sekarang | Disposisi |
|---|---|---|
| Comparison maksimum 5 Trail | `docs/PRD.md` §33 FR-06 | Dipindahkan. Ia parameter perilaku fitur, bukan prinsip global, sehingga tidak dibebankan ke registry BR |
| Larangan sertifikasi keahlian otomatis dari satu trip | `docs/PRD.md` BR-11 | Dinyatakan sudah tercakup. BR-11 ("User history tidak otomatis menaikkan experience level") lebih luas dan memuatnya penuh; tidak ada BR baru dibuat |

Tiga kemunculan batasan pertama di CLAUDE.md (§26, §3.2, §6) dan satu kemunculan
batasan kedua (§27) dihapus setelah parity terhadap PRD terverifikasi.
