# 02 Detail rute

**FR utama:** FR-07 Detail jalur (§34), FR-11 Status resmi (§41), FR-12 Cuaca (§44).
**Bahan:** features/02-detail-rute.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.4.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Cocokkah jalur ini untuk saya, dan apa yang akan saya hadapi di sana?"

Satu halaman menyatukan ringkasan, peta, pos, profil elevasi, kondisi terkini yang bersumber, laporan pendaki, izin, dan alasan kecocokan, dengan urutan pengungkapan §89. Setiap informasi yang belum ada ditulis jujur, tidak dikarang.

## Posisi di loop dan permukaan

- **Loop (§7):** evaluasi rute sebelum memutuskan dan merencanakan.
- **Tab:** Jelajah. Rute `/trails/{slug}`; jalur belum terbit merender halaman menunggu data (`Trails/Awaiting`). Aksi: simpan, banding, rencanakan trip.
- **Halaman publik:** `/pendakian/{slug}` (Blade, tanpa status resmi dan cuaca, §155); target struktur `/gunung/{slug}/via/{trail}` (§145, diskrepansi D-B).
- **Community:** laporan pendaki termoderasi dengan terima kasih; ikuti gunung untuk kabar di Beranda dan `/kabar`.

## Kriteria penerimaan

1. **Urutan.** Given jalur terbit, When pendaki membuka detailnya, Then tampil empat angka kunci sekali jalan naik, tantangan, kecocokan, profil elevasi, pos, kondisi, laporan, izin, dan sumber, dalam urutan §89.
2. **Angka konsisten dan jujur.** Given nilai yang belum tercatat, When angka tampil, Then ditulis "Belum tersedia", dan angka yang ada sama dengan kartu daftar dan tabel banding (satu sumber, `TrailFigures`).
3. **Jalur belum lengkap.** Given jalur belum terbit karena data kritis kurang (termasuk geometri di PostgreSQL), When dibuka, Then yang tampil halaman menunggu data yang menyebut apa yang ditunggu, tanpa angka dan tanpa label kecocokan (R-008, R-022, K23).
4. **Peta.** Given jalur bergeometri, When peta tampil, Then garis jalur dan pos berkoordinat digambar; tanpa geometri, tampil kalimat bahwa garis belum dimasukkan dan daftar pos tetap tampil.
5. **Profil elevasi.** Given profil elevasi belum ada, When bagiannya tampil, Then tertulis "Data ketinggian belum tersedia." dan tidak ada grafik kosong.
6. **Pos.** Given pos tercatat, When daftarnya tampil, Then nama, jenis, elevasi, jarak dari awal, dan perkiraan waktu bila ada data tempo; yang belum ada ditulis jujur.
7. **Status resmi.** Given status tercatat, When kondisi tampil, Then Buka, Dibatasi, Tutup, atau Belum diketahui dengan cakupan, sumber, waktu verifikasi, kesegaran, dan kalimat bahwa Buka tidak berarti aman (R-012, R-013, BR-05); tanpa status, Belum diketahui dan tidak dianggap buka (BR-07; K5).
8. **Cuaca.** Given prakiraan BMKG tersedia, When cuaca tampil, Then ada area acuan, waktu ambil, kesegaran, dan sumber (BR-15); bila gagal atau basi, kalimat jujur tanpa angka karangan (§47).
9. **Laporan pendaki.** Given ada laporan, When tampil, Then hanya yang lolos moderasi, ditandai komunitas, terpisah dari status resmi, dengan tanggal mendaki, dan tidak pernah menimpa status resmi (R-010, BR-06).
10. **Kecocokan.** Given profil lengkap, When bagian kecocokan tampil, Then label R-004 dengan definisinya dan faktor per kelompok tanpa skor; tanpa profil, ajakan mengisi profil.
11. **Izin.** Given jalur butuh izin, When bagian izin tampil, Then penyelenggara, jendela pemesanan, kuota bila terverifikasi, dan tautan sistem resmi; Memount menautkan, tidak menjual izin (§153).
12. **Tata letak.** Given layar 360 px, When halaman dibuka, Then satu kolom tanpa gulir horizontal; mulai 1024 px, dua kolom (spec v3 bagian 5.2).

## Aturan yang berlaku

- **Kanonis:** R-001, R-002, R-004, R-005, R-006, R-007, R-008, R-009, R-010, R-011, R-012, R-013, R-022; R-017 (foto komunitas bukan hero, milik `DESIGN.md`).
- **Aturan bisnis:** BR-02, BR-05, BR-06, BR-07, BR-12, BR-14, BR-15.
- **PRD:** §34, §41 sampai §47, §48 sampai §51, §55, §59, §60, §89 sampai §93, §110, §116, §117, §153, §155.

## Data dan sumber

