# Arsitektur

Peta kode untuk anggota tim baru. Tujuannya satu: Anda tahu file mana yang harus disentuh sebelum membuka editor.

## Alur produk

Seluruh aplikasi melayani satu alur. Kalau ragu di mana sebuah fitur berada, cari posisinya di sini.

```
Profil pendaki  →  Rencana (goal)  →  Route Fit  →  Trip  →  Persiapan
                                                                 ↓
        Riwayat  ←  Laporan kondisi  ←  Hike Mode  ←  Readiness check
```

Frontend sedang dimigrasi dari Livewire ke **React 19 + Inertia 3** (keputusan pemilik
24 September 2026, jalur C). Kolom komponen di bawah menyebut yang berlaku hari ini;
halaman yang sudah pindah ditandai **[React]**, ledger lengkapnya di `HANDOVER.md` §0.

| Langkah | Komponen | Route |
|---|---|---|
| Profil | `Onboarding\ProfileSetup` | `/onboarding` |
| Rencana | `Goals\GoalForm` | `/goals/create` |
| Route Fit | `Recommendations\RecommendationResults` | `/recommendations/{run}` |
| Jelajah jalur | **[React]** Jelajah, rincian jalur, penilaian kecocokan, Simpanan, dan Bandingkan di `pages/Trails/`; halaman gunung `pages/Mountains/Show.tsx`; tautan bagikan publik `pages/Public/SharedComparison.tsx` | `/trails*`, `/mountains/{mountain:slug}`, `/b/{token}` |
| Trip | **[React]** indeks `pages/Trips/Index.tsx` (`Trips\IndeksTrip`), rencanakan `pages/Trips/Create.tsx` (`Trips\RencanaTrip`), ruang kerja `pages/Trips/Show.tsx` | `/trips`, `/trips/create`, `/trips/{trip}` |
| Persiapan | **[React]** `pages/Trips/Preparation.tsx` (`Trips\HalamanPersiapan`) | `/trips/{trip}/preparation` |
| Readiness | **[React]** `pages/Trips/Readiness.tsx` (`Trips\HalamanKesiapan`) | `/trips/{trip}/readiness` |
| Hike Mode | `Trips\HikeMode` | `/trips/{trip}/hike` |
| Laporan | `Reports\ConditionReportForm` | `/reports/create` |
| Riwayat | `History\HikingHistoryPage` | `/history` |

Admin di `/admin/*` (middleware `role:admin`), moderasi di `/moderation` (middleware `role:moderator`).

### Daur hidup status trip

Status dan transisi sah sebuah `TripPlan`, persis `App\Enums\TripStatus::allowedTransitions()`. Tidak ada transisi
di diagram ini selain yang diizinkan method itu.

```mermaid
stateDiagram-v2
    [*] --> PLANNED : TripLifecycle::create()
    DRAFT --> PLANNED
    DRAFT --> CANCELLED
    PLANNED --> READY_FOR_DEPARTURE : PreDepartureCheck::confirm()
    PLANNED --> IN_PROGRESS : TripLifecycle::start()
    PLANNED --> CANCELLED : TripLifecycle::cancel()
    READY_FOR_DEPARTURE --> IN_PROGRESS : TripLifecycle::start()
    READY_FOR_DEPARTURE --> PLANNED
    READY_FOR_DEPARTURE --> CANCELLED : TripLifecycle::cancel()
    IN_PROGRESS --> COMPLETED : TripLifecycle::complete()
    IN_PROGRESS --> CANCELLED : TripLifecycle::cancel()
    COMPLETED --> [*]
    CANCELLED --> [*]

    note right of DRAFT
        Didefinisikan di enum; tidak ada jalur aplikasi
        yang membuat trip di status ini sekarang.
        TripLifecycle::create() langsung menulis PLANNED.
    end note
    note right of PLANNED
        Langkah ruang kerja Jalur dan Rencana sudah
        selesai di sini. Persiapan dan Cek memandu
        sampai status ini dan READY_FOR_DEPARTURE.
    end note
    note right of READY_FOR_DEPARTURE
        Kembali ke PLANNED sah menurut enum, tetapi
        tidak ada jalur aplikasi yang memicunya sekarang.
    end note
    note right of IN_PROGRESS
        Keempat langkah ruang kerja (Jalur, Rencana,
        Persiapan, Cek) berhenti menunjuk langkah
        berikutnya di sini.
    end note
```

Bukti: `app/Enums/TripStatus.php` (status, `canTransitionTo()`, dan `belumDimulai()`); `app/Services/Trips/TripLifecycle.php`
(`create()`, `start()`, `complete()`, `cancel()`); `app/Services/Trips/PreDepartureCheck.php` (transisi PLANNED ke
READY_FOR_DEPARTURE lewat `confirm()`, di dalam transaksi yang mengunci baris trip; jalur balik READY_FOR_DEPARTURE ke
PLANNED tidak tercapai lewat aplikasi mana pun saat ini); `app/Services/Trips/TripSteps.php` (`for()`, penanda langkah
berikutnya berhenti begitu rencana tidak lagi `masihDirencanakan()`, yaitu sudah dimulai atau tanggalnya lewat).

