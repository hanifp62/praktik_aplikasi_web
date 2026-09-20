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
