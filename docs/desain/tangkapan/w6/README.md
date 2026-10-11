# Tangkapan W6

## Cara pengambilan

Koordinator (Tugas 13) mengambil tangkapan ini pada 11 Oktober 2026 dari harness pratinjau, dengan cara yang sama
seperti W1 sampai W5.

- **Sumber halaman.** Suite PHPUnit `Pratinjau` (29 test, 29 lulus, 473 asersi) merender halaman sungguhan berisi data
  contoh di SQLite dalam memori, menulis 30 halaman ke `public/_pratinjau`.
- **Penyajian.** HTML disajikan statis oleh `php -S 127.0.0.1:8100` dengan router `tests/Pratinjau/statis.php`, tanpa
  basis data lokal dan tanpa Supabase. Port 8100 adalah port milik Tugas 13 menurut aturan eksekusi W6; port 8160
  dipakai paralel oleh pemeriksa terlingkup dan tidak disentuh.
- **Peramban.** Chrome lewat MCP chrome-devtools, konteks terisolasi `w6-tangkapan`.
- **Posisi mode lapangan.** `navigator.geolocation` diganti lewat skrip awal halaman (`initScript` pada navigasi)
  karena emulasi geolokasi DevTools tanpa izin peramban terbaca "ditolak" di harness ini: lintang -6,7462, bujur
  107,0040, akurasi 18 m, `timestamp` diset `Date.now() - 35000` supaya layar membaca "35 detik lalu" secara
  deterministik, bukan tergantung kapan tangkapan diambil.
- **Ukuran.** 390x844 dengan emulasi seluler, sentuh, rasio piksel 1, halaman penuh (`fullPage`). 1440x900 rasio
  piksel 1, halaman penuh. 320x568 dan 390x844 (tanpa `fullPage`) hanya untuk pengukuran `evaluate_script` (bagian
  Pengukuran di bawah).
- **Tema.** Terang dan gelap dipilih lewat emulasi `prefers-color-scheme`. Satu tangkapan tambahan memakai tombol
  "Gelap" di kepala mode lapangan, tema lapangan yang independen dari `prefers-color-scheme` sistem (lihat baris
  `mode-lapangan-390-lapangan-gelap.webp`).
- **Urutan emulasi.** Viewport dan skema warna selalu dikirim bersama, dan navigasi ke halaman pratinjau dilakukan
  SESUDAH emulasi. Mengubah skema warna sendirian pada halaman mode lapangan yang sudah termuat memicu navigasi
  Inertia ke URL SPA saat ini (`/trips/1/hike`), yang tidak ada di server statis dan menampilkan "Tidak ada di
  pratinjau statis."; satu percobaan pertama untuk `mode-lapangan-390-dark.webp` kena ini dan diambil ulang dengan
  urutan emulasi-lalu-navigasi yang benar.
- **Paket dan keselamatan.** Pratinjau `mendaki` dan `jelajah-mendaki` memakai sakelar `FIELD_PACK_ENABLED` dan
  `FIELD_SAFETY_ENABLED` MATI (bawaan pengguna sungguhan). Pratinjau `mendaki-paket`, `cetak`, `kartu-darurat`, dan
  `ruang-kerja-keselamatan` menyalakan sakelarnya lewat `config([...])` di dalam test pratinjau saja (ruling W6, bukan
  keputusan produk); sakelar aplikasi sendiri tidak disentuh (larangan mutlak butir 6).

## Cakupan W6

W6 mengisi mode lapangan untuk hari pendakian (spec v3 bagian 6.12, prototype 11 dan 12), paket lapangan luring, dan
keselamatan. Sembilan butir induk W6 (lihat tinjauan diri rencana): pintu tab `/mendaki`; mode lapangan; service
worker; manifest paket; unduh dan verifikasi paket; data dinamis luring; keluar dan ganti akun; kartu darurat dan versi
cetak; ringkasan dan laporan komunitas.

## Perbandingan dengan acuan

