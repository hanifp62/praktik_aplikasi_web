# Tangkapan W5

## Cara pengambilan

Koordinator mengambil tangkapan ini pada 8 Oktober 2026 dari harness pratinjau, dengan cara yang sama seperti W1 sampai W4.

- **Sumber halaman.** Suite PHPUnit `Pratinjau` (22 test, 22 lulus) merender halaman sungguhan berisi data contoh
  `DemoSeeder` di SQLite dalam memori. W5 menambah `dasbor-b0`, `dasbor-b1`, `dasbor-b3`, dan `dasbor-b4`, dan
  memperbarui `dasbor` (fase B2).
- **Penyajian.** HTML disajikan statis oleh `php -S 127.0.0.1:8100` dengan router `tests/Pratinjau/statis.php`, tanpa
  basis data lokal dan tanpa Supabase.
- **Peramban.** Chrome lewat MCP chrome-devtools, dalam konteks terisolasi `w5-tangkapan`.
- **Ukuran.**
  - 390 px: emulasi perangkat seluler dengan sentuh dan rasio piksel 1. Tinggi viewport disamakan dengan
    `document.documentElement.scrollHeight` halaman itu (B2 3.153, B0 859, B1 1.756, B3 844, B4 1.885, Bandingkan
    2.274), supaya bilah tab dan strip pendakian tetap di dasar gambar.
  - 1440 x 900: rasio piksel 1, halaman penuh.
  - 320 x 568: hanya untuk pengukuran.
- **Tema.** Terang dan gelap dipilih lewat emulasi `prefers-color-scheme`.
- **Urutan emulasi.** Viewport selalu dikirim bersama skema warna. Navigasi ke halaman pratinjau dilakukan sesudah
  emulasi, karena emulasi seluler memuat ulang halaman ke URL Inertia (`/dashboard`) yang tidak ada di server statis.

## Cakupan W5

W5 mengisi Beranda berfase (spec v3 bagian 6.1, prototype 01). Server memilih satu dari lima fase, dan setiap fase
membawa tepat satu aksi utama.

| Fase | Keadaan | Aksi utama di data contoh |
|---|---|---|
| B0 | Profil pendaki belum diisi | Isi profil pendaki |
| B1 | Tanpa trip mendatang | Rencanakan trip |
| B2 | Trip mendatang | Lanjutkan persiapan |
| B3 | Pendakian berlangsung | Buka mode lapangan |
| B4 | Pendakian selesai paling lama tujuh hari yang belum dilaporkan | Bagikan laporan kondisi |

Bagian lain Beranda:
- **Tenggat terdekat:** jendela izin, terbit prakiraan BMKG, dan pengingat surel, dalam zona gunung.
- **Kabar gunung yang diikuti:** status resmi dipisah dari laporan komunitas.
- **Jalur lain untuk Anda:** paling banyak tiga kartu dengan label, garis profil, dan satu alasan.
- **Tangga tanjakan.**

Di 1440 px, jalur lain dan tangga pindah ke kolom samping 22rem. B0 dan B3 tetap satu kolom.

Tugas 7 juga mengubah dua halaman lain, yang diperiksa di peramban menurut ruling W5-7a:
- sel kecocokan Bandingkan;
- daftar laporan komunitas di rincian jalur.

## Perbandingan dengan acuan

| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |
|---|---|---|---|
| `dasbor-390-light.webp`, `dasbor-390-dark.webp`, `dasbor-1440-light.webp`, `dasbor-1440-dark.webp` (B2) | 01 | Sapaan waktu dengan nama; ringkasan "Gede via Cibodas tinggal 15 hari lagi. 10 butir kritis belum dikonfirmasi."; kartu dengan lencana Camping dan H-15, nama trip, rentang tanggal dengan "sampai", jam mulai WIB, peserta, garis profil dan tiga angka; empat bilah langkah (Jalur dan Rencana bercentang, Persiapan kuning, Cek belum); "3 dari 13 butir kritis dikonfirmasi" dengan ruas dan tiga nama butir; "1 butir cuaca menunggu BMKG, terbit mulai Sel, 20 Okt"; satu tombol "Lanjutkan persiapan"; "Tenggat terdekat" dengan penanda kalender, "Waktu gunung, WIB", pemesanan izin ditutup (H-3), prakiraan BMKG untuk 23 Okt, dan dua pengingat surel; kabar Gunung Gede dengan "Semua kabar", dua status bersumber, kalimat R-012, dan laporan komunitas bergaris putus-putus; tiga tawaran berlabel Cocok dengan alasan yang tidak berulang; tangga Andong 380 m, Batur 600 m, Prau 700 m, dan Gede 1.650 m rencana bergaris putus-putus dengan kalimat perbandingan dan BR-02; kalimat dukungan keputusan; 1440 px kolom utama 704 px dan kolom samping 352 px | Foto hero kartu perjalanan menunggu foto berlisensi W8 (DK-37); visual datanya garis profil (W5-R11). Avatar foto, titik lonceng berwarna, dan baris tanggal-jam kapital di atas sapaan tidak dibangun; sapaan memakai nama lengkap (W5-R20). Tangga tanpa jalur latar dan tanpa catatan bulanan (W5-R18). Setiap anak tangga menyebut gunungnya di baris kedua karena nama jalur tidak unik, sedangkan acuan hanya menulis nama pendek (ruling W5-6a). Alasan tawaran berasal dari faktor mesin, bukan perbandingan dengan riwayat seperti acuan. Status resmi data contoh berlencana "Terverifikasi admin", bukan "RESMI", sesuai sumbernya (R-009). Baris izin tanpa "Buka sistem resmi" karena data contoh tidak mencatat tautan pemesanan; tautannya tampil bila aturan izin mencatatnya (`PermitService::tautanPeriksa()`, `DashboardDeadlineTest`). Kabar data contoh berbunyi "Dicatat 0 detik yang lalu" karena waktu seeding (OPEN ISSUE) |
| `dasbor-b0-390-light.webp`, `dasbor-b0-390-dark.webp`, `dasbor-b0-1440-light.webp`, `dasbor-b0-1440-dark.webp` | Tanpa acuan; susunannya dibandingkan dengan 01 | Satu kartu "Kenali pengalaman Anda" dengan satu tombol "Isi profil pendaki", keterangan bahwa Jelajah tetap dapat dibuka, dan tautan "Lihat jalur tanpa penilaian"; satu kolom di 1440 px | Tidak ada |
| `dasbor-b1-390-light.webp`, `dasbor-b1-390-dark.webp`, `dasbor-b1-1440-light.webp`, `dasbor-b1-1440-dark.webp` | Tanpa acuan | Kartu "Rencanakan pendakian berikutnya" dengan satu tombol "Rencanakan trip"; rencana yang tanggalnya lewat disebut dengan "Ubah tanggal atau batalkan" (W5-R5); keadaan kosong kabar menyebut apa yang ditunggu; jalur lain dan tangga tanpa anak tangga rencana; 1440 px dua kolom 704 dan 352 px | Tidak ada |
| `dasbor-b3-390-light.webp`, `dasbor-b3-390-dark.webp`, `dasbor-b3-1440-light.webp`, `dasbor-b3-1440-dark.webp` | Tanpa acuan | "Pos berikutnya: Telaga Biru" dengan satu tombol "Buka mode lapangan" dan strip pendakian berlangsung di atas bilah tab; satu kolom di 1440 px | Kartu B3 dan strip sama-sama menuju mode lapangan (W5-C8); mode lapangan sendiri milik W6 |
| `dasbor-b4-390-light.webp`, `dasbor-b4-390-dark.webp`, `dasbor-b4-1440-light.webp`, `dasbor-b4-1440-dark.webp` | Tanpa acuan | Hasil dan tanggal selesai, satu tombol "Bagikan laporan kondisi", tautan "Lihat ringkasan pendakian", kalimat bahwa laporan tidak mengubah status resmi; tangga menempatkan Gede 1.650 m sebagai anak tangga tuntas; 1440 px dua kolom 704 dan 352 px | Tidak ada |
| `bandingkan-390-light.webp`, `bandingkan-1440-dark.webp` | 07 (bagian sel kecocokan) | "Kecocokan dasar" tertulis sekali sebagai judul baris, tidak diulang di setiap sel; tombol "Kenapa?" bernama aksesibel "Kenapa? Gede via Selabintana", "Kenapa? Pangrango via Cibodas", dan "Kenapa? Gede via Gunung Putri" (pohon aksesibilitas, expandable); tanpa gulir mendatar di 390 px | Tidak ada perubahan lain di W5 |
| `rincian-laporan-390-light.webp`, `rincian-1440-dark.webp` | 04 (bagian laporan komunitas) | "Laporan kondisi dari komunitas" memakai daftar `divide-y divide-subtle`, bukan kartu di dalam kartu; kalimat "tidak mengubah status resmi" dan lencana Komunitas tetap; tanpa gulir mendatar di 390 px | Tidak ada perubahan lain di W5 |
| `fokus-aksi-390-light.webp` | Tanpa acuan | Cincin fokus pada "Lanjutkan persiapan" sesudah lima kali Tab dari awal halaman | Tidak ada |

