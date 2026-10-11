# 05 Kabar dan kondisi

**FR utama:** FR-11 Status resmi (§41), FR-12 Cuaca (§44), FR-13 Laporan kondisi jalur (§48).
**Bahan:** features/05-kabar-kondisi.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.4, 7, dan 10.3.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Bagaimana kondisi jalur yang saya pertimbangkan sekarang, dan dari mana informasinya?"

Status resmi, prakiraan cuaca, dan laporan pendaki tampil terpisah menurut asalnya, masing-masing dengan sumber dan waktu pembaruannya, supaya pendaki menilai sendiri seberapa bisa dipercaya dan seberapa segar informasinya. Kabar adalah intel kondisi jalur, bukan feed sosial (DK-11).

## Posisi di loop dan permukaan

- **Loop (§7):** masukan untuk memilih, merencanakan, dan cek kesiapan; laporan dikirim sesudah mendaki.
- **Lapisan Community (§145):** kondisi dan laporan di detail jalur; kabar gunung yang diikuti di Beranda (W5); halaman lengkap `/kabar` dibuka dari Beranda dan Jelajah, bukan dari tab; formulir laporan `/reports/create` dari ringkasan pendakian di Progres.
- **Moderasi:** antrean di konsol (spesifikasi 08).

## Kriteria penerimaan

1. **Status resmi.** Given status tercatat, When tampil, Then salah satu dari Buka, Dibatasi, Tutup, Belum diketahui, dibedakan bentuk dan kata, bukan warna saja, beserta penerbit, cakupan, waktu terbit, dan waktu verifikasi (K5).
2. **Tanpa status.** Given tidak ada status, When tampil, Then Belum diketahui dan tidak dianggap buka (BR-07, R-006).
3. **Status kedaluwarsa.** Given `expires_at` status lewat, When dibaca, Then status kembali Belum diketahui dan masuk daftar tinjau admin (§98).
4. **Buka bukan jaminan.** Given status Buka, When tampil, Then kalimatnya menyatakan bahwa Buka tidak berarti jalur aman (R-012, BR-05).
5. **Cuaca.** Given prakiraan BMKG tersedia, When tampil, Then ada per waktu dengan area acuan, waktu analisis dan waktu ambil, kesegaran, dan sumber; bila gagal atau basi, kalimat jujur tanpa angka karangan (R-013, BR-15, §47).
6. **Laporan pendaki.** Given laporan dikirim, When belum dimoderasi, Then tidak tampil; When disetujui, Then tampil ditandai komunitas, dengan tag kondisi, tanggal mendaki, nama pelapor, dan foto yang metadata lokasinya sudah dibuang; laporan tidak pernah menimpa status resmi (R-010, BR-06, §50).
7. **Kesegaran.** Given data dari berbagai sumber, When label kesegaran tampil, Then ambangnya per sumber (K8), dan kesegaran laporan dihitung dari tanggal mendaki (§51).
8. **Kirim laporan.** Given pendaki mengirim laporan, When tag belum dipilih, Then pengiriman ditolak dengan pesan; pengiriman dibatasi laju per jam; foto dibatasi ukuran dan dibersihkan.
9. **Terima kasih.** Given laporan terbit, When pendaki lain berterima kasih, Then tercatat satu kali per pendaki, dan pelapor tidak dapat berterima kasih pada laporannya sendiri.
10. **Ikuti gunung.** Given pendaki mengikuti gunung, When membuka `/kabar`, Then kabar dikelompokkan menurut asal (§92).
11. **Tanpa feed sosial.** Given halaman kabar, When tampil, Then tidak ada jenis "cerita", "tips", maupun reaksi jempol (DK-11, K9).

## Aturan yang berlaku

- **Kanonis:** R-006, R-009, R-010, R-011, R-012, R-013; R-017 (milik `DESIGN.md`).
- **Aturan bisnis:** BR-05, BR-06, BR-07, BR-12, BR-13, BR-14, BR-15.
- **PRD:** §41 sampai §51, §57, §59, §60, §81 (unggahan), §82 (privasi lokasi), §92, §93, §97 (cache), §98 (penjadwal), §103 (matriks test laporan komunitas), §116, §117, §118.

