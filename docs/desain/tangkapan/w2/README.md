# Tangkapan W2

Tangkapan ini diambil pada 28 September 2026 dari harness pratinjau, dengan cara yang sama seperti W1. Suite PHPUnit
`Pratinjau` merender sembilan halaman sungguhan berisi data contoh `DemoSeeder` di SQLite dalam memori. HTML-nya
disajikan statis oleh `php -S 127.0.0.1:8100` dengan router `tests/Pratinjau/statis.php`, tanpa basis data lokal dan
tanpa Supabase. Peramban yang dipakai adalah Chrome 153 lewat MCP chrome-devtools. Ukuran 390 x 844 memakai emulasi
perangkat seluler dengan sentuh dan rasio piksel 1; ukuran 1440 x 900 dan 1024 x 768 memakai rasio piksel 1. Tema
dipilih lewat cookie `tema`.

W2 mengganti kerangka, bukan isi halaman. Di bawah 1024 px tidak ada lagi bilah atas global. Setiap halaman membawa
kepalanya sendiri: halaman akar tab memuat judul dan avatar ke Profil, sedangkan halaman turunan memuat tombol kembali
ke induk logisnya. Navigasi utama berupa bilah tab pil yang mengambang. Mulai 1024 px, header 68 px menempel di atas
dengan navigasi segmen dan menu avatar. Kerangka juga memuat strip pendakian aktif, lembar Pengingat dari lonceng
Beranda, serta toast dan pesan jaringan yang dipasang sekali. Isi di bawah setiap kepala masih isi lama dan dirombak
W3 sampai W7.

Pada tangkapan halaman penuh, lapisan tetap (bilah tab, strip pendakian, baki banding) tergambar di posisi viewport,
sehingga tampak menimpa isi di tengah gambar. Di layar sungguhan lapisan itu tetap di tepi bawah dan isi diberi ruang
di bawahnya.

| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |
|---|---|---|---|
| `dasbor-390-light.webp`, `dasbor-390-dark.webp` | `../../prototype/acuan/01-beranda.png` | Kepala tanpa bilah atas global: tanda "Jalur Berpos" dengan MEMOUNT di kiri, lonceng dan avatar bulat 44 px di kanan. Bilah tab pil mengambang dengan tab Beranda aktif berikon isi di atas sorotan. | Salam, kartu trip, dan empat tautan teks masih isi lama; acuan memuat salam berwaktu, kartu trip berfoto dengan H-15 dan empat langkah, Tenggat terdekat, kabar resmi dan komunitas, serta Tangga tanjakan (W5). Lencana Data contoh berada di bawah judul, bukan di baris kepala seperti acuan (W2-K5, disengaja karena baris 360 px tidak muat dan label di atas judul terbaca sebagai eyebrow). Jarak atas halaman `py-8`, acuan sekitar 16 px (W3 sampai W7). |
| `dasbor-1440-light.webp`, `dasbor-1440-dark.webp`, `jelajah-1440-*.webp`, `kepala-1024-light.webp` | `../../prototype/acuan/15-jelajah-lebar.png` | Header 68 px: lockup di kiri, grup navigasi segmen berikon di tengah dengan segmen aktif berpermukaan terangkat, lalu lencana Data contoh, Simpanan berhitungan (5), dan avatar di kanan. Di 1024 px header tetap satu baris setinggi 68 px tanpa luapan (lebar isi sama dengan lebar klien, 1009 px). | Isi di bawah header masih daftar satu kolom; acuan memuat tiga kolom (saringan berhitung, daftar kartu, peta bergaris kecocokan) (W3). |
| `menu-avatar-1440-light.webp` | `../../prototype/acuan/15-jelajah-lebar.png` | Menu avatar berupa popover di bawah avatar: nama dan surel, lalu Profil dan Keluar dengan cincin fokus di dalam baris. Budi pendaki biasa, jadi Moderasi dan Admin tidak tampil. | Acuan tidak menggambar menu terbuka. Popover tanpa cadangan untuk peramban 2023 (W2-2c). |
| `jelajah-390-light.webp`, `jelajah-390-dark.webp` | `../../prototype/acuan/02-jelajah.png` | Kepala akar tab: judul "Jelajah", tombol Simpanan berhitungan (5), dan avatar, seperti acuan. Tab Jelajah aktif di bilah tab pil. | Pencarian kalimat dengan chip tafsiran, tombol Saring berlembar, pilihan Daftar dan Peta, dan kartu jalur berwadah dengan grafik kecil dan status resmi belum ada (W3). |
| `jelajah-mendaki-390-light.webp` | `../../prototype/acuan/02-jelajah.png` | Strip pendakian solid di atas bilah tab: nama jalur, pos yang dituju, lama berjalan, dan "Buka". Nama panjang membungkus. Baki banding duduk tepat di atas strip. | Acuan tidak menggambar strip; bentuknya mengikuti spec bagian 5.1. Isi Jelajah sama dengan baris di atas (W3). |
| `trip-390-light.webp`, `trip-1440-light.webp` | `../../prototype/acuan/01-beranda.png`, `13-progres.png` (pola kepala akar tab) | Kepala akar tab "Trip" dengan avatar, dan bilah tab dengan Trip aktif. | Prototype tidak punya layar daftar Trip; isinya dirombak W4. |
| `mendaki-390-light.webp`, `mendaki-1440-light.webp` | `../../prototype/acuan/11-mendaki.png` | Kepala akar tab "Mendaki" dengan avatar, dan bilah tab dengan Mendaki aktif. | Acuan 11 adalah mode lapangan saat mendaki (tema lapangan gelap, peta, pos berikutnya, Jeda dan Selesai); halaman ini masih halaman sebelum mulai (W6). |
| `progres-390-light.webp`, `progres-1440-light.webp` | `../../prototype/acuan/13-progres.png` | Kepala akar tab "Progres" dengan avatar. | Isi masih peta, empat angka, dan bilah tanjakan mendatar; acuan memuat grafik batang per pendakian dengan pilihan Tanjakan dan Jarak, Target pribadi, Pencapaian, dan Riwayat berkartu (W7). |
| `detail-jalur-390-light.webp` | `../../prototype/acuan/04-detail-jalur.png` | Halaman tugas tanpa bilah tab, dengan tombol kembali di kiri atas yang tautannya menuju induk logis (Jelajah). | Isi detail masih susunan lama, dan tombol aksinya masih di akhir halaman, belum bar aksi tetap (W3). |
| `pengingat-390-light.webp` | Tidak ada | Lembar Pengingat dari lonceng Beranda: fokus awal di "Tutup". Pratinjau statis tidak punya server, jadi lembar menampilkan keadaan galatnya ("Pengingat belum dapat dimuat") beserta tombol coba lagi (W2-K17). | Isi daftar pengingat dan tenggat izin hanya terlihat dengan server; dibuktikan `ReminderSheetTest`. |
| `fokus-kerangka-390-light.webp` | Tidak ada | Cincin fokus pada tab Jelajah sesudah menelusuri Beranda dengan Tab. | |

Pemeriksaan papan ketik (tanpa tetikus):

- **390 px, Beranda.** Urutan Tab: tautan lewati, lonceng, avatar, isi halaman ("Cek kesiapan", "Daftar persiapan",
  empat tautan), lalu tab Beranda dan Jelajah, masing-masing dengan cincin fokus terlihat. Enter pada lonceng membuka
  lembar Pengingat dengan fokus di "Tutup". Escape menutupnya, dan fokus kembali ke lonceng.
- **390 px, detail jalur.** Tab pertama sesudah tautan lewati berhenti di "Kembali ke Jelajah", dan tidak ada bilah
  tab di halaman.
- **1440 px, Jelajah.** Urutan Tab: tautan lewati, lockup, lima segmen (Beranda, Jelajah, Trip, Mendaki, Progres),
  Simpanan ("5 jalur tersimpan" untuk pembaca layar), lalu avatar. Enter pada avatar membuka menu, dan Tab masuk ke
  Profil. Escape menutup menu, dan fokus kembali ke avatar.
