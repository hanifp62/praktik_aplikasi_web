# Arsitektur

Peta kode untuk anggota tim baru. Tujuannya satu: Anda tahu file mana yang harus disentuh sebelum membuka editor.

## Alur produk

Seluruh aplikasi melayani satu alur. Kalau ragu di mana sebuah fitur berada, cari posisinya di sini.

```
Profil pendaki  →  Rencana (goal)  →  Route Fit  →  Trip  →  Persiapan
                                                                 ↓
        Riwayat  ←  Laporan kondisi  ←  Hike Mode  ←  Readiness check
```

| Langkah | Komponen Livewire | Route |
|---|---|---|
| Profil | `Onboarding\ProfileSetup` | `/onboarding` |
| Rencana | `Goals\GoalForm` | `/goals/create` |
| Route Fit | `Recommendations\RecommendationResults` | `/recommendations/{run}` |
| Jelajah jalur | `Trails\TrailIndex`, `Trails\TrailDetail`, `Trails\RouteComparison` | `/trails*` |
| Trip | `Trips\TripForm`, `Trips\TripShow`, `Trips\TripIndex` | `/trips*` |
| Persiapan | `Trips\PreparationChecklist` | `/trips/{trip}/preparation` |
| Readiness | `Trips\ReadinessDashboard` | `/trips/{trip}/readiness` |
| Hike Mode | `Trips\HikeMode` | `/trips/{trip}/hike` |
| Laporan | `Reports\ConditionReportForm` | `/reports/create` |
| Riwayat | `History\HikingHistoryPage` | `/history` |

Admin di `/admin/*` (middleware `role:admin`), moderasi di `/moderation` (middleware `role:moderator`).

## Mau ubah apa, sentuh file mana

| Yang ingin diubah | Tempatnya |
|---|---|
| Ambang label COCOK / PERLU PERSIAPAN | `config/hiking.php` → `route_fit` |
| Bobot faktor kompatibilitas | **Bukan di kode.** Tabel `recommendation_rules` lewat halaman admin, supaya kalibrasi terekam di jejak audit tiap run |
| Radius kedatangan checkpoint | `config/hiking.php` → `hike_mode` |
| Ambang kesegaran cuaca / laporan komunitas | `config/hiking.php` → `conditions` |
| Sumber tile peta | `config/hiking.php` → `map`, atau variabel `MAP_*` di `.env` |
| Batas laju submit | `config/hiking.php` → `rate_limits` |
| Warna, radius, gaya tombol | `resources/css/app.css` (token) dan `resources/views/components/ui/` |
| Menambah faktor kompatibilitas baru | `App\Enums\CompatibilityFactor` + method baru di `CompatibilityScorer::score()` |
| Menambah kategori item persiapan | `App\Enums\PreparationCategory` + seeder `PreparationTemplateSeeder` |
| Menambah tag kondisi jalur | `App\Enums\ConditionTag` |
| Mengubah zona waktu sebuah gunung | Kolom `mountains.timezone` |
| Mencatat aturan izin sebuah jalur | `/admin/permits` |
| Menambah halaman admin baru | Daftarkan route-nya, lalu tambahkan ke `resources/views/components/ui/admin-nav.blade.php` |

## Lapisan kode

**`app/Enums`** — kosakata domain. Setiap enum membawa `label()` berbahasa Indonesia dan, bila relevan, method perbandingan seperti `rank()`. Tidak ada string mentah yang berkeliaran di service.

**`app/Models`** — Eloquent. Mass assignment dijaga atribut `#[Fillable]` Laravel 13; jangan mengganti dengan `$guarded`. Trait `HasSpatialColumns` menangani kolom PostGIS dan **selalu no-op pada koneksi non-Postgres**, karena test berjalan di SQLite.

**`app/Services`** — logika domain. Tiap kelas memikul satu tanggung jawab:

| Service | Tanggung jawab |
|---|---|
| `RouteFitService` | Tiga lapis mesin rekomendasi: hard constraint, kompatibilitas, preferensi (PRD §25) |
| `RouteFit\CompatibilityScorer` | Menilai tiap faktor 0..1, dan menandai faktor yang datanya belum ada |
| `RecommendationExplanationService` | Mengubah skor menjadi penjelasan yang dibaca manusia |
| `OfficialStatusService` | Status resmi, kaskade gunung → jalur → segmen, dan cache snapshot publik |
| `WeatherService` | Integrasi BMKG, normalisasi ke UTC, dan kesegaran |
| `ConditionAggregatorService` | Menggabungkan status, cuaca, dan laporan komunitas **tanpa mencampur otoritasnya** |
| `PermitService` | Aturan perizinan pihak lain dan benturan jendela booking |
| `PreparationService` | Membangun checklist dari karakteristik jalur dan aturan izinnya |
| `ReadinessService` | `compute()` menghitung tanpa efek samping, `record()` menyimpan |
| `AuditLogService`, `AnalyticsRecorder` | Jejak audit dan funnel |

**`app/Support`** — utilitas yang tidak memuat kebijakan produk: `Timezone` (konversi WIB/WITA/WIT), `ImageSanitizer` (pembersih EXIF), `PostGis` (kolom geografi).

**`app/Policies`** — otorisasi tingkat objek. Setiap komponen Livewire yang menerima model dari route **wajib** memanggil `$this->authorize()` di `mount()`.

**`app/Livewire`** — presentasi. Komponen boleh memanggil service; komponen tidak boleh memuat aturan domain. Kalau Anda menulis `if` yang berisi kebijakan produk di komponen, tempatnya salah.