| Tangkapan | Gambar acuan | Yang sudah sesuai | Yang belum sesuai dan gelombang yang mengubahnya |
|---|---|---|---|
| `mode-lapangan-390-light.webp`, `mode-lapangan-390-dark.webp`, `mode-lapangan-1440-light.webp`, `mode-lapangan-1440-dark.webp`, `mode-lapangan-390-lapangan-gelap.webp` | 11 | Kepala dengan tombol kembali ("Kembali ke detail trip. Pendakian tetap berjalan."), nama jalur, "Berjalan, N jam M menit bergerak", lencana "Data contoh", dan tombol tema ("Gelap"/"Terang", `aria-label` "Ganti ke tema lapangan gelap/terang"); peta tanpa kontrol bawaan dengan pos bernomor 1 sampai 6 (pos berikutnya kuning, yang sudah dicapai terisi hijau), titik posisi, tombol zoom 48 px, "Posisi saya"; "Pos berikutnya, nomor 2" dengan nama besar "Telaga Biru"; tiga petak Sisa jarak/Naik lagi/Perkiraan ("250 m garis lurus", "↑235 m dari ketinggian pos", "Belum ada belum ada data waktu tempuh"); "±18 m · 35 detik lalu"; kalimat "Lokasi diproses di perangkat ini dan tidak dikirim ke server."; "Saya sudah di Telaga Biru"; "Semua pos (6)"; sakelar "Layar tetap menyala" mati bawaan; Jeda dan Selesai 60 px. Tombol tema menunjukkan tema lapangan MENGIKUTI `prefers-color-scheme` pada muat pertama (label baca "Ganti ke tema lapangan terang" saat OS gelap), lalu bisa dibalik sendiri terlepas dari OS (tangkapan `-lapangan-gelap`, diambil di OS terang). | Peta dasar MERENDER ubin OpenTopoMap sungguhan pada tangkapan ini (lihat Catatan di bawah), berbeda dari dugaan rencana "peta dasar di pratinjau kosong karena harness statis tanpa ubin" tidak tepat: harness pratinjau statis punya akses jaringan keluar, jadi MapLibre berhasil mengambil ubin walau datanya (jalur, pos) tetap data contoh. Garis jalur yang dilalui/sisa tetap tidak tergambar karena geometri data contoh kosong (kurasi data W8, tidak berubah). Ikon orang pada judul "Bantu pendaki berikutnya" dan ikon gunung/tenda pada lencana pencapaian tidak ada di halaman ini (W6-R16, lihat baris ringkasan). Waktu bergerak berubah setiap tangkapan diambil ("2 jam 16", "18", "20", "21", "29 menit") karena data contoh memakai offset relatif terhadap jam nyata, bukan nilai beku; ini artefak harness, bukan cacat produk. |
| `jelajah-mendaki-390-light.webp`, `jelajah-mendaki-390-dark.webp` | Tanpa acuan tunggal; strip pendakian aktif W2 menuju mode lapangan (induk W6 butir 1) | Strip pendakian berlangsung tampil di atas daftar Jelajah dengan tautan ke mode lapangan; tanpa gulir mendatar di 390 px (`scrollWidth` = `clientWidth`). | Butir K-2 (strip terpatah "1 / jam" pada 320 px karena spasi biasa, bukan spasi tak putus) adalah titipan W9 yang sudah diputuskan sebelumnya; dicatat terlihat di tangkapan ini, bukan dilaporkan sebagai cacat baru. |
| `kartu-darurat-390-light.webp`, `kartu-darurat-390-dark.webp` | Tanpa acuan tunggal; prototype 11 bagian kartu darurat | Lembar "Kartu darurat" terbuka lewat tombol berlabel sama; "Setelah menelepon, tetap di tempat kecuali tempat itu berbahaya. Petugas mencari dari lokasi yang Anda sebutkan."; dua tombol telepon 112 (Darurat) dan 115 (Basarnas) 69,89 px (dibulatkan 70 px); kotak krem "Bacakan kepada petugas" dengan Jalur, Koordinat, Diukur (pukul, umur, akurasi), Pos terdekat (garis lurus), Pos terakhir yang dicapai; Jeda/Selesai di bawahnya. Kalimat "Kontak pengelola resmi gunung ini belum tercatat di Memount." tampil karena data contoh trip ini tidak mencatat kontak pengelola (bukan tombol telepon pengelola yang disembunyikan tanpa penjelasan). | Tidak ada |
| `ringkasan-390-light.webp`, `ringkasan-390-dark.webp`, `ringkasan-1440-light.webp`, `ringkasan-1440-dark.webp` | 12 | Panel gelap empat angka (Jarak 19,4 km, Tanjakan ↑1.650 m, Waktu bergerak 10 jam 35 menit, Titik tertinggi 2.958 m) dengan "Ubah angka"; kalimat "Tanpa rekaman jejak ... Ubah bila berbeda."; "Dibanding pendakian Anda sendiri" dengan tangga Andong 380 m, Batur 600 m, Prau 700 m, Gede via Cibodas 1.650 m pada garis dasar sama; kalimat "+950 m dari tanjakan tertinggi Anda sebelumnya (Prau via Wates, Gunung Prau, 700 m)"; pencapaian "Tanjakan pertama di atas 1.000 m"; kalimat BR-11 "Tingkat pengalaman Anda tetap Pemula sampai Anda mengubahnya sendiri di Profil." dengan tautan "Buka Profil"; "Bantu pendaki berikutnya" bergaris putus-putus komunitas dengan tanggal, 9 chip kondisi (`aria-pressed`, 44 px), segmen, catatan, foto opsional dengan kalimat "Lokasi GPS di dalam foto dihapus sebelum disimpan.", kalimat terbit/tidak terbit, tombol "Kirim untuk dimoderasi" nonaktif (`disabled=true`) sampai satu chip dipilih (diverifikasi lewat `evaluate_script`, bukan hanya tampilan); grafik "Profil elevasi" dan "Daftar pos" dengan ketinggian dan jarak antarpos karena ringkasan ini punya geometri data contoh (berbeda dari mode lapangan yang kosong). | Nama anak tangga menambahkan nama gunung di baris kedua ("Prau via Wates, Gunung Prau"), sedikit berbeda dari kalimat dugaan rencana "(Prau via Wates, 700 m)" tanpa nama gunung; ini konsisten dengan ruling W5-6a (nama jalur tidak unik, R-001/R-002), bukan penyimpangan. Ikon orang pada judul "Bantu pendaki berikutnya" dan ikon gunung/tenda pada lencana pencapaian tidak ditambahkan (registri ikon W6 hanya menambah `clock`, W6-R16). |
| `ringkasan-batal-390-light.webp`, `ringkasan-batal-390-dark.webp`, `ringkasan-batal-1440-light.webp`, `ringkasan-batal-1440-dark.webp` | Tanpa acuan; varian dibatalkan dari 12 | Halaman "Trip Pangrango via Cibodas dibatalkan" dengan data contoh berbeda dari trip selesai (jalur dan gunung lain), tanpa panel angka empat kolom pendakian selesai; tanpa gulir mendatar. | Tidak diperiksa lebih dalam teks alasan pembatalan per kata; lihat `TripCancellationReasonTest` (Step 2) untuk cakupan kalimat per alasan. |
| `mendaki-390-light.webp` | Tanpa acuan; induk W6 butir 1 | Tab "Mendaki" dengan kartu trip H-15, bagian "Paket luring" (sakelar mati: "Paket lapangan luring belum dibuka. Buka mode lapangan selagi ada sinyal dan biarkan tetap terbuka, lalu bawa peta cetak atau perangkat lain sebagai cadangan." tanpa tombol unduh), "Lokasi" (diminta sesudah pendakian dimulai, tidak dikirim ke server), "Rekam jejak" (mati bawaan, "Atur di Profil"), "Cek kesiapan" (belum dilakukan, "Buka cek kesiapan"), dan "Tinjau peta dan pos sebelum mulai". | Tidak ada |
| `mendaki-paket-390-light.webp` | Tanpa acuan; sakelar paket menyala di test pratinjau saja | Sama seperti `mendaki` ditambah bagian "Paket luring" menyala: "Belum ada paket untuk trip ini di perangkat ini." dengan penjelasan isi paket (garis jalur, pos, profil elevasi, rencana, potret status/cuaca/laporan komunitas saat paket dibuat; peta dasar tidak ikut) dan tombol "Siapkan paket luring"; tombol aksesibel "Mulai pendakian, Gede via Cibodas, Gunung Gede" tinggi 66 px (diverifikasi `getBoundingClientRect()`, bukan dari kelas CSS). | Tidak ada |
| `cetak-390-light.webp` | Tanpa acuan; `/trips/{trip}/cetak` | "Versi cetak" dengan tombol "Cetak halaman ini" (`window.print()`); rencana (Trip, Ringkasan, Mulai, Kembali, Registrasi); kotak "Kartu darurat" dengan 112/115 dan ruang kosong untuk diisi tangan; "Sketsa rute" (garis lurus antarpos, tanpa skala, "Bukan peta navigasi"); tabel "Pos" dibungkus wadah gulir (lihat Pengukuran); "Aturan resmi" berlabel "DATA CONTOH, bukan pengelola resmi"; "Pesan untuk kontak" dengan eskalasi waktu kembali dan kalimat "Memount tidak memantau perjalanan ini dan tidak membagikan lokasi saya."; penutup "Kertas ini cadangan untuk ponsel, bukan peta navigasi. Ikuti petunjuk petugas di jalur." | Tidak ada |
| `ruang-kerja-keselamatan-390-light.webp` | Tanpa acuan; ruang kerja trip, bagian keselamatan sakelar menyala | Langkah Jalur/Rencana/Persiapan/Cek; "Kecocokan jalur: Kurang cocok untuk rencana ini" dengan tautan "Lihat penilaian lengkap"; "Izin pendakian" dengan penyelenggara "DATA CONTOH, bukan pengelola resmi"; "Pengingat perjalanan" dengan kalimat R-012 ("Isi surel tidak pernah menyatakan jalur atau pendaki aman", terverifikasi lewat kode, bukan di layar); "Rencana untuk kontak" dengan isian Tanggal/Jam/Nomor registrasi, "Simpan jam kembali", pratinjau pesan, "Kirim lewat WhatsApp" (`https://wa.me/?text=...`), "Kirim lewat SMS" (`sms:?&body=...`), "Salin pesan" (`TombolSalin`, salinan ke papan klip dengan kalimat berhasil dan cadangan manual), "Buka versi cetak"; "Tindakan" (Mulai pendakian, Batalkan trip); "Hapus rencana". | Tidak ada |
| `luring-390-light.webp` | Tanpa acuan; W6-9j, judul "Halaman ini belum dapat dimuat" | Shell luring default (tanpa paket dibuka): judul dan kalimat R-013/R-012 ("Aplikasi sengaja tidak menampilkan salinan lama halaman saat luring... lebih berbahaya daripada tidak ada tampilan sama sekali."); "Paket lapangan di perangkat ini" dengan nama trip, "Status resmi" terpisah dari "Status belum dapat dipastikan sejak 4 Okt 2026 07.15 WIB" (label dan ikon jam dari `TerakhirDiketahui`, `resources/js/luring/DaftarPaket.tsx:63`); "Prakiraan cuaca" dan "Laporan pendaki" sama-sama "Terakhir diketahui saat paket dibuat"; "Diperiksa utuh di perangkat ini 11 Okt 2026, 07.23 WIB."; tombol "Buka mode lapangan: Gede via Cibodas, Gunung Gede, 26 Okt 2026" (nama aksesibel dengan konteks penuh). | Ini adalah representasi pratinjau untuk halaman `/offline` (W6-9j); tidak ada berkas pratinjau terpisah bernama `offline.html` (lihat Batas harness). |
| `luring-mode-lapangan-390-light.webp` | Tanpa acuan; W6-9k | Diambil ulang putaran penutupan OPEN ISSUE W6-FT (jejak sesudah batal): sesudah "Buka mode lapangan": judul "Mode lapangan dari paket: Gede via Cibodas, Gunung Gede"; kalimat "Dari paket luring. ... Keadaan pendakian dari catatan terakhir di perangkat ini (11 Okt, 15.11 WIB); bila pendakian sudah diselesaikan atau dibatalkan sesudah itu, tiba di pos dan jeda dari sini tidak diterima server, dan jejak yang terekam lebih dari 10 menit sesudah pendakian diselesaikan atau dibatalkan tidak disimpan. ..." (frasa "atau dibatalkan" pada klausa jejak ditambahkan ruling W6-FT, karena TripLifecycle::cancel() kini menutup sesi seperti complete()); peta menampilkan teks "Peta dasar tidak tersedia luring; garis dan pos dari paket." (BERBEDA dari mode lapangan daring yang menampilkan ubin sungguhan, lihat baris pertama); atribusi "Garis jalur, pos, dan angka: DATA CONTOH, bukan sumber resmi, Tim Memount."; Sisa jarak dan Perkiraan "Belum ada" karena posisi tiruan tidak diberikan pada state ini ("menunggu posisi"); Jeda tunggal (Selesai tidak tampil pada state ini di pratinjau). | Ini adalah representasi pratinjau untuk `/offline?dibuka=paket` (W6-9k); tidak ada berkas pratinjau terpisah dengan nama itu, dicapai dengan menekan tombol "Buka mode lapangan" pada `luring.html` (lihat Batas harness). Jam di kalimat bergerak mengikuti jam nyata saat tangkapan diambil (temuan Minor task-13-report.md Step 5), jadi nilai menit/jamnya berbeda dari tangkapan sebelumnya. |

