# 04 Perjalanan dan persiapan

**FR utama:** FR-03 Tujuan pendakian (§23), FR-08 Rencana perjalanan (§35), FR-09 Rencana persiapan (§36), FR-10 Model kesiapan (§38), FR-24 Pengingat perjalanan (§149).
**Bahan:** features/04-perjalanan-persiapan.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.8 sampai 6.11.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Bagaimana saya memilih, merencanakan, dan bersiap?" (§145, tab Trip).

Pendaki menetapkan tanggal, peserta, dan catatan untuk satu jalur, lalu dituntun langkah demi langkah: persiapan, cek kesiapan, dan pengingat menjelang keberangkatan. Kesiapan adalah dukungan keputusan, bukan izin medis atau hukum (R-014, §40).

## Posisi di loop dan permukaan

- **Loop (§7):** Tujuan, lalu Rekomendasi, lalu rencana, Persiapan, dan Cek, sebelum Mendaki.
- **Tab:** Trip. Rute `/goals/create`, `/trips`, `/trips/create`, `/trips/{trip}`, `/trips/{trip}/edit`, `/trips/{trip}/preparation`, `/trips/{trip}/readiness`.
- **Beranda:** kartu perjalanan berikutnya dan tenggat (W5, DK-45).

## Kriteria penerimaan

1. **Buat rencana.** Given jalur terbit, When pendaki mengisi nama, tanggal mulai, tanggal selesai opsional, jam mulai opsional, peserta, jenis trip, dan catatan, Then rencana tersimpan; tanggal selesai sebelum tanggal mulai, rentang melebihi batas config, atau peserta di luar batas ditolak dengan pesan jelas.
2. **Langkah.** Given rencana ada, When halaman trip dibuka, Then langkah berurutan menunjukkan yang sudah selesai dan yang berikutnya; langkah memandu, tidak mengunci.
3. **Ubah dan hapus.** Given pemilik membuka rencananya, When mengubah atau menghapus, Then perubahan langsung terlihat; trip yang sudah berjalan menolak dengan alasan yang disebut sebelum pendaki mencoba; pendaki lain ditolak (§77).
4. **Persiapan.** Given daftar persiapan dibangkitkan dari templat jalur, When pendaki menandai butir, Then keadaannya tersimpan, butir kritis ditandai, dan hitungan butir kritis terlihat; butir yang belum dicentang bukan berarti tidak dimiliki (BR-10).
5. **Kesiapan.** Given cek kesiapan dijalankan, When hasil tampil, Then keadaan kesiapan beserta faktor yang kurang dan hitungan "N dari M butir kritis sudah dikonfirmasi", tanpa skor atau persen (R-005, R-014, K4).
6. **Konfirmasi pra-berangkat.** Given pendaki meninjau semuanya, When mengonfirmasi, Then konfirmasi dicatat dengan waktunya.
7. **Jendela izin.** Given jalur punya aturan izin, When halaman trip dibuka, Then jendela pemesanan relatif tanggal rencana ditulis (belum dibuka, terbuka sampai, sudah tutup) dengan tautan kanal resmi (§153).
8. **Pengingat.** Given trip belum dimulai, When pemilik menyalakan pengingat, Then surel dijadwalkan H-3 dan H-1 pukul 07:00 zona gunung, tidak pernah terkirim dua kali, dan tidak dikirim pada hari keberangkatan; halaman trip menampilkan setiap pengingat dengan waktu, zona, dan keadaan (menunggu, terkirim, dilewati); isi surel tidak menyatakan jalur atau pendaki aman (R-012).
9. **Pengingat setelah trip dimulai.** Given trip sudah dimulai, selesai, atau dibatalkan, When halaman trip dibuka, Then sakelar pengingat tidak ditawarkan dan kalimatnya menjelaskan sebabnya.
10. **Data pendukung kosong.** Given templat persiapan, status resmi, atau prakiraan belum ada, When bagiannya tampil, Then yang belum dinilai disebut jujur, bukan disembunyikan atau dikarang.
11. **Tujuan pendakian.** Given pendaki mengisi tujuan (tanggal, wilayah, jenis trip, durasi, tantangan), When rekomendasi atau rencana dibuat, Then tujuan itu menjadi masukan dan isian awalnya (FR-03).

## Aturan yang berlaku

- **Kanonis:** R-002, R-006, R-007, R-012, R-013, R-014.
- **Aturan bisnis:** BR-04, BR-05, BR-10, BR-11.
- **PRD:** §23, §35, §36, §37, §38, §39, §40, §98 (penjadwal), §102 (matriks test kesiapan), §115 (kriteria penerimaan persiapan), §149, §153, §154 (penjadwal di Vercel).

## Data dan sumber

