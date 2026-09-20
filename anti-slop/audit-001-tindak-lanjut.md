# Tindak lanjut audit antislop 001

Tanggal: 20 September 2026
Yang disetujui: temuan 1, 2, dan 3 (seluruh Hard Gate)
Commit: `bcdd544`, `2abb2f6`, `48bf730`

Nomor 4 sampai 8 tidak disentuh.

---

## 1. R-35 — aplikasinya belum pernah dijalankan → selesai

Dijalankan di SQLite lokal, supaya Supabase produksi tidak tersentuh sama sekali.

Ditelusuri lewat HTTP sungguhan: `/`, `/login`, `/register`, `/forgot-password`,
`/offline`, `/sitemap.xml`, dan satu halaman jalur publik. Semuanya 200, nol galat di
log server. Kartu sosial yang ditambahkan Tugas 5 terbukti benar-benar terkirim di HTML
yang dikirim peramban, bukan hanya lolos assertion.

Halaman yang butuh login tidak dapat ditelusuri lewat curl: formulir masuknya Livewire
Volt dan tidak punya rute POST. Penggantinya bukan inspeksi kode, melainkan
`EveryRouteRendersTest`, yang melewati kernel HTTP yang sama dan mencakup **seluruh rute
GET sekaligus**, termasuk halaman yang ditulis besok tanpa ada yang perlu mengingat
menambahkannya. Dibuktikan menyala: satu halaman dirusak sebentar dan ia menyebut
namanya.

**Ia langsung menemukan satu galat.** Meminta `/login` sebagai pengguna yang sudah masuk
melempar TypeError, karena `RedirectIfAuthenticated` memanggil `redirect()` sementara
binding redirect di container sudah ditukar Livewire dengan Redirector miliknya, yang
bukan `Response`.

Cakupannya dinyatakan apa adanya: **di produksi ini tidak terjadi**, karena tiap
permintaan php-fpm memulai container baru dan proyek ini tidak memakai Octane. Di bawah
Octane ia akan menjadi galat sungguhan.

## 2. R-03 — tidak ada yang mengukur lebar 400px → selesai

Enam target sentuh diperbaiki, dan yang terkecil justru yang paling sering disentuh:

| Kontrol | Sebelum | Sesudah |
|---|---|---|
| Menu hamburger | 40px | 44px |
| Tombol status daftar persiapan | ~24px | 44px |
| Pemicu menu profil | 32px | 44px |
| Dua tombol keluar | diwarisi dari anaknya | 44px |
| Tombol sekunder Breeze | 32px | 44px |

Tautan teks di dalam kalimat tidak ikut diperbesar: WCAG 2.5.8 mengecualikannya sendiri,
dan memperbesarnya justru merusak baris kalimat yang memuatnya. `TapTargetTest`
menyatakan pengecualian itu sebagai aturan, bukan sebagai daftar berkas.

Baris metrik di daftar jalur juga diperbaiki: ia memaksa tiga kolom pada lebar berapa
pun, dan di dalam kartu ber-padding pada 400px itu menyisakan sekitar seratus piksel per
kolom.

Yang diukur dan ternyata sudah bersih, jadi tidak disentuh: nol lebar piksel tetap, nol
`whitespace-nowrap`, dan kesepuluh tabel sudah terbungkus `overflow-x-auto`.

## 3. R-27 — keadaan UI tidak lengkap → selesai

Sebelas daftar utama sekarang menjelaskan dirinya ketika kosong, dengan teks yang
menyebut penyebab dan bukan ketiadaan (§104).

Aturannya diperbaiki tiga kali sebelum dipakai, dan tiap kali karena aturannya yang
salah, bukan berkasnya:

1. Pemeriksaan per berkas menuduh tiga view yang sudah dijaga, sekaligus membebaskan tiga
   perulangan lain hanya karena ada satu `isEmpty()` di berkas yang sama.
2. Nomor baris yang dilaporkannya meleset di hampir setiap berkas, karena komentar
   multi-baris diganti satu spasi.
3. Versi per-perulangan menemukan 28, sebagian besar perulangan dalam atas isi satu item.
   Menambahkan 28 keadaan kosong justru teknik tanpa tujuan.

Tersisa lima belas, diputuskan menurut apa yang sebenarnya dapat terjadi: tujuh mendapat
keadaan kosong sungguhan, enam diberi penanda "daftar tetap" karena isinya enum yang
ditetapkan di kode, dan dua perulangan `<option>` dikecualikan aturannya sendiri.

---

## Keadaan sesudah

| Diukur | Sebelum audit | Sesudah |
|---|---|---|
| Test | 786 | **792** (781 lulus, 11 dilewati) |
| Target sentuh di bawah 44px | 6 | 0 |
| Daftar utama tanpa keadaan kosong | 15 | 0 |
| Rute GET yang tidak pernah dirender test | seluruhnya | 0 |
| Aplikasi pernah dijalankan | tidak | ya, tujuh halaman lewat HTTP |

