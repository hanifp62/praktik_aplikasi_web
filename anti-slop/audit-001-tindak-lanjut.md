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