| Tabel atau config | Kolom atau kunci | Asal |
|---|---|---|
| `trip_plans` | `user_id`, `trail_id`, `hiking_goal_id`, `name`, `planned_date`, `end_date`, `start_time`, `participants_count`, `trip_type`, `notes`, `status`, `reminders_enabled`, `completed_at` | `create_trip_plans_table`, `add_plan_details_to_trip_plans_table` (kemungkinan belum dijalankan di Supabase, INFERENCE, menunggu Q22) |
| `preparation_templates`, `preparation_items` | templat per jalur: `category`, `label`, `description`, `is_critical`, `sort_order`, `applies_when` | `create_preparation_templates_table`, `create_preparation_items_table` |
| `trip_preparation_items` | `category`, `label`, `is_critical`, `status`, `status_updated_at` | `create_trip_preparation_items_table` |
| `readiness_checks` | `computed_state`, `route_fit_snapshot`, `preparation_state`, `official_status_snapshot`, `condition_snapshot`, `explanation`, `pre_departure_confirmed`, `computed_at` | `create_readiness_checks_table` |
| `trip_reminders` | `jenis` (H-3, H-1), `dijadwalkan_untuk`, `sent_at`, `skipped_at` | `create_trip_reminders_table` (kemungkinan belum dijalankan di Supabase, INFERENCE, menunggu Q22) |
| `hiking_goals` | `target_date`, `region`, `trip_type`, `expected_duration_minutes`, `preferred_challenge`, `max_elevation_gain_m`, `notes` | `create_hiking_goals_table` |
| `permit_requirements` | jendela pemesanan dan tautan resmi | `create_permit_requirements_table` |
| `scheduled_task_runs` | pemantauan `trips:kirim-pengingat` | `create_scheduled_task_runs_table` |
| `config/hiking.php` | `trips.max_participants`, `trips.max_span_days`, `reminders.days_before`, `reminders.local_hour`, `rate_limits.personal_writes_per_minute` | config |

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Belum ada trip | Kalimat dan jalan ke tujuan pendakian | Ada: "Belum ada rencana trip." di `resources/js/pages/Trips/Index.tsx` |
| Trip tidak dapat diubah atau dihapus | Alasan disebut sebelum mencoba | Ada: `kelola` di `TripController::show()`, `resources/js/components/trips/rencana/HapusRencana.tsx` |
| Waktu pengingat sudah lewat | Kalimat jujur | Ada: `pengingat.catatan` dari `TripReminderScheduler::untukHalaman()` |
| Trip sudah dimulai | Sakelar pengingat tidak ditawarkan | Ada (W0): `resources/js/components/trips/PengingatTrip.tsx` |
| Kesiapan basi | Hasil lama ditandai | Ada: `tests/Feature/StaleReadinessTest.php` |
| Galat validasi rencana | Pesan per isian | Ada: `StoreTripRequest`, `UpdateTripRequest` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Rencana perjalanan | Ada | `app/Http/Controllers/Trips/TripController.php`, `app/Services/Trips/TripLifecycle.php`, `app/Http/Requests/Trips/StoreTripRequest.php`, `app/Http/Requests/Trips/UpdateTripRequest.php`, `app/Http/Requests/Trips/ValidatesTripPlanDetails.php`, `resources/js/pages/Trips/Create.tsx`, `Edit.tsx`, `Show.tsx`, `tests/Feature/TripLifecycleTest.php`, `tests/Feature/TripPlanDetailTest.php`, `tests/Feature/TripOwnershipTest.php` |
| Langkah berurutan | Ada | `app/Services/Trips/TripSteps.php`, `resources/js/components/trips/LangkahTrip.tsx` |
| Checklist perlengkapan | Ada | `app/Http/Controllers/Trips/PreparationController.php`, `app/Services/PreparationService.php`, `resources/js/pages/Trips/Preparation.tsx`; isi awal dari perlengkapan pribadi menyusul W4 dan W7 |
| Cek kesiapan | Ada, label lama | `app/Http/Controllers/Trips/ReadinessController.php`, `app/Services/ReadinessService.php`, `app/Services/Readiness/ReadinessAssessment.php`, `app/Services/Trips/PreDepartureCheck.php`, `resources/js/pages/Trips/Readiness.tsx`, `app/Http/Resources/TripReadinessResource.php`, `tests/Feature/ReadinessDashboardTest.php`. Hitungan tampil sebagai "N dari M item dikonfirmasi" ditambah "K item kritis belum dikonfirmasi"; hitungan khusus butir kritis ala prototype 10 menyusul W4. `ReadinessState` masih berlabel "Siap" dan "Tidak direkomendasikan" (diganti DK-48 di W4) |
| Jendela izin | Ada | `app/Services/PermitService.php`, `tests/Feature/TripPermitWindowTest.php` |
| Pengingat perjalanan (FR-24) | Ada; tampilan di halaman trip sejak W0 | `app/Services/Trips/TripReminderScheduler.php`, `app/Console/Commands/SendTripReminders.php` (tiap jam di `routes/console.php`), `app/Notifications/TripReminderNotification.php`, `app/Http/Controllers/Trips/TripReminderController.php`, `PengingatTrip.tsx`, `tests/Feature/TripReminderTest.php`; dipantau `SchedulerHealthService::TUGAS` (W0). Syarat produksi: penjadwal benar-benar berjalan dan driver surel nyata (K11, K24) |
| Tujuan pendakian (FR-03) | Ada | `app/Http/Controllers/Goals/GoalController.php`, `app/Http/Requests/StoreGoalRequest.php`, `resources/js/pages/Goals/Create.tsx`, `tests/Feature/GoalInputIsUsedTest.php` |
| Indeks trip sebagai ruang kerja | Sebagian | `resources/js/pages/Trips/Index.tsx` berupa daftar berhalaman; kartu trip terdekat dan kelompok Berlangsung, Mendatang, Selesai menyusul W4 (spec v3 bagian 6.9) |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman utama rencana perjalanan | `resources/js/pages/Trips/Index.tsx`, `Show.tsx` | Ada |
| 2 | Buat form rencana tiket dengan mock | `resources/js/pages/Trips/Create.tsx`, `resources/js/components/trips/rencana/IsianRencana.tsx` (data nyata; "tiket" dibaca sebagai rencana trip) | Ada |
| 3 | Validasi tanggal rencana mulai selesai | `ValidatesTripPlanDetails` | Ada |
| 4 | Tampilkan rencana sebagai langkah berurutan | `TripSteps`, `LangkahTrip.tsx` | Ada |
| 5 | Buat tampilan checklist perlengkapan mock | `resources/js/pages/Trips/Preparation.tsx` | Ada |
| 6 | Interaksi centang perlengkapan dan hitung sisa | Rute `trips.preparation.update` | Ada |
| 7 | Bangun halaman skor kesiapan diri | `resources/js/pages/Trips/Readiness.tsx` | Berbeda: keadaan dan faktor, bukan skor (K4) |
| 8 | Rincian faktor kesiapan dan keterangan jujur | `app/Http/Resources/TripReadinessResource.php` | Ada |
| 9 | Buat halaman pengingat perjalanan mock | `PengingatTrip.tsx` di halaman trip, bukan halaman terpisah | Ada (W0) |
| 10 | Toggle pengingat dan daftar status | Rute `trips.reminders.update`, `PengingatTrip.tsx` | Ada (W0) |
| 11 | Buat tabel rencana perjalanan dan migrasi | `create_trip_plans_table`, `add_plan_details_to_trip_plans_table` | Ada |
| 12 | API CRUD rencana dan validasi tanggal | Rute `trips.*`, `TripLifecycle` | Ada |
| 13 | Skema tabel perlengkapan dan status siap | `create_trip_preparation_items_table` | Ada |
| 14 | API checklist perlengkapan perjalanan | `PreparationController` | Ada |
| 15 | Skema data kebugaran dan pengalaman pengguna | `create_user_experience_table` | Berbeda: pengalaman diisi sendiri; kebugaran tidak dinilai (K4) |
| 16 | Service hitung skor kesiapan dan faktor | `ReadinessService`, `ReadinessAssessment` | Berbeda: keadaan, bukan skor |
| 17 | API pengingat dan jadwal keberangkatan | `TripReminderScheduler::jadwal()` dan `untukHalaman()` | Ada |
| 18 | Job pengirim pengingat email dan notifikasi | `SendTripReminders` (surel sinkron) | Ada untuk surel; notifikasi dorong menunggu keputusan pemilik (spec v3 bagian 15 Q6) |
| 19 | Penanganan data pendukung belum tersedia | Kalimat jujur di `PreparationService` dan `ReadinessAssessment` | Ada |

## Keputusan dan konflik terkait

- **DK-07** FR-24 dan detail rencana (tanggal selesai, peserta) masuk cakupan; **DK-45** Beranda berfase; **DK-48** label kesiapan "Persiapan kritis lengkap", "Masih perlu persiapan", "Tidak disarankan dengan rencana ini" (W4); **DK-50** alasan pembatalan (W7).
- **K4** keadaan kesiapan dan faktor, bukan skor kebugaran; **K11** pengingat butuh driver surel dan penjadwal produksi; **K12** peserta dan tanggal selesai sebagai kolom aditif; **K24** penjadwal di Vercel lewat Cron yang memanggil endpoint terautentikasi.
