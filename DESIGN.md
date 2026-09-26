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

### Tiga dial

| Dial | Nilai | Dasarnya |
|---|---|---|
| **ENERGY** | **1** (tenang) | Seluruh aplikasi mode Operate, bukan Persuade. Tugasnya membantu orang merencanakan pendakian yang keselamatannya nyata, dan halaman yang menyapa keras menunda isi demi sapaan. |
| **RHYTHM** | **2** (seimbang) | Komposisi berubah menurut isinya: halaman status tidak berbentuk seperti halaman metrik, dan keduanya tidak berbentuk seperti halaman naratif. Tetapi ini produk utilitas, jadi variasinya mengikuti isi, bukan mengejar variasi. |
| **MOTION** | **1** (tenang) | Tepat satu momen gerak yang diarahkan di seluruh aplikasi, pada konfirmasi butir persiapan, dan alasannya tertulis di tempatnya. Selebihnya diam. `prefers-reduced-motion` dihormati sekali untuk seluruh aplikasi. |

### Tipografi

| Peran | Huruf | Alasan |
|---|---|---|
| Antarmuka | Plus Jakarta Sans | Dirancang Tokotype untuk identitas kota Jakarta. Huruf Indonesia untuk produk tentang gunung Indonesia, dan alasan itu bertahan ketika seleranya berubah sedangkan "sedang populer" tidak. |
| Judul halaman | Newsreader | Serif memberi bobot editorial yang tidak dapat dicapai ketebalan sans, dan membedakan judul dari antarmuka tanpa membesarkannya sampai berteriak. |
| Mono | tumpukan sistem | Tidak diunduh. Diukur: font-mono dipakai dua kali di seluruh aplikasi, keduanya kunci penjadwal di satu halaman admin. Mengunduh dua bobot huruf untuk itu menagih bita kepada seluruh pengguna demi dua baris yang tak pernah mereka lihat. |

Pengukuran tidak memakai huruf mono. Ia memakai `tabular-nums` pada huruf sans, karena
yang dibutuhkan kolom angka yang tidak bergoyang, bukan suara huruf yang berbeda.

### Palet

Satu warna merek, tiga warna semantik, satu tangga netral hangat.

| Peran | Tangga | Mengapa ia berhak menjadi warna tersendiri |
|---|---|---|
| Merek | `brand` (hijau) | Aksi utama dan penanda positif. `brand-ink` adalah hijau sebagai latar depan, lihat Tema gelap. |
| Peringatan | `warn` (kuning) | Kondisi yang perlu diperhatikan, bukan kegagalan. |
| Bahaya | `danger` (merah) | Penutupan resmi dan tindakan merusak. |
| Komunitas | `community` (biru) | PRD §92 menuntut keterangan resmi dan masukan komunitas terbedakan secara visual. |
| Netral | hangat | Kanvas editorial bernuansa hangat; permukaan hangat dengan teks dingin adalah ciri tema yang ditempel di atas default. |

**Aturannya:** warna baru hanya boleh ditambahkan ketika ia membawa arti yang tidak
dibawa keempatnya. Empat sudah di atas batas dua-sampai-tiga yang lazim, dan setiap
tambahan berikutnya harus membayar dirinya sendiri.

**Warna semantik tidak pernah didesaturasi demi keselarasan.** Ketiganya membawa arti
keselamatan, dan keselarasan bukan alasan yang cukup untuk melemahkannya.

Rasio kontras dihitung, bukan ditaksir, dan dijaga `ColourContrastTest`: dua puluh
pasangan, termasuk yang marginnya tipis.

### Tema gelap

Diputuskan pemilik 23 September 2026 (D5, DK-17) dan dibangun 24 September 2026. Tiga
pilihan: **Ikuti sistem** (bawaan), **Terang**, **Gelap**. Pilihan disimpan di cookie
`tema` dan dirender server sebagai `data-theme` pada `<html>`, karena `wire:navigate`
mengganti atribut `<html>` pada tiap perpindahan halaman; tema yang hanya disetel skrip
akan terlempar kembali ke terang di tengah sesi. Pemilihnya formulir biasa dengan tiga
radio berlabel teks di halaman Profil, mode pendakian, dan kaki halaman depan.

Tema gelap hidup sepenuhnya di token `app.css`: skala warna dipetakan ulang menurut
**peran**, bukan nilainya, sehingga `bg-brand-100 text-brand-900` tetap berarti "latar
lembut, teks pekat" di kedua tema dan tidak ada satu pun kelas `dark:` di view. Kanvas
gelap stone-900, bukan hitam; kartu mengandalkan garis, bukan bayangan, karena beda
permukaan dan kanvas hanya 1,15:1. Peta topografi tetap terang dan hanya diredupkan:
kontur adalah informasi keselamatan.

Hijau merek berpisah jalan di tema gelap, jadi ia punya dua token: `brand-700` tetap
menjadi **isian** tombol (putih di atasnya 5,48:1 di kedua tema), sedangkan `brand-ink`
menjadi **latar depan** (tautan, penekanan, garis profil elevasi) dan menerang di tema
gelap karena brand-700 hanya 2,77:1 di atas permukaan gelap. `ColourContrastTest`
menghitung setiap pasangan di kedua tema dan menjaga dua blok gelap tetap identik.

