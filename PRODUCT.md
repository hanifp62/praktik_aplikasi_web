# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Pendaki gunung di Indonesia, dengan pemula sebagai pengguna utama. Mereka merencanakan
pendakian dari ponsel, sering di jaringan lambat, dan sebagian besar keputusan diambil
berhari-hari sebelum berangkat, bukan saat di jalur.

Tiga peran lain yang nyata dan sudah berjalan di sistem:

- **Admin kurator data**: memasukkan gunung, jalur, status resmi, dan perizinan.
- **Moderator**: menilai laporan kondisi dari komunitas sebelum terbit.
- **Pemandu bersertifikat jenjang Ahli**: menyumbang data jalur untuk kawasan yang
  disahkan baginya, termasuk geometri dan koordinat pos.

## Product Purpose

Membantu pendaki memilih jalur yang sesuai dengan pengalaman yang benar-benar ia miliki,
lalu bersiap sampai benar-benar layak berangkat.

Ukuran keberhasilannya sudah ditetapkan PRD §63 dan tidak diubah: bagian rencana yang
sampai ke pre-departure check, bukan jumlah pengguna terdaftar.

## Positioning

Yang tidak dapat disalin produk sebelah adalah **model kepercayaan datanya**, bukan
daftar fiturnya.

Setiap keterangan membawa sumber, jenis sumber, waktu, keadaan verifikasi, dan
cakupannya (§60). Keterangan resmi dari pengelola kawasan tidak pernah tercampur dengan
laporan sesama pendaki (§92). Ketiadaan data dinyatakan sebagai UNKNOWN dan tidak pernah
dianggap BUKA (§95). Penilaian kecocokan dijelaskan per faktor, dan skor internalnya
tidak pernah ditampilkan sebagai angka (BR-09).

## Growth

Pemasaran adalah bagian dari rancangan produk, bukan lapisan yang ditempel setelahnya.
Kontrak SEO teknisnya ada di `docs/ARCHITECTURE.md`; struktur URL publiknya ada di
`PRD.md` §145.2. Bagian ini hanya soal strategi.

**Corong.**

```text
SEARCH → HALAMAN JALUR PUBLIK → ROUTE FIT → SHORTLIST → TRIP
       → HIKE → REPORT → PROGRESS → SHARE → PENGGUNA BARU
```

Corong ini menutup lingkaran: pendaki yang selesai melapor menghasilkan bahan yang
membuat halaman publik berguna bagi pendaki berikutnya. Pertumbuhan datang dari
kegunaan, bukan dari volume halaman.

**Halaman publik yang masuk akal.** Halaman gunung, halaman jalur/via, halaman
kawasan atau niat pencarian, panduan persiapan, dan ringkasan kondisi terkini bila
datanya memang ada. Ditulis untuk orang yang sedang merencanakan pendakian sungguhan.

**Mekanisme pertumbuhan.** Pencarian organik; berbagi perbandingan; data komunitas;
kemitraan kampus dan komunitas luar ruang; progresi pribadi; perencanaan berulang.

**Yang tidak dikejar.** Trafik sia-sia. Kunjungan yang tidak pernah sampai ke Route Fit
tidak dihitung sebagai keberhasilan, dan tidak ada mekanisme pertumbuhan yang boleh
mengorbankan kepercayaan data atau melunakkan bahasa keselamatan.

## Operating Context

- **Tidak ada sinyal di hampir seluruh gunung Indonesia.** Ini menentukan rancangan,
  bukan sekadar kasus tepi: jejak direkam di perangkat dan dikirim setelah turun, dan
  service worker menolak menyajikan halaman dari cache karena status "BUKA" yang basi
  lebih berbahaya daripada halaman yang gagal terbuka.
- **Tiga zona waktu** (WIB, WITA, WIT). Waktu ditampilkan menurut zona gunungnya, bukan
  zona pembacanya, dan penandanya selalu disebut (§93).
- **Izin pendakian punya jendela pemesanan** yang bergerak relatif terhadap tanggal
  rencana, misalnya Semeru dibuka H-30 dan ditutup H-2.
- **Prakiraan cuaca dari BMKG**, diambil terjadwal dua kali sehari, tidak pernah
  dipanggil langsung dari peramban (§45).
- **Status jalur berubah tanpa pemberitahuan**: penutupan karena kebakaran, aktivitas
  vulkanik, atau pemulihan jalur diumumkan sepihak oleh pengelola kawasan.

## Capabilities and Constraints

- **Route Fit Engine**: menilai kecocokan jalur terhadap profil pendaki lewat tujuh
  faktor berbobot, dengan hard constraint yang mengecualikan jalur tertutup atau tidak
  terbit. Skor internal untuk peringkat dan audit saja.