## Mau ubah apa, sentuh file mana

| Yang ingin diubah | Tempatnya |
|---|---|
| Ambang label COCOK / PERLU PERSIAPAN | `config/hiking.php` → `route_fit` |
| Bobot faktor kompatibilitas | **Bukan di kode.** Tabel `recommendation_rules` lewat halaman admin, supaya kalibrasi terekam di jejak audit tiap run |
| Radius kedatangan checkpoint | `config/hiking.php` → `hike_mode` |
| Paket lapangan luring dan alat keselamatan (sakelar, ambang turun status Buka) | `config/hiking.php` → `field_pack` (`enabled`, `open_status_stale_after_hours`) dan `field_safety.enabled`, atau `FIELD_PACK_ENABLED` dan `FIELD_SAFETY_ENABLED`; keduanya mati sampai pemilik memutus Q10 dan Q14. Rutenya dijaga middleware `fitur` (`EnsureFeatureEnabled`) dan menjawab 404 selama mati |
| Hitungan mode lapangan (pembulatan jarak, naik lagi, tiba di pos) | `resources/js/components/mendaki/hitung.ts`, modul tanpa impor yang diuji dari PHPUnit lewat Node (`FieldModeFiguresTest`) |
| Ambang kesegaran cuaca / laporan komunitas | `config/hiking.php` → `conditions` |
| Lama ajakan melapor di Beranda dan batas kandidat "Jalur lain untuk Anda" | `config/hiking.php` → `beranda` (`hari_ajakan_lapor`, `batas_kandidat`) |
| Sumber tile peta dan atribusinya | `config/hiking.php` → `map`, atau variabel `MAP_*` di `.env`; atribusi bawaan OpenTopoMap termasuk SRTM (`MapConfigTest`) |
| Umur, jumlah, dan batas laju tautan bagikan perbandingan | `config/hiking.php` → `share_links` (`ttl_days`, `max_active_per_user`, `views_per_minute` per alamat IP); membuat dan mencabut memakai `rate_limits.personal_writes_per_minute` |
| Jumlah kandidat yang dinilai Jelajah per kunjungan | `config/hiking.php` → `jelajah.batas_kandidat`; halaman menyebut bila hasilnya terpotong |
| Label kecocokan, status akses, kesegaran, dan label sumber di layar | `resources/js/components/trails/` (`LabelKecocokan`, `StatusAkses`, `Kesegaran`, `LabelSumber`); ringkasan status bersumber dari `app/Services/Trails/StatusJalurRingkas.php` |
| Batas laju submit | `config/hiking.php` → `rate_limits` |
| Warna, radius, huruf, gaya tombol | Token di `resources/css/app.css`, pemetaan kelas di blok `@theme` berkas yang sama, primitif React di `resources/js/components/ui/`, komponen Blade halaman publik di `resources/views/components/ui/` |
| Tab navigasi, kerangka ponsel dan layar lebar, strip pendakian, lembar Pengingat | `resources/js/layouts/` (daftar tab `NavLinks.tsx`, kerangka `AppLayout.tsx`); kepala setiap halaman lewat `PageHeader` dengan `akar` atau `induk` |
| Props bersama kerangka (hitungan Simpanan, pendakian aktif, daftar pengingat) | `app/Http/Middleware/HandleInertiaRequests.php`: `jumlahSimpanan` dan `pendakianAktif` masing-masing satu kueri terindeks; `daftarPengingat` (`Inertia::optional`) lima kueri tetap, hanya dimuat lewat muat ulang parsial saat lembar Pengingat dibuka. Nama prop baru tidak boleh sama dengan nama prop halaman |
| Menambah faktor kompatibilitas baru | `App\Enums\CompatibilityFactor` + method baru di `CompatibilityScorer::score()` |
| Menambah kategori item persiapan | `App\Enums\PreparationCategory` + seeder `PreparationTemplateSeeder` |
| Menambah tag kondisi jalur | `App\Enums\ConditionTag` |
| Mengubah zona waktu sebuah gunung | Kolom `mountains.timezone` |
| Mencatat aturan izin sebuah jalur | `/admin/permits` |
| Menambah halaman admin baru | Daftarkan route-nya, lalu tambahkan ke `resources/views/components/ui/admin-nav.blade.php` |

## Lapisan kode

**`app/Enums`**: kosakata domain. Setiap enum membawa `label()` berbahasa Indonesia dan, bila relevan, method perbandingan seperti `rank()`. Tidak ada string mentah yang berkeliaran di service.

**`app/Models`**: Eloquent. Mass assignment dijaga atribut `#[Fillable]` Laravel 13; jangan mengganti dengan `$guarded`. Trait `HasSpatialColumns` menangani kolom PostGIS dan **selalu no-op pada koneksi non-Postgres**, karena test berjalan di SQLite.