Temuan 4 sampai 8 tetap terbuka dan tidak disentuh.

---

# Lanjutan: temuan 4 sampai 8

Commit: `61e9de5`, `2304685`

## 4. R-06 (JetBrains Mono adalah pilihan bawaan AI) selesai, dan lebih besar daripada temuannya

Pengukuran menjawab pertanyaan "mengapa huruf ini" dengan jawaban yang lebih sederhana
daripada mencari penggantinya.

Komentar di `tailwind.config.js` menyebut huruf itu "khusus pengukuran: jarak, elevation
gain, durasi, dan koordinat". Tidak satu pun pengukuran memakainya. Semuanya memakai
`tabular-nums` pada huruf sans. `font-mono` muncul **tepat dua kali** di seluruh
aplikasi, keduanya kunci tugas penjadwal pada satu halaman admin.

Jadi setiap pengguna mengunduh dua bobot huruf untuk dua baris yang tidak pernah ia
lihat, dan alasan yang tertulis menggambarkan pemakaian yang tidak ada. Alasan semacam
itu lebih buruk daripada tidak ada alasan, karena ia menghentikan pertanyaan berikutnya.

Ketiga layout berhenti memuatnya. Kunci seperti `weather:refresh` justru persis yang
pantas memakai mono bawaan sistem.

## 5. R-37 (tidak ada berkas arah) selesai

`DESIGN.md` memisahkan kata pemilik produk dari simpulan yang ditarik agen, supaya
bagian yang boleh dibantah terlihat. Tiga dial ditetapkan beserta dasarnya: ENERGY 1,
RHYTHM 2, MOTION 1.

Satu hal sengaja dibiarkan terbuka di sana, bukan dijawab sendiri: **mode gelap belum
diputuskan pemilik produk.** Ia bukan pekerjaan yang ditunda melainkan keputusan yang
belum diambil.

## 6. R-29 (empat warna inti) selesai

Keempatnya memang punya alasan, hanya saja alasan itu tidak pernah ditulis sebagai
keputusan palet. Sekarang tertulis, beserta aturannya: warna baru hanya boleh masuk
ketika ia membawa arti yang tidak dibawa keempatnya.

## 7. R-11 (lima varian radius) selesai

Yang sungguh tidak konsisten ternyata bukan jumlah variannya. `rounded-md` dan
`rounded-control` bernilai persis sama, `0.375rem`, dengan dua nama, dan hanya satu di
antaranya dibaca dari token.

| Kelas | Sebelum | Sesudah |
|---|---|---|
| `rounded-control` | 19 | **89** |
| `rounded-md` | 70 | 0 |
| `rounded-lg` | 29 | 29 |
| `rounded-full` | 11 | 11 |
| `rounded-sm` | 1 | 1 |

Nilainya identik, jadi tidak ada satu piksel pun yang berubah. `rounded-sm`
dipertahankan karena perannya sah: cincin fokus yang memeluk teks sebaris.

## 8. R-02 (em dash) selesai, dengan batas yang dinyatakan

Sebelas em dash di kode dan berkas arah diganti koma, titik dua, atau tanda kurung.

Dokumen spec, rencana, dan audit di `docs/` sengaja tidak disentuh: ia catatan bertanggal
tentang apa yang terjadi pada hari itu, dan menulis ulang catatan supaya terlihat rapi
adalah menyunting rekaman, bukan memperbaiki tulisan. Penjaganya menyatakan batas itu di
dalam dirinya sendiri.

---

# Keadaan akhir

| Diukur | Sebelum audit | Sesudah |
|---|---|---|
| Test | 786 | **798** (787 lulus, 11 dilewati) |
| Temuan terbuka | 8 | **0** |
| Target sentuh di bawah 44px | 6 | 0 |
| Daftar utama tanpa keadaan kosong | 15 | 0 |
| Rute GET yang tidak pernah dirender test | seluruhnya | 0 |
| Huruf web yang diunduh | 3 keluarga | 2 |
| Nama radius untuk satu nilai | 2 | 1 |
| Em dash di kode | 11 | 0 |
| Berkas arah desain | tidak ada | `DESIGN.md`, tiga dial ditetapkan |

Satu hal menunggu keputusan pemilik produk, dan ditulis di `DESIGN.md` alih-alih
dijawab sendiri: mode gelap.

Satu hal menunggu izin yang bukan milik pemilik produk: pembacaan Supabase produksi
ditolak lapisan izin Claude Code (`Reason: [Production Reads]`), sehingga dugaan bahwa
delapan migrasi belum berjalan masih dugaan, bukan ukuran.
