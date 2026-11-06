# 06 Pendakian dan progres

**FR utama:** FR-14 Riwayat pendakian (§52), FR-15 Mode pendakian (§53), FR-25 Target pribadi dan catatan pendakian manual (§150).
**Bahan:** features/06-pendakian-progres.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.12 sampai 6.14, dan 10.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna di tab Mendaki: "Apa yang saya butuhkan di jalur sekarang?" Di tab Progres: "Bagaimana perkembangan saya?" (§145).

Mode lapangan menemani pendaki di jalur dengan tampilan tenang dan kontrol besar: posisi atas izin, pos berikutnya, sisa jarak, dan perkiraan waktu. Sesudah turun, pendakian tercatat di riwayat dan dibandingkan hanya dengan pendakian pendaki itu sendiri, tanpa papan peringkat (R-015).

## Posisi di loop dan permukaan

- **Loop (§7):** Mendaki, lalu Laporan, lalu Riwayat.
- **Tab Mendaki:** `/mendaki` (pintu tab), mode lapangan `/trips/{trip}/hike`.
- **Tab Progres:** `/progres`, `/history`, `/history/{trip}`; laporan kondisi dari ringkasan pendakian (`/reports/create`).

## Kriteria penerimaan

1. **Mulai.** Given trip siap dimulai, When pendaki memulai pendakian, Then mode lapangan terbuka; lokasi hanya diminta di sini, atas persetujuan di perangkat (§82, BR-13).
2. **Mode lapangan.** Given mode lapangan terbuka, When posisi diketahui, Then peta menampilkan jalur dan posisi, pos berikutnya, sisa jarak, dan perkiraan waktu dari data tempo bila ada; kontrol utama besar dan mudah ditekan.
3. **Data tidak cukup.** Given jalur tanpa pos atau posisi belum diketahui, When mode lapangan tampil, Then ditulis jujur ("Titik penting belum tersedia", lokasi belum tersedia), bukan angka kosong atau tebakan.
4. **Rekam jejak.** Given pendaki menyalakan rekam jejak (mati bawaan), When sinyal kembali setelah turun, Then jejak terkirim; pendaki dapat menghapus jejaknya.
5. **Selesai.** Given pendakian berakhir, When pendaki menandai hasil dan catatan pribadi, Then pendakian tercatat di riwayat dengan keadaannya; angka dari data jalur bila tanpa rekaman.
6. **Riwayat.** Given ada pendakian tercatat, When `/history` dibuka, Then daftar memuat tanggal, jalur, dan keadaan, dan setiap pendakian dapat dibuka ringkasannya.
7. **Grafik progres.** Given kurang dari dua pendakian, When `/progres` dibuka, Then grafik tidak digambar dan kalimatnya mengajak mencatat pendakian; dengan dua atau lebih, grafik membandingkan pendakian pendaki dengan pendakiannya sendiri.
8. **Tanpa kompetisi.** Given halaman Progres, When tampil, Then tidak ada peringkat, skor publik, perbandingan dengan pendaki lain, maupun hadiah untuk kecepatan (R-015, §156).
9. **Target pribadi (FR-25).** Given pendaki menetapkan target jarak, tanjakan, atau frekuensi per bulan, When Progres tampil, Then target dibandingkan dengan pendakian sendiri tanpa streak dan tanpa teguran, dan tidak mengubah label kecocokan maupun tingkat pengalaman (BR-11).
10. **Catat manual (FR-25).** Given pendaki mencatat pendakian di luar trip, When tersimpan, Then ditandai diisi sendiri, dapat diperbaiki dan dihapus, dan tidak menaikkan tingkat pengalaman (BR-11).
11. **Batas cakupan.** Given mode lapangan, When dipakai, Then tidak menjanjikan pelacakan latar, deteksi keluar jalur, navigasi luring penuh, atau SOS (§53).

## Aturan yang berlaku

- **Kanonis:** R-006, R-007, R-012, R-014; R-015 (milik `DESIGN.md`).
- **Aturan bisnis:** BR-11, BR-13.
- **PRD:** §52, §53, §54, §55, §82, §83, §84, §120 (kriteria lokasi), §150, §156.

## Data dan sumber