**`app/Services`**: logika domain. Tiap kelas memikul satu tanggung jawab:

| Service | Tanggung jawab |
|---|---|
| `RouteFitService` | Tiga lapis mesin rekomendasi: hard constraint, kompatibilitas, preferensi (PRD §25) |
| `RouteFit\CompatibilityScorer` | Menilai tiap faktor 0..1, dan menandai faktor yang datanya belum ada |
| `RecommendationExplanationService` | Mengubah skor menjadi penjelasan yang dibaca manusia |
| `OfficialStatusService` | Status resmi, kaskade gunung → jalur → segmen, dan cache snapshot publik |
| `WeatherService` | Integrasi BMKG, normalisasi ke UTC, dan kesegaran |
| `ConditionAggregatorService` | Menggabungkan status, cuaca, dan laporan komunitas **tanpa mencampur otoritasnya** |
| `PermitService` | Aturan perizinan pihak lain, keadaan jendela booking (`bookingWindowFor()`, satu-satunya aritmetika jendela), dan satu penentu ajakan pemesanan per keadaan (`pemesanan()`, atau `pemesananDariJendela()` atas jendela yang sudah dihitung) untuk Rencanakan, Persiapan, dan Cek |
| `PreparationService` | Membangun checklist dari karakteristik jalur dan aturan izinnya; satu predikat butir yang menunggu BMKG (`menungguBmkg()`) untuk Persiapan, Cek, dan langkah trip |
| `ReadinessService` | `compute()` menghitung tanpa efek samping, `record()` menyimpan |
| `Trips\IndeksTrip`, `Trips\RencanaTrip`, `Trips\HalamanPersiapan`, `Trips\HalamanKesiapan` | Kelas halaman klaster Trip (W4): indeks berkelompok, rencanakan dengan muat ulang parsial kecocokan untuk rencana, persiapan dengan butir kritis dan butir yang menunggu BMKG, dan cek kesiapan dengan "Sejak cek terakhir"; kueri tetap, tanpa skor di props |
| `Trips\KepalaTrip`, `Trips\TripSteps` | Kepala satu trip dan empat langkahnya (Jalur, Rencana, Persiapan, Cek) beserta aksi berikutnya untuk indeks. Predikat yang dipakai lebih dari satu halaman tinggal di model: `TripPlan::tujuanPenilaian()` (jenis dan tanggal trip untuk kecocokan), `TripPlan::tinjauanTercatat()` (relasi `latestConfirmedReadinessCheck`), `TripPlan::tanggalRencanaLewat()`, `Trail::terbit()` |
| `Trips\PreDepartureCheck` | Tinjauan pra-berangkat: satu sumber alasan penolakan untuk halaman dan POST, dan pencatatan dalam transaksi yang mengunci baris trip |
| `Dashboard\RingkasanDasbor`, `Dashboard\TenggatTrip`, `Dashboard\KabarBeranda`, `Dashboard\JalurUntukAnda`, `Dashboard\TanggaTanjakan` | Beranda berfase (W5): `RingkasanDasbor` memilih fase B0 sampai B4 sekali per permintaan (`KonteksBeranda`) dan menyusun kartu fase dengan satu aksi utama; kelas bagian lain membaca konteks yang sama. Setiap bagian prop malas, jadi muat ulang parsial lembar Pengingat tidak menghitung Beranda |
| `Trips\TenggatIzin` | Judul, tanggal, dan kalimat jendela pemesanan izin satu trip, dipakai lembar Pengingat dan Tenggat terdekat Beranda |
| `AuditLogService`, `AnalyticsRecorder` | Jejak audit dan funnel |
| `Trails\HalamanJelajah`, `Trails\TrailDetailPage`, `Trails\PenilaianKecocokan`, `Trails\RouteComparisonTable`, `Mountains\HalamanGunung` | Kelas halaman (spec v3 bagian 9.2): satu kelas merakit props satu halaman React dari service domain, dengan anggaran kueri yang tidak tumbuh bersama jumlah data |
| `Trails\TautanBagikan` | Tautan bagikan perbandingan (FR-23): token acak 192 bit yang hanya disimpan hash SHA-256-nya, masa berlaku, batas tautan aktif, dan cabut oleh pemiliknya |
| `Trails\ProfilElevasiJalur`, `Trails\PosSepanjangJalur` | Profil elevasi dengan ruas tercuram, dan km pos di sepanjang garis rute (PostGIS); tanpa geometri tidak ada km yang ditebak |

**`app/Support`**: utilitas yang tidak memuat kebijakan produk: `Timezone` (konversi WIB/WITA/WIT), `ImageSanitizer` (pembersih EXIF), `PostGis` (kolom geografi).

**`app/Policies`**: otorisasi tingkat objek. Setiap komponen Livewire yang menerima model dari route **wajib** memanggil `$this->authorize()` di `mount()`.