## Pengukuran lapisan dan reflow

Diukur dengan `evaluate_script` di pratinjau:

- **Reflow.**
  - 320 x 568: `scrollWidth` sama dengan `clientWidth` (320) untuk kelima halaman Beranda.
  - 390 px: `scrollWidth` 390 untuk kelima halaman Beranda dan Bandingkan.
  - Rincian jalur di 390 px juga tanpa gulir mendatar.
- **Empat langkah kartu B2 di 320 px:** lebar 47, 73, 62, dan 47 px dalam satu baris, sama dengan simulasi rencana.
- **Tinggi kartu mengikuti isi (DK-31).** Kartu `article` dan `section` di `main`:
  - B2 9, B1 7, B4 7, B0 1, dan B3 1;
  - tidak ada yang `scrollHeight`-nya melebihi `clientHeight`;
  - tidak ada tinggi tetap di atribut gaya.
- **Kolom samping 1440 px:**
  - B2, B1, dan B4: `grid-template-columns` 704px dan 352px;
  - B0 dan B3: satu kolom, tanpa grid dua kolom.

## Jalur papan ketik

Diuji dengan tombol Tab sungguhan dan perekam `focusin`. Setiap kendali yang dilewati menampilkan cincin fokus.

- **390 px, B2.** Urutan Tab:
  1. tautan lewati;
  2. Pengingat;
  3. Profil;
  4. nama trip "Gede via Cibodas";
  5. "Lanjutkan persiapan";
  6. "Semua kabar";
  7. tiga tautan nama jalur tawaran. Setiap kartu bergulir masuk pandangan, dengan posisi tegak yang sama;
  8. "Lihat progres";
  9. bilah tab.

  Tidak ada yang tertutup bilah tab: `elementFromPoint` di tengah kendali mengembalikan kendali itu sendiri. "Buka
  sistem resmi" dan "Ikuti Gunung Gede" tidak tampil di data contoh. Tautan resmi tidak tampil karena tanpa tautan
  pemesanan; tombol Ikuti tidak tampil karena gunungnya sudah diikuti.
- **390 px, B0, B1, B3, B4.** Sesudah tiga kendali kepala, fokus pertama di isi halaman adalah aksi utama fase itu:
  - B0: "Isi profil pendaki", lalu "Lihat jalur tanpa penilaian";
  - B1: "Rencanakan trip", lalu "Ubah tanggal atau batalkan";
  - B3: "Buka mode lapangan", lalu strip pendakian berlangsung;
  - B4: "Bagikan laporan kondisi", lalu "Lihat ringkasan pendakian".
- **1440 px, B2.** Urutan:
  1. tautan lewati, logo, dan lima navigasi;
  2. Simpanan, menu akun, Pengingat;
  3. kolom utama: nama trip, "Lanjutkan persiapan", "Semua kabar";
  4. kolom samping: tiga tawaran jalur.

  Jadi kolom utama lebih dulu, kolom samping sesudahnya.

## Batas harness

Belum dapat dibuktikan di pratinjau statis karena butuh server Laravel (izin Q1 `memount:demo` atau Playwright Q17):
- kiriman "Ingatkan saat dibuka";
- kiriman "Ikuti {gunung}", termasuk perpindahan fokus ke judul kabar dan pengumuman "Anda mengikuti {gunung}.";
- keadaan memproses kedua tombol itu.

Data contoh juga tidak memunculkan keduanya. Perilakunya dijaga test Feature:
- `DashboardPhaseTest`;
- `PermitWindowReminderTest`;
- `TrailNewsTest`, termasuk asersi sumber ruling W5-FH.
