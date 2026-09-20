# Dari Katalog ke Mesin Kecocokan — Rancangan

Tanggal: 21 September 2026
Dasar: PRD (3365 baris, 128 bagian)
Metode: ATM (amati, tiru, modifikasi) terhadap AllTrails, Strava, Traveloka

---

## 1. Masalahnya, diukur

Keluhan pemilik produk, empat kali berturut-turut: antarmuka masih terbaca seperti CRUD
sederhana. Diukur pada kode, bukan ditaksir:

| Yang diukur | Nilai |
|---|---|
| Halaman yang menampilkan **penjelasan kecocokan** (`factor-bars`) | **1 dari 12** |
| Halaman Jelajahi Jalur menampilkan kecocokan | **tidak sama sekali** |
| Item menu utama | **9 kata benda** |
| Tahap dalam Core Product Loop (§7) | **8** |
| Tahap "simpan/pertimbangkan" | **tidak ada** |
| Perbandingan dapat dicapai dari halaman jelajah | **tidak** |
| Berkas view berubah pada fase visual sebelumnya | 81, dan **59 di antaranya hanya ganti nama kelas** |

**Diagnosis:** §8 PRD menyebut diferensiator produk ini adalah menghubungkan karakteristik
pendaki dengan karakteristik jalur lalu menjelaskan alasannya. Mesin itu terbangun penuh
dan dipanggil di satu halaman. Di sebelas halaman lain, aplikasi ini memang katalog, dan
katalog memang terbaca sebagai CRUD.

Fase visual sebelumnya memperbaiki **cara membangun** (token, kontras, penjaga) dan bukan
**apa yang dibangun**. Itu sebabnya 817 test bertambah tanpa satu pun dampak terasa.

---

## 2. Riset tiga rujukan, dan satu titik temu yang tidak diduga

### AllTrails

Tujuan pengguna yang sebenarnya bukan "menemukan jalur indah" melainkan **memperkirakan
ongkos**: berapa waktu, tenaga, dan sumber daya yang harus ia keluarkan.

Titik sakit terbesarnya, dari beberapa studi kasus independen: pengguna bolak-balik antar
halaman jalur untuk mengingat elevasi, durasi, dan kesulitan, karena tidak ada cara
melihat beberapa pilihan berdampingan. Hasilnya decision fatigue dan perencanaan melambat.

### Traveloka

Corongnya bertahap: **lihat → simpan → bandingkan → pesan**, dan riset menemukan masalah
di ketiga tahap awal. Bunyi masalah tahap membandingkan nyaris identik dengan AllTrails:
harus membuka halaman satu per satu.

Studi pembanding Traveloka versus Tiket.com menemukan Traveloka unggul pada **kegunaan**
karena kekayaan filternya, bukan karena tampilannya lebih cantik.

### Strava

Segment adalah **perbandingan yang dipersempit** ke kelompok acuan yang cukup kecil
sehingga menang terasa mungkin. Progress bar bekerja lewat endowed progress effect. Tujuh
puluh persen pengguna menyebut membandingkan diri sebagai pendorong utama.

### Titik temunya

**Ketiganya bukan katalog. Ketiganya mesin pembanding.**

Dan §8 PRD, yaitu menghubungkan pendaki dengan jalur lalu menjelaskan alasannya,
**juga sebuah perbandingan**: antara kemampuan seseorang dan tuntutan sebuah jalur.

Tujuan produk ini dan mekanik ketiga rujukan itu ternyata satu hal yang sama, dan
selama ini dibangun sebagai dua hal yang berbeda.

---

## 3. Kelayakan, diperiksa sebelum dirancang

`RouteFitService::evaluate()` menerima `?HikingGoal $goal` yang **boleh null**, serta
menerima `$status`, `$segmentRestrictions`, dan `$permit` yang sudah dimuat dari luar.

Artinya kecocokan dapat dihitung untuk dua belas jalur sekaligus **tanpa satu query
tambahan pun**, asalkan status dan pembatasan segmennya di-eager-load sekali. Anggaran
query §96 aman, dan mesinnya memang sudah dirancang untuk dipakai seperti ini.

Ini diperiksa lebih dulu justru karena rancangan yang indah dan tidak dapat dibangun
adalah cara paling mahal untuk membuang waktu.

---

## 4. Rancangan

### 4.1 Kecocokan ambient, dua tingkat

| Tingkat | Masukan | Muncul di | Menjawab |
|---|---|---|---|
| **Kecocokan dasar** | profil, pengalaman, status resmi | setiap tempat jalur muncul | "Cocokkah untuk saya?" |
| **Kecocokan untuk rencana ini** | + hiking goal | alur rekomendasi, trip | "Cocokkah untuk rencana tanggal ini?" |

Keduanya memakai mesin yang sama. Yang membedakan hanya ada tidaknya goal, dan mesin itu
sudah menerima goal kosong.