**`app/Livewire`**: presentasi lama, sedang dimigrasi. Komponen boleh memanggil service; komponen tidak boleh memuat aturan domain. Kalau Anda menulis `if` yang berisi kebijakan produk di komponen, tempatnya salah.

**`app/Http/Controllers` + `resources/js/pages`**: presentasi baru (React lewat Inertia). Kontraknya:

- **Server memegang data dan otorisasi, React memegang interaksi.** Controller membaca query lewat service (`Services\Trails\TrailSearch`), memformat lewat resource (`Http\Resources\TrailRowResource`: angka sudah "8,0 km" saat sampai di klien), dan merender `Inertia::render('Nama/Halaman', $props)`. Aksi yang mengubah data adalah rute POST biasa dengan Policy/scope yang sama seperti dulu; klien tidak pernah dipercaya untuk keputusan akses.
- **Props bersama** (`Http\Middleware\HandleInertiaRequests`): `auth.user` secukupnya untuk navigasi (peran hanya menentukan apa yang DITAMPILKAN), `merek`, `demo` (lencana Data contoh), `jumlahSimpanan` dan `pendakianAktif` (kerangka; null untuk tamu dan di mode lapangan), `daftarPengingat` (opsional, hanya lewat muat ulang parsial lembar Pengingat), `tema`, `tampilan` (efek dan gerak), `peta`, `adminNav` (hanya admin), dan `flash` (termasuk `simpananTerhapus` untuk Urungkan dan `tautanBagikan` yang membawa token sekali saja), ditambah `errors` dari middleware dasar Inertia. Biaya kueri props kerangka ada di baris "Props bersama kerangka" pada tabel di atas. Bentuknya dikunci di `resources/js/types/index.d.ts`.
- **Judul dan meta dirender server**: setiap halaman mengirim prop `title` (dan `description` bila publik) yang dibaca `resources/views/inertia.blade.php`, supaya tab, riwayat, dan mesin pencari benar sebelum React berjalan. SSR belum diaktifkan (`config/inertia.php`), keputusan terpisah.
- **Rute bertipe** dari Wayfinder (`resources/js/routes`, `resources/js/actions`); jalankan `php artisan wayfinder:generate` setiap rute berubah. Jangan menulis URL sebagai string di komponen.
- **Skor internal tidak pernah sampai ke props** (BR-09), sama seperti ke HTML.
- **Dua pintu masuk Vite** selama migrasi: `app.js` (layout Livewire) dan `app.tsx` (Inertia), berbagi `dasar.js` (service worker, tema, pemuat peta). Token warna tetap satu-satunya sumber; komponen React memakai kelas Tailwind yang sama, tanpa CSS-in-JS dan tanpa `dark:`.
- **Test**: halaman React diuji lewat HTTP dan `assertInertia` (props), aksi lewat POST; markup yang dirender klien dijaga dari sumbernya (`InertiaLayoutTest`) dan dipotret di peramban.

## Aturan yang tidak boleh dilanggar

Sembilan aturan pertama berasal dari PRD dan ada test yang menjaganya: melanggarnya membuat suite merah. Aturan kesepuluh adalah aturan tata kelola yang tidak dapat dijaga suite, dan ditandai demikian.

1. **Sistem tidak pernah menyatakan sesuatu aman.** Yang boleh dinyatakan adalah fakta bersumber: "Status resmi saat ini OPEN". (§40)
2. **Skor numerik tidak pernah sampai ke klien.** `internal_score` hanya untuk peringkat dan audit. (§28)
3. **Data yang tidak ada dilaporkan sebagai tidak diketahui, bukan diasumsikan baik.** Ini berlaku dua arah: status kosong bukan OPEN, dan karakteristik jalur kosong bukan berarti jalurnya mudah. (§95)
4. **Laporan komunitas tidak pernah mengubah status resmi.** Keduanya ditampilkan berdampingan dengan otoritasnya masing-masing. (§43, §50)
5. **OPEN di level gunung tidak berarti seluruh jalurnya OPEN.** Pembatasan turun ke bawah; kelonggaran tidak. (§42)
6. **Lokasi diminta hanya di Hike Mode**, tidak di halaman lain, dan posisi langsung tidak dikirim ke server: mode lapangan menghitungnya di perangkat; selama pendakian server hanya menerima urutan pos yang dicapai dan waktu jeda (GR-07). Satu-satunya titik lokasi yang sampai ke server adalah jejak yang direkam atas pilihan pendaki (mati bawaan), dikirim sekali sesudah pendakian berakhir dan dapat dihapus pemiliknya (§53, W6-P2). (§83)
7. **Makna tidak pernah disampaikan lewat warna saja.** (§87)
8. **Kegagalan sumber eksternal tidak memblokir alur.** BMKG yang tidak dapat dihubungi dilaporkan apa adanya, tetapi tidak menahan pengguna dari langkah inti. (§94)
9. **Waktu disimpan UTC, ditampilkan dalam zona gunungnya** lengkap dengan penanda WIB/WITA/WIT. (§93)
10. **Arsitekturnya adalah monolit modular, dan itu pilihan sadar.** Dua belas domain produk adalah batas modul, bukan batas layanan. Memecahnya menjadi layanan terpisah membutuhkan bukti kebutuhan yang terukur, bukan preferensi, dan membutuhkan persetujuan pemilik produk (R-021). *Aturan tata kelola, bukan aturan berpenjaga-test.*