### Catatan tentang peta dasar di harness pratinjau

Rencana W6 menduga "peta dasar mode lapangan di pratinjau kosong karena harness statis tanpa ubin". Pemeriksaan
langsung membuktikan sebaliknya untuk `mode-lapangan` (online): server statis `php -S` di mesin ini punya jalur keluar
ke internet, dan Content-Security-Policy aplikasi mengizinkan `img-src ... https://tile.opentopomap.org`, sehingga
MapLibre berhasil mengambil ubin OpenTopoMap sungguhan (terlihat di semua tangkapan `mode-lapangan-*`). Sebaliknya,
mode lapangan DARI PAKET (`luring-mode-lapangan`) secara sengaja TIDAK meminta ubin sama sekali dan menampilkan teks
"Peta dasar tidak tersedia luring" terlepas dari koneksi jaringan nyata, karena paket luring tidak membawa ubin peta
(by design, bukan kegagalan jaringan). Keduanya adalah perilaku yang benar dan berbeda satu sama lain; dugaan rencana
hanya salah untuk kasus online.

## Pengukuran lapisan, reflow, dan gerak

Diukur dengan `evaluate_script` di pratinjau, nilai dari perangkat ini (bukan simulasi rencana).

### Reflow (`scrollWidth` vs `clientWidth`, 320x568 dan 390x844)

