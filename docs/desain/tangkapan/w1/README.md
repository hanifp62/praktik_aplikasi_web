# Tangkapan W1

Tangkapan ini diambil pada 27 September 2026 dari harness pratinjau W1. Suite PHPUnit `Pratinjau` merender halaman
sungguhan berisi data contoh `DemoSeeder` di SQLite dalam memori. HTML-nya ditulis ke `public/_pratinjau/`, lalu
disajikan statis oleh `php -S 127.0.0.1:8100` dengan router `tests/Pratinjau/statis.php`, tanpa basis data lokal dan
tanpa Supabase.

Peramban yang dipakai adalah Chrome 153 (`Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like
Gecko) Chrome/153.0.0.0 Safari/537.36`) lewat MCP chrome-devtools. Ukuran 390 x 844 memakai emulasi viewport
perangkat seluler dengan sentuh dan rasio piksel 1, karena jendela peramban tidak bisa dikecilkan di bawah sekitar
500 px. Ukuran 1440 x 900 juga memakai rasio piksel 1. Tema dipilih lewat cookie `tema`, sama seperti pilihan tema di
Profil.

W1 hanya mengganti fondasi: token warna, huruf, ikon, radius, dan primitif. Susunan halaman sendiri berubah di
gelombang berikutnya. Jadi kolom ketiga hanya memuat yang terlihat sama dengan gambar acuan, dan kolom keempat memuat
yang terlihat belum sama.

| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |
|---|---|---|---|
| `dasbor-390-light.webp`, `dasbor-390-dark.webp`, `dasbor-1440-*.webp` | `../../prototype/acuan/01-beranda.png` | Kanvas kertas di tema terang, kanvas hijau hutan gelap di tema gelap. Tombol utama hijau hutan dengan teks terang (di tema gelap hijau muda dengan teks gelap). Plus Jakarta Sans dengan judul bobot 800. Tanda "Jalur Berpos" dengan kata MEMOUNT. Kartu putih bersudut besar. Lencana "Data contoh" bergaris putus di header. Di 390 px, bilah tab bawah berikon Phosphor, tab aktif berikon isi dan berlabel tebal. | Header acuan memuat lonceng dan avatar; tangkapan memuat tombol menu garis tiga (W2). Bilah tab acuan berupa pil melayang dengan sorotan tab aktif; tangkapan memuat bilah penuh bergaris atas (W2). Di 1440 px navigasi masih tautan teks bergaris bawah. Isi Beranda acuan belum ada: salam berwaktu, kartu trip dengan foto ilustrasi, H-15 dan empat langkah, Tenggat terdekat, kabar resmi dan komunitas, Jalur lain dengan grafik kecil, serta Tangga tanjakan. Tangkapan masih memuat salam umum, hitung mundur, dua tombol, dan empat tautan teks (W5). Tanggal dan angka di acuan berhuruf mono; tangkapan memakai satu keluarga huruf sesuai `DESIGN.md`. |
| `jelajah-390-*.webp`, `jelajah-1440-*.webp` | `../../prototype/acuan/02-jelajah.png`, `15-jelajah-lebar.png` | Kanvas, huruf, dan warna yang sama dengan baris di atas. Label kecocokan publik berbentuk pil berikon dan berteks, tidak hanya berwarna: "Cocok", "Perlu persiapan", "Kurang cocok", ditambah "Belum dinilai" untuk keadaan yang belum dinilai. Pembuka "Kenapa?" ada di setiap jalur. Tab Jelajah aktif. | Acuan memuat pencarian kalimat dengan chip tafsiran, tombol Saring berlembar, hitungan jalur, serta pilihan Daftar dan Peta. Tangkapan memuat isian cari dan tombol Saring (390 px) atau empat isian saringan (1440 px). Kartu acuan berwadah, dengan penanda simpan, grafik kecil elevasi, satu kalimat alasan, dan status resmi "Buka · Resmi". Jalur di tangkapan tanpa wadah dan tanpa grafik, dengan tombol "Simpan rute", tingkat teknis, dan kecuraman. Di 1440 px acuan tiga kolom (saringan berhitung, daftar, dan peta bergaris kecocokan); tangkapan satu kolom daftar (W3). |
| `jelajah-kosong-390-*.webp` | `../../prototype/acuan/02-jelajah.png` | Acuan tidak menggambar keadaan kosong. Yang terlihat mengikuti jenis "menunggu" di `DESIGN.md`: wadah bergaris putus menyebut siapa yang ditunggu ("Menunggu kurator Memount menerbitkan jalur yang datanya lengkap") dan syarat terbitnya, dengan hitungan "0 jalur". | Kepala halaman dan saringan sama dengan baris Jelajah (W3). |
| `primitif-390-*.webp`, `primitif-1440-*.webp` | `../../prototype/acuan/06-simpanan.png`, `08-rencanakan.png`, `09-persiapan.png`, `14-profil.png` | Kontrol segmen bersumur dengan segmen terpilih putih dan subjudul ("Tektok 1 hari", "Camping 2 hari"), seperti Jenis trip di `08` dan Tema di `14`. Stepper "Peserta, Termasuk Anda" dengan tombol kurangi dan tambah (`08`). Sakelar hijau "Ingatkan saya" (`08`). Kotak centang hijau hutan bersudut (`09`). Chip tafsiran hijau muda dengan tanda silang (`02`, `15`). Bar aksi melayang di atas navigasi bawah (`06`). Lencana "Perlu persiapan" kuning dan "Cocok" hijau (`06`). | Lencana "Kritis" di tangkapan bernada bahaya: teks merah tua di atas merah muda, tidak berhuruf kapital. Di `09`, KRITIS berupa lencana gelap berhuruf kapital (W4, Persiapan). Pilihan jam dan kalender di `08` belum ada sebagai primitif (W4). Kartu simpanan dengan kotak centang dan catatan pribadi di `06` belum ada (W3). |