## Struktur repo dan panduan luar

Keputusan DK-51 (spec v3 bagian 17): repo tetap monolit modular Laravel. Dua panduan
struktur yang dibandingkan pemilik (monorepo dengan `apps/` dan `packages/`, serta empat repo
dengan layanan mikro, Kubernetes, Terraform, dan Ansible) diambil prinsipnya lewat pemetaan
di bawah, bukan dengan memindahkan `app/`. Memindahkan susunan memutus autoload Composer,
Artisan, Laravel Boost, konvensi Inertia, dan seluruh suite test.

| Unsur panduan | Padanan di Memount |
|---|---|
| `apps/backend/src` (config, controllers, middlewares, models, routes, services) | `config/`, `app/Http/Controllers`, `app/Http/Middleware`, `app/Models`, `routes/`, `app/Services`, `app/Policies` |
| `apps/frontend/src` (components, core, features, views) | Komponen fitur di `resources/js/components`, inti di `resources/js/lib`, halaman di `resources/js/pages`; primitif desain di `resources/js/components/ui/`, komponen domain jalur di `resources/js/components/trails/` (W3) |
| `packages/api-client` | Rute dan aksi bertipe Wayfinder, router Inertia |
| `packages/ui-design-system` | Token dan pemetaan `@theme` di `resources/css/app.css`, primitif `resources/js/components/ui/` (W1), dan pameran primitif `/dev/primitif` yang hanya terdaftar di `APP_ENV=local` (`routes/dev.php`) |
| `packages/config` (ESLint, Prettier, TypeScript) | `tsconfig.json` dan Pint; ESLint dan Prettier menunggu izin (Q17) |
| `database/` dengan migrasi, seeder, dan README | `database/migrations`, `database/seeders`, `database/factories`, `database/README.md`; dump SQL diabaikan git |
| `tests/e2e` | Belum ada; Playwright menunggu izin (Q17) |
| `.github/workflows` (test dan deploy) | `test.yml`, `security.yml`, `deploy.yml` manual (DK-54) |
| `docker-compose.yml` | PostgreSQL + PostGIS untuk suite Spatial (DK-55) |
| `.gitignore` sebagai benteng | `.gitignore`, `SecretsNotInRepoTest`, `RepoHygieneTest` |
| README panduan pasang | `README.md`, `CONTRIBUTING.md`, `SECURITY.md`, `docs/README.md` |
| `Dockerfile`, layanan mikro, Kubernetes, Terraform, Ansible | Tidak diambil (aturan 10 di atas) |

**Kriteria bukti kebutuhan** sebelum usulan memecah layanan atau menambah infrastruktur
diajukan ke pemilik (R-021). Setiap butir diukur, bukan diperkirakan:

1. Satu domain membutuhkan skala atau sumber daya yang berbeda jauh dari sisanya, terlihat di metrik produksi (latensi p95, CPU, memori) setelah cache, antrean, dan indeks sudah dicoba.
2. Kegagalan satu domain terbukti menjatuhkan domain lain meski batas waktu, antrean, dan penanganan kegagalan sudah ada.
3. Ada tim terpisah yang butuh jadwal rilis sendiri untuk domain itu.
4. Ada kebutuhan runtime atau kepatuhan (misalnya residensi data) yang tidak dapat dipenuhi di dalam monolit.
5. Untuk Kubernetes, Terraform, atau Ansible: ada lebih dari satu lingkungan yang harus disediakan ulang secara identik, dan platform terkelola (Vercel, Supabase) tidak lagi mencukupi.

Sampai salah satunya terbukti, dua belas domain produk tetap batas modul di dalam `app/`.

## Kontrak SEO teknis

Ini kontrak engineering, bukan strategi pemasaran. Strategi pertumbuhan ada di `PRODUCT.md`; struktur URL publik ada di `docs/PRD.md` §145.2.

- **Metadata, canonical, sitemap, dan structured data hanya boleh merepresentasikan konten yang benar-benar tersedia dan terlihat di halaman.** Structured data yang mendeskripsikan sesuatu yang tidak dirender adalah klaim palsu kepada mesin pencari, dan sifatnya sama dengan mengarang data kepada pengguna.
- **Tidak boleh ada SEO claim yang tidak sesuai dengan konten.** Judul, deskripsi, dan markup mengikuti apa yang halaman itu benar-benar tawarkan.
- Halaman jalur yang datanya belum lengkap tidak dipromosikan sebagai lengkap. Gerbang publikasi §110 dan larangan mengarang data berlaku sama di lapisan metadata.
- Larangan konten tipis hasil AI dalam skala besar ada di `docs/PRD.md` BR-16.
- Halaman tautan bagikan `/b/{token}` tidak diindeks (meta `robots` dan header `X-Robots-Tag: noindex`), tidak masuk sitemap, dan tidak memuat catatan pribadi maupun label kecocokan (`ComparisonShareTest`).