Tanpa gulir mendatar (`scrollWidth` = `clientWidth`) pada kesepuluh halaman: `mode-lapangan`, `kartu-darurat`,
`ringkasan`, `ringkasan-batal`, `mendaki`, `mendaki-paket`, `cetak`, `luring` (offline shell), `jelajah-mendaki`, dan
`ruang-kerja-keselamatan`, pada kedua ukuran. Tabel pos halaman `cetak` terbungkus wadah gulir sendiri (konsisten
dengan simulasi rencana yang mencatat perbaikan ini sesudah sebelumnya 378 px lawan 320 px).

### Tinggi kendali (`getBoundingClientRect().height`)

| Kendali | Halaman | Tinggi terukur |
|---|---|---|
| "Mulai pendakian, Gede via Cibodas, Gunung Gede" | `mendaki-paket` | 66 px |
| Tombol telepon 112 dan 115 | `kartu-darurat` (lembar terbuka) | 69,89 px (≈ 70 px) |
| Jeda | `mode-lapangan` | 60 px |
| Selesai | `mode-lapangan` | 60 px |
| Tombol zoom peta (Perbesar/Perkecil) | `mode-lapangan` | 48 px |
| "Saya sudah di Telaga Biru" | `mode-lapangan` | 49,33 px |
| Chip kondisi ("Berlumpur") | `ringkasan` | 44 px |