| Tabel | Kolom yang dipakai | Migrasi |
|---|---|---|
| `trails` | angka kunci, karakter medan, `elevation_profile`, `geometry` (PostgreSQL), `data_source_id`, `is_published` | `create_trails_table`, `add_elevation_profile_to_trails_table` |
| `trail_segments` | `sequence`, `name`, `description`, `distance_km`, `elevation_gain_m`, `technical_demand`, `terrain_character`, `geometry` | `create_trail_segments_table` |
| `checkpoints` | `sequence`, `name`, `checkpoint_type`, `elevation_m`, `latitude`, `longitude`, `trail_segment_id` | `create_checkpoints_table`, `add_coordinates_to_checkpoints` |
| `official_statuses` | `statusable`, `scope`, `status`, `source`, `source_url`, `published_at`, `verified_at`, `effective_at`, `expires_at`, `reason` | `create_official_statuses_table` |
| `weather_snapshots` | `reference_area`, `forecast_at`, `weather_description`, `temperature_c`, `humidity_percent`, `wind_speed_kmh`, `analysis_date`, `source`, `fetched_at` | `create_weather_snapshots_table`, `add_forecast_at_to_weather_snapshots`, `drop_trail_id_from_weather_snapshots` |
| `trail_condition_reports`, `report_thanks` | `hike_date`, `condition_tags`, `photo_path`, `note`, `moderation_status`; terima kasih per pendaki | `create_trail_condition_reports_table`, `create_report_thanks_table` |
| `permit_requirements` | `authority`, `booking_url`, `daily_quota`, `booking_opens_days_before`, `booking_closes_days_before`, `guide_required`, `source`, `verified_at` | `create_permit_requirements_table` |
| `data_sources`, `mountain_follows` | sumber data jalur; ikuti gunung | `create_data_sources_table`, `create_mountain_follows_table` |

