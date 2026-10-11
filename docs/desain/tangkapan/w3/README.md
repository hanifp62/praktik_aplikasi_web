# Tangkapan W3

Tangkapan ini diambil koordinator pada 6 Oktober 2026 dari harness pratinjau, dengan cara yang sama seperti W1 dan W2.
Suite PHPUnit `Pratinjau` (14 test, 14 lulus) merender halaman sungguhan berisi data contoh `DemoSeeder` di SQLite dalam
memori; W3 menambah halaman gunung, penilaian kecocokan, Simpanan, tautan bagikan, Bandingkan, dan Jelajah tanpa jalur
terbit. HTML-nya disajikan statis oleh `php -S 127.0.0.1:8100` dengan router `tests/Pratinjau/statis.php`, tanpa basis
data lokal dan tanpa Supabase. Peramban yang dipakai adalah Chrome lewat MCP chrome-devtools, dalam konteks terisolasi
`w3-tangkapan`. Ukuran 390 x 844 (dan 360 x 740 serta 320 x 568 untuk pengukuran) memakai emulasi perangkat seluler
dengan sentuh dan rasio piksel 1; ukuran 1440 x 900 memakai rasio piksel 1, dan tangkapan halaman penuhnya selebar
1424 px karena bilah gulir. Props `tema` di pratinjau bernilai `system`, jadi terang dan gelap dipilih lewat emulasi
`prefers-color-scheme`, setara cookie `tema` bernilai system di W1 dan W2.

W3 mengisi klaster Jelajah di bawah kerangka W2. Jelajah memuat pencarian kalimat, lembar Saring, dan kartu jalur
dengan label kecocokan, tiga angka, garis profil satu skala, satu alasan, status bersumber, dan "Kenapa?"; peta sebaran
bergaris pola tampil bila jalur punya geometri. Halaman gunung dan halaman penilaian kecocokan baru; rincian jalur
ditulis ulang dengan bar aksi tetap; Simpanan mendapat kotak centang, catatan pribadi, dan baki banding; Bandingkan
pindah ke tab Trip dan menyajikan perbedaan tanpa pemenang; tautan bagikan `/b/{token}` adalah halaman publik hanya-baca
yang menunggu izin pemilik (P8). Semua layar memakai tata bahasa kepercayaan yang sama: `LabelKecocokan`, `StatusAkses`
dan `BarisStatus`, `LabelSumber`, serta `Kesegaran`.

Pada tangkapan halaman penuh, lapisan tetap (bilah tab, bar aksi, baki banding) tergambar di posisi viewport, sehingga
tampak menimpa isi di tengah gambar. Di layar sungguhan lapisan itu tetap di tepi bawah dan isi diberi ruang di
bawahnya. Panel lengket di 1440 px (saringan Jelajah, `Index.tsx:237`, dan kolom kanan rincian jalur, `Show.tsx:211`)
dibatasi setinggi layar dan bergulir sendiri, jadi tangkapan halaman penuh memotongnya di tinggi viewport.

Data contoh tidak punya geometri. Karena itu pemilih Daftar dan Peta, pola garis, legenda, chip, kartu pratinjau peta,
peta rincian jalur, dan penanda pos di profil elevasi belum dipotret. Belum dipotret juga: chip tafsiran (pratinjau
tanpa kata pencarian), tombol "Buka sistem pemesanan resmi" (izin contoh tanpa URL pemesanan), keadaan memuat, galat
peta, keadaan tersaring tanpa hasil, panel "Kenapa?" yang terbuka, lembar Saring, dialog Bagikan, dan toast Urungkan.
Dua artefak data contoh ikut terpotret: laporan komunitas tertulis "Dilaporkan 0 detik yang lalu" karena dibuat saat
render, dan sumber bernama "DATA CONTOH". Bagian atas beberapa gambar acuan kosong (03, 05, dan 07 sedikit; 04 sekitar
sepertiga atasnya, termasuk kepala, angka, dan tantangan), jadi bagian yang tidak tergambar dibandingkan dengan daftar
pemeriksaan rencana W3 Tugas 12 Step 6, bukan dengan gambarnya.

| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |
|---|---|---|---|
| `jelajah-390-light.webp`, `jelajah-390-dark.webp` | `../../prototype/acuan/02-jelajah.png` | Kepala "Jelajah" dengan Simpanan berhitungan (5) dan avatar, lencana Data contoh di bawah judul (W2-K5), kotak cari berikon, tombol "Saring", hitungan "9 jalur, 1 disembunyikan saringan status resmi", kalimat dasar penilaian, dan kalimat R-012. Kartu: label ikon dan kata ("Cocok", "Perlu persiapan", "Kurang cocok"), tombol simpan berikon (`TombolIkon` 44 px), nama, gunung berpautan dan provinsi, tiga angka berlabel (Jarak, Tanjakan, Naik) dengan bobot sama, garis profil satu skala berwarna label, satu alasan, status bersumber dan bertanggal ("Buka" lalu "Terverifikasi admin, 29 Sep"; "Dibatasi" berbentuk belah ketupat), dan "Kenapa?". Baris "1 jalur disembunyikan saringan status resmi." dengan "Tampilkan juga", lalu bagian "Belum bisa dinilai" yang menyebut "Menunggu kurator Memount melengkapi datanya. Status resmi belum diketahui dan tidak dianggap buka." Tema gelap setara, tanpa masalah kontras yang terlihat. | Pemilih Daftar dan Peta, pola garis, legenda, chip, dan kartu pratinjau belum dipotret (tanpa geometri); yang tampil kalimat "Peta belum tersedia: belum ada jalur di hasil ini yang punya garis rute.", yang tidak menyebut siapa yang ditunggu (temuan baru 3). Chip tafsiran belum dipotret. "Saring" dan hitungan berada di dua baris (`basis-full` di ponsel, `Index.tsx:257`), acuan satu baris bersama pemilih tampilan. Kartu Cocok membawa alasan perhatian ("Terdapat medan yang belum pernah Anda lalui: Punggungan terbuka." pada Andong; "Jalur ini tidak memiliki area camping resmi ..." pada Batu dan Batur) (Konflik 7, ruling W3-C7; urutan alasan diputus rencana W5). Acuan menulis durasi sebagai rentang ("1-2 j"), tangkapan satu nilai ("1,5 j"), karena rentang menunggu data (DK-31). Jarak atas halaman `py-6` (sekitar 24 px), acuan sekitar 16 px (W3 sampai W7, sama dengan catatan W2). |
| `jelajah-1440-light.webp`, `jelajah-1440-dark.webp` | `../../prototype/acuan/15-jelajah-lebar.png` | Header 68 px dari W2 dengan segmen Jelajah aktif, Data contoh, Simpanan 5, dan avatar. Tiga kolom: panel saringan berhitung (Kecocokan dasar 5, 1, 3; Jenis trip 9, 7; sakelar "Hanya rendah (5 jalur)" dengan kalimat "Jalur yang tuntutan teknisnya belum diketahui tidak dianggap rendah."; Tanjakan "Dari (m)" dan "Sampai (m)" yang diketik dengan kalimat penjelasnya; Status resmi), kolom daftar dengan hitungan, "Urut: kecocokan", kalimat dasar, kalimat R-012, dan kartu, lalu kolom peta dalam keadaan menunggu: "Peta menunggu garis rute", "Menunggu kurator Memount menggambar garis rute jalur di hasil ini". Tema gelap setara. | Garis rute bermotif, legenda, kendali zoom, chip, dan kartu pratinjau belum dipotret (tanpa geometri). Acuan memakai kartu ringkas (angka di kanan, profil kecil) dan tanpa judul halaman; tangkapan memakai kartu penuh yang sama dengan ponsel dan judul "Jelajah" (belum dijadwalkan; diputus koordinator). Di 1440 x 900 panel saringan terlihat sampai "Status resmi, Buka", sisanya (Dibatasi, Belum diketahui) dicapai lewat gulir di dalam panel; acuan memuat semua kelompok dalam satu layar karena tanpa kelompok Tuntutan teknis. |
| `jelajah-kosong-390-light.webp`, `jelajah-kosong-390-dark.webp`, `jelajah-kosong-1440-light.webp`, `jelajah-kosong-1440-dark.webp` | Tidak ada (kerangka dari `02-jelajah.png` dan `15-jelajah-lebar.png`) | Keadaan menunggu `EmptyState`: "Belum ada jalur yang terbit", "Menunggu kurator Memount menerbitkan jalur yang datanya lengkap", dan syarat tampilnya jalur (sumber, jarak, tanjakan, durasi, pos, status resmi, garis rute). Saringan tidak disalahkan atas katalog kosong; hitungan "0 jalur" dan Simpanan 0. Di 1440 panel saringan tetap tampil dengan hitungan nol dan kolom peta tidak tampil. | Kalimat "Profil elevasi memakai skala yang sama di semua kartu." tampil tanpa satu kartu pun (temuan baru 4). |
| `gunung-390-light.webp`, `gunung-390-dark.webp` | `../../prototype/acuan/03-gunung.png` | Tombol kembali, judul "Gunung Gede", Data contoh, "Ikuti kabar". Visual data sebagai hero: tiga profil jalur satu skala dengan kalimatnya (R-017; kaki halaman: "Foto: belum ada foto gunung berlisensi. Visual di atas digambar dari profil elevasi jalurnya."). Puncak 2.958 mdpl dengan kalimat BR-02 ("Ketinggian hanya keterangan. Berat-ringannya pendakian dinilai per jalur ..."). Status tingkat gunung "Buka" dengan kalimat "Status di tingkat gunung tidak berarti semua jalur buka" dan kalimat R-012, bersumber, "Diverifikasi 29 Sep", dan "Terkini". "Pilih jalur 3" berlabel ikon dan kata, beralasan, berangka, berstatus bersumber, dan bermedan. Izin dengan kalimat "Memount menautkan sistem resmi dan tidak menjual izin.", lalu "Kondisi dari pendaki" berlabel Komunitas dengan kalimat "Laporan komunitas melengkapi status resmi, tidak menggantikannya." Tema gelap setara. | Foto hero dan kartu PVMBG acuan menunggu W8 (DK-37, DK-43); yang tampil visual profil. Nama taman nasional dan kabupaten belum ada datanya, hanya provinsi ("Jawa Barat, waktu dalam WIB") (W8). Tombol "Buka sistem pemesanan resmi" belum dipotret, dan kalimat "menautkan sistem resmi" tetap tampil walau tautannya tidak ada (temuan baru 11). Jarak atas `py-6` (W3 sampai W7). |
| `gunung-1440-light.webp`, `gunung-1440-dark.webp` | `../../prototype/acuan/03-gunung.png` (acuan hanya ponsel) | Satu kolom sekitar 720 px di tengah; tiga profil dalam satu baris; kartu status memuat "Terverifikasi admin" dan "Status akses gunung"; urutan bagian sama dengan ponsel. | Acuan tidak punya tata letak lebar halaman gunung (tanpa gelombang; mengikuti ponsel). |
| `detail-jalur-390-light.webp`, `detail-jalur-390-dark.webp` | `../../prototype/acuan/04-detail-jalur.png` | Halaman tugas tanpa bilah tab, tombol kembali ke Jelajah, gunung berpautan ("Gunung Gede, 2.958 mdpl, Jawa Barat"), "Ikuti kabar Gunung Gede", kalimat menunggu "Garis rute belum dimasukkan. Menunggu kurator Memount ...", kalimat "Angka di bawah untuk sekali jalan naik", tiga angka. "Apa tantangannya?" dengan ruas dan kata (Berat, Sedang). "Cocokkah untuk saya?" dengan label, rencana yang dinilai, tiga daftar (Mengapa sesuai, Yang perlu diperhatikan, Celah persiapan), "Lihat ketujuh faktor penilaian", dan kalimat "Dinilai dengan aturan tertulis, bukan AI, dan tanpa skor angka. Ini dukungan keputusan, bukan jaminan keselamatan." Profil elevasi dengan ruas tercuram diarsir dan kalimatnya ("Bagian tercuram, km 7,3 sampai 9,7, naik 546 m dalam 2,4 km (diarsir)"). Pos bernomor; Kandang Badak dan Puncak Gede beraksen kuning bersama kata "Kemah" dan "Tujuan". Kondisi sekarang: status resmi bersumber dan bertanggal dengan "Terkini" dan kalimat R-012, perizinan, prakiraan cuaca dengan wilayah acuan, "Data cuaca tidak tersedia." dan catatan "Prakiraan untuk 21 Okt belum terbit ... muncul di sini mulai 18 Okt." (R-013), laporan komunitas, "Yang perlu disiapkan", dan "Sumber di halaman ini" dengan urutan kepercayaan. Bar aksi satu baris: simpan berikon, Banding, Rencanakan trip. | Tiga angka, bukan empat (W3-R29, ruling W3-C4; `elevation_loss_m` menunggu kurasi W8). Durasi ditulis "7 jam 30 menit" dan membungkus dua baris di 390 px; prototype memakai rentang "7-8 j" yang butuh data rentang (DK-31). Kalimat mesin "Elevation gain 1650 m lebih besar dari referensi pengalaman Anda (700 m)." memuat istilah Inggris dan angka tanpa titik ribuan (OPEN ISSUE, HANDOVER W3). Peta jalur dan penanda pos bernomor di profil belum dipotret (tanpa geometri; km pos hanya dari garis). Tema gelap: angka pos di atas aksen kuning bertinta terang (temuan baru 1). Jam tertulis "19:33 WIB" (temuan baru 2). Kalimat utama kecocokan diulang sebagai butir pertama "Yang perlu diperhatikan" (temuan baru 6). Laporan komunitas berupa kartu di dalam kartu (temuan baru 7). Jarak atas `py-6` (W3 sampai W7). |
| `detail-jalur-1440-light.webp`, `detail-jalur-1440-dark.webp` | `../../prototype/acuan/04-detail-jalur.png` (acuan hanya ponsel) | Dua kolom: isi di kiri, kolom kanan lengket berisi aksi (Tersimpan, Banding, Rencanakan trip) di atas "Cocokkah untuk saya?" (`Show.tsx:208-215`); durasi muat satu baris; status, perizinan, dan sumber satu baris per butir. Tanpa bar aksi mengambang. | Tiga aksi kolom kanan membungkus dua baris, "Rencanakan trip" sendiri di baris kedua (temuan baru 9). Tema gelap: angka pos beraksen sama dengan baris di atas (temuan baru 1). |
| `fokus-bar-aksi-390-light.webp` | `../../prototype/acuan/04-detail-jalur.png` | Cincin fokus papan ketik terlihat di tombol simpan 44 px di bar aksi; Banding dan Rencanakan trip satu baris di sampingnya; bar tidak tertutup lapisan lain. | |
| `kecocokan-390-light.webp`, `kecocokan-390-dark.webp`, `kecocokan-1440-light.webp`, `kecocokan-1440-dark.webp` | `../../prototype/acuan/05-route-fit.png` | Halaman tugas tanpa bilah tab, judul "Penilaian kecocokan" dengan jalur dan gunung berpautan. Label "Kurang cocok, untuk rencana ini" dengan definisinya, dan catatan "Tiga label yang mungkin ... Tidak ada skor angka, karena angka seperti persentase memberi kesan pasti yang tidak kami miliki." "Dinilai dari" dengan profil, rencana trip, dan "Ubah". Lapis 1 "Syarat dasar: terpenuhi" dengan syarat status yang bersumber dan bertanggal, kalimat aturan, dan kalimat R-012. Lapis 2 dan 3: tujuh faktor diurut pengaruh, keadaan dalam kata (Sesuai, Perlu perhatian) dan bobot dalam kata (Pengaruh besar, sedang, kecil). "Kenapa bukan Cocok?", "Yang bisa Anda lakukan", dan "Dinilai 6 Okt 2026, 19.33 WIB, aturan Route Fit v1." Bar aksi "Ke jalur" dan "Lanjutkan persiapan". Di 1440 satu kolom sekitar 620 px di tengah. Tema gelap setara. | Kalimat mesin "Elevation gain 1650 m ..." pada faktor Tanjakan (OPEN ISSUE, HANDOVER W3). Kotak "Jalur ini" dan "Anda" di faktor yang terbuka belum dipotret (semua faktor tertutup). Syarat "Data kritis jalur lengkap: ... dan garis peta" tercentang padahal jalur contoh tanpa geometri (temuan baru 12, artefak pratinjau). Jarak atas `py-6` (W3 sampai W7). |
| `simpanan-390-light.webp`, `simpanan-390-dark.webp`, `simpanan-tiga-centang-390-light.webp` | `../../prototype/acuan/06-simpanan.png` | Tombol kembali, "Bagikan" di kepala, judul, Data contoh, kalimat "5 jalur tersimpan, maksimal 50. Pilih sampai 3 untuk dibandingkan." Kartu dengan kotak centang, nama, label ikon dan kata, gunung, angka, catatan "Catatan pribadi, hanya Anda" atau "Tambah catatan pribadi", "Lihat rincian", dan "Hapus dari simpanan". Baki satu baris: "Dipilih 0 dari 3." dengan ruas kosong dan "Bandingkan 3 terbaru"; dengan tiga centang "Dipilih 3 dari 3." dengan tiga ruas terisi dan "Bandingkan 3 jalur". Tema gelap setara. | Judul tanpa angka di sebelahnya (prototype "Simpanan 5"), dan kalimatnya menyebut batas 50, bukan "Simpan sebanyak yang Anda mau" (DK-05: batas teknis tetap disebut). "Hapus dari simpanan" menjorok sekitar 16 px dari tautan di atasnya (ruling W3-7a, selisih rata kiri kosmetik yang diterima). Acuan menaruh tombol baki penuh lebar di baris kedua; tangkapan satu baris (anggaran tinggi lapisan bawah W2-F1 dan W2-F5, disengaja). Jarak atas `py-6` (W3 sampai W7). |
| `simpanan-1440-light.webp`, `simpanan-1440-dark.webp` | `../../prototype/acuan/06-simpanan.png` (acuan hanya ponsel) | Satu kolom kartu; tiga tautan kartu dalam satu baris; Data contoh di header. | Isi baki menumpuk di kiri: hitungan dan "Bandingkan 3 terbaru" di sepertiga kiri, sisa baki kosong (temuan baru 8, bukti untuk T7-M6). |
| `bandingkan-390-light.webp`, `bandingkan-390-dark.webp` | `../../prototype/acuan/07-bandingkan.png` | Tombol kembali, "Bagikan", judul "Bandingkan 3 jalur", Data contoh, kalimat "Perbandingan ini tidak memilih pemenang. Ia menunjukkan bedanya, Anda yang memutuskan.", "Lihat simpanan (5)", pembuka "Pilih jalur yang dibandingkan", sakelar "Sorot yang berbeda". Label ikon dan kata per kolom dengan "Kenapa?", baris yang sama ditandai ", sama" di kepala barisnya, status bersumber, izin "(belum diverifikasi)", profil satu skala dengan kalimatnya, kalimat R-012. "Jadikan dasar rencana" tiga tombol berbobot setara (R-016; acuan menjadikan tombol pertama primer berpanah). Kalimat "Jalur yang tidak dipilih tetap ada di simpanan ..." Tab Trip aktif (ruling W3-C2). Tema gelap setara. | Tabel memakai kolom kepala baris yang menempel dan gulir mendatar, sehingga sekitar satu setengah kolom terlihat; prototype 07 menaruh label aspek di atas nilainya sehingga tiga kolom terlihat. Pilihan: pertahankan (semantik tabel dari W0) atau tata ulang di bawah 640 px dengan baris label `th scope="rowgroup"` di putaran perbaikan W3 (menunggu putusan koordinator). Sel kecocokan menulis ulang "Kecocokan dasar" di samping label (temuan baru 10). Tombol "Kenapa?" di tabel tanpa nama jalur bagi pembaca layar (lihat Jalur papan ketik; ditunda W5). Jarak atas `py-6` (W3 sampai W7). |
| `bandingkan-1440-light.webp`, `bandingkan-1440-dark.webp` | `../../prototype/acuan/07-bandingkan.png` (acuan hanya ponsel) | Ketiga kolom terlihat utuh dengan kolom "Aspek"; bilah tanjakan satu skala; tiga "Jadikan dasar rencana" sama lebar; tab Trip aktif di header. | Di sel kecocokan kolom kedua, "Kecocokan dasar" membungkus ke bawah label sementara kolom lain di sampingnya (temuan baru 10). |
| `bagikan-390-light.webp`, `bagikan-390-dark.webp`, `bagikan-1440-light.webp`, `bagikan-1440-dark.webp` | Tidak ada; susunannya dibanding `../../prototype/acuan/07-bandingkan.png` | Seperti 07: judul, kalimat pengantar, tabel per kolom jalur dengan kolom aspek, dan status bersumber dan bertanggal. Berbeda dari 07 sesuai spec: tanpa label kecocokan ("Label kecocokan tidak ikut karena dihitung dari profil pembuatnya"), tanpa catatan, nama pembuat, dan "Jadikan dasar rencana"; lockup MEMOUNT tanpa navigasi aplikasi; kalimat masa berlaku ("berlaku sampai 13 Okt 2026"); baris "Sumber data jalur"; kalimat R-012; ajakan "Daftar untuk menilai kecocokannya" dan "Masuk". Di 390 gulir mendatar seperti Bandingkan. Tema gelap setara. | Tanpa lencana Data contoh (temuan baru 5). Urutan baris berbeda dari Bandingkan (status resmi di baris pertama, di Bandingkan dekat akhir), dan baris laporan terbaru, izin, profil elevasi, serta bilah tanjakan tidak ada (perbedaan susunan; diputus koordinator). |