Semua angka sesuai ambang Global Constraints (66, 60, 48, dan 44 px untuk kendali lain).

### Gerak (`prefers-reduced-motion`)

Alat `emulate` chrome-devtools yang tersedia pada sesi ini hanya mengekspos `colorScheme`, bukan fitur media
`prefers-reduced-motion`, sehingga pemicuan langsung di peramban tidak dapat dilakukan lewat tool ini (OPEN ISSUE
alat). Diverifikasi lewat kode sumber sebagai gantinya: `resources/css/app.css:787-796` mendefinisikan
`@media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: 1ms !important; ... } }`
sebagai aturan universal yang berlaku atas `.denyut-tiba` (`resources/css/app.css:868-870`, dipakai
`resources/js/components/mendaki/ModeLapangan.tsx:116` saat `mode.pengumuman` dimulai dengan "Tiba"), dan atas
`useModeLapangan.ts:189` yang membaca `window.matchMedia('(prefers-reduced-motion: reduce)').matches` untuk setelan
Gerak aplikasi. Mekanismenya identik dengan yang dijaga `MotionTest` (lulus pada Step 2, bagian Unit+Feature).

## Jalur papan ketik

Diuji dengan `press_key` sungguhan (Tab, Shift+Tab, Enter, Space, Escape) dan `document.activeElement`/`aria-pressed`
terukur sesudah tiap tombol, bukan simulasi.

### Mode lapangan, 390 px, tema terang

Urutan Tab dari awal halaman:

1. "Lewati ke konten utama" (tautan lewati)
2. "Kembali ke detail trip. Pendakian tetap berjalan." (tautan)
3. tombol tema ("Gelap", `aria-label` "Ganti ke tema lapangan gelap")
4. kanvas peta (`aria-label` "Peta interaktif"), stop tambahan yang tidak disebut di brief, bukan temuan: cincin
   fokus tetap terlihat (`outline: auto 0.67px`), tidak tersembunyi, tidak melompat mundur
