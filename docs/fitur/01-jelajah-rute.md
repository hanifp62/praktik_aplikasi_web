# 01 Jelajah rute

**FR utama:** FR-04 Route Fit Engine (§24), FR-05 Hasil rekomendasi (§32), FR-21 Arsitektur informasi (§145), FR-27 Pencarian kalimat sederhana (§152).
**Bahan:** features/01-jelajah-rute.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` bagian 6.2.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Apa yang tersedia, dan mana yang relevan untuk saya?" (§145).

Jelajah adalah titik awal menemukan jalur lewat daftar, peta, pencarian, dan saringan. Yang dipilih adalah jalur, bukan gunung (R-001, R-002). Setiap kandidat membawa label kecocokan beserta alasannya, sehingga pendaki memilih dengan yakin. Jelajah menyajikan kandidat dan alasannya; ia tidak memutuskan untuk pendaki.

## Posisi di loop dan permukaan

- **Loop (§7):** langkah menemukan dan memilih rute, sesudah profil dan tujuan, sebelum rencana perjalanan.
- **Tab:** Jelajah. Rute `/trails` (daftar dan peta), `/trails/{slug}` (spesifikasi 02), `/trails/saved` (spesifikasi 03). Rekomendasi dari tujuan pendakian (`/goals/create`, `/recommendations/{run}`) dicatat di sini karena FR-05, walau pintunya di tab Trip.
- **Layar:** ponsel memilih Daftar atau Peta; layar lebar menampilkan keduanya berdampingan (spec v3 bagian 6.2, prototype 02 dan 15).
- **Community:** laporan kondisi dibuka lewat detail jalur, bukan dari Jelajah langsung (§145).

## Kriteria penerimaan

1. **Daftar.** Given pendaki sudah masuk, When membuka `/trails`, Then tampil jalur terbit dengan nama, gunung dan provinsi, durasi, jarak, tanjakan, dan tuntutan teknis berlabel kata, dua belas per halaman.
2. **Angka yang hilang.** Given jalur tanpa jarak atau tanjakan tercatat, When barisnya tampil, Then nilai itu ditulis "Belum tersedia", tidak pernah 0 (R-007).
3. **Peta.** Given ada jalur terbit bergeometri, When pendaki memilih tampilan Peta, Then hanya jalur bergeometri yang digambar sebagai garis; titik gunung tidak menggantikan jalur (DK-06), dan jumlah jalur yang belum tergambar disebut.
4. **Saringan.** Given pendaki mengisi saringan jarak, durasi, wilayah, atau tuntutan teknis, When menerapkannya, Then daftar dan hitungannya berubah tanpa kehilangan posisi, dan setiap saringan dapat dilepas.
5. **Tuntutan teknis belum diketahui.** Given saringan tuntutan teknis rendah aktif, When ada jalur dengan tuntutan teknis UNKNOWN, Then jalur itu tidak masuk hasil saringan dan tidak diberi label rendah (R-006).
6. **Kalimat sederhana.** Given pendaki mengetik "gunung dekat Malang untuk pemula", When mencari, Then kalimat itu ditafsirkan menjadi saringan yang terlihat dan dapat dilepas, dan teks aslinya tidak dibuang diam-diam (FR-27).
7. **Label kecocokan.** Given profil pendaki lengkap, When kartu tampil, Then labelnya hanya salah satu dari Cocok, Perlu persiapan, Kurang cocok, dengan ikon dan kata, dan "Kenapa?" membuka alasan per faktor tanpa skor atau persen (R-004, R-005, BR-09; K2, K3).
8. **Profil belum lengkap.** Given profil belum lengkap, When Jelajah dibuka, Then tidak ada label yang ditebak; tampil keadaan "Belum dinilai" dan ajakan mengisi profil (R-006, §91).
9. **Jalur menunggu data.** Given jalur belum terbit karena data kritisnya belum lengkap, When Jelajah dibuka, Then jalur itu tidak masuk hasil, hitungan, maupun rekomendasi, dan tampil terpisah sebagai menunggu data tanpa angka (R-008, R-022).
10. **Simpan.** Given pendaki menekan "Simpan rute" pada kartu, When berhasil, Then keadaannya menjadi "Tersimpan", diumumkan ke pembaca layar, dan jalur tetap tersimpan setelah keluar lalu masuk kembali.
11. **Hasil kosong.** Given saringan tidak menyisakan jalur, When daftar kosong, Then tampil penyebabnya dan saringan mana yang sebaiknya dilonggarkan.
12. **Rekomendasi.** Given pendaki mengisi tujuan pendakian, When rekomendasi dihasilkan, Then hasilnya hanya jalur terbit yang lengkap, jalur berstatus CLOSED tidak menjadi rekomendasi aktif (BR-04), dan setiap hasil membawa alasan (BR-08).

## Aturan yang berlaku

- **Kanonis:** R-001, R-002, R-003, R-004, R-005, R-006, R-007, R-008, R-009, R-022.
- **Aturan bisnis:** BR-01, BR-02, BR-03, BR-04, BR-07, BR-08, BR-09.
- **PRD:** §24 sampai §31 (Route Fit), §32 (hasil rekomendasi), §55 (kebijakan peta), §85 (kebijakan AI: pencarian berbasis aturan), §88 (mobile-first), §89, §90, §91 (pengungkapan bertahap, penjelasan, tanpa presisi palsu), §104 (kasus tepi), §110 (syarat terbit), §114 (kriteria penerimaan rekomendasi), §152 (FR-27).
- **Lintas fitur:** tema §157; bahasa §158 (Inggris belum aktif, DK-40).

## Data dan sumber

| Tabel | Kolom yang dipakai | Migrasi |
|---|---|---|
| `mountains` | `name`, `slug`, `province`, `region`, `elevation_mdpl`, `timezone`, `latitude`, `longitude`, `archived_at` | `create_mountains_table`, `add_timezone_to_mountains`, `tambah_koordinat_biasa_ke_gunung` |
| `trails` | `name`, `slug`, `distance_km`, `elevation_gain_m`, `elevation_loss_m`, `estimated_duration_minutes`, `technical_demand`, `terrain_character`, `navigation_complexity`, `water_availability`, `camping_available`, `is_published`, `archived_at`, `data_source_id`, `elevation_profile`; `geometry` (geography LINESTRING lewat `PostGis::addGeography`, hanya PostgreSQL) | `create_trails_table`, `add_elevation_profile_to_trails_table` |
| `official_statuses` | `status`, `scope`, `verified_at`, `expires_at` (jalur CLOSED bukan rekomendasi aktif) | `create_official_statuses_table` |
| `profiles`, `user_experience`, `user_preferences` | masukan Route Fit: tingkat pengalaman, `terrain_experience`, `navigation_experience`, durasi terlama, tanjakan tertinggi, preferensi | `create_profiles_table`, `create_user_experience_table`, `create_user_preferences_table` |
| `recommendation_rules` | `key`, `category`, `weight`, `active`, `engine_version` | `create_recommendation_rules_table` |
| `hiking_goals`, `recommendation_runs`, `recommendation_results` | tujuan pendakian; jejak audit rekomendasi; `label`, `explanation`, `trail_snapshot`. `internal_score` tidak pernah dikirim ke halaman (BR-09) | `create_hiking_goals_table`, `create_recommendation_runs_table`, `create_recommendation_results_table`, `add_trail_snapshot_to_recommendation_results_table` |
| `trail_considerations` | `user_id`, `trail_id` (simpan dari kartu) | `create_trail_considerations_table` |

Sumber luar: peta dasar dari penyedia yang dikonfigurasi di `config/hiking.php` (kunci `map`), bukan dari basis data. Tidak ada geometri jalur resmi yang dipublikasikan (spec v3 bagian 10.1); geometri diisi admin atau ahli berkredensial (spesifikasi 08).

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Kosong karena saringan | Penyebab dan saringan yang dapat dilonggarkan | Ada (W3): "Tidak ada jalur dengan saringan ini" dengan jumlah yang disembunyikan, "Tampilkan juga", dan "Hapus semua saringan"; katalog yang belum terbit diperiksa lebih dulu sebagai keadaan menunggu (W1-F1, `tests/Feature/TrailIndexSearchTest.php`) |
| Pertama kali, profil belum lengkap | Tanpa label tebakan; "Belum dinilai" dan ajakan mengisi profil | Ada (W3): setiap kartu berlabel "Belum dinilai" dan kotak "Jalur di sini belum dinilai untuk Anda" dengan "Isi profil pendaki" di `resources/js/pages/Trails/Index.tsx`; kecocokan tidak dihitung tanpa profil |
| Data jalur belum lengkap | Tidak dihitung, tidak direkomendasikan, tampil terpisah | Ada (W3): bagian "Belum bisa dinilai" dengan kartu bergaris putus-putus `resources/js/components/trails/jelajah/KartuBelumDinilai.tsx` yang menyebut yang ditunggu (`tests/Feature/TrailCardTest.php`) |
| Memuat | Kerangka kartu, bukan area kosong | Ada (W3): skeleton kartu selama kunjungan saringan di `resources/js/pages/Trails/Index.tsx` |
| Galat peta | Kalimat jujur, daftar tetap tersedia | Ada: pengakuan gagal gaya peta di `resources/js/components/peta/usePetaDasar.ts` |
| Belum ada jalur bergeometri | Penjelasan, daftar tetap tampil | Ada (W3): kalimat jumlah jalur tanpa garis rute di pojok peta; tanpa satu pun geometri, MapLibre tidak dimuat dan panel peta menyebut yang ditunggu (`tests/Feature/TrailIndexMapTest.php`) |
| Luring | Tidak dijanjikan untuk Jelajah | Service worker hanya menyimpan shell dan halaman `/offline` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Peta rute interaktif | Ada | `resources/js/components/trails/PetaJelajah.tsx`, `app/Services/Trails/PetaSebaranJalur.php` (hanya jalur bergeometri, PostGIS), `tests/Feature/TrailIndexMapTest.php`, `tests/Spatial/PetaSebaranJalurTest.php`; sejak W3 pola garis per label dengan legenda, chip pemilih, dan kartu pratinjau |
| Daftar dan kartu rute | Ada | `resources/js/pages/Trails/Index.tsx`, `resources/js/components/trails/jelajah/KartuJalur.tsx`, `app/Http/Resources/KartuJalurResource.php`, `app/Services/Trails/HalamanJelajah.php`, `tests/Feature/TrailCardTest.php`, `tests/Feature/ResultListBudgetTest.php`; 12 jalur per halaman (`TrailSearch::PER_HALAMAN`) |
| Pencarian dan saringan | Ada untuk kata kunci, wilayah, kecocokan dasar, jenis trip, tuntutan teknis, status resmi, dan rentang tanjakan; lembar saringan menghitung "Tampilkan N jalur" | `app/Services/Trails/TrailSearch.php`, `resources/js/components/trails/jelajah/SaringanJelajah.tsx`, `resources/js/components/trails/jelajah/saringan.ts`, `tests/Feature/TrailIndexSearchTest.php` |
| Pencarian kalimat (FR-27) | Sebagian (temuan F3) | `TrailSearch` mengenali lima alias kota (Malang, Surabaya, Bandung, Bogor, Semarang) dan membuang teks pencarian ketika alias cocok; sejak W3 tafsirannya tampil sebagai chip yang dapat dilepas dengan "Kembalikan tafsiran kalimat" (`resources/js/components/trails/jelajah/TafsiranKalimat.tsx`) |
| Label kecocokan dan "Kenapa?" | Ada | `resources/js/components/trails/LabelKecocokan.tsx` di `KartuJalur.tsx` ("Kenapa?" dan "Lihat penilaian lengkap"), `app/Services/TrailFitService.php`, `tests/Feature/TrailCardTest.php`, `tests/Feature/TrustGrammarTest.php`, `tests/Feature/FactorBarsDoNotLeakScoreTest.php` |
| Ajakan lengkapi profil | Ada | `resources/js/pages/Trails/Index.tsx` ("Isi profil sekarang") |
| Simpan rute | Ada | Sejak W3 tombol simpan 44 px dengan Urungkan (`resources/js/components/trails/TombolSimpan.tsx`) lewat rute idempoten `trails.saved.store` dan `trails.saved.remove`, `app/Http/Controllers/Trails/ConsiderationController.php`, `tests/Feature/SavedUndoTest.php`, `tests/Feature/ConsiderationTest.php`; Jelajah tanpa baki, hitungan dan pintu Simpanan di kepala halaman |
| Ajakan masuk saat menyimpan (tamu) | Berbeda | Seluruh Jelajah di belakang login (grup `auth` di `routes/web.php`); tamu hanya melihat halaman publik `/pendakian/{slug}` |
| Rekomendasi dari tujuan (FR-05) | Ada | `app/Http/Controllers/Goals/GoalController.php`, `app/Http/Controllers/Recommendations/RecommendationController.php`, `resources/js/pages/Recommendations/Show.tsx`, `resources/js/components/discovery/KartuHasil.tsx`, `resources/js/components/discovery/PanelAlasan.tsx`, `tests/Feature/RecommendationFlowTest.php` |

## Task NgodingPakeAI

Catatan 23 September 2026: task 1 sampai 9 ditandai done oleh agen sebelumnya tetapi belum diverifikasi; task 10 selesai dan terverifikasi. Tabel ini memetakan task ke kode yang sudah ada (aturan brownfield).

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman Jelajah Rute dengan data tiruan | `resources/js/pages/Trails/Index.tsx` dengan data nyata | Ada |
| 2 | Buat peta interaktif sebaran jalur | `PetaJelajah.tsx`, `PetaSebaranJalur` | Ada; versi 22 September yang memakai koordinat gunung (F2) diganti geometri jalur (DK-06) |
| 3 | Buat kartu daftar jalur dengan metadata | `KartuJalur.tsx`, `KartuJalurResource` (turunan `TrailRowResource`) | Ada; `TrailRow.tsx` dihapus di gelombang perbaikan akhir W3 (W3-C8) |
| 4 | Buat pencarian kata kunci dan filter jarak waktu kesulitan | `TrailSearch`, `SaringanJelajah.tsx` | Ada |
| 5 | Dukung pencarian kalimat sederhana di frontend | `TrailSearch` (di server, bukan frontend) | Sebagian (F3) |
| 6 | Tampilkan badge kecocokan pada kartu jalur | `KartuJalur.tsx` dengan `LabelKecocokan.tsx` | Ada; label mengikuti R-004 (K3); `FitLine.tsx` kini hanya di sel Bandingkan |
| 7 | Buat panel alasan Kenapa pada badge kecocokan | `KartuJalur.tsx` (panel "Kenapa?" di kartu) | Ada |
| 8 | Tampilkan ajakan lengkapi profil di badge | `resources/js/pages/Trails/Index.tsx` | Ada |
| 9 | Buat tombol simpan rute pada kartu | `TombolSimpan.tsx`, rute `trails.saved.store` dan `trails.saved.remove` | Ada; versi sesi 22 September diganti `trail_considerations`, sakelar `trails.timbang` dihapus di W5 karena tidak lagi punya pemanggil (ruling W3-F3) |
| 10 | Bangun halaman daftar rute tersimpan | `resources/js/pages/Trails/Saved.tsx`, `SavedRoutesController` | Ada (dilanjutkan di spesifikasi 03) |
| 11 | Tambahkan empty state dan saran ubah filter | `resources/js/pages/Trails/Index.tsx` | Ada |
| 12 | Buat halaman detail jalur dengan ringkasan | Spesifikasi 02 | Ada |
| 13 | Tambahkan ajakan masuk saat menyimpan rute | Tidak ada padanan | Berbeda (Jelajah di belakang login) |
| 14 | Buat skema database jalur dan geometri rute | `create_trails_table` | Ada; skema kanonis adalah migrasi yang sudah berjalan (K16) |
| 15 | Seed data jalur pendakian dengan geometri lengkap | `database/seeders/MvpDatasetSeeder.php` tanpa geometri | Belum ada; data contoh lokal berlabel DATA CONTOH direncanakan W1 (DK-35) |
| 16 | Buat API daftar jalur dengan filter | `TrailIndexController` (props Inertia, bukan API JSON) | Ada, bentuk berbeda |
| 17 | Buat API pencarian kata kunci dan kalimat | `TrailSearch` | Sebagian (F3) |
| 18 | Buat API detail jalur beserta geometri | `TrailDetailController`, `app/Services/Trails/TrailDetailPage.php` | Ada, props Inertia |
| 19 | Buat skema profil dan preferensi pengguna | `create_profiles_table`, `create_user_experience_table`, `create_user_preferences_table` | Ada |
| 20 | Buat layanan skor kecocokan jalur | `app/Services/RouteFitService.php`, `app/Services/RouteFit/CompatibilityScorer.php`, `TrailFitService` | Ada; skor internal tidak tampil (BR-09, K2) |
| 21 | Buat API simpan dan daftar rute tersimpan | `ConsiderationController`, `SavedRoutesController` | Ada |

## Keputusan dan konflik terkait

- **DK-05** simpan longgar, banding sempit (diputuskan atas delegasi pemilik); **DK-06** peta hanya menggambar jalur bergeometri (diputuskan atas delegasi pemilik); **DK-25** lima tab; **DK-35** data contoh hanya SQLite lokal; **DK-40** kontrol yang belum berfungsi tidak ditampilkan.
- **K2** skor kecocokan tidak tampil; **K3** tiga label R-004; **K7** peta bukan antarmuka utama (DK-06); **K16** skema kanonis adalah migrasi; **K20** FR-27 berbasis aturan dengan batas jujur.
- **Temuan:** F2 (peta memakai koordinat gunung, ditutup DK-06), F3 (alias kota, terbuka), F6 (daftar jauh di bawah layar pertama ponsel, ditata ulang W3), F7 (baki simpanan sticky berpotensi menutupi fokus; baki Jelajah dilepas W3).
