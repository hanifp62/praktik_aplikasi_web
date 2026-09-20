# Sistem Desain — Rancangan

Tanggal: 20 September 2026
Dunia visual: **editorial utilitarian**, satu nuansa untuk seluruh aplikasi

---

## 1. Diagnosis, terukur

Keluhannya: antarmuka masih terbaca seperti CRUD sederhana. Diukur pada kode, bukan
ditaksir:

| Yang diukur | Nilai |
|---|---|
| Token yang ada | 25, **seluruhnya nilai global** |
| Token semantik yang menyebut peran | **1** (`--control-border`) |
| Abu mentah Tailwind langsung di view | **597 pemakaian** |
| Empat abu untuk teks, dipakai bergantian | `900`×145, `700`×109, `600`×116, `500`×112 |
| Pemakaian `text-sm` + `text-xs` | 363 dari 426 (85%) |
| SVG di seluruh aplikasi | **5**, tanpa satu pun pustaka ikon |
| Tag `og:` dan `twitter:` | **0** |
| `rel="icon"` di layout | **0** |
| Pembatas lebar baca (`max-w-prose`) | **1** |
| `text-wrap: balance` | **0** |
| Umpan balik memuat (`wire:loading`) | 4 tempat, tanpa satu pun skeleton |

Ini bukan design system. Ini Tailwind bawaan dengan satu warna merek ditempel.

Riset sistem desain enterprise menyebut pembedanya persis: sistem profesional bertingkat
tiga, **global → semantik → komponen**, sedangkan struktur rata adalah ciri template.
Riset yang sama mencatat satu hal yang mendiagnosis kegagalan proses ini sendiri:
ketika sistemnya tidak berstruktur, perkakas AI jatuh ke pola generik dari data latihnya.
Itu yang terjadi di sini — tiga fase pengembangan menghasilkan Tailwind default karena
tidak ada apa pun untuk diikuti.

---

## 2. Arsitektur token

### Tingkat 1 — primitif

Tangga warna yang sudah ada (`brand`, `warn`, `danger`, `community`) ditambah **tangga
netral hangat** yang belum ada sama sekali.

Netralnya didefinisikan sendiri, bukan memakai abu Tailwind. Kanvas editorial bernuansa
hangat sementara abu Tailwind bersemu biru, dan permukaan hangat dengan teks dingin
adalah ciri tema yang ditempel di atas default. Nilainya dihitung agar rasio kontrasnya
setara dengan yang digantikan, bukan ditaksir mata.

### Tingkat 2 — semantik

Lapisan yang hilang. Namanya menyebut peran, bukan warna.

| Token | Peran | Rasio terhitung |
|---|---|---|
| `--text-primary` | Judul, nilai kunci | 16,97:1 |
| `--text-secondary` | Badan teks | 9,86:1 |
| `--text-muted` | Label, metadata, waktu | **4,63:1** |
| `--canvas` | Latar halaman | — |
| `--surface` | Kartu dan panel | — |
| `--surface-sunken` | Sumur, kepala tabel | — |
| `--border-subtle` | Pemisah dekoratif | — |
| `--border-strong` | Batas kontrol interaktif | sudah ada, 3,52:1 |

Tangganya **16,97 / 9,86 / 4,63**: tiga tingkat yang terbaca sebagai hierarki,
menggantikan empat abu yang tertukar-tukar.

`--text-muted` hanya berjarak 0,13 dari ambang 4,5:1. Itu bukan alasan menghindarinya,
melainkan alasan menguncinya dengan test yang menghitung, sehingga penyesuaian kanvas di
kemudian hari gagal berisik alih-alih melanggar AA diam-diam.

### Tingkat 3 — token komponen

**Tidak dibuat.** Riset menyebutnya untuk pengecualian, dan belum ada komponen di sini
yang perlu menyimpang dari semantiknya. Membuatnya di muka menghasilkan lapisan yang
hanya meneruskan nilai.

---

## 3. Peta migrasi

| Sekarang | Jumlah | Menjadi |
|---|---|---|
| `text-gray-900`, `text-gray-800` | 158 | `text-primary` |
| `text-gray-700`, `text-gray-600` | 225 | `text-secondary` |
| `text-gray-500` | 112 | `text-muted` |
| `border-gray-200`, `border-gray-100` | 51 | `border-subtle` |
| `bg-gray-100`, `bg-gray-50` | 51 | bergantung konteks |

Dua penggabungan pertama adalah konsolidasi yang disengaja: `600` dan `700` selama ini
dipakai bergantian untuk peran yang sama.

Baris terakhir tidak dapat diganti secara mekanis. `bg-gray-100` dipakai untuk kanvas
halaman dan untuk chip kecil, dan keduanya menuju token yang berbeda.

---

## 4. Urutan

1. **Tambah token semantik**, nilainya dipetakan persis ke abu sekarang. Rupa tidak
   berubah.
2. **Migrasikan 597 kelas.** Rupa tetap tidak berubah; ini penggantian nama murni.
3. **Ubah nilai semantiknya.** Seluruh aplikasi berubah sekaligus, di selusin baris.

Langkah 1 dan 2 mempertahankan perilaku, dan itu yang membuatnya dapat diverifikasi:
kalau rupanya berubah di langkah 2, ada yang rusak. Perubahan rupa hanya boleh terjadi
di langkah 3.

