# Fase 1: Memberi Wujud — Rencana Implementasi

**Spec:** `docs/superpowers/specs/2026-09-20-pengalaman-pendaki-design.md`

**Tujuan:** Menampakkan kecerdasan yang sudah ada di dalam sistem, tanpa menambah satu
pun konsep domain baru.

**Arsitektur:** Seluruh data yang dibutuhkan fase ini sudah tersimpan dan sudah dimuat
ke view; yang belum ada hanya komponen yang menggambarnya. Karena itu fase ini hampir
seluruhnya berupa komponen Blade baru ditambah perubahan view, dengan perubahan
komponen Livewire seminimal mungkin.

**Tumpukan:** Laravel 13, Livewire 3, Tailwind 3, MapLibre GL (dimuat malas lewat
`window.muatPeta()`), PHPUnit 12, Pint.

## Batasan menyeluruh

Berlaku untuk setiap tugas di bawah, tanpa perlu diulang:

- **BR-09**: `internal_score` tidak pernah sampai ke pengguna, dalam bentuk apa pun,
  termasuk sebagai lebar batang atau persentase yang dapat dibaca balik.
- **§92**: keterangan resmi dan masukan komunitas harus tetap terbedakan secara visual.
- **WCAG 2.2 AA**: kontras 4.5:1 teks normal, 3:1 komponen non-teks; peta memakai
  `role="region"` dengan `aria-label`, tidak pernah `role="img"`; setiap grafik
  membawa padanan teks.
- **§88 mobile-first**: tidak ada yang meluber pada lebar 400px.
- **Anggaran ukuran halaman** yang sudah dijaga `ResultListBudgetTest` tetap berlaku:
  120 kB untuk halaman hasil.
- **Anggaran query** yang dijaga `RecommendationQueryBudgetTest` tetap konstan terhadap
  jumlah jalur.
- Setiap tugas: test yang gagal lebih dulu, lalu perbaikan, lalu Pint, lalu commit.

## Struktur berkas

| Berkas | Tanggung jawab |
|---|---|
| `resources/views/components/ui/map.blade.php` | **Baru.** Satu-satunya tempat MapLibre dipakai. Memuat konfigurasi sendiri dari `config('hiking.map')`. |
| `resources/views/components/ui/photo-strip.blade.php` | **Baru.** Deretan foto komunitas beserta atributnya. |
| `resources/views/components/ui/factor-bars.blade.php` | **Baru.** Penalaran Route Fit sebagai batang. |
| `resources/views/livewire/trips/hike-mode.blade.php` | Memakai `<x-ui.map>`, kode petanya dicabut. |
| `resources/views/livewire/trails/trail-detail.blade.php` | Peta, profil elevasi, foto, pos sebagai perjalanan. |
| `resources/views/livewire/recommendations/recommendation-results.blade.php` | Batang faktor menggantikan butir prosa. |
| `app/Livewire/Trails/TrailDetail.php` | Memuat foto laporan yang lolos moderasi. |

---

## Tugas 1: Komponen peta yang dapat dipakai ulang

**Berkas:**
- Buat: `resources/views/components/ui/map.blade.php`
- Test: `tests/Feature/MapComponentTest.php`
- Ubah: `resources/views/livewire/trips/hike-mode.blade.php`

**Antarmuka yang dihasilkan** (dipakai tugas berikutnya dan Fase 2):

```blade
<x-ui.map
    id="trail-map"
    :geometry="$geometry"          {{-- array GeoJSON geometry, boleh null --}}
    :markers="$checkpoints"        {{-- [['lng'=>, 'lat'=>, 'label'=>], ...] --}}
    label="Peta jalur Merbabu via Selo"
    height="h-80"
/>
```

- [ ] Test gagal dulu: komponen merender wadah dengan `role="region"`, `aria-label`
      terisi, dan atribusi peta; tanpa geometri maupun penanda ia tidak merender apa pun.
- [ ] Bangun komponennya dengan mencabut kode dari hike-mode, bukan menulis ulang.
      Konfigurasi dibaca di dalam komponen dari `config('hiking.map')`.
- [ ] Ganti hike-mode agar memakainya; `HikeModeTest` yang sudah ada harus tetap hijau
      tanpa diubah, itu buktinya perilakunya tidak bergeser.
- [ ] Pint, suite penuh, commit.

## Tugas 2: Halaman jalur menggambar geometrinya

**Berkas:** `resources/views/livewire/trails/trail-detail.blade.php`,
`tests/Feature/TrailDetailMapTest.php`

Variabel `$geometry` sudah dioper ke view dan tidak pernah dipakai.

- [ ] Test gagal dulu: jalur bergeometri menampilkan peta; jalur tanpa geometri tidak
      menampilkan wadah peta kosong, melainkan menyebut bahwa garis jalurnya belum ada
      dan siapa yang ditunggu (nada §92 dan halaman awaiting yang sudah ada).
- [ ] Pasang `<x-ui.map>` dengan penanda pos.
- [ ] Pint, suite, commit.

## Tugas 3: Foto komunitas dikembalikan kepada pendaki

**Berkas:** `app/Livewire/Trails/TrailDetail.php`,
`resources/views/components/ui/photo-strip.blade.php`,
`tests/Feature/CommunityPhotoTest.php`

Foto hanya tampil di antrean moderasi.

- [ ] Test gagal dulu: foto laporan **yang lolos moderasi** tampil di halaman jalur;
      laporan tertunda dan ditolak tidak; foto jalur lain tidak; setiap foto membawa
      pelapor dan tanggal pendakiannya; blok fotonya ditandai sebagai masukan komunitas,
      bukan keterangan resmi (§92).
- [ ] Muat foto lewat relasi yang sudah ada dengan `visibleToPublic()`, tanpa menambah
      query per foto.
- [ ] Pint, suite, commit.

## Tugas 4: Penalaran Route Fit menjadi batang

**Berkas:** `resources/views/components/ui/factor-bars.blade.php`,
`resources/views/livewire/recommendations/recommendation-results.blade.php`,
`tests/Feature/FactorBarsTest.php`

`matched_factors` sudah tersimpan per baris hasil berisi skor dan bobot tiap faktor.

- [ ] Test gagal dulu: tiap faktor tampil dengan namanya dan kekuatannya; **skor
      internal tidak pernah muncul** dalam teks, atribut, maupun nilai lebar yang dapat
      dibaca balik (BR-09); padanan teksnya ada untuk pembaca layar.
- [ ] Bangun komponennya. Lebar batang memakai kategori kasar, bukan persentase skor.
- [ ] Anggaran 120 kB harus tetap lolos; kalau terlampaui, batangnya dirender hanya pada
      kartu yang penjelasannya sedang dibuka.
- [ ] Pint, suite, commit.

## Tugas 5: Pos sebagai perjalanan, bukan tabel

**Berkas:** `resources/views/livewire/trails/trail-detail.blade.php`,
`tests/Feature/CheckpointJourneyTest.php`

- [ ] Test gagal dulu: pos tampil berurutan dengan jarak dan ketinggian masing-masing,
      dan urutannya mengikuti `sequence`, bukan urutan penyisipan.
- [ ] Bangun tampilannya sebagai garis waktu vertikal.
- [ ] Pint, suite, commit.

---

## Tinjauan mandiri

- Setiap tugas menghasilkan sesuatu yang dapat dilihat pengguna, bukan pekerjaan
  setengah jadi yang menunggu tugas lain.
- Tidak ada tugas yang menambah tabel, kolom, atau konsep domain.
- Ketergantungannya satu arah: Tugas 1 dipakai Tugas 2; sisanya saling bebas.