## Pengukuran lapisan

Diukur koordinator dengan `evaluate_script` di pratinjau (`.superpowers/sdd/2026-09-27-memount-v3-w3-jelajah/t12-pengukuran.md`,
Langkah 7). Semua angka sama dengan simulasi rencana.

- **Bar aksi rincian jalur** (`section[aria-label="Aksi jalur"] .chrome`). 360 x 740: tinggi 69 px, tiga tombol satu
  baris (Simpan 44 x 44, Banding 90 x 44, Rencanakan trip 153 x 44, tepi atas 671 px), `--bar-aksi` 70px, tanpa gulir
  mendatar. 320 x 568: tinggi 82 px (14 persen layar), satu baris tombol; label "Rencanakan trip" membungkus dua baris
  di dalam tombolnya (57 px), tepi kanan tombol terakhir 291 px, tanpa gulir mendatar.
- **Baki banding Simpanan** (`section[aria-label="Pilihan banding"] .chrome`), nol sampai tiga centang lalu centang
  keempat. 360 x 740: 69, 58, 69, 69, 78 px, dan baki ditambah apa pun di bawahnya menutup 22, 20, 22, 22, 23 persen
  layar. 320 x 568: 78, 58, 78, 78, 98 px; 30, 27, 30, 30, 34 persen; tanpa gulir mendatar. Pengumuman `role="status"`
  berturut-turut: "Dipilih 0 dari 3.", "Dipilih 1 dari 3. Pilih minimal 2 jalur untuk membandingkan.", "Dipilih 2 dari
  3.", "Dipilih 3 dari 3. Batas banding tercapai.", "Maksimal 3. Lepas satu dulu."