## Data dan sumber

| Tabel | Kolom yang dipakai | Migrasi |
|---|---|---|
| `official_statuses` | `statusable` (gunung atau jalur), `scope`, `status`, `data_source_id`, `source`, `source_url`, `published_at`, `fetched_at`, `verified_at`, `effective_at`, `expires_at`, `reason`, `notes`, `recorded_by` | `create_official_statuses_table` |
| `weather_snapshots` | `reference_area`, `forecast_at`, `local_datetime`, `weather_description`, `temperature_c`, `humidity_percent`, `wind_speed_kmh`, `wind_direction`, `cloud_cover_percent`, `visibility_m`, `analysis_date`, `source`, `fetched_at` | `create_weather_snapshots_table`, `add_forecast_at_to_weather_snapshots`, `drop_trail_id_from_weather_snapshots` |
| `trail_condition_reports` | `trail_id`, `trail_segment_id`, `user_id` (kosong setelah akun pelapor dihapus), `hike_date`, `condition_tags`, `photo_path`, `note`, `moderation_status`, `moderated_by`, `moderated_at` | `create_trail_condition_reports_table`, `anonymise_reports_on_user_delete` |
| `report_thanks`, `mountain_follows`, `moderation_actions` | terima kasih, ikuti gunung, keputusan moderasi | `create_report_thanks_table`, `create_mountain_follows_table`, `create_moderation_actions_table` |
| `data_sources`, `authorities` | sumber dan penerbit | `create_data_sources_table`, `buat_tabel_otoritas_dan_kredensial_ahli` |