| Tabel | Kolom yang dipakai | Migrasi |
|---|---|---|
| `hiking_sessions` | `trip_plan_id`, `user_id`, `current_checkpoint_id`, `reached_checkpoint_sequence`, `status`, `started_at`, `ended_at`, `location_updated_at` | `create_hiking_sessions_table`, `add_reached_sequence_to_hiking_sessions` |
| `hike_track_points` | `hiking_session_id`, `recorded_at`, `latitude`, `longitude`, `elevation_m`, `accuracy_m` | `create_hike_track_points_table` |
| `hiking_history` | `user_id`, `trip_plan_id`, `trail_id`, `trail_condition_report_id`, `trip_type`, `completion_state`, `preparation_completion_percent`, `personal_notes`, `completed_at` | `create_hiking_history_table` |
| `user_preferences` | `record_track` (mati bawaan) | `add_track_recording_preference` |
| `checkpoints`, `trails` | pos berurutan, koordinat, `elevation_profile`, `geometry` | spesifikasi 02 |
| `personal_targets`, `user_owned_items` | hanya `id` dan `timestamps` (rintisan kosong) | `create_personal_targets_table`, `create_user_owned_items_table` |
| `hiking_history` (sumber manual) | belum ada kolom; migrasi kosong | `add_source_and_recorded_values_to_hiking_history_table` |