## Yang belum ada

Dicatat terbuka supaya tidak terlupakan:

- **Pemeriksaan aksesibilitas manual.** Test hanya menutup hal yang dapat diperiksa mesin: label, struktur judul, bahasa dokumen. Urutan fokus, kebermaknaan teks alternatif, dan kontras pada seluruh kombinasi masih butuh mata manusia.
- **Suite spasial belum pernah hijau.** Dua run CI publik yang ada gagal; run terakhir (21 September 2026, commit `6d9e090`) gagal di langkah Spatial. Sampai 26 September 2026 `SpatialTestCase` galat sebelum terhubung ke basis data apa pun; setelah perbaikannya, hasil pertama di PostGIS menunggu push yang diizinkan. Secara lokal suite ini dilewati sampai `SPATIAL_TEST_DSN` diisi; `docker-compose.yml` menyediakan PostGIS yang sama dengan CI (DK-55), tetapi Docker tidak wajib.
- **Kurasi data lapangan.** Per 20 September 2026: 7 jalur, semuanya tayang, **0 punya geometri**; 34 checkpoint, **0 punya koordinat**; 7 status resmi tercatat, semuanya `UNKNOWN`. Artinya peta tidak dapat menggambar jalur, mode pendakian tidak dapat mengenali checkpoint terdekat, dan ketujuh jalur yang tayang tidak lolos gerbang §110. Koordinat gunung sungguhan tidak boleh dikarang: angka yang salah pada fitur navigasi lapangan lebih berbahaya daripada tidak ada angka. Ini pekerjaan kurator, bukan pekerjaan kode.
- **Pengambilan sumber resmi otomatis.** §98 menyebutnya pekerjaan background. Belum ada, dan memang belum ada API resmi yang dapat dipanggil; status tetap dicatat manual oleh kurator. Pemeriksaan data basi sendiri sudah ada (`data:freshness-check` dan panel "Perlu ditinjau"), sehingga status yang kedaluwarsa tidak lagi mundur ke `UNKNOWN` tanpa suara.

## Jalur kegagalan

Halaman galat ada di `resources/views/errors/`, berbahasa Indonesia, dan **tidak menyentuh basis data, sesi, maupun Livewire**: ia dirender justru ketika sesuatu sedang rusak. Font dari luar juga tidak dimuat, karena kalau jaringannya yang bermasalah, menunggunya hanya menunda pengguna membaca apa yang terjadi. Halaman 500 dan 503 mengarahkan pendaki yang sedang bersiap berangkat untuk memastikan status jalur langsung ke pengelola, bukan menunggu aplikasi pulih.

**Menghapus jalur akan ikut menghapus laporan komunitasnya.** Seluruh foreign key ke `trails` memakai `CASCADE`. Saat ini tidak ada cara menghapus jalur dari antarmuka, hanya mengarsipkan, jadi risikonya belum terjangkau. Kalau suatu saat fitur hapus jalur ditambahkan, intel lapangan ikut hilang, dan itu bertentangan dengan keputusan pada penghapusan akun yang justru mempertahankan laporan tanpa nama pemiliknya.

Keputusan penghapusan akun yang dimaksud ada di `docs/KEBIJAKAN-DATA-PRIBADI.md`, dan itulah canonical policy-nya. Berkas arsitektur ini hanya menunjuk ke sana; ia tidak memutuskan apa pun soal data pribadi.

## Catatan operasional

**PHP di Windows butuh CA bundle.** Tanpa `curl.cainfo` dan `openssl.cafile` di `php.ini`, setiap panggilan HTTPS dari PHP gagal dengan cURL error 60, termasuk pengambilan prakiraan BMKG. `curl.exe` tetap berhasil karena membawa bundle sendiri, sehingga gejalanya mudah salah dibaca sebagai masalah API. Arahkan keduanya ke sebuah `ca-bundle.crt`; Git for Windows sudah menyertakan satu.

**RLS menyala tanpa satu pun policy, dan itu memang disengaja.** Supabase memberi peran `anon` dan `authenticated` hak penuh atas tabel di schema `public` lalu mengeksposnya lewat REST API dengan kunci yang memang dirancang untuk dipublikasikan. Aplikasi ini tidak pernah memakai PostgREST; ia terhubung langsung sebagai `postgres`, pemilik tabelnya, yang melewati RLS. Jadi RLS tanpa policy berarti "tolak semua" bagi REST API sekaligus "tidak berpengaruh" bagi Laravel. Advisor Supabase akan menandainya sebagai `rls_enabled_no_policy` level INFO; itu keadaan yang benar di sini, bukan pekerjaan yang belum selesai. Jangan menambahkan policy untuk meredakan advisor. Migrasinya juga mencabut GRANT, karena **TRUNCATE tidak tunduk pada RLS** dan tanpa pencabutan itu `audit_logs` masih dapat dikosongkan.