Sumber luar: prakiraan BMKG (kode wilayah adm4) lewat `weather:refresh`; status buka dan tutup terbit sebagai surat Kemenhut atau Balai dan dicatat admin dengan sumbernya (spec v3 bagian 10.1); level PVMBG belum diinput (DK-43, W8). `config/hiking.php`: `rate_limits.report_submissions_per_hour` (5).

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Status belum ada | Belum diketahui | Ada: `OfficialStatusValue::UNKNOWN` berlabel "Belum diketahui" |
| Cuaca gagal atau basi | Kalimat dan sumber, tanpa angka | Ada: `WeatherService` mengembalikan `available: false` beserta pesan |
| Laporan menunggu moderasi | Tidak tampil publik; pelapor tahu keadaannya | Ada untuk publik; keadaan terkirim bagi pelapor menyusul W6 (spec v3 bagian 6.13) |
| Belum mengikuti gunung | Bagian kabar tidak tampil kosong | Ada: bagian cuaca `/kabar` hanya tampil bila ada yang diikuti (`EmptyStateCoverageTest`) |
| Foto gagal dibersihkan | Tidak pernah disimpan dengan metadata lokasinya | Ada: foto dibuang dan laporannya tetap tersimpan (`ImageSanitizer::trySanitize()` di `ConditionReportController`) |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Kabar resmi | Ada | `app/Services/OfficialStatusService.php`, `resources/js/components/trails/StatusAkses.tsx`, `app/Http/Controllers/Admin/OfficialStatusController.php`, `app/Console/Commands/CheckDataFreshness.php`, `tests/Feature/OfficialStatusTargetTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Feature/DataFreshnessReviewTest.php` |
| Prakiraan cuaca | Sebagian | `app/Services/WeatherService.php`, `app/Console/Commands/RefreshWeatherSnapshots.php`, `tests/Feature/WeatherRefreshTest.php`; tampil waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin, curah hujan, dan penanda jam berisiko belum tampil |
| Laporan pendaki | Ada | `app/Http/Controllers/Community/ConditionReportController.php`, `app/Http/Requests/StoreConditionReportRequest.php`, `app/Support/ImageSanitizer.php`, `resources/js/pages/Community/ConditionReport.tsx`, `resources/js/components/trails/rinci/LaporanKomunitas.tsx`, `tests/Feature/CommunityReportModerationTest.php`, `tests/Feature/CommunityPhotoTest.php`; saring per jenis cerita atau tips tidak dibangun (DK-11) |
| Sumber dan kesegaran | Ada | `app/Services/DataFreshnessService.php`, `resources/js/components/community/LabelKesegaran.tsx`, `resources/js/components/trails/Kesegaran.tsx`, `resources/js/components/trails/LabelSumber.tsx`, `tests/Feature/TrustGrammarTest.php` |
| Kabar gunung yang diikuti | Ada | `app/Http/Controllers/Community/TrailNewsController.php`, `app/Services/TrailNewsService.php`, `app/Services/Community/KabarJalur.php`, `resources/js/pages/Community/TrailNews.tsx`, `app/Http/Controllers/Trails/MountainFollowController.php`, `tests/Feature/TrailNewsTest.php` |
| Terima kasih | Ada | `app/Http/Controllers/Trails/ReportThanksController.php`, `app/Http/Requests/Trails/ThankReportRequest.php`, `tests/Feature/ReportThanksTest.php` |
| Status gunung api PVMBG | Belum ada | DK-43, W8 |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman Kabar & Kondisi dengan data tiruan | `resources/js/pages/Community/TrailNews.tsx` dengan data nyata | Ada |
| 2 | Buat kartu status resmi jalur dengan label warna | `StatusAkses.tsx` (bentuk dan kata, bukan warna saja) | Ada |
| 3 | Buat panel prakiraan cuaca per waktu | `resources/js/components/trails/rinci/Kondisi.tsx` | Sebagian |
| 4 | Buat daftar laporan pendaki dengan penanda kiriman | `LaporanKomunitas.tsx` | Ada |
| 5 | Tambah filter dan urutkan laporan pendaki | Terbaru dulu | Berbeda: jenis cerita dan tips tidak ada (DK-11) |
| 6 | Tampilkan label sumber dan kesegaran info | `LabelSumber.tsx`, `LabelKesegaran.tsx` | Ada |
| 7 | Buat empty state keterangan info tidak tersedia | Status Belum diketahui, cuaca `available: false` | Ada |
| 8 | Rapikan tampilan responsif halaman kabar | `TrailNews.tsx` | Ada; redesain W5 |
| 9 | Buat skema dan migrasi tabel pengumuman resmi | `create_official_statuses_table` | Ada |
| 10 | Buat skema dan migrasi tabel laporan pendaki | `create_trail_condition_reports_table` | Ada |
| 11 | Buat skema dan migrasi data prakiraan cuaca | `create_weather_snapshots_table` dan dua migrasi lanjutannya | Ada |
| 12 | Buat endpoint pengumuman status resmi jalur | `OfficialStatusService` lewat props Inertia | Ada, bentuk berbeda |
| 13 | Buat endpoint prakiraan cuaca dengan metadata kesegaran | `WeatherService` | Ada, bentuk berbeda |
| 14 | Buat endpoint laporan pendaki disetujui dengan filter | `app/Services/ConditionAggregatorService.php` | Ada |
| 15 | Buat service moderasi laporan pendaki | `app/Http/Controllers/Moderation/ModerationQueueController.php` | Ada (rincian di spesifikasi 08) |
| 16 | Isi seed data kabar resmi dan prakiraan awal | `database/seeders/OfficialStatusSeeder.php` | Ada untuk status: status yang berlaku September 2026 dari pemberitaan (ADMIN_VERIFIED, berbatas waktu), gunung tanpa keterangan dibiarkan UNKNOWN; prakiraan diisi `weather:refresh`, bukan seeder |

## Keputusan dan konflik terkait

- **DK-09** hierarki sumber (diputuskan atas delegasi pemilik); **DK-11** tanpa cerita, tips, dan reaksi jempol; **DK-43** status gunung api terpisah (W8); **DK-36** data dinamis saat luring tampil "terakhir diketahui" (W6).
- **K5** empat status termasuk UNKNOWN; **K8** ambang kesegaran per sumber; **K9** tanpa feed sosial; **K17** cuaca per area acuan jalur; **K33** PVMBG tidak dipetakan ke status akses.