Ketiga migrasi rintisan FR-25 tidak boleh dijalankan di basis data pemilik sebelum W7 mengisinya.

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Tidak ada trip yang dapat dimulai | Pintu tab menjelaskan dan menawarkan trip lain | Ada: `tests/Feature/TrackTabTest.php` |
| Izin lokasi ditolak | Kalimat jujur; peta dan pos tetap terbaca | Ada: `resources/js/components/mendaki/usePosisiLapangan.ts` dan kalimat lokasi di `ModeLapangan.tsx`; tiba di pos dapat ditandai sendiri |
| Sinyal hilang | Data yang sudah dibuka tetap terbaca; jejak dan aksi diantrekan | Ada: hitungan di perangkat (`hitung.ts`), antrean aksi (`antrean.ts`), perekam jejak di perangkat lalu pengirim sesudah turun (`resources/js/jejak.js`, `resources/js/luring/kirimJejak.ts`); paket luring di balik sakelar (GR-07) |
| Riwayat kosong | Kalimat dan ajakan | Ada: prop `kosong` dari `HikerProgressController` |
| Grafik kurang dari dua titik | Tidak digambar, dijelaskan | Ada: penjaga `titik.length < 2` di `resources/js/components/community/GrafikTanjakan.tsx` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Peta navigasi langsung | Ada | `app/Http/Controllers/Trips/HikeModeController.php`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/components/trips/PetaPendakian.tsx`, `usePendakian.ts`, `resources/js/components/trips/TataLetakPendakian.tsx`, `tests/Feature/LocationPrivacyTest.php` |
| Titik berikutnya dan sisa waktu | Ada | `app/Services/Trips/HalamanMendaki.php`, `app/Services/Trips/HikeCheckpointResolver.php`, `resources/js/components/mendaki/hitung.ts`, `tests/Feature/HikeModeCheckpointTest.php`, `tests/Feature/FieldModeFiguresTest.php` |
| Tab Mendaki | Ada | `app/Http/Controllers/Trips/TrackController.php`, `resources/js/pages/Track/Index.tsx`, `tests/Feature/TrackTabTest.php` |
| Rekam jejak | Ada | Perekam di perangkat `resources/js/jejak.js`; pengirim sesudah pendakian `resources/js/luring/kirimJejak.ts` (W6-P2); server `app/Http/Controllers/HikeTrackController.php` dan predikat `app/Services/Trips/PenerimaanJejak.php`; `tests/Feature/PengirimJejakTest.php`, `tests/Feature/TrackPreferenceTest.php`, `tests/Feature/HikeTrackOnSummaryTest.php` |
| Catat pendakian selesai | Ada untuk trip | `app/Http/Controllers/Trips/TripLifecycleController.php` (`complete`), `app/Http/Requests/Trips/CompleteTripRequest.php`, `app/Http/Controllers/Community/HikeSummaryController.php`, `app/Services/Community/HasilPendakian.php`, `tests/Feature/HikeSummaryTest.php` |
| Riwayat dan grafik progres | Ada | `app/Http/Controllers/Community/HikingHistoryController.php`, `app/Http/Controllers/Community/HikerProgressController.php`, `app/Services/HikerProgressService.php`, `app/Services/ProgressLadderService.php`, `GrafikTanjakan.tsx`, `tests/Feature/HikerProgressTest.php`, `tests/Feature/HikingHistoryPageTest.php`, `tests/Feature/ProgressLadderTest.php`, `tests/Feature/ProgressLadderOnTrailDetailTest.php` |
| Target pribadi dan catat manual (FR-25) | Belum ada | Migrasi rintisan kosong (lihat Data dan sumber); `tests/Feature/PersonalTargetTest.php`, `OwnedEquipmentTest.php`, `ManualHikeRecordTest.php` hanya membuka `/` dan tidak menguji fitur (W7) |
| Paket lapangan luring | Ada, di balik sakelar mati (Q10, Q14) | `app/Services/Trips/PaketLapangan.php`, `resources/views/sw.blade.php`, `resources/js/luring/`, `tests/Feature/FieldPackManifestTest.php`, `tests/Feature/FieldPackStalenessTest.php`, `tests/Feature/OfflineShellTest.php`, `tests/Feature/LogoutClearsDeviceDataTest.php` |
| Kartu darurat, versi cetak, pesan rencana | Ada, di balik sakelar mati (Q14) | `app/Services/Trips/KartuDarurat.php`, `app/Services/Trips/PesanRencana.php`, `app/Services/Trips/CetakTrip.php`, `tests/Feature/EmergencyCardTest.php`, `tests/Feature/TripPlanMessageTest.php` |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman mode pendakian dengan data tiruan | `resources/js/pages/Trips/Hike.tsx` dengan data nyata | Ada |
| 2 | Buat komponen peta navigasi posisi langsung | `PetaPendakian.tsx`, `usePendakian.ts` | Ada |
| 3 | Buat panel titik berikutnya dan sisa waktu | `HikeCheckpointResolver`, `CheckpointPaceService` | Ada, sebagian |
| 4 | Buat form catat pendakian selesai | Kartu "Selesaikan trip" di `resources/js/pages/Trips/Show.tsx` | Ada untuk trip; catat manual belum (FR-25) |
| 5 | Buat halaman riwayat daftar pendakian | `resources/js/pages/Community/HikingHistory.tsx` | Ada |
| 6 | Buat komponen grafik progres pendakian | `GrafikTanjakan.tsx` | Ada |
| 7 | Buat halaman detail dan kelola catatan pendakian | `resources/js/pages/Community/HikeSummary.tsx` | Sebagian: ubah dan hapus catatan manual menunggu FR-25 |
| 8 | Tambahkan state offline dan keterangan data kosong | `resources/views/offline.blade.php`, `resources/js/luring/shell.tsx`, `resources/js/jejak.js`, kalimat kosong per bagian | Ada (paket luring di balik sakelar) |
| 9 | Buat skema dan migrasi sesi pendakian | `create_hiking_sessions_table`, `add_reached_sequence_to_hiking_sessions`, `create_hike_track_points_table` | Ada |
| 10 | Buat skema dan migrasi catatan pendakian | `create_hiking_history_table`; kolom sumber manual belum ada | Sebagian |
| 11 | Buat skema titik penting dan ketinggian jalur | `create_checkpoints_table`, `add_coordinates_to_checkpoints`, `add_elevation_profile_to_trails_table` | Ada |
| 12 | Buat endpoint titik penting dan ketinggian jalur | Props mode lapangan dari `HikeModeController` | Ada, bentuk berbeda |
| 13 | Buat endpoint sesi pendakian dan posisi | Rute `trips.start`, `trips.hike.arrive`, `trips.hike.pause`, `trips.hike.resume`, `hike.track`; posisi tidak dikirim (GR-07) | Ada, bentuk berbeda |
| 14 | Buat endpoint CRUD catatan pendakian | Tidak ada | Belum ada (FR-25) |
| 15 | Buat endpoint ringkasan progres pendakian | `HikerProgressController`, `HikerProgressService` | Ada |

## Keputusan dan konflik terkait

- **DK-07** FR-25 masuk cakupan; **DK-36** paket lapangan luring berisi data stabil; **DK-49** mode lapangan mengikuti tema pengguna; **DK-50** alasan pembatalan dicatat opsional dari daftar tetap (W6).
- **K13** target bulanan sebagai target pribadi terhadap diri sendiri; **K14** catat pendakian manual tidak menaikkan pengalaman.
- **Menunggu pemilik:** spec v3 bagian 15 Q9 (ambang data dinamis luring), Q10 (kebijakan data di perangkat), Q14 (telaah hukum dan fitur keselamatan).