**Service worker menyimpan shell, tidak pernah isi** (spec v3 bagian 10.4, `resources/views/sw.blade.php`). Pemasangan hanya menyimpan shell kecil (`/offline`, dua ikon, `app.css`); shell paket (graf shell, MapLibre, dan `jejak.js`) disimpan saat paket pertama diunduh, lewat pesan `simpan-shell-paket` dengan penanda `/__memount-shell-paket` (W6-9g, W6-9o). Status "Siap luring" hanya ditulis sesudah pemeriksaan shell lulus, dan pemeriksaan itu diulang setiap pergantian controller sesudah pekerja baru aktif (W6-9n, W6-10e, W6-10g). Dokumen selalu dari jaringan dan jatuh ke `/offline`, dengan kalimat "Halaman ini belum dapat dimuat" (W6-9j) atau, lewat tautan "Buka paket luring", `/offline?dibuka=paket` (W6-9k); hanya `/build/`, `/icons/`, dan `/fonts/` disimpan di luar shell. Versi baru hanya membuang cache `shell-*` lama dan tidak pernah menyentuh `pack-*` (`ProgressiveWebAppTest::test_activate_never_deletes_pack_caches`). Paket lapangan diunduh dan diverifikasi oleh halaman (`resources/js/luring/paket.ts`) ke IndexedDB per pengguna `memount-luring-<kunci>`; shell luring (`resources/js/luring/shell.tsx`) membacanya tanpa jaringan.

**Pustaka peta dimuat sesuai kebutuhan.** `resources/js/dasar.js`, yang dipakai kedua pintu masuk Vite, hanya memuat sebuah fungsi `window.muatPeta()`; MapLibre 6 (modul sekitar 1,1 MB dan pekerja 0,5 MB, sekitar 434 kB gzip) baru diunduh ketika halaman benar-benar menggambar peta. Menambahkan `import 'maplibre-gl'` kembali ke pintu masuk akan mengembalikan beban itu ke seluruh halaman.

**Jejak pendakian dikirim sekali, sesudah turun** (W6-P2). Perekam (`resources/js/jejak.js`) hanya menyimpan titik di perangkat selama pendakian aktif, tanpa kiriman apa pun. Satu pengirim terpasang di tata letak aplikasi (`resources/js/luring/kirimJejak.ts`, dipasang di `AppLayout.tsx`) mengirim titik sekali sesudah pendakian selesai atau dibatalkan, saat ada sinyal, dan hanya sesudah server menjawab lewat `GET hike/{session}/track` bahwa sesi itu menerima jejak. Predikat `App\Services\Trips\PenerimaanJejak` dipakai pertanyaan dan unggahan: trip COMPLETED atau CANCELLED, tanpa pendakian lain berlangsung, dan perekaman jejak menyala. Mode lapangan dan shell luring tidak pernah mengirim. Unggahan tidak menyimpan titik yang terekam lebih dari `PenerimaanJejak::KELONGGARAN_JAM_PERANGKAT_MENIT` (10) menit sesudah `ended_at` sesi, dan selesai maupun batal sama-sama menutup sesi lewat `TripLifecycle::tutupSesi()` (W6-FH, W6-FT), sehingga titik yang direkam shell luring sesudah pendakian berakhir tidak terlampir.

**Data perangkat berkunci pengguna, dan keluar membersihkannya.** Basis data paket dan jejak di perangkat dinamai dengan kunci HMAC id pengguna (`App\Support\KunciPerangkat`, rahasia `hiking.device_key_secret`/`DEVICE_KEY_SECRET` dengan cadangan `app.key`). Keluar, ganti akun di perangkat yang sama, dan hapus akun menghapus paket, jejak, antrean, cache paket, dan OPFS milik akun itu; respons keluar dan hapus akun mengirim `Clear-Site-Data: "storage"` sebagai lapis kedua. Bila tab Memount lain masih memegang basis data, penghapusannya sudah diminta sebelum pendaki ditanya dan tidak dapat dibatalkan: datanya terhapus begitu tab itu menutup koneksinya. Pertanyaan sesudahnya (`KALIMAT_TERTUNDA` di `resources/js/luring/keluarMurni.ts`) hanya memilih antara keluar (OK) dan tetap masuk (Batal); Batal mempertahankan sesi, bukan datanya. Penjaga ganti akun tidak berjalan atas halaman dari riwayat (Tugas 11).

**Jeda dan lanjut menolak putar ulang.** `hiking_sessions.last_resumed_at` membuat permintaan jeda atau lanjut yang terkirim dua kali (balapan tab, koneksi terputus lalu mengulang) diabaikan begitu sudah tercatat, dengan batas jam perangkat (W6-10f, W6-10h).