5. "Perbesar peta"
6. "Perkecil peta"
7. "Posisi saya": urutan nyatanya SESUDAH kedua tombol zoom, bukan sebelum seperti dugaan brief; tidak membingungkan
   karena ketiganya kendali peta yang berdekatan secara visual dan cincin terlihat di ketiganya
8. "Saya sudah di Telaga Biru"
9. "Semua pos (6)" (`<summary>`)
10. sakelar "Layar tetap menyala" (`role="switch"`)
11. "Jeda"
12. "Selesai"

Cincin fokus terlihat pada seluruh 12 posisi (`outline-style: solid`/`auto` dengan lebar non-nol, atau cincin
`box-shadow` dua warna untuk tombol utama: `rgb(15,19,17) 0 0 0 2px` lalu `rgb(232,176,75) 0 0 0 4px`, teknik dua
lapis yang tetap kontras di latar terang maupun gelap). Enter pada "Selesai" membuka `<dialog open>` dengan fokus
berpindah ke dalamnya (tombol "Tutup"); Escape menutup dialog (`dialog[open]` hilang) dan mengembalikan fokus persis
ke "Selesai" (`document.activeElement` = tombol "Selesai"), sesuai klaim rencana. Tangkapan fokus pada "Jeda" sesudah
Shift+Tab dari "Selesai": `fokus-jeda-390-lapangan.webp`.

### Ringkasan, 390 px

"Ubah angka" membuka `<dialog open>`, tetapi fokus awal jatuh ke tombol "Tutup" (elemen fokusable pertama di DOM
dialog), BUKAN ke isian pertama seperti ditulis di brief; lihat Temuan di laporan utama (klasifikasi Minor, bukan
"membingungkan" per kriteria tambahan karena cincin tetap terlihat dan tombol itu sendiri berlabel jelas, tetapi
berbeda dari spesifikasi "fokus di isian pertama"). Chip kondisi: `aria-pressed` berubah `"false"` → `"true"` dengan
Enter, dan kembali `"true"` → `"false"` dengan Space (diuji pada chip "Berlumpur"); tombol "Kirim untuk dimoderasi"
berpindah `disabled: true` → `false` → `true` mengikuti jumlah chip terpilih, diverifikasi lewat properti `.disabled`
DOM, bukan hanya tampilan visual.

## Uji luring manual di Chrome (Step 9)

**Dijalankan 11 Oktober 2026**, sesudah izin pemilik ("lanjutkan sampai W6 selesai") dan sesudah koordinator mengisi
`database/demo.sqlite` lewat `memount:demo` di luar sesi ini. Server Laravel sungguhan dijalankan di port 8100 dengan
`DB_CONNECTION=demo`, tanpa `.env` Supabase; akun `budi@contoh.test`. Rincian lengkap, termasuk kalimat yang tampil
persis, keluaran `evaluate_script`, dan header respons, ada di
`.superpowers/sdd/2026-10-07-memount-v3-w6-mendaki/task-13-step9-report.md`. Ringkasan di bawah; bukti pengganti Step
5/6 yang lama tetap tercantum sebagai pelengkap sejarah.

### Hasil per butir

