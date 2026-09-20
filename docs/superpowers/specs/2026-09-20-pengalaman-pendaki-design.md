# Pengembangan Pengalaman Pendaki — Rancangan

Tanggal: 20 September 2026
Metode: ATM (Amati, Tiru, Modifikasi) terhadap AllTrails, Strava, dan Traveloka

---

## 1. Diagnosis, terukur

Keluhannya: "sistem ini hanya sekadar CRUD". Diagnosisnya perlu dipisah dua, karena
sebabnya bukan yang diduga.

**Logika domainnya bukan CRUD.** Ada mesin Route Fit berbobot dengan explainability per
faktor, penilaian kesiapan, kaskade status resmi tiga tingkat, jendela pemesanan izin,
dan deteksi basi di lima tempat.

**Yang benar: kecerdasan itu tidak punya wujud.** Hasil pengukuran seluruh view:

| Yang dihitung | Jumlah |
|---|---|
| Layar pengguna | 28 |
| `<form>` | 23 |
| `<table>` | 10 |
| Kartu teks | 140 |
| `<img>` di seluruh aplikasi | **2** |
| `<svg>` | **2** |
| Komponen peta | **tidak ada** |

Tiga bukti yang menjelaskan seluruhnya:

1. `trail-detail.blade.php` menerima variabel `$geometry` dan tidak pernah
   menggambarnya. Peta hanya ada sebagai kode sebaris di hike mode.
2. Foto yang diunggah pendaki hanya tampil di antrean moderasi, tidak pernah
   dikembalikan kepada pendaki lain.
3. Seluruh halaman jalur berada di balik login. Hanya `/` yang publik.

Dalam istilah Norman: **gulf of evaluation**. Sistem melakukan banyak, menampakkan
sedikit.

Dalam istilah Fogg (B = MAT): **Ability** terlayani sangat baik, **Motivation** dan
**Trigger** nol. Itu sebabnya terasa seperti formulir — dipakai sekali per pendakian.

Dalam Self-Determination Theory: **autonomy** terlayani, **competence** dan
**relatedness** tidak ada sama sekali.

Peak-end rule: puncak emosional produk ini, yaitu berhasil sampai puncak, berakhir
dengan mengisi formulir laporan kondisi.

---

## 2. Amati: apa yang dilakukan rujukan, dan mengapa berhasil

| Aplikasi | Mekanisme inti | Pendorong psikologis | Bukti |
|---|---|---|---|
| **AllTrails** | Tab Explore, peta lebih dulu, pratinjau medan 3D, foto kondisi terkini, ulasan | Keyakinan mengambil keputusan: mengurangi ketidakpastian sebelum berkomitmen | 500 ribu jalur; kekuatan yang paling sering disebut adalah penemuan yang ramah pemula |
| **Strava** | Umpan aktivitas, kudos, segmen, rekor pribadi, klub | SDT: competence dan relatedness; visibilitas sosial | 14 miliar kudos pada 2025 (naik 20%); rasio 1 jam aktivitas per 2 menit di aplikasi; rangkaian sosial 5,69 hari vs 4,25 hari tanpa fitur sosial |
| **Traveloka** | Super-app, alur rencana perjalanan, pencarian terakhir diingat, pemesanan terbundel | Mengurangi gesekan perencanaan; pengenalan mengalahkan pengingatan | Isian otomatis dari pencarian terakhir; super-app gaya hidup terdepan di Asia Tenggara |

---

## 3. Modifikasi: aturan adaptasi

Bagian terpenting dokumen ini. Menyalin bulat-bulat akan merusak produk, karena premis
produk ini berbeda dari ketiganya: **keselamatan, dan pemisahan data resmi dari data
komunitas (§92).**

### Aturan 1: energi kompetitif dialihkan dari kecepatan ke kontribusi

Segmen dan papan peringkat Strava lahir dari bersepeda jalan raya. Di gunung, mengejar
waktu tercepat membunuh orang.

Tetapi mekanismenya tidak dibuang, **metriknya yang diganti**. Papan peringkat tetap ada
dan tetap kompetitif, hanya yang diperingkat bukan kecepatan melainkan:

- jumlah puncak yang didaki
- jalur yang datanya dilengkapi
- laporan kondisi yang disumbangkan dan lolos moderasi

Ini bukan kompromi, melainkan keuntungan ganda. Aplikasi ini punya 29 jalur yang
datanya kosong; memberi penghargaan pada kontribusi menyelesaikan masalah dingin-mula
datanya sekaligus. Energi yang di Strava mendorong orang mengambil risiko, di sini
mendorong orang mengisi data yang memang dibutuhkan.

### Aturan 2: kudos diarahkan ke perilaku yang memang dibutuhkan produk