---

## 5. Celah yang ditemukan audit, di luar token

Audit `redesign-existing-projects` dijalankan terhadap kode, bukan dibaca. Lima temuan
ini tidak tercakup rancangan token dan masing-masing menyumbang pada kesan sederhana.

### 5.1 Tidak ada sistem ikon sama sekali

Lima SVG di seluruh aplikasi, tanpa pustaka. Setiap baris adalah dinding kata, dan
pembaca harus membaca untuk mengetahui apa yang sedang dilihatnya.

Yang dipakai **Phosphor**, berat `regular`, ditanam sebagai SVG sebaris lewat komponen
Blade. Bukan Lucide maupun Heroicons: keduanya pilihan bawaan yang dipakai hampir semua
antarmuka yang dihasilkan AI, dan memakainya berarti mengulang tanda tangan yang sedang
kita hapus. Tanpa pustaka JavaScript dan tanpa beban build di luar ikon yang benar-benar
dipakai.

**Aturannya: ikon hanya di tempat yang membawa arti** — jenis pos, arah naik dan turun,
status, sumber data. Tidak pernah sebagai hiasan di samping judul.

### 5.2 Tidak ada satu pun tag sosial

Nol `og:`, nol `twitter:`, dan tidak ada `rel="icon"` di layout meskipun berkasnya ada.

Ini cacat pertumbuhan yang saya perkenalkan sendiri pada halaman jalur publik: setiap
tautan yang dibagikan muncul sebagai URL telanjang tanpa judul maupun gambar, dan
halaman publik itu memang dibuat untuk dibagikan.

### 5.3 Tidak ada pembatas lebar baca

Satu `max-w-prose` di seluruh aplikasi. Pada layar lebar, kalimat penjelas membentang
penuh dan mata kehilangan awal baris berikutnya. Ukuran nyaman sekitar 65 karakter.

### 5.4 Judul tidak pernah diseimbangkan

Nol `text-wrap: balance`. Judul dua baris kerap menyisakan satu kata sendirian di baris
kedua, dan itu terbaca sebagai kelalaian tata letak.

### 5.5 Umpan balik memuat hanya di empat tempat

Empat `wire:loading`, tanpa satu pun skeleton. Halaman yang memuat peta, menjalankan
mesin rekomendasi, atau menghitung tempo antarpos diam sampai isinya tiba.

Skeleton yang mengikuti bentuk isinya, bukan pemutar lingkaran, karena bentuk yang
sudah terlihat memberi tahu apa yang sedang ditunggu.

---

## 6. Yang sengaja tidak dikerjakan

- **Bento grid, kartu double-bezel, animasi masuk bertahap.** Disebut
  `high-end-visual-design`, dan skill itu menyasar mode Persuade, yaitu halaman yang
  tugasnya membujuk. Seluruh aplikasi ini mode Operate, tempat kemudahan memindai
  mengalahkan ekspresi, dan animasi masuk pada halaman perencanaan keselamatan menunda
  isi demi hiasan.
- **Kontrak komponen, preset kepadatan, dan versioning.** Riset yang sama menyebut
  sistem Tahap 1 harus condong ke pengiriman dan Tahap 3 ke tata kelola. Sistem ini
  Tahap 1: satu produk, satu pemelihara, sepuluh komponen bersama. Yang diambil hanya
  cakupan state untuk komponen yang sudah ada.
- **Penyeragaman radius.** `rounded-md` pada kontrol dan `rounded-lg` pada kartu adalah
  perbedaan bermakna. Menyeragamkannya menghapus perbedaan itu tanpa imbalan.
- **Tekstur, grain, dan bayangan berwarna.** Disebut beberapa skill sebagai penambah
  kedalaman. Ditolak karena dunia yang dipilih editorial utilitarian, dan tekstur pada
  antarmuka operasional menambah bita tanpa menambah keterbacaan.

---

## 7. Batasan yang tetap berlaku

- **WCAG 2.2 AA**, dihitung bukan ditaksir. Pasangan semantik ikut dihitung, bukan hanya
  pasangan warna.
- **Warna semantik tidak didesaturasi.** Status resmi, peringatan, dan bahaya membawa
  arti keselamatan.
- **§92**: keterangan resmi dan masukan komunitas tetap terbedakan secara visual.
- **§88 mobile-first**: tidak ada yang meluber pada lebar 400px.
- Anggaran halaman 120 kB dan anggaran query tetap dijaga test yang sudah ada.
- **746 test harus tetap hijau tanpa disunting** pada langkah 1 dan 2.

---

## 8. Ukuran keberhasilan

Bukan kesan, melainkan yang dapat diperiksa:

1. **Nol** pemakaian `text-gray-*`, `bg-gray-*`, `border-gray-*` mentah di view.
2. Mengubah seluruh rasa aplikasi cukup menyentuh **nilai semantik saja**, dan ada test
   yang membuktikan tidak ada view yang melewatinya.
3. Seluruh pasangan teks-terhadap-permukaan **dihitung** memenuhi AA, termasuk
   `--text-muted` yang marginnya tipis.
4. Setiap halaman publik membawa judul, ringkasan, dan gambar sosialnya sendiri.
5. Suite tetap hijau, anggaran halaman dan query tidak naik.