**Suite spasial menghapus isi basis data.** Ia memakai RefreshDatabase, jadi `SPATIAL_TEST_DSN` harus menunjuk Postgres lokal. Host yang tampak seperti basis data sungguhan ditolak oleh guard di `tests/Spatial/SpatialTestCase.php`, tetapi guard itu mengenali pola nama, bukan segalanya.

**Pembersihan snapshot kesiapan lama.** `php artisan memount:bersihkan-snapshot-kondisi` (`app/Console/Commands/BersihkanSnapshotKondisi.php`) memangkas laporan komunitas di `readiness_checks.condition_snapshot` lama ke kunci minimal tanpa data pribadi pelapor. Idempoten, berpotongan, `--dry-run` hanya menghitung, dan `updated_at` tidak bergeser. Menjalankannya di Supabase menunggu izin pemilik.

**Prakiraan cuaca perlu dijadwalkan.** `weather:refresh` sudah terdaftar dua kali sehari di `routes/console.php`, tetapi scheduler Laravel hanya berjalan bila `php artisan schedule:work` atau cron memanggilnya. Tanpa itu kolom cuaca akan basi lalu kosong.

**Di Vercel tidak ada proses latar (DK-12).** Image `Dockerfile` berjalan sebagai *container-backed Vercel Function* yang tidur sesudah lima menit tanpa trafik, jadi `docker/start.sh` tidak menjalankan `schedule:work` maupun migrasi. Skrip itu juga memaksa `APP_ENV=production` dan `APP_DEBUG=false` sebelum `config:cache`, apa pun isi variabel Vercel, dan memberi bawaan `TRUSTED_PROXIES=*` serta `LOG_CHANNEL=stderr`; job CI `image` membuktikannya dengan menjalankan image memakai variabel keliru. Vercel Cron memanggil `GET /api/cron/{tugas}` (`App\Http\Controllers\CronController`) dengan header `Authorization: Bearer <CRON_SECRET>`; yang dapat dipicu hanya tugas di `SchedulerHealthService::TUGAS`, dan setiap jalan dicatat di `scheduled_task_runs` seperti jalan dari scheduler. Jadwalnya di `vercel.json`, dijaga `VercelConfigTest` agar jamnya, setelah diubah ke UTC, mengikuti `routes/console.php`. Paket Hobby hanya mengizinkan cron harian, jadi pengingat dipicu pada jam jatuh temponya (22.00, 23.00, dan 00.00 UTC) ditambah susulan 02.00 UTC yang masih jatuh di hari yang sama di ketiga zona, dan `PENJADWAL_HANYA_HARIAN=true` menyesuaikan harapan halaman kesehatan. Vercel men-deploy setiap push ke `main` tanpa menunggu CI, jadi `main` hanya menerima commit yang sudah hijau di cabang kerja, dan migrasi Supabase (workflow `migrate-supabase.yml`) diterapkan sebelum commit yang membutuhkannya dibawa ke `main`. Urutan itu aman selama migrasinya aditif; migrasi yang menghapus, mengganti nama, atau mengubah kolom butuh rencana dua tahap.

## Basis data

PostgreSQL + PostGIS lewat Supabase. Kolom geografi (`geography(TYPE,4326)`) ditambahkan dengan SQL mentah lewat `App\Support\PostGis` karena Laravel tidak punya tipe kolomnya, lengkap dengan index GIST.

**Test berjalan di SQLite in-memory**, tempat kolom geografi tidak ada. Karena itu seluruh kode spasial harus punya jalur aman untuk koneksi non-Postgres, dan pengujiannya berada di suite terpisah (`tests/Spatial`) yang butuh Postgres sungguhan.

## Sumber data eksternal

**BMKG**: `https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={kode}`. Prakiraan 3 hari, interval 3 jam, diperbarui dua kali sehari, batas 60 permintaan per menit per IP. **Atribusi BMKG wajib tampil** di aplikasi. Browser tidak pernah memanggil BMKG langsung; pengambilan dilakukan scheduler (`weather:refresh`).

Prakiraan berbasis wilayah administrasi tingkat IV, sehingga **tidak boleh disebut cuaca puncak**: selalu "prakiraan area sekitar jalur". (§44)

## Kredensial

Kata sandi basis data dan kunci Supabase hanya tinggal di `.env`, yang di-gitignore dan tidak terlacak. Nilainya tidak pernah ditulis ke script, test, dokumentasi, maupun pesan commit: sekali tertempel, ia ikut riwayat git selamanya meski barisnya dihapus. `SecretsNotInRepoTest` menegakkan ini, memindai seluruh berkas terlacak untuk pola kredensial sungguhan, bukan sekadar nama variabelnya.

Rotasi kunci publishable dan kata sandi basis data hanya dapat dilakukan dari dashboard Supabase. Keduanya tidak boleh diganti lewat SQL: `ALTER USER postgres` memutus layanan internal Supabase yang memakai peran itu. Per 20 September 2026 rotasi belum dilakukan; paparan PostgREST-nya sendiri sudah ditutup lewat migrasi.