Sumber luar: prakiraan BMKG per kode wilayah adm4, diambil perintah `weather:refresh` dua kali sehari; peramban tidak memanggil BMKG langsung (§45).

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Jalur belum terbit | Halaman menunggu data, tanpa angka dan label | Ada: `TrailDetailController` merender `Trails/Awaiting` |
| Jalur diarsipkan | Tidak dapat dibuka | Ada: 404 (`abort_if` di `TrailDetailController`) |
| Tanpa geometri | Kalimat garis belum dimasukkan, pos tetap tampil | Ada: `tests/Feature/TrailDetailMapTest.php` |
| Tanpa profil elevasi | "Data ketinggian belum tersedia." | Ada: `tests/Feature/ElevationProfileDisplayTest.php` |
| Status UNKNOWN | "Belum diketahui", tidak dianggap buka | Ada: `OfficialStatusValue::UNKNOWN` |
| Cuaca gagal atau basi | Pesan jujur dan sumber, tanpa angka | Ada: cabang `available: false` di `TrailDetailPage::cuaca()` |
| Tanpa laporan terbit | Kalimat dari server | Ada: `komunitas.message` di `resources/js/components/trails/rinci/LaporanKomunitas.tsx` |
| Profil belum lengkap | Ajakan mengisi profil | Ada: "Isi profil sekarang" (`tests/Feature/TrailDetailPageTest.php`) |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Ringkasan rute | Ada | `app/Services/Trails/TrailDetailPage.php` (`angkaNaik`, `tantangan`), `app/Services/Trails/TrailFigures.php`, `resources/js/pages/Trails/Show.tsx`, `tests/Feature/TrailDetailPageTest.php` |
| Peta dan profil ketinggian | Ada | `resources/js/components/trails/rinci/PetaJalur.tsx`, `resources/js/components/trails/ProfilElevasi.tsx`, `app/Services/Trails/ProfilElevasiJalur.php`; sejak W3 ruas tercuram diarsir dengan keterangan kalimat (`tests/Feature/ElevationProfileDisplayTest.php`) |
| Titik penting | Ada | `app/Services/Trails/CheckpointJourney.php`, `app/Services/CheckpointPaceService.php`, `app/Services/Trails/PosSepanjangJalur.php` (km dari garis rute), `resources/js/components/trails/rinci/DaftarPos.tsx`, `tests/Feature/CheckpointJourneyTest.php`, `tests/Feature/CheckpointPaceDisplayTest.php`, `tests/Spatial/PosSepanjangJalurTest.php` |
| Kondisi dan sumber info | Ada, dengan catatan | `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/components/trails/LabelSumber.tsx`, `resources/js/components/trails/rinci/SumberHalaman.tsx`, `app/Services/OfficialStatusService.php`, `app/Services/ConditionAggregatorService.php`, `app/Services/WeatherService.php`. Label kepercayaan memakai `SourceType` (Resmi, Terverifikasi admin, Ahli bersertifikat, Komunitas, Interpretasi sistem), bukan "Pakar" (K6). Prakiraan menampilkan waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin dan curah hujan belum tampil walau kolom angin ada |
| Laporan pendaki | Ada | `LaporanKomunitas.tsx`, `app/Http/Controllers/Trails/ReportThanksController.php`, `tests/Feature/ReportThanksTest.php` |
| Alasan kecocokan | Ada | "Cocokkah untuk saya?" di `resources/js/components/trails/rinci/CocokkahUntukSaya.tsx`, `tests/Feature/AmbientFitTest.php`; halaman penilaian lengkap `/trails/{slug}/kecocokan` sejak W3 (`app/Http/Controllers/Trails/TrailFitAssessmentController.php`, `app/Services/Trails/PenilaianKecocokan.php`, `tests/Feature/FitAssessmentPageTest.php`) |
| Izin resmi | Ada | `perizinan` di `TrailDetailPage`, `tests/Feature/PermitVisibilityTest.php` |
| Jalur belum lengkap | Ada | `Trails/Awaiting`, `tests/Feature/AwaitingDataTest.php` |
| Halaman publik SEO | Ada, rute lama | `app/Http/Controllers/PublicTrailController.php`, `resources/views/public/trail.blade.php` |
| Halaman gunung | Ada (W3) | `/mountains/{slug}` lewat `app/Http/Controllers/Trails/MountainPageController.php`, `app/Services/Mountains/HalamanGunung.php`, `resources/js/pages/Mountains/Show.tsx`, `tests/Feature/MountainPageTest.php` (DK-44); foto berlisensi dan status gunung api menunggu W8 (DK-37, DK-43) |
| Status gunung api PVMBG | Belum ada | DK-43, W8 |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman detail rute dengan data tiruan | `resources/js/pages/Trails/Show.tsx` dengan data nyata | Ada |
| 2 | Buat panel ringkasan rute | `angkaNaik`, `tantangan` di `TrailDetailPage` | Ada |
| 3 | Buat peta jalur dan titik akhir | `PetaJalur.tsx` | Ada |
| 4 | Buat grafik profil ketinggian jalur | `ProfilElevasi.tsx` | Ada |
| 5 | Buat daftar titik penting di jalur | `CheckpointJourney`, `DaftarPos.tsx` | Ada |
| 6 | Buat panel kondisi dan sumber info | `Kondisi.tsx`, `LabelSumber.tsx` | Ada |
| 7 | Buat panel alasan kecocokan faktor | `FaktorKategori`, bagian kecocokan di `Show.tsx` | Ada |
| 8 | Tangani data kosong dengan keterangan jujur | `TrailFigures::BELUM` dan kalimat per bagian | Ada |
| 9 | Terapkan tata letak responsif mobile desktop | `Show.tsx` | Ada (W3: dua kolom dengan kolom kanan lengket mulai 1024 px, bar aksi satu baris di ponsel) |
| 10 | Hubungkan navigasi jelajah ke halaman detail | Seluruh kartu `KartuJalur` menjadi tautan | Ada |
| 11 | Buat skema dan migrasi tabel jalur | `create_trails_table` | Ada |
| 12 | Buat tabel titik penting dan profil ketinggian | `create_checkpoints_table`, `add_coordinates_to_checkpoints`, `add_elevation_profile_to_trails_table` | Ada |
| 13 | Buat tabel kondisi jalur dan sumber info | `create_official_statuses_table`, `create_trail_condition_reports_table`, `create_data_sources_table`, `create_weather_snapshots_table` | Ada |
| 14 | Buat endpoint detail rute | `TrailDetailController` (props Inertia) | Ada, bentuk berbeda |
| 15 | Buat endpoint profil ketinggian dan titik penting | Props `profilElevasi` dan `pos` | Ada, bentuk berbeda |
| 16 | Buat endpoint kondisi terkini dengan sumber | `ConditionAggregatorService`, props `statusResmi`, `cuaca`, `komunitas` | Ada |
| 17 | Buat layanan alasan kecocokan pengguna | `app/Services/RecommendationExplanationService.php`, `TrailFitService` | Ada |
| 18 | Terapkan validasi rute tanpa geometri | `Trail::publishabilityReport()` (geometri wajib di PostgreSQL), `Trails/Awaiting` | Ada |

## Keputusan dan konflik terkait

- **DK-06** peta hanya jalur bergeometri; **DK-09** hierarki sumber dengan ahli bersertifikat (diputuskan atas delegasi pemilik); **DK-37** foto gunung berlisensi (W8); **DK-43** status gunung api terpisah dan tidak dipetakan ke Buka atau Tutup (W8); **DK-44** halaman gunung terautentikasi (W3).
- **K5** status OPEN, RESTRICTED, CLOSED, UNKNOWN; "waspada" menjadi peringatan kondisi; **K6** label "Pakar" diganti hierarki DK-09; **K8** ambang kesegaran per sumber, bukan tujuh hari untuk semua; **K17** cuaca per area acuan jalur, bukan per gunung; **K23** detail jalur tanpa geometri menjadi halaman menunggu data; **K33** level PVMBG tidak pernah dipetakan ke status akses.