- **Panel lengket Jelajah saat mendaki** (`jelajah-mendaki.html`, 1440 x 900; strip "Pendakian berlangsung"
  `--strip-pendakian` 58px, tepi atasnya 842 px). Gulir 0: tepi bawah panel saringan 824 px, panel peta (keadaan
  menunggu) 150 sampai 394 px. Sesudah gulir 600 px: panel saringan berakhir 770 px, panel peta 92 sampai 337 px.
  Tidak ada yang tertutup strip.

## Jalur papan ketik

Tombol sungguhan lewat CDP tanpa tetikus, fokus dicatat lewat `focusin` (`t12-pengukuran.md`, Langkah 8).

- **390 px, Jelajah.** Urutan Tab: "Lewati ke konten utama", "Simpanan 5 jalur tersimpan", "Profil Budi Setiawan",
  kotak cari, "Saring", "Simpan Andong via Sawit", tautan nama "Andong via Sawit", tautan "Gunung Andong", lalu
  "Kenapa? Andong via Sawit". Simpan mendahului nama karena ia berada di baris label di atas nama, mengikuti urutan
  visual. Chip tafsiran tidak ada di jalur ini karena pratinjau tanpa kata pencarian. Enter pada "Saring" membuka dialog
  "Saring jalur" dengan fokus di dalamnya (tombol Tutup); Escape menutupnya dan fokus kembali ke "Saring". Batasan yang
  jujur: Enter pada simpan lalu fokus berpindah ke Urungkan di toast **tidak dapat dibuktikan** di pratinjau statis,
  karena POST simpan butuh server Laravel; perilaku itu dijaga `SavedUndoTest` dan test sumber, belum dilihat di
  peramban.
