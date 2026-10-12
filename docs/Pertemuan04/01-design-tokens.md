# 01 — Design Tokens MEMOUNT

## 1. Tujuan dan ruang lingkup

Dokumen ini mendeskripsikan sistem visual MEMOUNT berdasarkan dua sumber utama:

1. **Prototype acuan:** `docs/job-3/06-prototype.html`.
2. **Implementasi:** `resources/css/app.css`, `resources/views/components/memount-layout.blade.php`, `resources/views/components/bottom-nav.blade.php`, serta style pada Blade view.

Prototype menyimpan sejumlah layar sebagai HTML yang dikompresi di dalam array `PAGES`. Pemeriksaan token layar dilakukan terhadap frame yang sudah didekode, bukan hanya terhadap halaman pembungkus prototype. Cakupan layar utama adalah 00A sampai 14. Prototype juga memiliki frame 15 untuk Jelajah desktop (1440 × 900), sedangkan layar mobile umumnya menggunakan lebar 390 px.

> **Catatan pembacaan:** ada dua kelompok warna yang perlu dibedakan. CSS wrapper prototype mendefinisikan palet lama seperti `#f4f2ed`, sedangkan konten individual frame menggunakan warna layar seperti `#E9E5DC` dan `#1D2320`. Implementasi `app.css` saat ini masih menjadikan palet wrapper sebagai token global. Perbedaan tersebut dicatat sebagai gap, bukan disamarkan sebagai kesesuaian penuh.

## 2. Color tokens

### 2.1 Warna layar pada frame prototype

| Token/warna | Nilai | Peran pada prototype | Bukti/penggunaan |
|---|---|---|---|
| Canvas utama | `#E9E5DC` | Latar utama layar mobile | Nilai berulang dalam frame 00–15 yang didekode |
| Teks utama | `#1D2320` | Judul, teks utama, ikon utama | Header, teks statistik, label dan ikon |
| Green utama | `#234F32` | CTA, aksen, elemen aktif | Tombol aksi, indikator aktif, link penting |
| Green lebih gelap | `#16361F` | State green yang lebih gelap | Dipakai pada detail gaya tertentu; bukan pengganti otomatis green utama |
| Surface terang | `#FFFFFF` | Tombol, bidang/card terang, permukaan kontrol | Tombol kembali, kartu tertentu, avatar/frame |
| Surface sekunder | `#F6F3EC` | Bidang atau permukaan sekunder | Permukaan navigasi/kelompok kontrol tertentu |
| Border utama | `#E2DDD2` | Garis tepi lembut | Border tombol, card dan avatar |
| Teks sekunder | `#4E5551` | Deskripsi dan teks pendukung | Keterangan, paragraf dan label sekunder |
| Teks metadata | `#666D69` | Metadata berukuran kecil | Label `DATA CONTOH`, keterangan kecil |
| Border sekunder | `#CFC8BA` | Pemisah dan garis sekunder | Detail border di beberapa frame |
| Border putus-putus | `#B9B2A4` | Penanda sekunder/dashed | Label metadata dan elemen yang dibedakan dari kontrol utama |
| Green highlight | `#8FD3A0` | Highlight angka/statistik positif | Angka tanjakan pada kartu gelap di Ringkasan |
| Danger | `#8E2F22` | Aksi berisiko/destruktif | Elemen penghapusan pada Profil |
| Aksen amber | `#E8B04B` | Aksen kontras khusus | Terlihat pada Mode Mendaki dan elemen aksen tertentu |
| Border upload foto | `#7C9CCB` | Aksen kontrol foto | Tombol upload Foto pada Ringkasan |
| Teks biru | `#24508C` | Teks aksen pendamping kontrol foto | Digunakan pada elemen terkait foto di beberapa frame |

Warna lain muncul untuk state status, chip, grafik, dan kategori kondisi. Warna-warna tersebut bersifat kontekstual; gunakan nilai dari elemen/frame yang bersangkutan, bukan menjadikannya warna utama seluruh aplikasi.

### 2.2 Token global di implementasi Laravel

Berikut nilai yang benar-benar didefinisikan di `resources/css/app.css`:

