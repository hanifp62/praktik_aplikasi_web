# Arah desain

Berkas ini memegang arah; `.claude/skills/antislop` menyaringnya. Arah adalah milik
pemilik produk, saringan tidak pernah menciptakannya.

Bagian **Arah** di bawah adalah kata-kata pemilik produk, disalin bukan ditafsirkan.
Bagian **Turunan** adalah simpulan yang ditarik agen dari arah itu, ditandai terpisah
supaya pemilik dapat membetulkannya.

---

## Arah (dari pemilik produk)

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
| Merek | `brand` (hijau) | Aksi utama dan penanda positif. |
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
- **Mode gelap.** Belum diputuskan pemilik produk. Ia bukan pekerjaan yang ditunda
  melainkan keputusan yang belum diambil, dan berkas ini menunggu jawabannya.

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

# UNOWNED LEGACY RULE — NEEDS PRODUCT DISPOSITION

Dua batasan berikut hidup di CLAUDE.md dan **tidak terwakili** oleh R-001–R-024 mana pun.
Keduanya dicatat di sini agar tidak hilang saat pruning. Keduanya **bukan** canonical
rule, tidak diberi nomor R-xxx, dan menunggu keputusan ownership terpisah dari pemilik
produk.

| Batasan | Lokasi sekarang | Status |
|---|---|---|
| Comparison maksimum 5 Trail | CLAUDE.md §26 | UNOWNED LEGACY RULE — NEEDS PRODUCT DISPOSITION |
| Larangan sertifikasi keahlian otomatis dari satu trip | CLAUDE.md §27 | UNOWNED LEGACY RULE — NEEDS PRODUCT DISPOSITION |

Teks aslinya di CLAUDE.md tidak disentuh pada fase ini.