- **Penilaian kesiapan**: menggabungkan kecocokan jalur, kelengkapan persiapan, dan
  kondisi terkini. Bukan izin berangkat, melainkan dukungan keputusan.
- **Status resmi berjenjang** gunung, jalur, segmen, dengan kaskade satu arah:
  pembatasan turun, kelonggaran tidak (§42).
- **Kredensial ahli** mengikuti skema nyata BNSP, LSP, APGI, SKKNI, jenjang Muda, Madya,
  Ahli, berlaku tiga tahun. Hak menyumbang data gugur sendiri saat sertifikat
  kedaluwarsa.
- **Perekaman jejak** bersifat pilihan, default mati, dapat dihapus pemiliknya.
- Terminologi tetap: pos (bukan checkpoint dalam teks pengguna), jalur, kawasan, pengelola.

## Brand Commitments

- **Seluruh antarmuka berbahasa Indonesia**, termasuk pesan galat dan validasi.
- **Nadanya mengakui batas pengetahuannya.** Kekosongan data disajikan sebagai tahapan
  dengan menyebut siapa yang ditunggu, bukan sebagai permintaan maaf atau kotak kosong.
- **Tidak pernah menghukum keputusan yang benar.** Pendakian yang dibatalkan karena cuaca
  tetap tercatat sebagai pendakian.
- **Tidak ada perbandingan kecepatan antarpendaki.** Mengejar waktu di gunung membunuh
  orang; energi kompetitif diarahkan ke kontribusi data.

## Evidence on Hand

- `PRD.md` (3365 baris): sumber kebenaran produk, termasuk 15 aturan bisnis terkunci.
- `docs/superpowers/specs/2026-09-20-pengalaman-pendaki-design.md`: analisis ATM terhadap
  AllTrails, Strava, Traveloka beserta aturan adaptasinya.
- `docs/PROTOKOL-UJI-KEGUNAAN.md`: protokol uji dengan delapan tugas dan SUS berbahasa
  Indonesia, kini dapat dijalankan dari halaman Admin, Studi Kegunaan.
- **Data nyata**: 16 gunung dengan koordinat dari Wikidata, status resmi September 2026,
  syarat izin Semeru dan Rinjani, delapan badan resmi.
- **Yang belum ada dan tidak boleh dikarang**: geometri jalur, koordinat pos, foto jalur
  resmi, dan hasil uji dengan pengguna sungguhan. Dua puluh sembilan jalur tercatat
  namanya dengan karakteristik sengaja dikosongkan.

## Product Principles

1. **Yang tidak diketahui dinyatakan, bukan ditebak.** Nol bukan pengganti null, dan
   diamnya sistem tidak boleh terbaca sebagai kabar baik.
2. **Resmi dan komunitas tidak pernah tertukar**, termasuk secara visual.
3. **Menjelaskan, bukan menilai.** Pendaki harus dapat menyebut sendiri alasan sebuah
   jalur tidak direkomendasikan.
4. **Keselamatan mengalahkan keterlibatan.** Setiap mekanisme yang menaikkan penggunaan
   diuji dulu terhadap kemungkinan ia mendorong pengambilan risiko.
5. **Catatan tetap jujur terhadap waktunya.** Hasil lama tidak dihitung ulang diam-diam;
   ia diberi pengakuan bahwa keadaannya sudah berubah.

## Accessibility & Inclusion

WCAG 2.2 AA sebagai syarat, bukan cita-cita (§87), dan dijaga test: kontras 4.5:1 untuk
teks normal dan 3:1 untuk komponen non-teks, dihitung bukan ditaksir; warna tidak pernah
menjadi satu-satunya pembawa arti; peta memakai `role="region"` dengan padanan teks,
tidak pernah `role="img"`; setiap grafik membawa rangkuman angkanya sebagai teks.

Ponsel adalah platform utama (§88), termasuk peranti kelas bawah di jaringan lambat.

## Prioritas dan peran

Urutan prioritas pemilik produk, dan urutan ini mengikat ketika dua kebutuhan
bertabrakan:

1. IMK/HCI;
2. UI/UX;
3. logika produk dan arsitektur informasi;
4. arsitektur dan skalabilitas;
5. keamanan, privasi, kepatuhan;
6. keandalan dan penanganan kegagalan;
7. kesiapan operasional dan observability;
8. manajemen dan konsistensi data;
9. pemasaran, SEO, pertumbuhan.

Agen yang mengerjakan produk ini menggabungkan peran lead engineer, perancang UI/UX,
perancang produk, software engineer, QA/audit engineer, peninjau keamanan dan privasi,
serta technical product manager. Sasarannya mutu berkelas perusahaan, bukan kerumitan
berkelas perusahaan.

## ATM — Amati, Tiru, Modifikasi

