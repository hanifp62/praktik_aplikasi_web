# 08 Konsol pengelola

**FR utama:** FR-16 Admin (§56), FR-17 Moderasi (§57), FR-18 Pengelolaan sumber data (§58), FR-19 Log audit (§61), FR-20 Analitik (§62).
**Bahan:** features/08-konsol-pengelola.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 10.7.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengelola: "Data apa yang kurang atau basi, dan bagaimana memperbaikinya dengan sumber yang jelas?"

Admin, moderator, dan ahli berkredensial menjaga data jalur tetap akurat dan bersumber: melengkapi jalur dan pos, mencatat status resmi dan aturan izin, meninjau kiriman komunitas, dan menelusuri setiap perubahan penting.

## Posisi di loop dan permukaan

- **Loop:** di luar loop pendaki; menjaga mutu data yang dibaca setiap langkah loop.
- **Pintu:** dari menu akun sesuai peran (§145); otorisasi tetap di server (§76).
- **Rute:** `/admin/*` (peran admin), `/moderation` (peran moderator), `/jalur/{trail}/geometri` dan `/jalur/{trail}/pos` (admin atau ahli berkredensial untuk kawasannya, lewat Policy), `/kontribusi` (ahli).

## Kriteria penerimaan

1. **Kesehatan data.** Given admin membuka Kesehatan Sistem, When halaman tampil, Then jumlah jalur yang belum lengkap beserta kekurangannya dan tautan ke jalurnya, tugas terjadwal dengan keadaannya (termasuk yang belum pernah berjalan), dan migrasi yang belum dijalankan di basis data yang dipakai; geometri yang tidak dapat diperiksa ditulis tidak diketahui, bukan 0 (R-006).
2. **Gunung dan jalur.** Given admin, When membuat, mengubah, atau mengarsipkan gunung dan jalur, Then tersimpan dengan sumber; jalur hanya dapat diterbitkan bila gerbang publikasi lolos (R-022), dan gerbang itu dijaga di lapisan data, bukan hanya di satu layar.
3. **Geometri.** Given admin atau ahli berkredensial untuk kawasan jalur itu, When mengunggah GPX atau memilih garis dari OpenStreetMap, Then berkas divalidasi, garis dipratinjau sebelum disimpan, pencarian OSM dibatasi laju, dan tanjakan dapat diterapkan dari garis; ahli di luar kawasannya ditolak.
4. **Pos.** Given pengelola berhak, When menambah, mengubah, memindah urutan, atau menghapus pos, Then pos selalu milik jalur di alamatnya (scopeBindings) dan penghapusan yang merusak perjalanan dijaga.
5. **Status resmi.** Given admin mencatat status, When tersimpan, Then sumber, cakupan, dan waktunya tercatat, dan pencatatnya diambil dari sesi, bukan formulir.
6. **Izin dan sumber data.** Given admin, When mencatat aturan izin atau sumber data, Then sumber dan waktu verifikasi tercatat; sumber dapat diverifikasi atau diperdebatkan.
7. **Moderasi.** Given moderator membuka antrean, When memutuskan setuju, tolak, tandai, atau hapus dengan alasan, Then keputusan langsung memengaruhi yang tampil ke publik dan tercatat.
8. **Kredensial ahli.** Given admin memutuskan kredensial ahli, When disetujui, Then cakupan gunungnya menentukan jalur yang boleh disunting ahli itu.
9. **Audit.** Given aksi penting (terbitkan, moderasi, ubah data), When terjadi, Then tercatat pelaku, waktu, nilai sebelum dan sesudah; log dapat disaring per aksi.
10. **Analitik dan studi kegunaan.** Given admin, When membuka analitik atau studi kegunaan, Then angka berasal dari peristiwa yang benar-benar tercatat; hasil uji kegunaan tidak pernah dikarang (R-023).
11. **Kelola peran.** Given admin perlu mengubah peran pengguna, When fitur ini dibangun, Then perubahan tercatat di audit dan langsung berlaku (belum ada; lihat status).

## Aturan yang berlaku

- **Kanonis:** R-008, R-009, R-010, R-011, R-019, R-022, R-023.
- **Aturan bisnis:** BR-01, BR-04, BR-06, BR-07, BR-12, BR-14, BR-16.
- **PRD:** §56, §57, §58, §59, §60, §61, §62, §76, §77, §78, §79, §81, §98, §99, §110, §113, §118, §153, §159.

## Data dan sumber

