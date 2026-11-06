# Tangkapan W4

## Cara pengambilan

Koordinator mengambil tangkapan ini pada 7 Oktober 2026 dari harness pratinjau, dengan cara yang sama seperti W1 sampai W3.

- **Sumber halaman.** Suite PHPUnit `Pratinjau` (18 test, 18 lulus) merender halaman sungguhan berisi data contoh
  `DemoSeeder` di SQLite dalam memori. W4 menambah dan memperbarui `test_ruang_kerja`, `test_rencanakan`,
  `test_persiapan`, dan `test_cek_kesiapan`.
- **Penyajian.** HTML disajikan statis oleh `php -S 127.0.0.1:8100` dengan router `tests/Pratinjau/statis.php`, tanpa
  basis data lokal dan tanpa Supabase.
- **Peramban.** Chrome lewat MCP chrome-devtools, dalam konteks terisolasi `w4-tangkapan`.
- **Ukuran.**
  - 390 x 844, ditambah 360 x 740 dan 320 x 568 untuk pengukuran: emulasi perangkat seluler dengan sentuh dan rasio
    piksel 1.
  - 1440 x 900: rasio piksel 1.
  - Semua tangkapan adalah halaman penuh.
- **Tema.** Terang dan gelap dipilih lewat emulasi `prefers-color-scheme`.
- **Emulasi viewport.** Viewport wajib dikirim bersama skema warna di setiap pemanggilan emulasi, karena emulasi tanpa
  viewport mengembalikan jendela ke ukuran bawaan (504 px, rasio piksel 1,5). Setiap ukuran dicek lewat `innerWidth`.
- **Lapisan tetap.** Pada tangkapan halaman penuh, lapisan tetap (bilah tab dan bar aksi) tampak di tengah halaman.
  Itu artefak cara potret, bukan tata letak.

## Cakupan W4

W4 mengisi klaster Trip di bawah kerangka W2.

- **Indeks trip:** kartu trip terdekat dengan jenis, H-n, tanggal, langkah berikutnya, label kecocokan, dan satu aksi,
  lalu kelompok Berlangsung, Mendatang, dan Selesai.
- **Ruang kerja:** empat langkah (Jalur, Rencana, Persiapan, Cek) dengan kepala trip.
- **Rencanakan:**
  - kecocokan untuk rencana dihitung server lewat muat ulang parsial;
  - jendela izin;
  - kalimat terbit prakiraan BMKG;
  - jam dalam zona gunung;
  - peserta 1 sampai 12.
- **Persiapan:**
  - hitungan butir kritis dengan ruas, dan kelompok kategori;
  - butir cuaca yang menunggu BMKG;
  - centang optimis dengan Urungkan lewat antrean per halaman;
  - daftar pengingat.
- **Cek kesiapan:**
  - kata DK-48 beserta sebabnya;
  - tiga hal yang diperiksa;
  - "Sejak cek terakhir";
  - pesan hari keberangkatan;
  - aksi "Selesaikan N butir" dan "Saya sudah meninjau semuanya".

Data contoh tiap halaman dibuat di test terpisah. Karena itu hitungannya berbeda antarhalaman, misalnya Persiapan 3
dari 13 butir kritis dan Cek 5 dari 13.

## Perbandingan dengan acuan

| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |
|---|---|---|---|
| `trip-390-light.webp`, `trip-390-dark.webp`, `trip-1440-light.webp`, `trip-1440-dark.webp` | Tanpa acuan; susunannya dibandingkan dengan 09 | Kartu utama dengan jenis, H-15, rentang tanggal, "Langkah berikutnya", label kecocokan "untuk rencana ini", dan satu tombol penuh "Lanjutkan persiapan"; kelompok Selesai dengan trip batal dan selesai; tombol kepala "Rencanakan trip" sekunder sesudah perbaikan gerbang (W4-G1) | Data contoh trip terdekat tanpa butir persiapan, sehingga langkah berikutnya menyebut "Belum ada butir persiapan" (data, bukan kode); jarak atas halaman `py-6`, sedangkan acuan sekitar 16 px (catatan W2 dan W3) |
| `ruang-kerja-390-light.webp`, `ruang-kerja-390-dark.webp`, `ruang-kerja-1440-light.webp`, `ruang-kerja-1440-dark.webp` | Tanpa acuan | 1440 dua kolom: kolom Langkah dengan empat langkah dan keadaannya, kolom isi dengan kecocokan untuk rencana, izin, pengingat, catatan, dan tindakan; sesudah perbaikan gerbang tidak ada tombol penuh sebelum hari keberangkatan, dan titik fokusnya langkah berikutnya | Kalimat izin ruang kerja belum memakai penentu per keadaan jendela (ruling M1, menyusul W8); aksi per fase hari-H diputus Beranda W5 |
| `rencanakan-390-light.webp`, `rencanakan-390-dark.webp`, `rencanakan-1440-light.webp`, `rencanakan-1440-dark.webp` | 08 | Baris langkah dengan Rencana menyala; kartu jalur dengan "Ganti" dan angka padat; label kecocokan "untuk rencana ini" dengan alasannya di kotak; kontrol segmen tiga jenis dengan lamanya; kalimat rentang tanggal; kotak izin "Pemesanan izin terbuka sampai ..." dengan pengelola dan H-30 sampai H-3; kalimat terbit prakiraan BMKG; jam dengan zona gunung (WIB); stepper peserta "Termasuk Anda"; kalimat kuota belum terverifikasi; nama, catatan, sakelar "Ingatkan saya"; bar aksi "Simpan rencana" | Kalender grid diganti isian tanggal native (W4-R9); chip jam diganti isian jam (W4-R10); pengingat mati secara bawaan (W4-R13); kotak izin tanpa ikon kalender (W4-R18); jenis ketiga bernama "Multi-hari", bukan "Panjang" (enum `TripType`); alasan kecocokan memakai kalimat mesin yang lebih umum dari acuan (urutan dan isi alasan diputus W5, W3-C7); baris status resmi dengan kalimat R-012 di kartu jalur adalah tambahan wajib R-012 |
| `persiapan-390-light.webp`, `persiapan-390-dark.webp`, `persiapan-1440-light.webp`, `persiapan-1440-dark.webp` | 09 | Kepala trip dan baris langkah; keadaan persiapan dengan ruas per butir kritis dan nama yang belum; kalimat BR-10; kelompok dengan hitungan; lencana Kritis; butir cuaca "Menunggu BMKG: terbit mulai ..." berkotak putus-putus; keterangan izin di butirnya; daftar pengingat dengan waktu dan zona | Nama kelompok mengikuti `PreparationCategory` (Perlengkapan, Pemahaman jalur, Logistik, Cuaca, Status resmi), bukan nama acuan; tombol "Tidak berlaku" di setiap baris (W4-R19); butir status resmi tanpa status hidup (W4-R22); kotak menunggu tanpa glyph jam (W4-R18); lencana Kritis memakai token W1, bukan lencana hitam acuan; catatan per butir seperti "Dibawa Dimas" tidak ada karena belum ada fitur catatan butir (perlengkapan pribadi W7) |
| `cek-kesiapan-390-light.webp`, `cek-kesiapan-390-dark.webp`, `cek-kesiapan-1440-light.webp`, `cek-kesiapan-1440-dark.webp` | 10 | "Keadaan kesiapan" dengan kata DK-48 "Masih perlu persiapan", sebab-sebabnya, dan kalimat R-014; tiga hal bernomor; persiapan dengan ruas dan butir kurang beraksi "Konfirmasi"; butir menunggu BMKG tanpa tombol; status resmi bersumber dengan kalimat R-012; "Prakiraan untuk Kam, 22 Okt" dengan "Belum terbit"; laporan komunitas bergaris putus-putus; "Sejak cek terakhir" dengan tanda = dan +; kotak "Di hari keberangkatan"; "Selesaikan 7 butir" dan "Saya sudah meninjau semuanya" | Kartu gunung api menunggu W8 (W4-R29); kalimat sebab dan peringatan masih memakai kosakata mesin (OPEN ISSUE, W9); baris tambahan "Seluruh daftar" (W4-6a) dan tautan "Simpan hasil cek ini"; status resmi ditulis "Terverifikasi admin, 30 Sep" alih-alih lencana "Terkini"; kartu kecocokan memakai tautan "Lihat penilaian lengkap", bukan panah |