Pengungkapan bertahap mengikuti §89:

- **Baris daftar**: label kecocokan + satu alasan terkuat, satu kalimat.
- **Halaman detail**: tiga lapis penuh lewat `factor-bars` yang sudah terbangun.

**Aturan keras:** skor internal tidak pernah bocor ke antarmuka (BR-09), dan labelnya
tetap tiga kata PRD, yaitu Cocok, Perlu persiapan, Kurang cocok (§91).

**ATM:** AllTrails menempel difficulty pada setiap baris. Kita menempel *kecocokan untuk
Anda*, yang lebih tajam karena bukan sifat jalur melainkan hubungan pembaca dengan jalur
itu. Modifikasinya: difficulty adalah angka tetap untuk semua orang, kecocokan berubah
per pembaca.

### 4.2 Pertimbangkan, tahap corong yang hilang

Kandidat tersimpan per pengguna, **maksimal lima**, bertahan lintas perangkat dan sesi.
Baki ringkas yang ikut di setiap halaman, menunjukkan apa yang sedang ditimbang.

**Mengapa lima:** lebih dari lima kolom tidak terbaca pada lebar 400px (§88), dan riset
AllTrails menunjukkan beban keputusan justru naik ketika pilihan menumpuk.

**ATM:** ini persis tahap *save* pada corong Traveloka yang risetnya tunjukkan
bermasalah, dan di sini belum ada sama sekali. Modifikasinya: batas keras lima, karena
yang dibandingkan bukan harga melainkan keselamatan, dan daftar panjang menunda keputusan
alih-alih memperbaikinya.

### 4.3 Bandingkan, dibingkai sebagai ongkos versus kemampuan

Bukan tabel angka mentah berdampingan.

- **Kolom** = jalur yang sedang ditimbang
- **Baris** = dimensi ongkos: waktu, tanjakan, kecuraman, tuntutan teknis, kerumitan
  navigasi, ketersediaan air, perizinan
- **Setiap sel** ditandai terhadap kemampuan pembaca, bukan hanya terhadap jalur lain

Dapat dicapai **dari halaman jelajah**, bukan hanya dari alur rekomendasi.

**ATM:** menjawab langsung temuan AllTrails bahwa tujuan pengguna adalah memperkirakan
ongkos, dan temuan Traveloka bahwa membandingkan memaksa membuka halaman satu per satu.
Modifikasinya: sel tidak netral. Traveloka membandingkan hotel terhadap hotel; kita
membandingkan jalur terhadap *pembacanya*.

### 4.4 Tangga kemajuan

Jalur diposisikan terhadap yang **sudah didaki pembacanya**: "satu tingkat di atas
pendakian terakhir Anda", "setara dengan yang sudah pernah Anda selesaikan".

Dinyatakan sebagai pita, bukan angka (§91). Diturunkan dari riwayat pendakian yang sudah
tersimpan, bukan kolom baru.

**ATM:** mekanik Strava adalah perbandingan yang dipersempit sampai menang terasa
mungkin. Modifikasinya, dan ini modifikasi terpenting di seluruh dokumen: **acuannya diri
sendiri, bukan pendaki lain.**

Papan peringkat kontribusi tetap ditolak. Memberi hadiah pada jumlah menghasilkan laporan
bervolume tinggi bermutu rendah, yang menyerang persis model kepercayaan data (§60) yang
menjadi nilai produk ini. Meniru Strava sampai ke papan peringkatnya akan meniru
kerusakannya.

### 4.5 Navigasi, dari sembilan kata benda ke lima permukaan

| Sekarang | Menjadi | Isi |
|---|---|---|
| Jalur + Buat Rencana | **Jelajah** | cari, saring, kecocokan ambient, pintu ke rekomendasi tajam |
| — | **Pertimbangkan** | baki kandidat dan perbandingan |
| Trip | **Perjalanan** | rencana, persiapan, kesiapan, mode pendakian |
| Riwayat + Progres | **Progres** | riwayat, tangga kemajuan, dampak laporan |
| Kabar | **Kabar** | kesegaran kondisi dari komunitas |

Dasbor tetap halaman mendarat, bukan item menu. Moderasi dan Admin pindah ke menu profil:
keduanya peran, bukan tahap perjalanan.

**ATM:** AllTrails lima tab, Strava lima, Traveloka empat. Semuanya campuran satu
permukaan temuan, satu milik-saya, satu tindakan, satu identitas. Sembilan kata benda
adalah struktur basis data yang bocor ke menu.

---

## 5. Ambang keberhasilan, dapat diperiksa

Pemilik produk meminta dampak IMK, UI, dan UX yang **setara dengan ketiga rujukan**.
"Optimal di semua aspek" tidak dapat dibantah maupun dibuktikan, jadi ia diterjemahkan
menjadi ambang yang dapat diukur. PRD sudah menyediakan alatnya.