## Aturan yang tidak boleh dilanggar

Aturan berikut berasal dari PRD dan ada test yang menjaganya. Melanggarnya membuat suite merah.

1. **Sistem tidak pernah menyatakan sesuatu aman.** Yang boleh dinyatakan adalah fakta bersumber: "Status resmi saat ini OPEN". (§40)
2. **Skor numerik tidak pernah sampai ke klien.** `internal_score` hanya untuk peringkat dan audit. (§28)
3. **Data yang tidak ada dilaporkan sebagai tidak diketahui, bukan diasumsikan baik.** Ini berlaku dua arah: status kosong bukan OPEN, dan karakteristik jalur kosong bukan berarti jalurnya mudah. (§95)
4. **Laporan komunitas tidak pernah mengubah status resmi.** Keduanya ditampilkan berdampingan dengan otoritasnya masing-masing. (§43, §50)
5. **OPEN di level gunung tidak berarti seluruh jalurnya OPEN.** Pembatasan turun ke bawah; kelonggaran tidak. (§42)
6. **Lokasi diminta hanya di Hike Mode**, tidak di halaman lain. (§83)
7. **Makna tidak pernah disampaikan lewat warna saja.** (§87)
8. **Kegagalan sumber eksternal tidak memblokir alur.** BMKG yang tidak dapat dihubungi dilaporkan apa adanya, tetapi tidak menahan pengguna dari langkah inti. (§94)
9. **Waktu disimpan UTC, ditampilkan dalam zona gunungnya** lengkap dengan penanda WIB/WITA/WIT. (§93)

## Yang belum ada

Dicatat terbuka supaya tidak terlupakan:

- **Pemeriksaan aksesibilitas manual.** Test hanya menutup hal yang dapat diperiksa mesin: label, struktur judul, bahasa dokumen. Urutan fokus, kebermaknaan teks alternatif, dan kontras pada seluruh kombinasi masih butuh mata manusia.
- **Suite spasial di mesin pengembang.** Berjalan di CI, tetapi melewati dirinya secara lokal sampai `SPATIAL_TEST_DSN` diisi.
- **Kurasi data lapangan.** Per 20 September 2026: 7 jalur, semuanya tayang, **0 punya geometri**; 34 checkpoint, **0 punya koordinat**; 7 status resmi tercatat, semuanya `UNKNOWN`. Artinya peta tidak dapat menggambar jalur, mode pendakian tidak dapat mengenali checkpoint terdekat, dan ketujuh jalur yang tayang tidak lolos gerbang §110. Koordinat gunung sungguhan tidak boleh dikarang: angka yang salah pada fitur navigasi lapangan lebih berbahaya daripada tidak ada angka. Ini pekerjaan kurator, bukan pekerjaan kode.
- **Pengambilan sumber resmi dan pemeriksaan data basi.** §98 menyebut keduanya sebagai pekerjaan background. Yang ada baru `weather:refresh`. Tidak ada yang memberi tahu admin bahwa status resmi sebuah jalur sudah kedaluwarsa atau lama tidak diverifikasi; status yang kedaluwarsa diam-diam kembali menjadi `UNKNOWN` (aman menurut §95, tetapi senyap).

## Catatan operasional

**PHP di Windows butuh CA bundle.** Tanpa `curl.cainfo` dan `openssl.cafile` di `php.ini`, setiap panggilan HTTPS dari PHP gagal dengan cURL error 60, termasuk pengambilan prakiraan BMKG. `curl.exe` tetap berhasil karena membawa bundle sendiri, sehingga gejalanya mudah salah dibaca sebagai masalah API. Arahkan keduanya ke sebuah `ca-bundle.crt`; Git for Windows sudah menyertakan satu.

**Suite spasial menghapus isi basis data.** Ia memakai RefreshDatabase, jadi `SPATIAL_TEST_DSN` harus menunjuk Postgres lokal. Host yang tampak seperti basis data sungguhan ditolak oleh guard di `tests/Spatial/SpatialTestCase.php`, tetapi guard itu mengenali pola nama, bukan segalanya.

**Prakiraan cuaca perlu dijadwalkan.** `weather:refresh` sudah terdaftar dua kali sehari di `routes/console.php`, tetapi scheduler Laravel hanya berjalan bila `php artisan schedule:work` atau cron memanggilnya. Tanpa itu kolom cuaca akan basi lalu kosong.

## Basis data

PostgreSQL + PostGIS lewat Supabase. Kolom geografi (`geography(TYPE,4326)`) ditambahkan dengan SQL mentah lewat `App\Support\PostGis` karena Laravel tidak punya tipe kolomnya, lengkap dengan index GIST.

**Test berjalan di SQLite in-memory**, tempat kolom geografi tidak ada. Karena itu seluruh kode spasial harus punya jalur aman untuk koneksi non-Postgres, dan pengujiannya berada di suite terpisah (`tests/Spatial`) yang butuh Postgres sungguhan.

## Sumber data eksternal

**BMKG** — `https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={kode}`. Prakiraan 3 hari, interval 3 jam, diperbarui dua kali sehari, batas 60 permintaan per menit per IP. **Atribusi BMKG wajib tampil** di aplikasi. Browser tidak pernah memanggil BMKG langsung; pengambilan dilakukan scheduler (`weather:refresh`).

Prakiraan berbasis wilayah administrasi tingkat IV, sehingga **tidak boleh disebut cuaca puncak** — selalu "prakiraan area sekitar jalur". (§44)