| Tabel | Isi | Migrasi |
|---|---|---|
| `mountains`, `trails`, `trail_segments`, `checkpoints` | data gunung, jalur, segmen, pos; `trails.contributed_by`, `contributed_credential_id`, `contributed_at` | migrasi dasar, `buat_tabel_otoritas_dan_kredensial_ahli` |
| `official_statuses`, `permit_requirements`, `restricted_areas` | status resmi, aturan izin, area terlarang | `create_official_statuses_table`, `create_permit_requirements_table`, `create_restricted_areas_table` |
| `data_sources` | `source_name`, `source_type`, `source_url`, `retrieved_at`, `verified_at`, `verification_status` | `create_data_sources_table` |
| `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain` | otoritas pengelola; kredensial ahli dan cakupan gunungnya | `buat_tabel_otoritas_dan_kredensial_ahli` |
| `moderation_actions`, `audit_logs` | keputusan moderasi; jejak `before` dan `after` | `create_moderation_actions_table`, `create_audit_logs_table` |
| `analytics_events`, `usability_sessions`, `scheduled_task_runs` | corong, sesi uji kegunaan dan SUS, riwayat tugas terjadwal | `create_analytics_events_table`, `create_usability_sessions_table`, `create_scheduled_task_runs_table` |
| `users.role` | `hiker`, `moderator`, `admin` | `add_role_to_users_table` |

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Tugas terjadwal belum pernah berjalan | Disebut dan menyalakan peringatan | Ada: `SchedulerHealthService`, termasuk `trips:kirim-pengingat` sejak W0 |
| Tidak ada tugas terdaftar | Kalimat yang menyebut akibatnya | Ada: `resources/js/pages/Admin/Health.tsx` |
| Geometri tidak dapat diperiksa (SQLite) | Tidak diketahui, bukan 0 | Ada: `tests/Feature/SystemHealthPageTest.php` |
| Migrasi belum dijalankan | Disebut di halaman kesehatan | Ada: `app/Services/Admin/MigrationStatus.php` |
| Antrean moderasi kosong | Kalimat jelas | Ada: dijaga `EmptyStateCoverageTest` untuk setiap daftar halaman |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Ringkasan kesehatan data | Ada | `app/Http/Controllers/Admin/SystemHealthController.php`, `app/Services/Admin/DataHealthSummary.php`, `app/Services/Admin/TrailDataHealth.php`, `MigrationStatus.php`, `app/Services/SchedulerHealthService.php`, `Health.tsx`, `tests/Feature/SystemHealthPageTest.php`, `tests/Feature/SchedulerHealthTest.php` |
| Kelola jalur dan titik | Ada | `app/Http/Controllers/Admin/MountainController.php`, `TrailController.php`, `TrailGeometryController.php`, `CheckpointController.php`, `app/Services/Admin/GeometryDraft.php`, `app/Services/OpenStreetMapTrails.php`, `app/Http/Requests/Admin/GpxUploadRequest.php`, `resources/js/pages/Admin/Trails.tsx`, `TrailGeometry.tsx`, `Checkpoints.tsx`, `tests/Feature/AdminDataManagementTest.php`, `tests/Feature/TrailGeometryImportTest.php`, `tests/Feature/CheckpointManagementTest.php`, `tests/Feature/CheckpointDeletionGuardTest.php`, `tests/Feature/TrailPublishGateTest.php` |
| Verifikasi dan moderasi | Ada | `app/Http/Controllers/Moderation/ModerationQueueController.php`, `app/Http/Requests/Moderation/ModerateReportRequest.php` (alasan opsional sampai 1000 karakter), `resources/js/pages/Moderation/Queue.tsx`, `tests/Feature/ModerationFlowTest.php` |
| Sumber data, status resmi, izin, otoritas | Ada | `app/Http/Controllers/Admin/DataSourceController.php`, `OfficialStatusController.php`, `PermitController.php`, `AuthorityController.php`, `tests/Feature/OfficialStatusPageTest.php`, `tests/Feature/PermitManagerTest.php`, `tests/Feature/AuthorityManagerTest.php` |
| Kredensial ahli dan kontribusi | Ada | `app/Http/Controllers/Admin/CredentialController.php`, `app/Http/Controllers/Community/TrailContributionController.php`, `app/Policies/TrailPolicy.php` (ahli hanya untuk gunung dalam kredensialnya), `tests/Feature/CredentialManagerTest.php`, `tests/Feature/TrailContributionTest.php`, `tests/Feature/ExpertGeometryAccessTest.php` |
| Log audit | Ada | `app/Http/Controllers/Admin/AuditLogController.php`, `app/Services/AuditLogService.php`, `resources/js/pages/Admin/AuditLogs.tsx` (saring per aksi), `tests/Feature/AuditLogChangeTest.php` |
| Analitik dan studi kegunaan | Ada | `app/Http/Controllers/Admin/AnalyticsController.php`, `app/Services/AnalyticsRecorder.php`, `app/Http/Controllers/Admin/UsabilityStudyController.php`, `app/Services/UsabilityStudyService.php`, `tests/Feature/UsabilityStudyPageTest.php` |
| Kelola peran pengguna | Belum ada | Tidak ada rute atau halaman; `users.role` diubah langsung di basis data. Cakupan wilayah ahli sudah ada lewat `credential_mountain` |
| Meja kurator data resmi | Belum ada | DK-36, spec v3 bagian 10.7, W8 |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman utama Konsol Pengelola | `resources/js/components/admin/AdminNav.tsx` dan halaman `resources/js/pages/Admin/` | Ada, tanpa halaman beranda konsol tersendiri |
| 2 | Buat halaman ringkasan kesehatan data | `resources/js/pages/Admin/Health.tsx` | Ada |
| 3 | Buat halaman daftar dan detail jalur | `resources/js/pages/Admin/Trails.tsx` | Ada |
| 4 | Buat form tambah dan edit jalur | `resources/js/components/admin/FormulirJalur.tsx` | Ada |
| 5 | Buat panel kelola titik penting jalur | `resources/js/pages/Admin/Checkpoints.tsx` | Ada |
| 6 | Buat komponen unggah GPX dengan validasi | `resources/js/pages/Admin/TrailGeometry.tsx`, `GpxUploadRequest` | Ada |
| 7 | Buat halaman antrean verifikasi moderasi | `resources/js/pages/Moderation/Queue.tsx` | Ada |
| 8 | Buat modal keputusan setuju atau tolak | Keputusan beralasan lewat `ModerateReportRequest` | Ada, bentuk tidak diperiksa terhadap "modal" |
| 9 | Buat halaman kelola peran pengguna | Tidak ada | Belum ada |
| 10 | Buat halaman catatan audit dengan filter | `resources/js/pages/Admin/AuditLogs.tsx` | Ada |
| 11 | Terapkan penjagaan izin peran di UI | Menu menurut `isAdmin` dan `isModerator` di props bersama; otorisasi tetap di server | Ada |
| 12 | Buat skema dan migrasi data pengelola | `create_audit_logs_table`, `create_moderation_actions_table`, `buat_tabel_otoritas_dan_kredensial_ahli`, `create_data_sources_table` | Ada |
| 13 | Buat endpoint ringkasan kesehatan data | `DataHealthSummary` | Ada |
| 14 | Buat endpoint CRUD jalur dan titik | Rute `admin.trails.*`, `trails.checkpoints.*` | Ada |
| 15 | Buat auth dan middleware izin wilayah | Middleware `role` (`EnsureUserHasRole`) dan `TrailPolicy` dengan `credential_mountain` | Ada |
| 16 | Buat layanan validasi impor GPX | `TrailGeometryController::preview`, `GeometryDraft` | Ada |
| 17 | Buat endpoint antrean dan moderasi kiriman | Rute `moderation.queue`, `moderation.act` | Ada |
| 18 | Buat endpoint kelola peran dan wilayah | Wilayah lewat keputusan kredensial (`admin.credentials.decide`); peran tidak ada | Sebagian |
| 19 | Buat layanan dan endpoint catatan audit | `AuditLogService`, rute `admin.audit` | Ada |
| 20 | Seed data awal dan uji alur end-to-end | `database/seeders/DatabaseSeeder.php`, `MvpDatasetSeeder.php`, `AuthoritySeeder.php`; uji ujung ke ujung dengan Playwright menunggu keputusan pemilik (spec v3 bagian 15 Q17) | Sebagian |

## Keputusan dan konflik terkait

- **DK-09** hierarki sumber dengan ahli bersertifikat; **DK-36** meja kurator data resmi (W8); **DK-37** foto gunung berlisensi (W8); **DK-43** status gunung api (W8).
- **K6** "Pakar" menjadi ahli bersertifikat; **K19** Policy dan Gate dengan enum peran, bukan matriks `role_permissions`; **K34** jumlah gunung seed PERLU KLARIFIKASI lewat query atas izin pemilik.
- **Menunggu pemilik:** spec v3 bagian 15 Q11 (peran kurator dan penerbit mitra, aturan terbit asimetris), Q13 (kemitraan dan izin PVMBG); fitur kelola peran belum punya keputusan cakupan.