| # | Ambang | Alat ukur | Asalnya |
|---|---|---|---|
| A1 | Keenam UX validation task §113 selesai tanpa bantuan | uji pakai, 5 sampai 8 pemula | PRD §113 |
| A2 | **SUS ≥ 80** (Sauro-Lewis grade A) | `UsabilityStudyService`, sudah terbangun | ambang produk konsumen teratas |

> **Catatan pada A2, ditemukan saat memeriksa ulang spec ini.**
> `UsabilityStudyService::MINIMUM_RESPONDEN_SUS` bernilai **12**: di bawah itu service
> menolak menerbitkan angka, karena rata-rata SUS dari segelintir responden terlalu goyah
> untuk dilaporkan sebagai angka. Delapan pemula pada A1 tidak akan pernah menghasilkan
> skor SUS.
>
> §111 meminta 5 sampai 8 pemula, 3 sampai 5 berpengalaman, dan 2 sampai 3 pengelola,
> jadi totalnya 10 sampai 16. **A2 baru dapat dinilai bila keseluruhan kelompok itu
> terpenuhi sampai minimal dua belas orang.** Bila pesertanya kurang, A1 tetap dapat
> dinilai dan A2 dilaporkan sebagai belum dapat diukur, bukan ditaksir dari sampel kecil.
| A3 | Membandingkan tiga jalur **tanpa membuka halaman satu per satu** | jumlah navigasi halaman dalam uji tugas | titik sakit AllTrails dan Traveloka |
| A4 | Label kecocokan + alasannya hadir di **≥ 10 dari 12** layar pendaki | sapuan test | §8, §90 |
| A5 | Menjawab "mengapa jalur ini" tanpa membuka dokumentasi | uji tugas 2 | §90 |
| A6 | WCAG 2.2 AA menyeluruh, dihitung bukan ditaksir | suite kontras dan keyboard yang sudah ada | §87 |
| A7 | Tidak meluber pada lebar 400px, target sentuh 44px | penjaga yang sudah ada | §88 |
| A8 | Anggaran query dan halaman tidak naik | penjaga yang sudah ada | §96 |
| A9 | Skor internal tidak pernah bocor | penjaga yang sudah ada | BR-09 |
| A10 | Core loop §7 dapat ditelusuri utuh tanpa menebak | uji tugas menyeluruh | §7 |

A1, A2, A3, A5, dan A10 membutuhkan manusia. Fiturnya sudah ada di sistem
(`UsabilityStudyService`), jadi yang kurang pesertanya, bukan alatnya. A4 dan A6 sampai A9
dijaga test dan berlaku sejak hari pertama.

---

## 6. Yang sengaja tidak dikerjakan

- **Peta lebih dulu seperti AllTrails.** Geometri jalur baru masuk lewat impor admin, jadi
  peta-dulu akan memamerkan kekosongan. Masuk setelah datanya ada, bukan sebelum.
- **Foto jalur sebagai wajah kartu.** Foto berasal dari laporan komunitas dan membawa
  nama pelapornya; §82 dan §83 melarang menerbitkannya begitu saja. Ini alasan hukum dan
  etika, bukan selera.
- **Papan peringkat kontribusi.** Alasannya di 4.4.
- **Skor numerik yang terlihat pengguna.** §91 dan BR-09.
- **Mode gelap.** Belum diputuskan pemilik produk; tercatat terbuka di `DESIGN.md`.

---

## 7. Urutan

1. **Kecocokan ambient** (4.1). Fondasi; semua bagian lain bersandar padanya.
2. **Pertimbangkan** (4.2). Tahap corong yang hilang.
3. **Bandingkan** (4.3). Membutuhkan 1 dan 2.
4. **Tangga kemajuan** (4.4). Berdiri sendiri, dapat berjalan paralel.
5. **Navigasi** (4.5). Paling akhir: memindahkan menu sebelum isinya berubah memindahkan
   orang ke halaman yang belum berubah.

---

## 8. Risiko

| Risiko | Penanganan |
|---|---|
| Kecocokan ambient melanggar anggaran query | Status dan pembatasan segmen di-eager-load sekali, diteruskan ke `evaluate()`. Dijaga penjaga anggaran yang sudah ada. |
| Kecocokan tanpa goal menyesatkan | Labelnya dibedakan dan dinyatakan: "kecocokan dasar" versus "untuk rencana ini". Tidak pernah disajikan seolah sama. |
| Baki pertimbangan jadi tempat menimbun | Batas keras lima, dinyatakan di antarmuka, bukan diam-diam memotong. |
| Tangga kemajuan jadi gatekeeping | Berbunyi sebagai pertumbuhan, bukan izin. Tidak pernah menghalangi, hanya menerangkan. |
| Perombakan merusak yang sudah benar | 817 test yang ada dijalankan utuh di setiap langkah, tanpa disunting kecuali maksudnya memang berubah. |