- **390 px, rincian jalur.** Tab pertama sesudah tautan lewati berhenti di "Kembali ke Jelajah". Dari ujung belakang:
  "Rencanakan trip", "Banding", lalu "Simpan Gede via Cibodas", semuanya di bar aksi (tepi atas 775, tepi bawah 819 di
  layar 844), dan `elementFromPoint` di titik tengah masing-masing mengembalikan elemen itu sendiri, jadi tidak ada
  lapisan yang menutupinya. `fokus-bar-aksi-390-light.webp` diambil saat fokus papan ketik di "Simpan".
- **390 px, Simpanan.** Spasi pada kotak centang pertama mencentangnya, dan pengumuman berubah ke "Dipilih 1 dari 3.
  Pilih minimal 2 jalur untuk membandingkan." Enter pada "Bagikan" membuka dialog "Bagikan perbandingan" dengan fokus
  di dalamnya (Tutup); Escape menutupnya dan fokus kembali ke "Bagikan".
- **1440 px, Bandingkan.** Urutan Tab: lewati, MEMOUNT, Beranda, Jelajah, Trip, Mendaki, Progres, Simpanan, menu akun,
  "Kembali ke Trip", "Bagikan", "Lihat simpanan (5)", ringkasan "Pilih jalur yang dibandingkan", lalu sakelar
  `role="switch"` bernama "Sorot yang berbeda" (lewat `aria-labelledby`). Spasi menyalakannya (`aria-checked` true;
  11 dari 14 baris tabel berlatar `surface-sunken` dan tebal). Sepuluh Tab kemudian, melewati tautan kolom dan tiga
  "Kenapa?", fokus tiba di "Jadikan dasar rencana: Gede via Selabintana" dengan cincin fokus 2px solid; ketiga tombol
  dasar rencana berkelas sama (bobot setara, R-016). Setiap kendali yang difokus membawa cincin (outline 2px atau
  2.67px).