"Terima kasih" diberikan pada laporan kondisi, bukan pada pendakian. Yang dihargai
adalah memberi tahu pendaki lain bahwa jembatan di Pos 3 putus, bukan sampai puncak
lebih cepat.

### Aturan 3: segmen menjadi pos, perlombaan menjadi keterangan

Aplikasi ini sudah punya pos (checkpoint). Alih-alih memperlombakan waktu antarpos,
yang ditampilkan adalah **waktu tempuh khas menurut data komunitas**: "kebanyakan
pendaki mencapai Pos 3 dalam 3 jam".

Struktur segmen Strava dipakai untuk menghasilkan keyakinan keputusan ala AllTrails.

### Aturan 4: umpan aktivitas menjadi kabar jalur

Umpan Strava berisi aktivitas orang lain. Di sini isinya perubahan status resmi dan
laporan kondisi untuk gunung yang diikuti pengguna. Ini yang memberi **Trigger** dalam
kerangka Fogg: alasan membuka aplikasi ketika tidak sedang merencanakan pendakian.

### Aturan 5: bundel Traveloka menjadi bundel keberangkatan

Traveloka membundel tiket, hotel, dan atraksi. Di sini yang dibundel: izin, cuaca,
daftar persiapan, status jalur, dan kesiapan — dalam satu layar keberangkatan.

---

## 4. Arsitektur fitur, tiga fase

Urutannya ditentukan ketergantungan, bukan kehati-hatian: Fase 2 dan 3 keduanya
membutuhkan kosakata visual yang dibangun Fase 1. Mengerjakan Fase 2 lebih dulu berarti
membangun peta dua kali.

### Fase 1 — Memberi wujud pada yang sudah ada

Tidak ada konsep domain baru. Seluruh datanya sudah ada dan hanya belum ditampilkan.

1. **Komponen peta** yang dapat dipakai ulang, memuat MapLibre secara malas seperti hike
   mode. Dipakai di halaman jalur, hasil rekomendasi, dan nanti hasil pendakian.
2. **Halaman jalur digambar ulang**: peta jalur, profil elevasi, foto komunitas,
   daftar pos sebagai perjalanan bukan tabel.
3. **Foto komunitas ditampilkan** kepada pendaki, bukan hanya moderator.
4. **Penalaran Route Fit menjadi visual**: skor per faktor sebagai batang, bukan butir
   prosa di panel terlipat. Skor internal tetap tidak pernah ditampilkan (BR-09).
5. **Kesiapan menjadi keadaan yang terlihat**, bukan satu label.

### Fase 2 — Menutup lingkaran

6. **Halaman hasil pendakian**: jejak GPS di peta, profil elevasi yang benar-benar
   ditempuh, waktu, statistik. Ini menjawab peak-end rule.
7. **Progres pribadi**: gunung yang sudah didaki sebagai peta Indonesia, akumulasi
   elevasi, jumlah pendakian, rekor pribadi yang tidak pernah dibandingkan dengan orang
   lain.
8. **Waktu tempuh khas antarpos** dari data komunitas (Aturan 3).

### Fase 3 — Kehadiran komunitas dan pertumbuhan

9. **Kabar jalur**: umpan perubahan status dan laporan untuk gunung yang diikuti
   (Aturan 4).
10. **Terima kasih** pada laporan kondisi (Aturan 2).
11. **Papan kontribusi** (Aturan 1).
12. **Halaman jalur publik** yang dapat diindeks pencarian, tanpa login, memuat
    keterangan dasar dan status resmi. Ini mesin pertumbuhan AllTrails, dan saat ini
    aplikasi ini tidak terlihat oleh siapa pun yang mencari nama gunung di mesin
    pencari. Data pribadi dan fitur perencanaan tetap di balik login.

---

## 5. Yang sengaja tidak dikerjakan

- **Papan peringkat kecepatan pendakian.** Alasannya keselamatan, bukan teknis.
  Mekanismenya tetap dipakai dengan metrik yang diganti (Aturan 1).
- **Menyalin super-app Traveloka.** Produk ini satu tujuan, dan menambah layanan tak
  berkaitan akan mengaburkannya.
- **Skor internal ditampilkan.** BR-09 mengunci ini dan tetap dikunci.

---

## 6. Ukuran keberhasilan

Bukan jumlah fitur yang terpasang, melainkan:

1. **North Star yang sudah ada (§63)**: bagian rencana yang sampai ke pre-departure
   check.
2. **Pendaki kembali antar-pendakian**: sesi yang terjadi ketika pengguna tidak sedang
   merencanakan apa pun. Nol saat ini menurut definisi, karena tidak ada alasan membuka
   aplikasi.
3. **Jalur yang datanya dilengkapi kontributor**, dari 29 yang kosong.
4. Seluruh ambang mutu yang sudah berlaku tetap berlaku: WCAG 2.2 AA, anggaran query,
   anggaran ukuran halaman, dan suite yang hijau.