| Token implementasi | Nilai aktual | Kegunaan di CSS |
|---|---|---|
| `--color-mm-bg` | `#f4f2ed` | Background body dan `.mm-shell` |
| `--color-mm-surface` | `#ffffff` | Surface terang |
| `--color-mm-ink` | `#171b18` | Teks utama |
| `--color-mm-muted` | `#707772` | Teks sekunder |
| `--color-mm-line` | `#e8e4db` | Garis/border |
| `--color-mm-green` | `#1f6f47` | Green aksi utama |
| `--color-mm-green-dark` | `#214f34` | Tombol, fokus, dan aksen/auth |
| `--color-mm-green-soft` | `#edf4ef` | Latar hijau lembut |
| `--color-mm-green-faint` | `#c7e0d3` | Latar opening/login |
| `--color-mm-green-wave` | `#8fc473` | Panel bawah login |
| `--color-mm-yellow` | `#f0b83b` | Titik aksen logo |
| `--color-mm-amber-bg` | `#fbf4e6` | Latar status amber |
| `--color-mm-amber-border` | `#f0d899` | Border status amber |
| `--color-mm-amber-ink` | `#7a4e0b` | Teks status amber |
| `--color-sand` | `#e9e5dc` | Alias warna sand |
| `--shadow-card` | `0 2px 8px rgb(23 27 24 / 6%)` | Bayangan card |
| `--shadow-float` | `0 10px 28px rgba(24, 32, 27, .07)` | Bayangan elemen mengambang |

**Gap penting:** walaupun `--color-sand` bernilai `#e9e5dc`, aturan `body` dan `.mm-shell` menggunakan `--color-mm-bg` (`#f4f2ed`). Warna teks/green global juga menggunakan nilai yang berbeda dari nilai utama pada frame prototype. Karena itu, nilai prototype dan implementasi harus dianggap dua kolom berbeda sampai perbandingan visual ulang membuktikan kesesuaiannya.

## 3. Typography

| Aspek | Prototype | Implementasi aktual | Catatan |
|---|---|---|---|
| Sans utama | `Geist`, fallback `system-ui`, `-apple-system`, `Segoe UI`, sans-serif | `Geist`, `Inter`, `ui-sans-serif`, `system-ui`, `-apple-system`, `Segoe UI`, sans-serif | `app.css` tidak mendefinisikan blok `@font-face`; font Geist lokal belum terbukti tersedia di aplikasi |
| Monospace | `Geist Mono`, fallback `ui-monospace`, monospace | `Geist Mono`, fallback `ui-monospace`, monospace pada token Tailwind | Beberapa metadata di prototype memakai class `m`; pemakaian font aktual bergantung pada ketersediaan font |
| Judul halaman | Umumnya sekitar 24–28 px pada frame utama | Beragam antar-view | Prototype memanfaatkan style per-frame dan inline; belum ada satu skala tipografi formal yang digunakan secara merata |
| Body/deskripsi | Umumnya sekitar 14 px dengan line-height nyaman | Beragam; beberapa komponen menggunakan 12–14 px | Periksa per halaman karena tidak semua view memakai komponen global yang sama |
| Label/metadata | Umumnya 10–12 px | Nav 10 px, input/button sekitar 12–13 px pada CSS global | Angka tersebut adalah ukuran yang ditemukan di implementasi, bukan aturan universal |
| Weight | Hierarki 400–800 ditemukan pada frame | Button 750–800; nav 600; elemen lain memakai utility Tailwind | Gunakan weight sesuai peran elemen dan prototype, bukan seragam untuk semua teks |

## 4. Spacing dan layout

Tidak ditemukan satu skala spacing bernama yang mengatur seluruh inline style pada prototype. Nilai yang berulang meliputi 4, 6, 8, 10, 12, 14, 16, 20, dan 24 px. Sebagian nilai berasal dari style eksplisit masing-masing frame atau utility Tailwind.

| Pola | Nilai/aturan yang ditemukan | Lokasi |
|---|---|---|
| Viewport layar mobile prototype | Lebar 390 px; tinggi berbeda tiap frame | Array dimensi prototype |
| Frame onboarding | 390 × 844 px | Frame 00 |
| Ringkasan | 390 × 1940 px | Frame 12 |
| Progres | 390 × 1880 px | Frame 13 |
| Profil | 390 × 1740 px | Frame 14 |
| Jelajah desktop | 1440 × 900 px | Frame 15, di luar cakupan utama 00A–14 |
| Shell Laravel | `width: min(390px, 100%)`, `max-width: 390px`, `min-height: 100dvh` | `.mm-shell` di `app.css` |
| Input standar | `min-height: 42px`, padding horizontal 12 px, radius 10 px | `.memount-input` |
| Tombol standar | `min-height: 46px`, radius 10 px | `.memount-button` dan `.memount-button-secondary` |
| Bottom nav | Minimal tinggi item 56 px; padding 10 px 4 px 8 px | `.mm-nav-item` |
| Header kembali | Tombol prototype lazimnya 44 × 44 px berbentuk bulat | Header beberapa frame |