`fokus-tab-chip-390-light.webp` adalah tangkapan viewport sesudah menekan Tab dari awal pameran sampai chip pertama.
Cincin fokus ganda (celah kanvas lalu cincin hijau) terlihat pada chip "Tektok".

Pemeriksaan interaksi pameran di 390 px (tema terang):

- Tombol menaikkan hitungan "Tombol contoh ditekan". Chip mengubah `aria-pressed`. Radio Peta, sakelar bar aksi, dan
  kotak centang Jas hujan mengubah keadaannya. Stepper berhenti di 12 dan di 1, dan tombol batasnya menjadi nonaktif.
- Melepas chip tafsiran memunculkan toast; Urungkan mengembalikannya (dua chip, satu, lalu dua lagi). Melepas semua
  chip memunculkan "Kembalikan tafsiran kalimat".
- "Buka saringan" membuka lembar "Saring jalur" dengan fokus awal pada "Tutup". Selama lembar terbuka, `overflow`
  halaman bernilai `hidden`. Escape menutup lembar, dan fokus kembali ke "Buka saringan".
- "Buka dialog konfirmasi" memulai fokus pada "Batal". Escape menutup dialog, dan fokus kembali ke pemicunya.
- Dengan `data-gerak="kurangi"`, `animationDuration` lembar yang terbuka bernilai `0.001s`.
- Toast tetap tampil lebih dari 9,5 detik selama penunjuk berada di atasnya, masih ada 4 detik setelah penunjuk
  pergi, dan hilang sebelum detik ke-9. Penunjuk disimulasikan dengan event `mouseover` dan `mouseout`, karena
  perintah hover dan klik chrome-devtools menolak elemen toast yang melayang dengan pesan "did not become
  interactive". `document.elementFromPoint` di tengah tombol Urungkan mengembalikan tombol itu sendiri, jadi toast
  tidak tertutup elemen lain.
- Tautan lewati ke konten memakai cincin fokus bawaan peramban; kontrol lain sampai chip pertama memakai cincin
  token.
- Mode `forced-colors` tidak diperiksa di W1 (sisa untuk W9).

Sesudah gelombang perbaikan akhir W1, delapan tangkapan Jelajah dan pameran diambil ulang dari build baru, dan
perilaku fokus diperiksa dengan tombol papan ketik sungguhan (390 px):

- Enter pada chip tafsiran memindah fokus ke Urungkan, dan toast bertahan lebih dari 9 detik selama difokus. Enter pada
  Urungkan mengembalikan chip dan memfokusnya. Tutup pemberitahuan memindah fokus ke chip tetangga, bukan ke `<body>`.
- Lembar dan dialog yang dibuka tanpa fokus pada pemicunya mengembalikan fokus ke tombol pemicu sesudah Escape.
- Stepper di batas 12 mempertahankan fokus pada "Tambah peserta" dengan `aria-disabled="true"`.
- Dengan bar aksi tampil, toast berada di atasnya tanpa tumpang tindih (`--bar-aksi` 163 px; toast 541 sampai 601 px,
  bar 605 sampai 768 px), dan kedua tombol bar tetap dapat diklik.