### Identitas merek

**Tanda:** "Jalur Berpos" (konsep A riset identitas visual, disetujui pemilik sebagai
arah, D4). Satu garis rute monoline yang mendaki lewat dua belokan dan berakhir pada
cincin pos. Tidak ada gunung: Trail adalah unit keputusan (R-001, R-002). Dua master
digambar terpisah, 48 untuk 32 px ke atas dan 16 untuk ukuran kecil, dengan celah bersih
2,4 px pada 16 px. Komponennya `x-brand.mark`, memakai `currentColor` sehingga satu
berkas melayani terang, gelap, dan mono. Warnanya token `logo-mark` (brand-700 terang,
brand-500 gelap) dan wordmark `logo-type`.

**Wordmark:** nama dari `config('app.name')` dalam Plus Jakarta Sans tebal, kapital,
tracking +0,06em, di dalam `x-brand.lockup`. Nama merek final masih keputusan pemilik
(DK-13); tidak ada komponen yang menyimpan nama.

**Status aset:** sketsa yang sudah tampil di produk, belum aset final. Gerbang DK-16
yang belum dilewati: audit kemiripan terhadap ikon "route" dan "lightning", penelusuran
PDKI tanda gambar, uji pemahaman 5 sampai 8 pendaki, lalu persetujuan pemilik.

**Nada:** tagline "Pilih jalur yang sesuai. Pahami alasannya." (D2). Sapaan tetap
"Anda". Kalimat publik menyebut "apa yang masih kurang sebelum berangkat", tidak pernah
"layak berangkat" (R-014).

### Navigasi dan Jelajah (React, 25 September 2026)

**Navigasi bawah di ponsel.** Lima permukaan (Jelajah, Pertimbangkan, Perjalanan,
Progres, Kabar) tinggal di bilah bawah pada lebar di bawah `sm`, dengan ikon di atas
label pendek; menu atas di ponsel tinggal berisi akun (profil, peran, keluar). Alasan:
ibu jari menjangkau bagian bawah layar, dan ketiga rujukan (AllTrails, Strava,
Traveloka) serta riset strategi `docs/arsip/2026-09-22-strategi-platform.md` ("Mobile: Bottom nav") menaruh tab di sana. Ikon di bilah
ini membawa arti penanda arah dan selalu berdampingan dengan labelnya, jadi tidak
melanggar aturan "ikon hanya di tempat yang membawa arti". Tab aktif ditandai garis,
tebal huruf, dan `aria-current`, bukan warna saja. Elemen yang menempel di bawah layar
memakai `bottom-[var(--nav-bawah,0px)]` supaya tidak tertutup.

**Jelajah berbasis peta.** Peta dan daftar berdampingan di layar lebar (daftar 5
bagian, peta 6 bagian, peta menempel saat digulir); di ponsel salah satu, dipilih
lewat sakelar Daftar/Peta (features/01). Peta menggambar **bentuk jalur**, bukan titik
gunung (keputusan F2): jalur tanpa geometri tidak tampil di peta. Garis diwarnai
menurut kecocokan dari token (brand-700, warn-600, danger-600, abu-abu belum dinilai)
dan labelnya selalu tertulis di ringkasan dan di daftar. Peta dibuat sekali; saringan
hanya mengganti datanya sehingga posisi pembaca tidak hilang. Kotak cari selalu
terlihat; saringan lain terlipat di ponsel di balik tombol "Saring (n)"; saringan
yang berlaku tampil sebagai chip yang dapat dihapus satu per satu.

### Radius

Radius adalah alat hierarki, jadi ia tidak diseragamkan. Tetapi ia juga tidak boleh
dipilih sendiri-sendiri di tiap berkas.

| Kelas | Untuk |
|---|---|
| `rounded-control` | Kontrol interaktif dan permukaan kecil: tombol, isian, lencana, dropdown |
| `rounded-lg` | Permukaan besar: kartu, panel |
| `rounded-full` | Yang memang bulat penuh: chip, avatar, alur bilah kemajuan |
| `rounded-sm` | Hanya cincin fokus yang memeluk teks sebaris |

`rounded-control` dibaca dari `--radius-control`, jadi bentuk kontrol di seluruh aplikasi
berubah dari satu baris. `rounded-md` tidak dipakai lagi: nilainya persis sama dengan
`rounded-control`, dan dua nama untuk satu nilai adalah persis ketidakkonsistenan yang
membuat berkas berikutnya memilih sendiri.

### Bayangan

Satu elevasi, `shadow-overlay`, hanya untuk lapisan yang benar-benar melayang di atas
halaman: dropdown dan modal. Kartu memakai garis rambut. Halaman yang seluruh isinya
melayang tidak punya bidang dasar.

### Ikon

Phosphor berat `regular`, ditanam sebagai path, tanpa dependensi npm. Bukan Lucide
maupun Heroicons: keduanya pilihan bawaan hampir semua antarmuka hasil AI.

**Ikon hanya di tempat yang membawa arti**: arah naik dan turun, jenis pos, asal
keterangan. Tidak pernah sebagai hiasan di samping judul, dan satu test menjaga komponen
judul serta kartu tetap bebas darinya.

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