- **Minor baru.** Tiga tombol "Kenapa?" di tabel Banding bernama aksesibel "Kenapa?" saja, tanpa nama jalur, sementara
  kartu Jelajah sudah membawa nama jalur sesudah perbaikan T4-M8. Komponennya `FitLine`, yang juga dipakai Beranda,
  jadi ledger menundanya ke W5.

## Temuan baru dari tangkapan

Butir di bawah ini tidak disebut rencana W3 Tugas 12 Step 6 dan menunggu putusan koordinator. Gelombang yang tertulis
adalah usulan.

1. **Tema gelap: angka pos di atas aksen kuning hampir tak terbaca.** Lingkaran pos 5 (Kandang Badak) dan 6 (Puncak
   Gede) memakai `bg-aksen-pos text-primary` (`DaftarPos.tsx:31`); di tema gelap `text-primary` bernilai `#F2EFE8` di
   atas `#E8B04B`, sekitar 1,7:1 dihitung dengan rumus WCAG dari token di `DESIGN.md` bagian Palet dan token (teks
   butuh 4,5:1; di tema terang tintanya gelap dan lolos). Kelas yang sama ada di penanda profil
   (`ProfilElevasi.tsx:93`) dan penanda peta (`PetaJalur.tsx:15`) yang belum dipotret. Tangkapan:
   `detail-jalur-390-dark.webp`, `detail-jalur-1440-dark.webp`. Usul: perbaikan W3 sebelum izin akhir, dengan
   `ColourContrastTest` yang menghitung pasangan ini.