Cara belajar dari produk yang sudah terbukti. Sasarannya bukan kemiripan visual,
melainkan dampak IMK/UI/UX dan dukungan keputusan yang setara atau lebih baik.

**Amati.** Pelajari masalah yang dipecahkan, konteks pemakaian, model mental pengguna,
pola interaksi, hierarki informasi, mekanisme keputusan, umpan balik, mekanisme
kepercayaan, mekanisme retensi, batasan operasional, dan efek UX yang terukur.

**Tiru.** Ambil hanya mekanisme atau prinsip yang mendasarinya. **Jangan** menyalin
merek, warna dan identitas visual, tata letak per piksel, teks milik orang lain, kode
sumber, atau asumsi yang hanya berlaku di domain lain.

**Modifikasi.** Sesuaikan dengan pendakian gunung Indonesia: pengguna lokal, model
domain Mountain ≠ Trail, konsep via dan basecamp, status resmi, kenyataan data cuaca,
konteks luar ruang dan seluler, koneksi lemah, sensitivitas lokasi, laporan kondisi
komunitas, persiapan, dan masalah produk yang sebenarnya.

Jangan menambahkan fitur pesaing semata-mata karena pesaing memilikinya.

## Rujukan dan apa yang diambil

**AllTrails — kepercayaan lewat konteks.** Mekanisme yang dipelajari: penemuan jalur,
penyaringan, karakteristik rute, kondisi, ulasan sebagai intelijen komunitas, rute
tersimpan. Efek yang dituju: *"Saya memahami ongkos dan konteks jalur sebelum
berangkat."* Adaptasinya: jarak, elevation gain/loss, durasi, medan, tuntutan teknis,
kompleksitas navigasi, konteks segmen dan checkpoint, status resmi, konteks cuaca,
kondisi terbaru, dan celah persiapan.

**Traveloka — kemudahan memutuskan.** Mekanisme: pencarian, filter, penyimpanan,
perbandingan, penyempitan pilihan, menunda komitmen tanpa kehilangan kandidat. Efek yang
dituju: *"Saya dapat mempersempit pilihan, menyimpan kandidat, membandingkan, lalu
memutuskan."* Adaptasinya: permukaan Pertimbangkan, shortlist, perbandingan maksimum
lima, berbagi read-only, dan membuat trip dari jalur terpilih.

**Strava — kesinambungan dan kemajuan pribadi.** Mekanisme: siklus hidup aktivitas,
riwayat, perbandingan dengan diri sendiri, penemuan rute, komunitas, alasan untuk
kembali. Efek yang dituju: *"Pengalaman sebelumnya berharga dan saya dapat melihat
perkembangan diri saya."* Adaptasinya: riwayat pendakian dan progresi elevation gain,
jarak, durasi, serta paparan kompleksitas rute. Kuantitas kontribusi tidak dijadikan
metrik papan peringkat inti.

## Model kerja IMK

Alur: riset → konteks pemakaian → arsitektur informasi → perancangan interaksi →
prototipe → uji kegunaan → iterasi → implementasi → pengukuran. Protokol ujinya ada di
`docs/PROTOKOL-UJI-KEGUNAAN.md`.

Tiga hal yang tidak boleh disamakan:

- kilau visual bukan kegunaan;
- banyaknya fitur bukan mutu produk;
- CSS responsif bukan UX seluler yang baik, dan test hijau bukan kebenaran produksi.

Prinsip intinya: progressive disclosure; explainability; transparansi sumber; tanpa
false precision; interaksi sadar konteks; beban kognitif rendah di mode lapangan;
mobile-first; aksesibilitas sejak perancangan; state yang dapat diduga; serta state
kosong, galat, dan fallback yang bermakna.

## Hasil UX yang diukur

Enam hasil yang dituju, dan semuanya masih hipotesis sampai divalidasi:

1. **Discoverability** — pengguna menemukan jalur yang relevan.
2. **Comprehension** — pengguna memahami karakteristik jalur.
3. **Comparability** — pengguna melihat trade-off.
4. **Confidence** — pengguna memahami alasannya dan sumbernya.
5. **Preparedness** — pengguna tahu apa yang masih kurang.
6. **Continuity** — pengguna memahami progresinya dan punya alasan untuk kembali.

Target validasi yang diusulkan: efektivitas keputusan ≥90% peserta memilih jalur relevan
tanpa intervensi moderator; pemahaman keputusan ≥80% menyebut dua alasan rekomendasi
dengan benar; efisiensi perbandingan ≥80% membandingkan tiga jalur dalam dua menit pada
uji yang ditentukan; pemahaman kepercayaan ≥90% membedakan informasi resmi dari
komunitas; SUS ≥80 untuk studi yang ditentukan.

**Angka-angka ini tidak boleh dikarang.** Selama studinya belum dijalankan, ia target,
bukan hasil.