Trip yang dipakai: id **1**, "Gede via Cibodas" (satu-satunya trip `budi@contoh.test`, awalnya H-15 belum dimulai;
dimulai sendiri lewat tombol "Mulai pendakian" karena dispatch mengizinkan trip "yang dapat dimulai dan jalurnya
terbit" bila tidak ada yang sudah berlangsung).

1. **Unduh paket (daring).** "Siapkan paket luring" menampilkan "Ukuran paket 3 kB. Ruang perangkat cukup."; "Unduh
   paket" menghasilkan "Siap luring. Diperiksa utuh di perangkat ini 11 Okt, 18.45 WIB." (`luring-1-siap.webp`).
   IndexedDB `memount-luring-<kunci>` toko `bagian` 3 baris, toko `paket` 1 baris `siap: true`; Cache Storage
   `shell-1dc2ee64` memuat `/offline` dan `/build/assets/shell-BLW_jinw.js`. Bukti penyimpanan berupa keluaran teks `evaluate_script` (basis data `memount-luring-<kunci>`: toko `bagian` 3 baris, yaitu `lapangan`, `rencana`, dan `dinamis`; toko `paket` 1 baris `"siap":true`; `caches.keys()` memuat `shell-1dc2ee64`), karena panel Application DevTools tidak dapat ditangkap alat ini. Tangkapan `luring-2-penyimpanan.webp` yang semula dibuat ternyata salinan persis `luring-1-siap.webp` (SHA-256 sama), jadi dibuang. **PASS.**
2. **Cache `pack-uji` tiruan.** `caches.open('pack-uji').then((c) => c.put('/uji-paket', ...))` berhasil, diverifikasi
   lewat `.keys()`. **PASS.**
3. **Jaringan dimatikan, navigasi ke `/trips/1`.** Jatuh ke shell luring ("Halaman ini belum dapat dimuat") dengan
   "Paket lapangan di perangkat ini", "Status resmi" terpisah dari "Status belum dapat dipastikan sejak 4 Okt 2026
   15.27 WIB.". "Buka mode lapangan" membuka mode lapangan dari paket ("Dari paket luring. ...", "Peta dasar tidak
   tersedia luring"). "Saya sudah di Pintu Cibodas" memajukan pos berikutnya ke Telaga Biru dan menampilkan kalimat
   antrean "Luring. 1 catatan menunggu sinyal untuk dikirim." (`luring-3-lapangan-offline.webp`). **PASS.** Catatan
   alat: emulasi "Offline" chrome-devtools pada sesi ini tidak mengubah `navigator.onLine`, jadi kalimat antrean di
   atas baru tampil persis sesudah `navigator.onLine` ditimpa manual (teknik yang sama dengan solusi geolokasi Step
   5); tanpa itu, kalimat yang tampil adalah cabang lain yang sama validnya, "N catatan belum terkirim. Memount
   mencoba lagi otomatis." (rincian di laporan Step 9).
4. **Selesai tertahan sebelum sinyal kembali.** Tombol "Selesai" TIDAK dirender pada mode lapangan dari paket
   (`data.sumber` selalu `'paket'` di jalur itu; hanya "Jeda" dan "Kartu darurat" ada). Mekanisme tertahan W6-FC
   diuji dari jalur yang benar secara arsitektur: halaman daring `/trips/1/hike` yang sinyal sungguhannya gagal.
   Mengetuk "Selesai" lalu "Simpan hasil pendakian" TIDAK mengirim apa pun; kalimat yang tampil persis "1 catatan
   tiba atau jeda belum terkirim. Tunggu sampai terkirim saat ada sinyal, lalu simpan." **PASS** untuk mekanismenya;
   lihat laporan Step 9 Temuan 3 untuk klarifikasi jalur mana yang benar-benar memilikinya.
5. **Sinyal kembali.** Status antrean kosong segera sesudah jaringan dipulihkan; muat ulang `/trips/1/hike`
   menunjukkan "Pos berikutnya, nomor 2, Telaga Biru" dari server, membuktikan aksi tiba tersimpan. **PASS.**
6. **Versi service worker baru.** Server dihentikan dan dijalankan ulang dengan `FIELD_PACK_ENABLED=false`;
   `registration.update()` lalu muat ulang menghasilkan versi baru `activated`; Cache Storage hanya `shell-0c8687ee`
   (baru) dan `pack-uji` (dengan `/uji-paket` masih ada); IndexedDB paket masih ada (`luring-4-sw-baru.webp`).
   **PASS**, persis sesuai dugaan rencana. Server dikembalikan ke `FIELD_PACK_ENABLED=true`.
7. **Selesaikan pendakian dan ringkasan.** "Selesai sampai target" dikirim; `POST /trips/1/complete` menjawab 302 ke
   `/history/1`. Ringkasan menunjukkan "Pos terjauh: Pintu Cibodas", cocok dengan satu-satunya aksi tiba yang
   terkirim selama sesi. **PASS.**
8. **Keluar.** `POST /logout` menjawab 302 dengan header respons `clear-site-data:"storage"` (dikonfirmasi dua kali).
   Sesudahnya IndexedDB, Cache Storage, `localStorage`, dan pendaftaran service worker semuanya kosong
   (`luring-5-keluar.webp`). **PASS.**

### Temuan metodologi (bukan cacat produk)

- Emulasi viewport alat chrome-devtools MCP tidak bertahan melewati navigasi dokumen penuh pada sesi ini:
  `luring-1-siap.webp` dan `luring-5-keluar.webp` (diambil ulang pada sesi pemeriksaan
  terpisah) benar 390x844; `luring-3-lapangan-offline.webp` dan `luring-4-sw-baru.webp` 908x1304 karena ditemukan
  sesudah trip id 1 (satu-satunya yang memenuhi syarat) sudah terlanjur diselesaikan permanen. Isi dan perilaku pada
  kedua tangkapan itu tetap benar dan terverifikasi lewat snapshot aksesibilitas; hanya dimensi piksel yang
  menyimpang dari target "390 x 844 bila memungkinkan".
- Emulasi "Offline" chrome-devtools tidak memblokir navigasi dokumen utama ke `127.0.0.1` pada sesi ini (permintaan
  tembus ke server sungguhan) dan tidak mengubah `navigator.onLine`, walau permintaan `fetch()` dari dalam halaman
  memang gagal. Untuk menguji shell `/offline` yang sungguhan, server dihentikan sesaat untuk memaksa kegagalan
  nyata, lalu dijalankan ulang sebelum interaksi berikutnya.

Rincian lengkap, termasuk kutipan kode dan nomor baris untuk setiap klaim, ada di `task-13-step9-report.md` (folder
rencana W6).

### Bukti pengganti historis (Step 5/6, sebelum izin Q1)

**Tangkapan pratinjau:** `luring-390-light.webp` (shell luring default, W6-9j) dan `luring-mode-lapangan-390-light.webp`
(mode lapangan dari paket di IndexedDB tiruan pratinjau, W6-9k).

**Test otomatis** (`php artisan test --compact --filter`, dijalankan sebelum izin Q1):

| Kelas test | Jumlah test | Hasil |
|---|---|---|
| `ProgressiveWebAppTest` | 21 | lulus |
| `OfflineShellTest` | 18 | lulus |
| `FieldPackDeviceTest` | 21 | lulus |
| `FieldPackManifestTest` | 13 | lulus |
| `FieldPackStalenessTest` | 16 | lulus |
| `LogoutClearsDeviceDataTest` | 16 | lulus |
| `PengirimJejakTest` | 18 | lulus |
| **Jumlah** | **123** | **123 lulus, 1.199 asersi** |

**Bukti peramban dengan penyimpanan sungguhan dari gelombang implementasi dan pemeriksaan sebelumnya** (dikutip,
bukan diukur ulang):

- `task-9-fix1-check.md`, bagian "Bukti peramban (W6-9k dan m-1, 320x720 mobile+touch)", baris 95-126: alamat dan
  `history.state` tetap pada halaman asal sesudah "Buka mode lapangan" dari paket, fokus berpindah ke `#konten`,
  label dua paket di jalur sama dibedakan tanggal.
- `task-9b-review.md`, bagian "8. Bukti peramban", baris 307 dan seterusnya: ukuran cache pemasangan service worker
  nyata dan daftar entrinya.
- `task-10-fix2-check.md`, bagian "4. Bukti peramban (service worker NYATA, port 8161)", baris 96-142: DUA deploy
  balapan service worker SUNGGUHAN (bukan simulasi), log `controllerchange` lalu `statechange: activated` lalu vonis
  turun baru muncul sesudahnya.
- `task-11-fix1-check.md`, bagian "4. Bukti peramban (port 8160, penyimpanan sungguhan)", baris 63-134: penjaga
  keluar dengan props riwayat, hitungan percobaan ulang, dan basis data `jejak` tidak dibuat ulang oleh tab lain.
- `task-W6-P2-report.md`, bagian "6. Bukti peramban (port 8162, chrome-devtools MCP)" baris 291-335 dan "13.3 Bukti
  peramban (port 8162, IndexedDB sungguhan)" baris 550-602: perekaman jejak tanpa kiriman selama pendakian aktif,
  antrean dan penghapusan jejak dengan IndexedDB sungguhan, termasuk skenario kirim-lalu-putus dan balapan hapus.

## Batas harness

- Tidak ada berkas pratinjau terpisah bernama `offline.html` atau dengan query `?dibuka=paket`; representasi W6-9j dan
  W6-9k dicapai lewat `luring.html` sebelum dan sesudah tombol "Buka mode lapangan" ditekan (lihat tabel
  perbandingan). Perilaku route `/offline` sungguhan dijaga `OfflineShellTest`.
- Fitur `prefers-reduced-motion` tidak dapat diemulasikan langsung lewat tool `emulate` chrome-devtools yang dimuat
  pada sesi ini (hanya `colorScheme` yang tersedia untuk fitur media); diverifikasi lewat kode dan `MotionTest`
  sebagai gantinya (lihat bagian Pengukuran).
- Peta dasar mode lapangan DARING merender ubin sungguhan di harness ini karena akses jaringan keluar tersedia;
  perilaku ini tidak diverifikasi terhadap kondisi benar-benar tanpa internet pada mesin ini (beda dari uji luring
  Step 9 yang memang menguji mati-jaringan).