2. **Format jam tidak seragam.** Rincian jalur menulis "Diverifikasi 29 Sep 2026 19:33 WIB" (kartu status resmi dan
   "Sumber di halaman ini"), sedangkan penilaian kecocokan menulis "19.33 WIB"; `DESIGN.md` bagian Tipografi
   menetapkan jam "15.06". Sumbernya format bawaan `Timezone::display()` `'d M Y H:i'` (`app/Support/Timezone.php:82`)
   lawan `'j M Y, H.i'` (`PenilaianKecocokan.php:143`). Tangkapan: `detail-jalur-390-light.webp`,
   `kecocokan-390-light.webp`. Usul: W9 (sapuan salinan), karena format bawaan dipakai lintas modul.
3. **Jelajah ponsel: keadaan menunggu peta tidak menyebut siapa yang ditunggu.** "Peta belum tersedia: belum ada jalur
   di hasil ini yang punya garis rute." (`Index.tsx:272`), sedangkan panel 1440 px menyebut "Menunggu kurator Memount
   menggambar garis rute jalur di hasil ini" (`Index.tsx:391`). Tangkapan: `jelajah-390-light.webp`. Usul: perbaikan
   W3 (satu kalimat), paling lambat W9.
4. **Jelajah kosong: klaim tentang kartu tanpa kartu.** "Profil elevasi memakai skala yang sama di semua kartu."
   tampil ketika tidak ada satu kartu pun, kelas yang sama dengan N4 yang ditutup ruling W3-F6 untuk tampilan Peta.
   Tangkapan: `jelajah-kosong-390-light.webp`, `jelajah-kosong-1440-light.webp`. Usul: perbaikan W3, paling lambat W9.
5. **Tautan bagikan tanpa lencana Data contoh.** `Public/SharedComparison.tsx` tidak memasang `LencanaDataContoh`,
   padahal `DESIGN.md` bagian Nada dan teks (DK-35) dan docblock `LencanaDataContoh.tsx:6-8` meminta label itu di setiap
   halaman yang menyajikan data contoh; yang tersisa hanya baris "Sumber data jalur: DATA CONTOH, bukan sumber resmi".
   Tangkapan: `bagikan-390-light.webp`, `bagikan-1440-light.webp`. Usul: perbaikan W3, karena halaman ini publik tanpa
   akun.
6. **Rincian jalur: kalimat utama kecocokan diulang.** "Beban fisik jalur ini lebih berat dibandingkan tingkat
   pengalaman yang Anda isi." tampil sebagai kalimat utama "Cocokkah untuk saya?" lalu persis sama sebagai butir pertama
   "Yang perlu diperhatikan". Tangkapan: `detail-jalur-390-light.webp`, `detail-jalur-1440-light.webp`. Usul: W5
   (rencana W5 memutus urutan alasan, ruling W3-C7) atau W9.
7. **Rincian jalur: kartu di dalam kartu.** Setiap laporan di "Laporan kondisi dari komunitas" berupa kotak bergaris
   di dalam kartu (`LaporanKomunitas.tsx:36`), dan memakai ukuran huruf mentah `text-sm` dan `text-xs`. Komponen ini
   hampir tidak diubah W3 (selisih satu baris). Tangkapan: `detail-jalur-390-light.webp`. Usul: W5 (Kabar Kondisi).
8. **Simpanan 1440 px: isi baki menumpuk di kiri.** Hitungan dan "Bandingkan 3 terbaru" berada di sepertiga kiri baki,
   sisanya kosong. Ini bukti tangkapan untuk T7-M6, yang menurut ruling W3-F1 diperbaiki hanya bila T12 membuktikannya.
   Tangkapan: `simpanan-1440-light.webp`, `simpanan-1440-dark.webp`. Usul: perbaikan W3.
