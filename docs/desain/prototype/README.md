# Prototype pemilik

Prototype Memount yang diserahkan pemilik produk pada 25 September 2026 dan dijadikan
patokan desain serta UI/UX (keputusan DK-30; spec v3 bagian 1 dan 3). Berkas dan gambar
acuannya dipindah dari root ke sini dan dilacak git sebagai rujukan tim (DK-47, DK-56).

| Berkas | Isi |
|---|---|
| memount-prototype.html | Bundel asli 5,3 MB: 16 layar dalam bingkai iframe; buka di Chrome dengan JavaScript aktif. SHA-256 b72fd89e80a6b1394eb510754e27fee0a214d28de732b46086002e21439e69a2, dijaga `RepoHygieneTest` |
| acuan/ | 16 gambar acuan, satu per layar, untuk disandingkan dengan tangkapan 390 px dan 1440 px setiap gelombang (spec v3 bagian 13) |

## Layar

| Gambar | Judul bingkai di prototype | Ukuran bingkai (px) |
|---|---|---|
| acuan/00-onboarding.png | 00 · Profil pendaki (onboarding) | 390 x 844 |
| acuan/01-beranda.png | 01 · Beranda | 390 x 1960 |
| acuan/02-jelajah.png | 02 · Jelajah (daftar, peta, saringan) | 390 x 844 |
| acuan/03-gunung.png | 03 · Halaman gunung | 390 x 1780 |
| acuan/04-detail-jalur.png | 04 · Detail jalur | 390 x 3960 |
| acuan/05-route-fit.png | 05 · Route Fit: kenapa? | 390 x 2020 |
| acuan/06-simpanan.png | 06 · Simpanan + catatan pribadi | 390 x 844 |
| acuan/07-bandingkan.png | 07 · Bandingkan (maks. 3) | 390 x 1640 |
| acuan/08-rencanakan.png | 08 · Rencanakan trip | 390 x 1700 |
| acuan/09-persiapan.png | 09 · Trip: persiapan | 390 x 2080 |
| acuan/10-cek-kesiapan.png | 10 · Cek kesiapan | 390 x 1720 |
| acuan/11-mendaki.png | 11 · Mendaki (mode lapangan) | 390 x 844 |
| acuan/12-ringkasan.png | 12 · Ringkasan + laporan kondisi | 390 x 1940 |
| acuan/13-progres.png | 13 · Progres | 390 x 1880 |
| acuan/14-profil.png | 14 · Profil | 390 x 1740 |
| acuan/15-jelajah-lebar.png | 15 · Jelajah di layar lebar | 1440 x 900 |

## Yang diambil dan yang dikoreksi

Prototype adalah patokan pola, hierarki, bahasa visual, dan alur, bukan angka dan teks per
piksel: semua angka dan teks di aplikasi berasal dari sistem (asumsi A8 spec v3). Koreksi
terukur atas prototype dicatat di DK-31 (kontras batas isian dan garis komunitas, label tab
12 px, tinggi kartu mengikuti isi, waktu tempuh sebagai rentang bersumber, tombol "Jadikan
dasar rencana" berbobot setara). Huruf Geist dan ikon bergaya Lucide di prototype tidak
dipakai; penggantinya DK-38 dan DK-39. Rinciannya di spec v3 bagian 3 dan 8.

## Membuat ulang gambar acuan

Buka memount-prototype.html di Chrome desktop dengan jendela selebar minimal 1500 px dan
tunggu tulisan "Unpacking..." hilang. Setiap layar tampil sebagai bingkai bertajuk seperti di
tabel. Di DevTools, panel Elements, klik kanan node iframe setiap bingkai, pilih "Capture node
screenshot", lalu simpan dengan nama di tabel. Resolusi hasilnya mengikuti rasio piksel
perangkat, jadi hash berkas baru boleh berbeda; namanya tidak.

## Catatan hak

Bundel memuat huruf Geist, Geist Mono, dan Newsreader (SIL Open Font License 1.1) serta foto
JPG tanpa metadata EXIF (diperiksa 26 September 2026). Sumber dan lisensi foto belum
tercatat, sehingga prototype tidak dipakai sebagai sumber aset aplikasi; foto aplikasi
mengikuti DK-37.