Gunakan ukuran viewport yang sesuai ketika membuat screenshot. Jangan mengecilkan seluruh halaman panjang menjadi satu gambar kecil hanya untuk memasukkannya ke PDF.

## 5. Border, radius, dan shadow

| Elemen | Gaya prototype/implementasi yang teramati | Catatan |
|---|---|---|
| Tombol kembali | 44 × 44 px; radius setengah ukuran (22 px); border `#E2DDD2` | Pola pada beberapa frame mobile |
| Card | Radius beragam menurut komponen; banyak card berkisar 12–18 px | Jangan mengasumsikan semua card identik |
| Tombol global Laravel | Radius 10 px | `.memount-button` dan versi secondary |
| Card global Laravel | Radius 18 px; border `--color-mm-line`; background putih | `.mm-card` |
| Input global Laravel | Radius 10 px | `.memount-input` |
| Nav bawah | Background putih transparan 96%; blur 8 px; border atas `--color-mm-line` | `.mm-bottom-nav` |
| Shadow card | `0 2px 8px rgb(23 27 24 / 6%)` | Token `--shadow-card` |
| Shadow floating | `0 10px 28px rgba(24, 32, 27, .07)` | Token `--shadow-float` |

## 6. Iconography

- Prototype memakai SVG inline dan class `i` untuk ikon.
- Beberapa ikon berukuran sekitar 18–20 px; ikon navigasi implementasi global berukuran 22 × 22 px.
- Ikon berfungsi sebagai pendamping label atau aksi, bukan pengganti label aksesibel.
- `resources/views/components/bottom-nav.blade.php` memuat SVG langsung untuk lima item navigasi.
- Tidak terlihat dependency icon package khusus pada `package.json`; jangan mendokumentasikan Lucide atau library lain sebagai dependency aplikasi tanpa bukti tambahan.

## 7. Status dan warna semantik

Komponen `x-status-badge` menerima `OPEN`, `CLOSED`, `RESTRICTED`, atau nilai lain yang jatuh ke gaya default. Nilai default ditampilkan sebagai teks `UNKNOWN` jika input-nya memang demikian. CSS juga mempunyai kelompok kelas status/Route Fit.

Pada logika rekomendasi, status jalur yang tidak tersedia diturunkan menjadi `UNKNOWN`. Status resmi tidak boleh diasumsikan `OPEN` tanpa sumber yang tersedia.

## 8. Navigation dan responsive rules

- Prototype mobile menggunakan navigasi lima item: **Beranda, Jelajah, Trip, Mendaki, Progres**.
- `x-bottom-nav` mengimplementasikan lima item itu; Profil diakses melalui tautan/avatar di halaman terkait, bukan menu keenam.
- `.mm-shell` membatasi lebar konten hingga 390 px dan memusatkannya pada layar yang lebih lebar.
- Frame 15 adalah contoh Jelajah layar lebar dengan ukuran 1440 × 900 px. Implementasi `/jelajah` menggunakan route/view yang sama dan menerima query `mode`; keberhasilan kesetaraan desktop perlu dibuktikan lewat screenshot.
- `html` mengaktifkan `scroll-behavior: smooth`; fokus keyboard memakai outline 2,5 px pada warna green gelap.

## 9. Ringkasan gap desain

1. Palet global implementasi (`#f4f2ed`, `#171b18`, `#1f6f47`, `#e8e4db`) belum identik dengan warna utama per-frame (`#E9E5DC`, `#1D2320`, `#234F32`, `#E2DDD2`).
2. Token `--color-sand: #e9e5dc` sudah tersedia, tetapi `body` dan `.mm-shell` masih memakai `--color-mm-bg: #f4f2ed`.
3. Prototype memuat font-face Geist/Geist Mono; stylesheet aplikasi menetapkan nama font namun tidak menyertakan `@font-face` lokal yang sama.
4. Prototype memakai inline styles yang cukup spesifik per frame. Karena itu, daftar di dokumen ini merupakan inventaris token/aturan yang teramati, bukan klaim bahwa seluruh layar sudah pixel-perfect.

## 10. Sumber file

- `docs/job-3/06-prototype.html` — frame, dimensi, font dan gaya visual acuan.
- `resources/css/app.css` — token Tailwind dan komponen UI global implementasi.
- `resources/views/components/memount-layout.blade.php` — dokumen HTML, Vite dan background body.
- `resources/views/components/bottom-nav.blade.php` — navigasi global lima item.
- `resources/views/components/status-badge.blade.php` — status jalur.
- `resources/views/components/trail-card.blade.php` — komponen kartu rekomendasi.