9. **Rincian jalur 1440 px: aksi membungkus.** Di kolom kanan 22rem, "Tersimpan" dan "Banding" di baris pertama dan
   "Rencanakan trip" sendiri di baris kedua. Tangkapan: `detail-jalur-1440-light.webp`. Usul: W9.
10. **Bandingkan: "Kecocokan dasar" berulang di setiap sel.** Kepala baris sudah bernama "Kecocokan dasar", tetapi
    setiap sel menulisnya lagi di samping label, dan di 1440 px kolom kedua membungkusnya ke bawah label sementara
    kolom lain tidak. Tangkapan: `bandingkan-390-light.webp`, `bandingkan-1440-light.webp`. Usul: W9.
11. **Gunung: kalimat menautkan tanpa tautan.** "Memount menautkan sistem resmi dan tidak menjual izin." tampil walau
    tombol pemesanan tidak dirender karena izin tanpa URL (`KartuIzin.tsx:39-51`). Tangkapan: `gunung-390-light.webp`.
    Usul: W8 (data izin) atau W9 (kalimat bersyarat).
12. **Artefak pratinjau, bukan cacat produk yang terbukti.** Penilaian kecocokan mencentang "Data kritis jalur lengkap:
    ... dan garis peta", sementara rincian jalur yang sama menulis "Garis rute belum dimasukkan". Di SQLite
    `Trail::hasGeometry()` menganggap geometri ada (`app/Models/Trail.php:147-151`), jadi jalur contoh tanpa garis
    tetap lolos gerbang kelengkapan; di PostgreSQL gerbang publikasi menolak jalur tanpa geometri, tetapi buktinya
    menunggu suite Spatial di CI (R-024). Tangkapan: `kecocokan-390-light.webp`, `detail-jalur-390-light.webp`. Usul:
    dicatat di HANDOVER W3 supaya pembaca tangkapan tidak salah baca.

## Sesudah perbaikan gerbang (6 Oktober 2026)

Koordinator memutus setiap temuan di atas dan setiap FAIL Delivery Gate. Enam butir diperbaiki dalam satu putaran
gerbang (ruling W3-G1 dan W3-G1b), ditinjau terpisah, lalu halaman yang berubah dipotret ulang dengan cara yang sama:
`jelajah`, `jelajah-kosong`, `gunung`, `detail-jalur`, dan `bagikan` pada 390 dan 1440 px terang dan gelap, serta
`simpanan` pada 1440 px terang dan gelap. Tangkapan lain tidak berubah.

| Temuan | Keadaan sekarang |
|---|---|
| 1. Angka pos di atas aksen kuning, tema gelap | Diperbaiki: token tinta `--accent-pos-ink` di kelima blok tema, kontras 8,18:1 (sebelumnya sekitar 1,70:1), dijaga `ColourContrastTest` untuk tema terang, gelap, dan dua tema lapangan. Warna aksen tidak berubah. |
| 2. Jam "19:33" lawan "19.33" | Ditunda ke W9 (format waktu lintas aplikasi lewat `Timezone::display()`, ruling W3-G2). |
| 3. Kalimat peta di ponsel tanpa nama penunggu | Diperbaiki, bersama kalimat data ketinggian, daftar pos, dan angka ringkas: semuanya menyebut "Menunggu kurator Memount". |
| 4. "semua kartu" di Jelajah kosong | Diperbaiki: kalimat skala hanya tampil bila ada kartu. |
| 5. `/b/` tanpa lencana Data contoh | Diperbaiki: `LencanaDataContoh` dipasang di halaman publik saat data contoh. |
| 6. Kalimat utama Cocokkah diulang | Diperbaiki: kalimat utama tidak lagi diulang sebagai butir "Yang perlu diperhatikan". |
| 7. Kartu di dalam kartu laporan komunitas | Ditunda ke W5 (Kabar Kondisi). |
| 8. Baki Simpanan 1440 menumpuk kiri | Diperbaiki: `BarAksi` mendapat prop `lebarPenuh` yang hanya dipakai Simpanan; tata letak ponsel tetap. |
| 9. Aksi kolom kanan rincian 1440 membungkus | Ditunda ke W9. |
| 10. "Kecocokan dasar" berulang di Bandingkan | Ditunda ke W5 bersama nama aksesibel "Kenapa?" di `FitLine`. |
| 11. Kalimat menautkan tanpa tautan | Diperbaiki: tanpa tautan resmi, kalimatnya menjadi "Memount tidak memesan atau menjual izin." |
| 12. Artefak `hasGeometry()` SQLite | Tetap artefak pratinjau; paritas dan bukti PostgreSQL ditangani W8 dan CI. |