## Pengukuran lapisan dan reflow

Diukur dengan `evaluate_script` di pratinjau:

- **Bar aksi Rencanakan:** tinggi 106 px di 360 x 740 dan di 320 x 568, sama dengan simulasi rencana; `--bar-aksi`
  bernilai `106px`; tanpa gulir mendatar.
- **Baris langkah di 320 px:** empat item setinggi 44 px dalam satu baris.
- **Reflow 320 x 568:** `scrollWidth` sama dengan `clientWidth` (320) untuk indeks trip, ruang kerja, Rencanakan,
  Persiapan, dan Cek kesiapan.

## Jalur papan ketik

Diuji dengan tombol Tab sungguhan dan perekam `focusin`. Setiap kendali yang dilewati menampilkan cincin fokus.

- **390 px, Rencanakan:**
  - urutan: tautan lewati, Kembali ke Trip, langkah Jalur, Ganti, grup jenis trip, tanggal, jam, Kurangi peserta (tetap
    terfokus dengan `aria-disabled` di batas 1), Tambah peserta, nama, catatan, sakelar, lalu "Simpan rencana" di bar
    aksi;
  - "Simpan rencana" tidak tertutup lapisan apa pun (`elementFromPoint` mengembalikan tombol itu sendiri);
  - panah kanan memindah jenis dari Camping ke Multi-hari.
- **390 px, Persiapan:** Tab dari kotak centang pertama mendarat di "Tidak berlaku: Sepatu atau alas kaki ..." pada baris
  yang sama, dengan sasaran 44 px.
- **390 px, Cek kesiapan:** 16 kali Tab melewati Lihat penilaian lengkap, tujuh "Konfirmasi" yang namanya menyebut
  butirnya, Selesaikan 7 butir, lalu Saya sudah meninjau semuanya.
- **1440 px, ruang kerja:** dari kepala trip (Ubah rencana) ke kolom langkah (Lihat jalur, Buka daftar persiapan, Cek
  kesiapan), lalu kolom isi (Lihat penilaian lengkap, Matikan pengingat, Mulai pendakian, Batalkan trip, Hapus
  rencana).

Kendali tanpa server juga diklik:
- stepper peserta naik dari 1 ke 2;
- sakelar pengingat berubah dari `false` ke `true`;
- jenis Tektok terpilih;
- saringan Persiapan mengubah 16 butir menjadi 13 butir belum dikonfirmasi dengan `aria-pressed="true"`, lalu kembali.

Belum dapat dibuktikan di pratinjau statis karena butuh server Laravel (izin Q1 `memount:demo` atau Playwright Q17):
- toast Urungkan dan perpindahan fokusnya, sehingga tangkapan `fokus-urungkan-390-light.webp` belum ada;
- kalimat "Menghitung ulang untuk pilihan baru";
- rentang di ringkasan bar aksi sesudah jenis diganti, karena `jadwal` datang dari muat ulang parsial;
- semua kiriman POST.

Kiriman POST itu dijaga test Feature di server.

## Sesudah perbaikan gerbang (7 Oktober 2026)

Delivery Gate W4 menemukan dua layar dengan lebih dari satu tombol penuh. Perbaikan gerbang W4-G1 menjadikan:
- tombol kepala indeks "Rencanakan trip" sekunder;
- "Mulai pendakian" di ruang kerja aksi utama hanya pada hari keberangkatan menurut zona gunung (boolean dari server);
- pemicu "Hapus rencana" sekunder, dengan konfirmasi yang tetap memperingatkan.

Delapan tangkapan indeks dan ruang kerja (390 dan 1440, terang dan gelap) diambil ulang sesudah perbaikan itu.
